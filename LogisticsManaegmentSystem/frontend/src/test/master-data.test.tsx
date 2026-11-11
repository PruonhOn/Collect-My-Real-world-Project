import { cleanup, render, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { MemoryRouter } from 'react-router-dom'
import { afterEach, beforeEach, describe, expect, it, vi, type Mock } from 'vitest'
import { AuthContext } from '@/features/auth/auth-context'
import { ToastProvider } from '@/components/ui/Toast'
import { MasterDataPage } from '@/features/master-data/MasterDataPage'
import { MASTER_SURFACE_KEYS, SURFACE_KEYS, SURFACES, type SurfaceDef } from '@/features/master-data/registry'
import type {
  AnyRecord,
  Booking,
  Carrier,
  Container,
  CustomsClearance,
  Delivery,
  Document,
  Party,
  Shipment,
  ShipmentTracking,
  SurfaceKey,
} from '@/features/master-data/types'
import { NAVIGATION } from '@/app/navigation'
import { ApiRequestError } from '@/lib/api'
import type { CurrentUser, PaginationMeta } from '@/types/api'
import { getPaginated, post, put, del, downloadFile } from '@/lib/api'

/**
 * Mount tests for the shared master-data screen.
 *
 * The HTTP layer is mocked, not React Query, so the caching, the permission
 * gates and the dialog state are all exercised for real. Only the network is
 * faked.
 */

vi.mock('@/lib/api', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/lib/api')>()
  return {
    ...actual,
    getPaginated: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    del: vi.fn(),
    // Faked because the real one drives an axios blob request and a synthetic
    // anchor click, neither of which jsdom implements. The unit under test is the
    // page's decision to call it, with which path and which fallback name.
    downloadFile: vi.fn(),
  }
})

const getPaginatedMock = vi.mocked(getPaginated)
const postMock = vi.mocked(post)
const putMock = vi.mocked(put)
const delMock = vi.mocked(del)
const downloadFileMock = vi.mocked(downloadFile)

afterEach(cleanup)

beforeEach(() => {
  vi.clearAllMocks()
  getPaginatedMock.mockResolvedValue({ data: [], meta: meta() })
  postMock.mockResolvedValue({})
  putMock.mockResolvedValue({})
  delMock.mockResolvedValue({})
})

function party(overrides: Partial<Party> = {}): Party {
  // `Object.assign` rather than a spread, so the declared return type is what the
  // compiler checks against instead of `type: PartyType | undefined`.
  return Object.assign<Party, Partial<Party>>(
    {
      id: 7,
      type: 'customer',
      code: 'ACME',
      name: 'Acme Industries',
      tax_number: null,
      contact_name: 'Sam Rivers',
      contact_email: 'sam@acme.test',
      contact_phone: null,
      address: {
        line_1: 'Warehouse 4',
        line_2: null,
        city: 'Dubai',
        state: 'Dubai',
        postal_code: '00000',
        country_code: 'AE',
      },
      is_active: true,
      notes: null,
      created_at: '2026-01-04T08:00:00Z',
      updated_at: '2026-01-04T08:00:00Z',
    },
    overrides,
  )
}

function meta(overrides: Partial<PaginationMeta> = {}): PaginationMeta {
  return {
    current_page: 1,
    per_page: 25,
    total: 0,
    last_page: 1,
    from: null,
    to: null,
    has_more_pages: false,
    ...overrides,
  }
}

const currentUser: CurrentUser = {
  id: 1,
  name: 'Ada Admin',
  email: 'admin@logistics.test',
  phone: null,
  avatar_path: null,
  is_active: true,
  last_login_at: null,
  roles: ['admin'],
  permissions: ['customer.view', 'customer.create', 'customer.update', 'customer.delete'],
  customer: null,
}

/** A user who can read the list but not change anything. */
const readOnlyUser: CurrentUser = { ...currentUser, roles: ['viewer'], permissions: ['customer.view'] }

const shipmentsUser: CurrentUser = {
  ...currentUser,
  roles: ['operations'],
  permissions: ['shipment.view', 'shipment.create', 'shipment.update', 'shipment.delete'],
}

/**
 * The operations clerk, who has the three new modules and no consignment writes.
 *
 * Shaped after the role in `config/permissions.php`: view, create and update on all
 * three, and deliberately no `.delete`. A fixture that granted everything would let
 * a test pass on a page showing a Delete button this user cannot actually use.
 */
const operationsUser: CurrentUser = {
  ...currentUser,
  roles: ['operations-clerk'],
  permissions: [
    'shipment.view',
    'container.view',
    'container.create',
    'container.update',
    'customs.view',
    'customs.create',
    'customs.update',
    'delivery.view',
    'delivery.create',
    'delivery.update',
  ],
}

/**
 * The document controller, who may read the register and take copies of it.
 *
 * `document.download` is a grant of its own and is deliberately not implied by
 * `document.view`; a fixture that folded them together would let a test pass on a
 * page that hands out files to somebody who can only read their names.
 */
const documentsUser: CurrentUser = {
  ...currentUser,
  roles: ['document-controller'],
  permissions: [
    'document.view',
    'document.create',
    'document.update',
    'document.delete',
    'document.download',
  ],
}

/** The same user, without the authority to pull a file down. */
const documentsReader: CurrentUser = {
  ...documentsUser,
  permissions: ['document.view'],
}

function shipment(overrides: Partial<Shipment> = {}): Shipment {
  return Object.assign<Shipment, Partial<Shipment>>(
    {
      id: 42,
      reference: 'SHP-2026-0001',
      status: 'in_transit',
      status_label: 'In transit',
      is_closed: false,
      shipper: { id: 3, code: 'SHIP-ACME', name: 'Acme Exports' },
      consignee: { id: 4, code: 'CON-BETA', name: 'Beta Imports' },
      carrier: { id: 1, code: 'MAEU', name: 'Maersk' },
      vessel: { id: 2, name: 'MV Meridian', imo_number: 'IMO 9123456' },
      port_of_loading: { id: 7, un_locode: 'SGSIN', name: 'Singapore' },
      port_of_discharge: { id: 8, un_locode: 'NLRTM', name: 'Rotterdam' },
      origin_site: { id: 5, code: 'WH-DXB', name: 'Dubai Warehouse' },
      destination_site: { id: 6, code: 'WH-RTM', name: 'Rotterdam Warehouse' },
      truck: null,
      driver: null,
      incoterm: 'FOB',
      incoterm_place: 'Singapore',
      cargo_description: 'Cotton garments',
      pieces: 240,
      weight_kg: 18500,
      volume_cbm: '54.500',
      etd: '2026-03-02',
      eta: '2026-03-21',
      actual_departure: null,
      actual_arrival: null,
      is_late: false,
      notes: null,
      created_at: '2026-02-01T09:00:00Z',
      updated_at: '2026-02-01T09:00:00Z',
    },
    overrides,
  )
}

/**
 * A booking fixture.
 *
 * Deliberately the same routing and cargo as the shipment above: that is what a
 * consignment cut from a confirmed booking looks like, and a fixture that differed
 * would not exercise the two surfaces against each other.
 */
function booking(overrides: Partial<Booking> = {}): Booking {
  return Object.assign<Booking, Partial<Booking>>(
    {
      id: 77,
      reference: 'BKG-2026-0001',
      status: 'confirmed',
      status_label: 'Confirmed',
      is_closed: false,
      shipper: { id: 3, code: 'SHIP-ACME', name: 'Acme Exports' },
      consignee: { id: 4, code: 'CON-BETA', name: 'Beta Imports' },
      carrier: { id: 1, code: 'MAEU', name: 'Maersk' },
      vessel: { id: 2, name: 'MV Meridian', imo_number: 'IMO 9123456' },
      port_of_loading: { id: 7, un_locode: 'SGSIN', name: 'Singapore' },
      port_of_discharge: { id: 8, un_locode: 'NLRTM', name: 'Rotterdam' },
      origin_site: { id: 5, code: 'WH-DXB', name: 'Dubai Warehouse' },
      destination_site: { id: 6, code: 'WH-RTM', name: 'Rotterdam Warehouse' },
      incoterm: 'FOB',
      incoterm_place: 'Singapore',
      cargo_description: 'Cotton garments',
      pieces: 240,
      weight_kg: 18500,
      volume_cbm: '54.500',
      requested_etd: '2026-03-02',
      requested_eta: '2026-03-21',
      notes: null,
      created_at: '2026-02-01T09:00:00Z',
      updated_at: '2026-02-01T09:00:00Z',
    },
    overrides,
  )
}

function carrier(overrides: Partial<Carrier> = {}): Carrier {
  return Object.assign<Carrier, Partial<Carrier>>(
    {
      id: 1,
      code: 'MAEU',
      name: 'Maersk',
      scac: 'MAEU',
      type: 'sea',
      contact_name: null,
      contact_email: 'ops@maersk.test',
      contact_phone: null,
      address: {
        line_1: null,
        line_2: null,
        city: null,
        state: null,
        postal_code: null,
        country_code: null,
      },
      is_active: true,
      notes: null,
      created_at: '2026-01-04T08:00:00Z',
      updated_at: '2026-01-04T08:00:00Z',
    },
    overrides,
  )
}

/** An ISO timestamp `hours` ago, so relative-time assertions do not drift. */
function hoursAgo(hours: number): string {
  return new Date(Date.now() - hours * 3_600_000).toISOString()
}

/** An ISO timestamp `hours` ahead, for a slot that has not come round yet. */
function hoursFromNow(hours: number): string {
  return new Date(Date.now() + hours * 3_600_000).toISOString()
}

/**
 * A `Y-m-d` date `days` from today, for a document expiry.
 *
 * Date-only because an expiry is a day, not an instant, and the server and the
 * cell both read it that way. Kept relative so the countdown the cell renders
 * cannot drift as the calendar moves.
 */
function dateInDays(days: number): string {
  return new Date(Date.now() + days * 86_400_000).toISOString().slice(0, 10)
}

/**
 * A tracking row: a consignment in transit that reported two days ago.
 *
 * The default is deliberately the ordinary case - on schedule, recently heard, no
 * flags - so a test that asserts a flag has to say so rather than inheriting one.
 */
function tracking(overrides: Partial<ShipmentTracking> = {}): ShipmentTracking {
  return Object.assign<ShipmentTracking, Partial<ShipmentTracking>>(
    {
      id: 9,
      shipment: {
        id: 42,
        reference: 'SHP-2026-0001',
        status: 'in_transit',
        status_label: 'In transit',
        is_closed: false,
        is_late: false,
        eta: '2026-10-01',
        carrier: { id: 1, code: 'MAEU', name: 'Maersk' },
        shipper: { id: 3, code: 'SHIP-ACME', name: 'Acme Exports' },
        consignee: { id: 4, code: 'CON-BETA', name: 'Beta Imports' },
        port_of_discharge: { id: 6, un_locode: 'USLGB', name: 'Los Angeles' },
      },
      last_milestone: 'transshipment',
      last_milestone_label: 'Transshipment',
      is_problem: false,
      current_location: 'Algeciras, ES',
      last_reported_at: hoursAgo(48),
      revised_eta: null,
      delay_reason: null,
      source: 'EDI',
      is_delayed: false,
      is_unreported: false,
      is_stale: false,
      notes: null,
      created_at: '2026-01-04T08:00:00Z',
      updated_at: '2026-01-04T08:00:00Z',
    },
    overrides,
  )
}

/**
 * A box on a consignment's manifest: loaded, on the water, unflagged.
 *
 * The ordinary case, so a test that asserts a badge has to ask for it rather than
 * inheriting one. The consignment is nested because the list renders the discharge
 * port from it - the reason a held box can be read without opening the consignment.
 */
function container(overrides: Partial<Container> = {}): Container {
  return Object.assign<Container, Partial<Container>>(
    {
      id: 11,
      container_number: 'MSCU7741203',
      iso_code: '45G1',
      type: '40ft high cube, dry',
      status: 'in_transit',
      status_label: 'In transit',
      shipment: {
        id: 42,
        reference: 'SHP-2026-0001',
        status: 'in_transit',
        status_label: 'In transit',
        is_closed: false,
        port_of_discharge: { id: 8, un_locode: 'NLRTM', name: 'Rotterdam' },
        consignee: { id: 4, code: 'CON-BETA', name: 'Beta Imports' },
      },
      seal_number: 'SL771204',
      tare_weight_kg: 3900,
      max_gross_weight_kg: 30480,
      gross_weight_kg: 12680,
      utilisation_percent: 58,
      is_empty: false,
      needs_attention: false,
      is_closed: false,
      notes: null,
      created_at: '2026-02-01T09:00:00Z',
      updated_at: '2026-02-01T09:00:00Z',
    },
    overrides,
  )
}

/**
 * A customs filing.
 *
 * A pending one with no entry number, because that is the state a customs screen
 * exists to surface and it is the one a fixture that filled everything in would
 * never produce. `is_held` is true and `needs_attention` false: the two are
 * different questions and a filing that has not gone in is stuck rather than wrong.
 */
function clearance(overrides: Partial<CustomsClearance> = {}): CustomsClearance {
  return Object.assign<CustomsClearance, Partial<CustomsClearance>>(
    {
      id: 21,
      shipment: {
        id: 42,
        reference: 'SHP-2026-0002',
        status: 'customs',
        status_label: 'At customs',
        is_closed: false,
        is_late: true,
      },
      port: { id: 9, un_locode: 'USLGB', name: 'Los Angeles', country_code: 'US' },
      status: 'pending',
      status_label: 'Pending',
      entry_number: null,
      broker: 'Meridian Freight Services',
      submitted_at: null,
      cleared_at: null,
      is_held: true,
      needs_attention: false,
      is_closed: false,
      hold_reason: 'Phytosanitary certificate not presented',
      notes: null,
      created_at: '2026-02-01T09:00:00Z',
      updated_at: '2026-02-01T09:00:00Z',
    },
    overrides,
  )
}

/**
 * A booked collection: scheduled for a slot in three hours, nothing wrong with it.
 *
 * Three hours rather than a fixed date, because the overdue and relative-time
 * assertions are about the comparison with now and a hard-coded slot would start
 * failing the day the suite was written.
 */
function delivery(overrides: Partial<Delivery> = {}): Delivery {
  return Object.assign<Delivery, Partial<Delivery>>(
    {
      id: 31,
      reference: 'DLV-2026-0001',
      shipment: {
        id: 42,
        reference: 'SHP-2026-0003',
        status: 'arrived',
        status_label: 'Arrived',
        is_closed: false,
        is_late: false,
        consignee: { id: 4, code: 'CON-BETA', name: 'Beta Imports' },
      },
      status: 'scheduled',
      status_label: 'Scheduled',
      truck: { id: 3, code: 'TRK-0001', plate_number: 'DXB-4471' },
      driver: { id: 5, code: 'DRV-0001', name: 'Sam Okafor' },
      scheduled_at: hoursFromNow(3),
      picked_up_at: null,
      delivered_at: null,
      is_overdue: false,
      is_outstanding: true,
      is_moving: false,
      has_failed: false,
      is_closed: false,
      contact_name: 'Jordan Ellis',
      contact_phone: '+1-310-555-0177',
      delivery_address: '725 Harbor Plaza, Long Beach, CA 90802 - bay 14',
      pod_reference: null,
      failure_reason: null,
      notes: null,
      created_at: '2026-02-01T09:00:00Z',
      updated_at: '2026-02-01T09:00:00Z',
    },
    overrides,
  )
}

/**
 * Routes list requests by path.
 *
 * A single `mockResolvedValue` answers the page's own list *and* every reference
 * dropdown with the same rows, so a carrier option would be built from a shipment
 * and render as `undefined (undefined)`. The surface's `reference` declarations
 * decide which paths are asked for, so this answers those with their own shape
 * and hands the rows under test back only for the page's path.
 */function mockLists(rows: AnyRecord[] = [], metaOverrides: Partial<PaginationMeta> = {}) {
  /*
   * The transactional surfaces all answer with `rows`.
   *
   * A test renders one of them and the rows belong to that one, so sharing the
   * branch does not cross anything over. Every path has to answer rather than fall
   * through to the empty default, because each of these forms carries reference
   * dropdowns that fetch another module - a consignment dropdown, a port one - and
   * an unanswered reference query would leave that control loading forever.
   */
  const transactional = [
    SURFACES.shipments.path,
    SURFACES.bookings.path,
    SURFACES.tracking.path,
    SURFACES.containers.path,
    SURFACES.customs.path,
    SURFACES.deliveries.path,
  ]

  getPaginatedMock.mockImplementation(async (path: string) => {
    if (path === SURFACES.carriers.path) return { data: [carrier()], meta: meta({ total: 1 }) }

    /*
     * The document register, on its own branch rather than in the transactional set
     * below. It is the one surface whose rows are *not* interchangeable with the
     * others': a document carries a `file` block and a `download_url`, and its form
     * holds a consignment reference that fetches `/shipments` - which, if it answered
     * with the document rows, would build its options out of paperwork.
     */
    if (path === SURFACES.documents.path) {
      return { data: rows, meta: meta({ total: rows.length, ...metaOverrides }) }
    }

    if (transactional.includes(path)) {
      return { data: rows, meta: meta({ total: rows.length, ...metaOverrides }) }
    }

    return { data: [], meta: meta() }
  })
}

function renderPage(
  user: CurrentUser = currentUser,
  can: (permission: string | string[]) => boolean = () => true,
) {
  return renderSurface(SURFACES.customers, user, can)
}

/**
 * The same page, pointed at a different surface.
 *
 * Generic in the record type, because the page is. Handing it a union of every
 * `SurfaceDef` would not typecheck - `SurfaceDef<Carrier>` is not a
 * `SurfaceDef<Party>` - so each call infers its own record type from the surface
 * it was given.
 */
function renderSurface<T extends AnyRecord>(
  surface: SurfaceDef<T>,
  user: CurrentUser = currentUser,
  can: (permission: string | string[]) => boolean = () => true,
) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false, gcTime: 0 } },
  })

  return render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter>
        <ToastProvider>
          <AuthContext.Provider value={authValue(user, can)}>
            <MasterDataPage surface={surface} />
          </AuthContext.Provider>
        </ToastProvider>
      </MemoryRouter>
    </QueryClientProvider>,
  )
}

function authValue(user: CurrentUser, can: (permission: string | string[]) => boolean, login: Mock = vi.fn()) {
  return {
    user,
    isLoading: false,
    isAuthenticated: true,
    login,
    loginWithGoogle: vi.fn(),
    logout: vi.fn().mockResolvedValue(undefined),
    refresh: vi.fn().mockResolvedValue(undefined),
    can,
    canAll: (permissions: string[]) => permissions.every(can),
    hasRole: () => true,
  }
}

/** The params of the most recent list call. */
function lastListParams(): Record<string, unknown> {
  const calls = getPaginatedMock.mock.calls
  return (calls[calls.length - 1]?.[1] ?? {}) as Record<string, unknown>
}

/**
 * The create control.
 *
 * There are legitimately two on an empty list - one in the header, one in the
 * empty state - so the first match is the header's, which is the one present on
 * every screen.
 */
function newButton(): HTMLElement {
  return screen.getAllByRole('button', { name: /new customer/i })[0]
}

describe('MasterDataPage listing', () => {
  it('requests the surface path with the declared default sort', async () => {
    getPaginatedMock.mockResolvedValue({ data: [party()], meta: meta({ total: 1 }) })
    renderPage()

    await screen.findByText('Acme Industries')

    expect(getPaginatedMock).toHaveBeenCalledWith('/customers', expect.objectContaining({ sort: 'name', direction: 'asc' }))
  })

  it('renders a row from the columns the registry declares', async () => {
    getPaginatedMock.mockResolvedValue({ data: [party()], meta: meta({ total: 1 }) })
    renderPage()

    const row = (await screen.findByText('ACME')).closest('tr')
    expect(row).not.toBeNull()

    // Code, name, city, contact and status, and no contact phone because the
    // record has none.
    expect(within(row!).getByText('ACME')).toBeInTheDocument()
    expect(within(row!).getByText('Dubai, Dubai')).toBeInTheDocument()
    expect(within(row!).getByText('sam@acme.test')).toBeInTheDocument()
    expect(within(row!).getByText('Active')).toBeInTheDocument()
  })

  it('marks an inactive record rather than showing it as active', async () => {
    getPaginatedMock.mockResolvedValue({ data: [party({ is_active: false })], meta: meta({ total: 1 }) })
    renderPage()

    expect(await screen.findByText('Inactive')).toBeInTheDocument()
  })

  it('explains an empty list differently when a filter is already narrowing it', async () => {
    const user = userEvent.setup()
    renderPage()

    expect(await screen.findByText(/no customers yet/i)).toBeInTheDocument()

    await user.type(screen.getByRole('searchbox'), 'acme')
    await screen.findByText(/nothing matches those filters/i)
  })

  it('surfaces a list failure with a retry that refetches', async () => {
    getPaginatedMock.mockRejectedValue(new ApiRequestError(500, 'Server error'))
    const user = userEvent.setup()
    renderPage()

    expect(await screen.findByText(/server error/i)).toBeInTheDocument()

    getPaginatedMock.mockResolvedValue({ data: [party()], meta: meta({ total: 1 }) })
    await user.click(screen.getByRole('button', { name: /try again/i }))

    expect(await screen.findByText('Acme Industries')).toBeInTheDocument()
  })
})

describe('MasterDataPage search and filters', () => {
  it('debounces typing into a single request', async () => {
    const user = userEvent.setup()
    renderPage()
    await screen.findByText(/no customers yet/i)

    const before = getPaginatedMock.mock.calls.length
    await user.type(screen.getByRole('searchbox'), 'acme')

    await waitFor(() => {
      expect(getPaginatedMock.mock.calls.length).toBeGreaterThan(before)
    })

    await waitFor(() => expect(lastListParams().q).toBe('acme'))
  })

  it('omits an untouched status filter rather than sending it blank', async () => {
    renderPage()
    await screen.findByText(/no customers yet/i)

    // The API reads `is_active` with `has()`, which is true for '', so a blank
    // filter would silently narrow the list to inactive records.
    expect('is_active' in lastListParams()).toBe(false)
  })

  it('sends the status filter once chosen, as a number', async () => {
    const user = userEvent.setup()
    renderPage()
    await screen.findByText(/no customers yet/i)

    await user.selectOptions(screen.getByLabelText('Status'), '0')

    await waitFor(() => expect(lastListParams().is_active).toBe(0))
  })

  it('returns to the first page when a filter changes', async () => {
    const user = userEvent.setup()
    getPaginatedMock.mockResolvedValue({ data: [party()], meta: meta({ total: 100, last_page: 4 }) })
    renderPage()
    await screen.findByText('Acme Industries')

    await user.click(screen.getByRole('button', { name: /next page/i }))
    await waitFor(() => expect(lastListParams().page).toBe(2))

    await user.selectOptions(screen.getByLabelText('Status'), '1')

    await waitFor(() => expect(lastListParams().page).toBe(1))
  })

  it('clears the search and the filters together', async () => {
    const user = userEvent.setup()
    renderPage()
    await screen.findByText(/no customers yet/i)

    await user.type(screen.getByRole('searchbox'), 'acme')
    await user.selectOptions(screen.getByLabelText('Status'), '1')
    await waitFor(() => expect(lastListParams().q).toBe('acme'))

    await user.click(screen.getByRole('button', { name: /^clear$/i }))

    await waitFor(() => {
      expect(lastListParams().q).toBeUndefined()
      expect(lastListParams().is_active).toBeUndefined()
    })
  })
})

describe('MasterDataPage permissions', () => {
  it('hides the create control from a user without the write permission', async () => {
    getPaginatedMock.mockResolvedValue({ data: [party()], meta: meta({ total: 1 }) })
    renderPage(readOnlyUser, () => false)

    await screen.findByText('Acme Industries')

    expect(screen.queryByRole('button', { name: /new customer/i })).not.toBeInTheDocument()
    expect(screen.getByText('Read only')).toBeInTheDocument()
  })

  it('still lists records for a user who may only read', async () => {
    getPaginatedMock.mockResolvedValue({ data: [party()], meta: meta({ total: 1 }) })
    renderPage(readOnlyUser, (permission) => permission === 'customer.view')

    expect(await screen.findByText('Acme Industries')).toBeInTheDocument()
  })

  it('offers only the write actions the user actually holds', async () => {
    getPaginatedMock.mockResolvedValue({ data: [party()], meta: meta({ total: 1 }) })
    renderPage(currentUser, (permission) => permission === 'customer.view' || permission === 'customer.update')

    await screen.findByText('Acme Industries')

    expect(screen.getByRole('button', { name: /edit/i })).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /delete/i })).not.toBeInTheDocument()
  })
})

describe('MasterDataPage create', () => {
  it('posts a blank form and reports the result', async () => {
    const user = userEvent.setup()
    renderPage()
    await screen.findByText(/no customers yet/i)

    await user.click(newButton())

    await user.type(screen.getByLabelText(/^code/i), 'NEWCO')
    await user.type(screen.getByLabelText(/company name/i), 'New Company')
    await user.click(screen.getByRole('button', { name: /create customer/i }))

    await waitFor(() => {
      expect(postMock).toHaveBeenCalledWith('/customers', expect.objectContaining({ code: 'NEWCO', name: 'New Company' }))
    })
  })

  it('opens a second create form blank rather than pre-filled from the last one', async () => {
    const user = userEvent.setup()
    renderPage()
    await screen.findByText(/no customers yet/i)

    await user.click(newButton())
    await user.type(screen.getByLabelText(/^code/i), 'LEFTOVER')
    await user.click(screen.getByRole('button', { name: /^cancel$/i }))

    await user.click(newButton())

    // The dialog seeds its state once, so this is only true if the form remounts.
    await waitFor(() => expect(screen.getByLabelText(/^code/i)).toHaveValue(''))
  })

  it('attaches a 422 to the field the API named, not to a banner', async () => {
    const user = userEvent.setup()
    postMock.mockRejectedValue(new ApiRequestError(422, 'The given data was invalid.', { code: ['That code is already taken.'] }))
    renderPage()
    await screen.findByText(/no customers yet/i)

    await user.click(newButton())
    await user.click(screen.getByRole('button', { name: /create customer/i }))

    expect(await screen.findByText(/already taken/i)).toBeInTheDocument()
    expect(screen.queryByRole('alert')).not.toBeInTheDocument()
  })

  it('shows a 403 as a banner, because it belongs to no single field', async () => {
    const user = userEvent.setup()
    postMock.mockRejectedValue(new ApiRequestError(403, 'You do not have permission.'))
    renderPage()
    await screen.findByText(/no customers yet/i)

    await user.click(newButton())
    await user.click(screen.getByRole('button', { name: /create customer/i }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/do not have permission/i)
  })

  it('falls back to a generic message when the failure is not an API error', async () => {
    const user = userEvent.setup()
    postMock.mockRejectedValue(new TypeError('Failed to fetch'))
    renderPage()
    await screen.findByText(/no customers yet/i)

    await user.click(newButton())
    await user.click(screen.getByRole('button', { name: /create customer/i }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/unable to save/i)
  })
})

describe('MasterDataPage edit', () => {
  it('seeds the form from the record and sends the id on the payload', async () => {
    const user = userEvent.setup()
    getPaginatedMock.mockResolvedValue({ data: [party()], meta: meta({ total: 1 }) })
    renderPage()
    await screen.findByText('Acme Industries')

    await user.click(screen.getByRole('button', { name: /edit/i }))

    const code = await screen.findByLabelText(/^code/i)
    expect(code).toHaveValue('ACME')

    await user.clear(screen.getByLabelText(/company name/i))
    await user.type(screen.getByLabelText(/company name/i), 'Acme Renamed')
    await user.click(screen.getByRole('button', { name: /save changes/i }))

    await waitFor(() => {
      expect(putMock).toHaveBeenCalledWith('/customers/7', expect.objectContaining({ name: 'Acme Renamed' }))
    })
  })

  it('seeds a different record when a different row is opened', async () => {
    const user = userEvent.setup()
    getPaginatedMock.mockResolvedValue({
      data: [party(), party({ id: 8, code: 'GLOBEX', name: 'Globex Ltd' })],
      meta: meta({ total: 2 }),
    })
    renderPage()

    const rows = await screen.findAllByRole('row')
    await user.click(within(rows[2]).getByRole('button', { name: /edit/i }))

    expect(await screen.findByLabelText(/^code/i)).toHaveValue('GLOBEX')
  })

  it('keeps an inactive record inactive on save', async () => {
    const user = userEvent.setup()
    getPaginatedMock.mockResolvedValue({ data: [party({ is_active: false })], meta: meta({ total: 1 }) })
    renderPage()
    await screen.findByText('Acme Industries')

    await user.click(screen.getByRole('button', { name: /edit/i }))
    await screen.findByLabelText(/^code/i)
    await user.click(screen.getByRole('button', { name: /save changes/i }))

    await waitFor(() => expect(putMock).toHaveBeenCalledWith('/customers/7', expect.objectContaining({ is_active: 0 })))
  })
})

describe('MasterDataPage delete', () => {
  it('names the record in the confirmation and deletes on confirm', async () => {
    const user = userEvent.setup()
    getPaginatedMock.mockResolvedValue({ data: [party()], meta: meta({ total: 1 }) })
    renderPage()
    await screen.findByText('Acme Industries')

    await user.click(screen.getByRole('button', { name: /delete/i }))

    const dialog = await screen.findByRole('dialog')
    expect(within(dialog).getByText('Acme Industries')).toBeInTheDocument()

    await user.click(within(dialog).getByRole('button', { name: /^delete$/i }))

    await waitFor(() => expect(delMock).toHaveBeenCalledWith('/customers/7'))
  })

  it('sends nothing when the confirmation is cancelled', async () => {
    const user = userEvent.setup()
    getPaginatedMock.mockResolvedValue({ data: [party()], meta: meta({ total: 1 }) })
    renderPage()
    await screen.findByText('Acme Industries')

    await user.click(screen.getByRole('button', { name: /delete/i }))
    const dialog = await screen.findByRole('dialog')
    await user.click(within(dialog).getByRole('button', { name: /cancel/i }))

    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument())
    expect(delMock).not.toHaveBeenCalled()
  })

  it('keeps the dialog open and reports a failure', async () => {
    const user = userEvent.setup()
    getPaginatedMock.mockResolvedValue({ data: [party()], meta: meta({ total: 1 }) })
    delMock.mockRejectedValue(new ApiRequestError(409, 'This customer is still in use.'))
    renderPage()
    await screen.findByText('Acme Industries')

    await user.click(screen.getByRole('button', { name: /delete/i }))
    const dialog = await screen.findByRole('dialog')
    await user.click(within(dialog).getByRole('button', { name: /^delete$/i }))

    expect(await screen.findByText(/still in use/i)).toBeInTheDocument()
    expect(screen.getByRole('dialog')).toBeInTheDocument()
  })
})

describe('MasterDataPage pagination', () => {
  it('walks pages and changes the page size', async () => {
    const user = userEvent.setup()
    getPaginatedMock.mockResolvedValue({ data: [party()], meta: meta({ total: 100, last_page: 4 }) })
    renderPage()
    await screen.findByText('Acme Industries')

    await user.click(screen.getByRole('button', { name: /next page/i }))
    await waitFor(() => expect(lastListParams().page).toBe(2))

    await user.selectOptions(screen.getByLabelText(/rows per page/i), '50')
    await waitFor(() => {
      expect(lastListParams().per_page).toBe(50)
      // A new page size reorders the set, so page 2 may not exist any more.
      expect(lastListParams().page).toBe(1)
    })
  })
})

describe('shipments on the shared page', () => {
  function renderShipments(user: CurrentUser = shipmentsUser, can?: (p: string | string[]) => boolean) {
    return renderSurface(SURFACES.shipments, user, can)
  }

  /**
   * The table alone, once it has rendered.
   *
   * Awaited, because the toolbar's status filter carries the same words as the
   * status column - `findByText('Delivered')` matches an `<option>` in the filter
   * and resolves long before the row exists.
   */
  async function table() {
    return within(await screen.findByRole('table'))
  }

  /** The form alone. The toolbar carries its own "Carrier" and "Status" controls. */
  function dialog() {
    return within(screen.getByRole('dialog'))
  }

  it('requests the shipments path, not a master-data one', async () => {
    mockLists([shipment()])
    renderShipments()

    await screen.findByText('SHP-2026-0001')

    expect(getPaginatedMock).toHaveBeenCalledWith('/shipments', expect.anything())
    expect(getPaginatedMock).not.toHaveBeenCalledWith('/master/shipments', expect.anything())
  })

  it('shows the status the API computed, using the server label', async () => {
    mockLists([shipment()])
    renderShipments()

    await screen.findByText('SHP-2026-0001')

    expect((await table()).getByText('In transit')).toBeInTheDocument()
  })

  it('marks a shipment past its ETA as late without changing its status', async () => {
    /*
     * Being late is a fact about the dates, not another stage. Collapsing the two
     * into one badge would lose where the consignment actually got to, and the
     * server already knows the answer.
     */
    mockLists([shipment({ status: 'customs', status_label: 'At customs', is_late: true })])
    renderShipments()
    await screen.findByText('SHP-2026-0001')

    expect((await table()).getByText('At customs')).toBeInTheDocument()
    expect((await table()).getByText('Late')).toBeInTheDocument()
  })

  it('does not mark a delivered shipment as late, however old its ETA', async () => {
    // `is_late` is the server's answer and accounts for the shipment being closed.
    mockLists([
      shipment({
        status: 'delivered',
        status_label: 'Delivered',
        eta: '2020-01-01',
        is_late: false,
        is_closed: true,
      }),
    ])
    renderShipments()
    await screen.findByText('Delivered')

    expect((await table()).queryByText('Late')).not.toBeInTheDocument()
  })

  it('reads as unassigned when no carrier is chosen', async () => {
    mockLists([shipment({ carrier: null })])
    renderShipments()

    await screen.findByText('SHP-2026-0001')

    expect((await table()).getByText('Unassigned')).toBeInTheDocument()
  })

  it('seeds the edit form with every relation the surface declares', async () => {
    /*
     * The regression this guards: relation ids used to be hardcoded for carrier
     * and truck, so opening a shipment's edit form would have shown all ten of
     * its dropdowns blank while the record behind it was fully populated - and
     * saving would have quietly nulled every one of them.
     */
    const user = userEvent.setup()
    mockLists([shipment()])
    renderShipments()
    await screen.findByText('SHP-2026-0001')

    await user.click((await table()).getByRole('button', { name: /edit/i }))

    expect(await dialog().findByLabelText(/^reference/i)).toHaveValue('SHP-2026-0001')
    expect(dialog().getByLabelText(/^shipper$/i)).toHaveValue('3')
    expect(dialog().getByLabelText(/^consignee$/i)).toHaveValue('4')
    expect(dialog().getByLabelText(/^carrier$/i)).toHaveValue('1')
    expect(dialog().getByLabelText(/^vessel$/i)).toHaveValue('2')
    expect(dialog().getByLabelText(/port of loading/i)).toHaveValue('7')
    expect(dialog().getByLabelText(/port of discharge/i)).toHaveValue('8')
    expect(dialog().getByLabelText(/origin site/i)).toHaveValue('5')
    expect(dialog().getByLabelText(/destination site/i)).toHaveValue('6')
  })

  it('sends relation ids, not relation objects, when the form is saved', async () => {
    const user = userEvent.setup()
    mockLists([shipment()])
    renderShipments()
    await screen.findByText('SHP-2026-0001')

    await user.click((await table()).getByRole('button', { name: /edit/i }))
    await dialog().findByLabelText(/^reference/i)
    await user.click(screen.getByRole('button', { name: /save changes/i }))

    await waitFor(() => {
      expect(putMock).toHaveBeenCalledWith(
        '/shipments/42',
        expect.objectContaining({ carrier_id: '1', port_of_loading_id: '7', vessel_id: '2' }),
      )
    })

    const payload = putMock.mock.calls[0]?.[1] as Record<string, unknown>
    expect(payload.carrier).toBeUndefined()
    expect(payload.port_of_loading).toBeUndefined()
  })

  it('clears a relation to null rather than dropping the key', async () => {
    // An absent key means "leave this alone" to this API, so a cleared dropdown
    // that was omitted would silently keep the old carrier.
    const user = userEvent.setup()
    mockLists([shipment()])
    renderShipments()
    await screen.findByText('SHP-2026-0001')

    await user.click((await table()).getByRole('button', { name: /edit/i }))
    const carrierField = await dialog().findByLabelText(/^carrier$/i)
    await user.selectOptions(carrierField, '')
    await user.click(screen.getByRole('button', { name: /save changes/i }))

    await waitFor(() => {
      expect(putMock).toHaveBeenCalledWith(
        '/shipments/42',
        expect.objectContaining({ carrier_id: null }),
      )
    })
  })

  it('sends the status as a value the API knows, not a label', async () => {
    const user = userEvent.setup()
    mockLists([shipment()])
    renderShipments()
    await screen.findByText('SHP-2026-0001')

    await user.click((await table()).getByRole('button', { name: /edit/i }))
    await user.selectOptions(await dialog().findByLabelText(/^status/i), 'customs')
    await user.click(screen.getByRole('button', { name: /save changes/i }))

    await waitFor(() => {
      expect(putMock).toHaveBeenCalledWith('/shipments/42', expect.objectContaining({ status: 'customs' }))
    })
  })

  it('keeps a decimal volume as the string the API sent', async () => {
    // A float would render 54.500 as 54.5 and post back a different value than
    // the one stored, silently rewriting a three-decimal column on every save.
    const user = userEvent.setup()
    mockLists([shipment()])
    renderShipments()
    await screen.findByText('SHP-2026-0001')

    await user.click((await table()).getByRole('button', { name: /edit/i }))
    const volume = await dialog().findByLabelText(/volume/i)
    expect(volume).toHaveValue('54.500')
  })

  it('omits a blank required reference so the API can name the field', async () => {
    // Sending `reference: null` would produce a 422 with a worse message than the
    // one the API already writes, and hides which field was wrong.
    const user = userEvent.setup()
    mockLists([])
    renderShipments()
    await screen.findByText(/no shipments yet/i)

    await user.click(screen.getAllByRole('button', { name: /new shipment/i })[0])
    await dialog().findByLabelText(/^reference/i)
    await user.click(screen.getByRole('button', { name: /create shipment/i }))

    await waitFor(() => expect(postMock).toHaveBeenCalled())
    const payload = postMock.mock.calls[0]?.[1] as Record<string, unknown>
    expect('reference' in payload).toBe(false)
  })

  it('offers the shipment filters the API implements', () => {
    mockLists()
    renderShipments()

    expect(screen.getByLabelText(/^status$/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/^open$/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/^overdue$/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/^assignment$/i)).toBeInTheDocument()
  })

  it('hides the create control from a user without the write permission', async () => {
    const reader: CurrentUser = { ...shipmentsUser, roles: ['viewer'], permissions: ['shipment.view'] }
    mockLists([shipment()])
    renderShipments(reader, () => false)
    await screen.findByText('SHP-2026-0001')

    expect(screen.queryByRole('button', { name: /new shipment/i })).not.toBeInTheDocument()
  })

  it('offers only the write actions the user actually holds', async () => {
    mockLists([shipment()])
    renderShipments(shipmentsUser, (permission) => permission === 'shipment.view' || permission === 'shipment.update')
    await screen.findByText('SHP-2026-0001')

    expect((await table()).getByRole('button', { name: /edit/i })).toBeInTheDocument()
    expect((await table()).queryByRole('button', { name: /delete/i })).not.toBeInTheDocument()
  })
})

describe('bookings surface', () => {
  const bookingsUser: CurrentUser = {
    ...currentUser,
    roles: ['operations'],
    permissions: ['booking.view', 'booking.create', 'booking.update', 'booking.delete'],
  }

  function renderBookings(user: CurrentUser = bookingsUser, can?: (p: string | string[]) => boolean) {
    return renderSurface(SURFACES.bookings, user, can)
  }

  async function table() {
    return within(await screen.findByRole('table'))
  }

  it('requests the bookings path, not a master-data one', async () => {
    mockLists([booking()])
    renderBookings()

    await screen.findByText('BKG-2026-0001')

    expect(getPaginatedMock).toHaveBeenCalledWith('/bookings', expect.anything())
    expect(getPaginatedMock).not.toHaveBeenCalledWith('/master/bookings', expect.anything())
  })

  it('shows the booking status using the server label', async () => {
    /*
     * A booking's vocabulary is about the request, not the movement, so a client
     * `humanise` would have to be kept in step with the enum. The server already
     * decided the wording.
     */
    mockLists([booking({ status: 'requested', status_label: 'Requested' })])
    renderBookings()

    await screen.findByText('BKG-2026-0001')

    expect((await table()).getByText('Requested')).toBeInTheDocument()
  })

  it('labels the dates as requested, not as an estimated departure', async () => {
    /*
     * What was asked for and what the carrier agreed are not the same figure, and
     * the column that says so is the one an operator reads to decide whether to
     * chase the carrier. Calling it "ETD" would show a sailing nobody agreed to.
     */
    mockLists([booking()])
    renderBookings()

    await screen.findByText('BKG-2026-0001')

    expect((await table()).getByText('Requested ETD / ETA')).toBeInTheDocument()
  })

  it('lists a request that has produced no consignment', async () => {
    /*
     * The case the module exists for: a real request on file that has not become
     * cargo. The list must render it like any other booking.
     */
    mockLists([booking({ status: 'draft', status_label: 'Draft' })])
    renderBookings()

    await screen.findByText('BKG-2026-0001')

    expect((await table()).getByText('Draft')).toBeInTheDocument()
  })

  it('hides the write actions from a user who only holds the read permission', async () => {
    const reader: CurrentUser = { ...bookingsUser, roles: ['viewer'], permissions: ['booking.view'] }
    mockLists([booking()])
    renderBookings(reader, () => false)

    await screen.findByText('BKG-2026-0001')

    expect(screen.queryByRole('button', { name: /new booking/i })).not.toBeInTheDocument()
  })
})

describe('tracking surface', () => {
  /*
   * The permission set that matters: view and update, and no create or delete.
   *
   * There is no `tracking.create` or `tracking.delete` in this list, and that is
   * the whole reason the create control can be asserted absent below - the
   * permission the page checks is one the user does not hold, rather than a flag
   * the surface sets to say "read only".
   */
  const trackingUser: CurrentUser = {
    ...currentUser,
    roles: ['operations'],
    permissions: ['tracking.view', 'tracking.update'],
  }

  function renderTracking(user: CurrentUser = trackingUser, can?: (p: string | string[]) => boolean) {
    return renderSurface(SURFACES.tracking, user, can)
  }

  /** A `can` that believes only the permissions the user actually holds. */
  function canLike(user: CurrentUser) {
    return (permission: string | string[]) => {
      const wanted = Array.isArray(permission) ? permission : [permission]
      return wanted.every((p) => user.permissions.includes(p))
    }
  }

  async function table() {
    return within(await screen.findByRole('table'))
  }

  it('requests the tracking path, not a master-data one', async () => {
    mockLists([tracking()])
    renderTracking()

    await screen.findByText('SHP-2026-0001')

    expect(getPaginatedMock).toHaveBeenCalledWith('/tracking', expect.anything())
    expect(getPaginatedMock).not.toHaveBeenCalledWith('/master/tracking', expect.anything())
  })

  it('offers no way to create a tracking row, because the API has none', async () => {
    /*
     * A tracking row is provisioned by the consignment, not by a person, so there
     * is no endpoint to post to. A visible New button here would offer an action
     * that can only fail - and worse, tempt somebody into believing a report can
     * be filed for a consignment that has none.
     */
    mockLists([tracking()])
    renderTracking(trackingUser, canLike(trackingUser))

    await screen.findByText('SHP-2026-0001')

    expect(screen.queryByRole('button', { name: /new tracking/i })).not.toBeInTheDocument()
  })

  it('offers no way to delete one either', async () => {
    /*
     * The other half of the same argument, and the reason this surface is not
     * simply "bookings with fewer fields": deleting the row would destroy the only
     * record of what was ever reported, with nothing to replace it.
     */
    mockLists([tracking()])
    renderTracking(trackingUser, canLike(trackingUser))

    await screen.findByText('SHP-2026-0001')

    expect(screen.queryByRole('button', { name: /^delete /i })).not.toBeInTheDocument()
  })

  it('still lets a reader with only the read permission see the list', async () => {
    const reader: CurrentUser = { ...trackingUser, roles: ['viewer'], permissions: ['tracking.view'] }
    mockLists([tracking()])
    renderTracking(reader, canLike(reader))

    // No write permission, so no edit control on the row - but the worklist is the
    // screen, and it has to be readable by the people who chase carriers.
    expect(await screen.findByText('SHP-2026-0001')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /^edit /i })).not.toBeInTheDocument()
  })

  it('shows the milestone using the label the server decided on', async () => {
    mockLists([tracking({ last_milestone: 'customs_hold', last_milestone_label: 'Customs hold' })])
    renderTracking()

    await screen.findByText('SHP-2026-0001')

    expect((await table()).getByText('Customs hold')).toBeInTheDocument()
  })

  it('says a consignment has never been reported rather than showing nothing', async () => {
    /*
     * The most common row on a fresh install, and the one an empty-looking column
     * would render worst: every consignment exists before anyone has heard from
     * it. "Never reported" is a finding; a blank cell is an unrendered field.
     */
    mockLists([
      tracking({
        last_milestone: null,
        last_milestone_label: null,
        current_location: null,
        last_reported_at: null,
        source: null,
        is_unreported: true,
        is_stale: true,
      }),
    ])
    renderTracking()

    await screen.findByText('SHP-2026-0001')

    const rows = await table()
    expect(rows.getByText('Never reported')).toBeInTheDocument()
    expect(rows.getByText('Not reported')).toBeInTheDocument()
  })

  it('marks a problem milestone as needing attention', async () => {
    mockLists([
      tracking({
        last_milestone: 'exception',
        last_milestone_label: 'Exception',
        is_problem: true,
      }),
    ])
    renderTracking()

    await screen.findByText('SHP-2026-0001')

    expect((await table()).getByText('Needs attention')).toBeInTheDocument()
  })

  it('shows a revised date beside the arrival it replaced', async () => {
    /*
     * The two columns that make a delay legible. A revised date on its own says
     * only that somebody wrote a date down; the whole point is the gap between
     * what was planned and what the carrier now promises.
     */
    mockLists([
      tracking({
        revised_eta: '2026-10-08',
        delay_reason: 'Weather hold',
        is_delayed: true,
      }),
    ])
    renderTracking()

    await screen.findByText('SHP-2026-0001')

    const rows = await table()
    expect(rows.getByText('08 Oct 2026')).toBeInTheDocument()
    expect(rows.getByText('was 01 Oct 2026')).toBeInTheDocument()
    expect(rows.getByText('Delayed')).toBeInTheDocument()
  })

  it('reports how long ago the last report arrived, not just its date', async () => {
    /*
     * The question this screen is opened to answer. A timestamp alone makes the
     * operator do the subtraction that decides whether the row belongs on a
     * worklist at all.
     */
    mockLists([tracking({ last_reported_at: hoursAgo(72) })])
    renderTracking()

    await screen.findByText('SHP-2026-0001')

    expect((await table()).getByText('3 days ago')).toBeInTheDocument()
  })

  it('does not flag a delivered consignment as having gone quiet', async () => {
    /*
     * The badge is conditional on the consignment still moving, and the row-level
     * `is_stale` is not: silence is a true statement about finished cargo - it was
     * delivered weeks ago and nothing since - but a "gone quiet" alarm about it
     * points at work nobody can do.
     */
    mockLists([
      tracking({
        shipment: {
          ...tracking().shipment!,
          status: 'delivered',
          status_label: 'Delivered',
          is_closed: true,
        },
        last_reported_at: hoursAgo(24 * 60),
        is_stale: true,
      }),
    ])
    renderTracking()

    await screen.findByText('SHP-2026-0001')

    expect((await table()).queryByText('Gone quiet')).not.toBeInTheDocument()
  })

  it('sends the milestone as a value the API knows, not a label', async () => {
    /*
     * The same rule as a status dropdown: the wire carries the enum value, and a
     * label posted back would be rejected.
     */
    mockLists([tracking()])
    renderTracking(trackingUser, canLike(trackingUser))

    await userEvent.click((await table()).getByRole('button', { name: /edit/i }))

    await userEvent.selectOptions(
      await screen.findByLabelText(/last milestone/i),
      'out_for_delivery',
    )

    await userEvent.click(screen.getByRole('button', { name: /save changes/i }))

    await waitFor(() => expect(putMock).toHaveBeenCalled())

    const [path, payload] = putMock.mock.calls[0]
    expect(path).toBe('/tracking/9')
    expect(payload).toMatchObject({ last_milestone: 'out_for_delivery' })
  })

  it('cannot be pointed at a different consignment', async () => {
    /*
     * The API ignores a `shipment_id` a client sends rather than believing it, and
     * the form has no field that could produce one. Asserted through the absence
     * of any such input, since a field that existed would be the bug.
     */
    expect(SURFACES.tracking.fields.some((f) => f.name === 'shipment_id')).toBe(false)
    expect(SURFACES.tracking.relationFields).toBeUndefined()
  })

  it('offers the milestones the API accepts, and no others', async () => {
    /*
     * Ten options, matching `TrackingMilestone`. An eleventh milestone in the enum
     * and no assertion like this would leave the dropdown quietly missing it -
     * which on this surface means a real report cannot be filed at all.
     */
    const options = SURFACES.tracking.fields.find((f) => f.name === 'last_milestone')?.options

    expect(options?.map((o) => o.value)).toEqual([
      'booked',
      'gate_in',
      'departed',
      'transshipment',
      'arrived',
      'customs_hold',
      'customs_cleared',
      'out_for_delivery',
      'delivered',
      'exception',
    ])
  })

  it('offers the tracking filters the API implements', () => {
    expect(SURFACES.tracking.filters.map((f) => f.name)).toEqual([
      'last_milestone',
      'stale',
      'delayed',
      'problems',
      'open_only',
      'carrier_id',
    ])
  })

  it('sorts by the most recent report, since that is what a worklist is read by', () => {
    // Sorting by id would leave the consignment that went quiet this morning at
    // the bottom of page one, which is the row the operator came for.
    expect(SURFACES.tracking.defaultSort).toBe('last_reported_at')
  })
})

/**
 * Gates permissions off the user's own list, the way the real provider does.
 *
 * `renderSurface` defaults its `can` to allow-everything so most tests need not
 * think about it. A test about what a user *cannot* do has to hand over the real
 * predicate, or it asserts against a user who is allowed everything.
 */
function may(user: CurrentUser) {
  return (permission: string | string[]) =>
    (Array.isArray(permission) ? permission : [permission]).some((entry) =>
      user.permissions.includes(entry),
    )
}

describe('containers surface', () => {
  /** The table alone: the toolbar carries its own "Status" and "Consignment" controls. */
  async function table() {
    return within(await screen.findByRole('table'))
  }

  it('requests the containers path, sorted by box number', async () => {
    mockLists()

    renderSurface(SURFACES.containers, operationsUser, may(operationsUser))

    await waitFor(() =>
      expect(
        getPaginatedMock.mock.calls.some(
          ([path, params]) =>
            path === '/containers' && (params as { sort?: string })?.sort === 'container_number',
        ),
      ).toBe(true),
    )
  })

  it('reads a row as a box on a consignment, with the port it is bound for', async () => {
    mockLists([container()])

    renderSurface(SURFACES.containers, operationsUser, may(operationsUser))

    // The reference and the UN/LOCODE together, because a box on a hold is read by
    // asking which border it is stuck at and a bare reference cannot answer that.
    const rows = await table()

    expect(rows.getByText('MSCU7741203')).toBeDefined()
    expect(rows.getByText('SHP-2026-0001')).toBeDefined()
    expect(rows.getByText('NLRTM')).toBeDefined()
    expect(rows.getByText('45G1')).toBeDefined()
  })

  it('shows the status using the label the server decided on', async () => {
    mockLists([container({ status: 'customs_hold', status_label: 'Customs hold' })])

    renderSurface(SURFACES.containers, operationsUser, may(operationsUser))

    // The server's wording rather than a client `humanise`, so the list, the badge
    // and anything downstream that prints the value are one translation. Scoped to
    // the table, because the toolbar's status dropdown carries the same words.
    expect((await table()).getByText('Customs hold')).toBeDefined()
  })

  it('marks a held box as needing attention, and an ordinary one as not', async () => {
    mockLists([
      container({ id: 11, needs_attention: true, status: 'customs_hold' }),
      container({ id: 12, container_number: 'TGHU3350817', needs_attention: false }),
    ])

    renderSurface(SURFACES.containers, operationsUser, may(operationsUser))

    const rows = await table()

    expect(rows.getByText('MSCU7741203')).toBeDefined()

    // One badge across two rows, not one per row: the flag is the server's and the
    // second box is simply not on the worklist.
    expect(rows.getAllByText('Needs attention')).toHaveLength(1)
  })

  it('says an unweighed box has no figure rather than claiming it is empty', async () => {
    /*
     * Null is rendered as a dash, and the distinction is the point: an unweighed box
     * and a box with nothing in it are different findings, and a 0% would assert
     * the second about a row that only knows the first.
     */
    mockLists([
      container({
        id: 11,
        gross_weight_kg: null,
        max_gross_weight_kg: null,
        utilisation_percent: null,
      }),
    ])

    renderSurface(SURFACES.containers, operationsUser, may(operationsUser))

    const rows = await table()

    expect(rows.getByText('MSCU7741203')).toBeDefined()
    expect(rows.queryByText(/0%/)).toBeNull()
    expect(rows.queryByText(/kg/)).toBeNull()
  })

  it('seeds the edit form with the consignment the box is on', async () => {
    /*
     * The relation mapping, tested where it would fail. The API returns a nested
     * `shipment` and reads `shipment_id`; without the declared mapping the dropdown
     * would open blank on a row that plainly has a consignment.
     */
    mockLists([container()])

    renderSurface(SURFACES.containers, operationsUser, may(operationsUser))

    await screen.findByText('MSCU7741203')
    await userEvent.click((await table()).getByRole('button', { name: /edit/i }))

    // The selected label survives an option list that did not contain it, which is
    // what `relationLabels` is for - asserted in the dialog, because the row behind
    // it still carries the same reference.
    expect(await within(screen.getByRole('dialog')).findByText('SHP-2026-0001')).toBeDefined()
  })

  it('sends the consignment as an id, not as the object the API returned', async () => {
    mockLists([container()])

    renderSurface(SURFACES.containers, operationsUser, may(operationsUser))

    await screen.findByText('MSCU7741203')
    await userEvent.click((await table()).getByRole('button', { name: /edit/i }))
    await within(screen.getByRole('dialog')).findByText('SHP-2026-0001')
    await userEvent.click(screen.getByRole('button', { name: /save changes/i }))

    await waitFor(() => expect(putMock).toHaveBeenCalled())

    const [path, payload] = putMock.mock.calls[0]
    expect(path).toBe('/containers/11')
    // A string, because a `<select>` hands back strings and the API casts the id.
    // Asserted literally: a nested `shipment` object here would be a 422.
    expect(payload).toMatchObject({ shipment_id: '42', container_number: 'MSCU7741203' })
    expect(payload).not.toHaveProperty('shipment')
  })

  it('hides delete from a clerk who may not use it, and keeps the rest of the row actions', async () => {
    /*
     * The operations role in `config/permissions.php` grants view, create and
     * update but not delete on all three of these modules, so this is the assertion
     * that a page is not offering a control the API would refuse.
     */
    mockLists([container()])

    renderSurface(SURFACES.containers, operationsUser, may(operationsUser))

    const rows = await table()

    expect(rows.getByText('MSCU7741203')).toBeDefined()
    expect(rows.getByRole('button', { name: /edit/i })).toBeDefined()
    expect(rows.queryByRole('button', { name: /delete/i })).toBeNull()
  })

  it('offers no write actions at all to a user who may only read', async () => {
    const reader: CurrentUser = { ...currentUser, permissions: ['container.view'] }

    mockLists([container()])

    renderSurface(SURFACES.containers, reader, may(reader))

    const rows = await table()

    expect(rows.getByText('MSCU7741203')).toBeDefined()
    expect(screen.queryByRole('button', { name: /new container/i })).toBeNull()
    expect(rows.queryByRole('button', { name: /edit/i })).toBeNull()
    expect(rows.queryByRole('button', { name: /delete/i })).toBeNull()
    // And it says so, rather than leaving a blank column.
    expect(rows.getByText('Read only')).toBeDefined()
  })
})

describe('customs surface', () => {
  /** The table alone: the toolbar carries its own "Status" and "Consignment" controls. */
  async function table() {
    return within(await screen.findByRole('table'))
  }

  it('requests the customs path, sorted by when the filing went in', async () => {
    mockLists()

    renderSurface(SURFACES.customs, operationsUser)

    await waitFor(() =>
      expect(
        getPaginatedMock.mock.calls.some(
          ([path, params]) =>
            path === '/customs' && (params as { sort?: string })?.sort === 'submitted_at',
        ),
      ).toBe(true),
    )
  })

  it('says a filing with no entry number has not been filed, rather than showing nothing', async () => {
    mockLists([clearance()])

    renderSurface(SURFACES.customs, operationsUser)

    // A filing that has not gone in is a real and common state, not a missing
    // value - and it is the row this screen exists to surface.
    expect(await screen.findByText('Not filed')).toBeDefined()
    expect(screen.getByText('SHP-2026-0002')).toBeDefined()
    expect(screen.getByText('USLGB')).toBeDefined()
  })

  it('distinguishes a filing that is held from one that needs chasing', async () => {
    mockLists([clearance()])

    renderSurface(SURFACES.customs, operationsUser)

    await screen.findByText('Pending')

    // Two different questions, so two different badges. A refused filing is the
    // second and not the first: nothing is waiting on the authority, somebody has
    // to make a call. Scoped to the table, because the status dropdown above it
    // offers the same words - and `findByText` on one of those would resolve before
    // the row has even arrived.
    const rows = await table()

    expect(rows.getByText('Not filed')).toBeDefined()
    expect(rows.getByText('Held')).toBeDefined()
    expect(rows.queryByText('Needs attention')).toBeNull()
  })

  it('marks a refusal as needing attention and as no longer held', async () => {
    mockLists([
      clearance({
        status: 'rejected',
        status_label: 'Rejected',
        entry_number: 'ES-2210/2026',
        is_held: false,
        needs_attention: true,
      }),
    ])

    renderSurface(SURFACES.customs, operationsUser, may(operationsUser))

    const rows = await table()

    expect(rows.getByText('ES-2210/2026')).toBeDefined()
    expect(rows.getByText('Needs attention')).toBeDefined()
    expect(rows.queryByText('Held')).toBeNull()
  })

  it('says a port that has been removed from the lookup table is no longer on file', async () => {
    /*
     * Not a dash and not "Unassigned". The column nulls on delete precisely so a
     * filing survives its port, and rendering that as an empty cell would suggest
     * the filing was never made at one.
     */
    mockLists([clearance({ port: undefined })])

    renderSurface(SURFACES.customs, operationsUser, may(operationsUser))

    expect((await table()).findByText('Port no longer on file')).toBeDefined()
  })

  it('names a row with no entry number by its consignment and port', async () => {
    /*
     * The delete confirmation, a toast and the table's accessible row name all read
     * this, and a filing has no name of its own. Without the fallback the operator
     * would be asked to confirm "Clearance #21".
     */
    expect(SURFACES.customs.label(clearance())).toBe('SHP-2026-0002 — USLGB')
    expect(SURFACES.customs.label(clearance({ entry_number: 'SG-4471/2026' }))).toBe(
      'SG-4471/2026 — SHP-2026-0002',
    )
  })

  it('seeds the edit form with both relations the filing hangs off', async () => {
    mockLists([clearance()])

    renderSurface(SURFACES.customs, operationsUser, may(operationsUser))

    await screen.findByText('Not filed')
    await userEvent.click((await table()).getByRole('button', { name: /edit/i }))

    const form = within(screen.getByRole('dialog'))

    // In the dialog rather than on the screen: the row behind it still carries both
    // references, so an unscoped query would find two of each.
    expect(await form.findByText('SHP-2026-0002')).toBeDefined()
    expect(form.getByText('USLGB')).toBeDefined()
  })

  it('sends the port as an id, and keeps a blank port explicitly null', async () => {
    /*
     * Two halves of the same contract. The API takes `port_id`, not `port`; and
     * clearing a nullable relation has to send an explicit null, because an omitted
     * key on a PATCH means "leave this alone" and the port would silently stay.
     */
    mockLists([clearance()])

    renderSurface(SURFACES.customs, operationsUser, may(operationsUser))

    await screen.findByText('Not filed')
    await userEvent.click((await table()).getByRole('button', { name: /edit/i }))
    await within(screen.getByRole('dialog')).findByText('SHP-2026-0002')
    await userEvent.click(screen.getByRole('button', { name: /save changes/i }))

    await waitFor(() => expect(putMock).toHaveBeenCalled())

    const [path, payload] = putMock.mock.calls[0]
    expect(path).toBe('/customs/21')
    // Strings, because that is what a `<select>` hands back; the API casts the ids.
    expect(payload).toMatchObject({ shipment_id: '42', port_id: '9' })
    expect(payload).not.toHaveProperty('port')
  })

  it('keeps the broker on a filing that has not been submitted yet', async () => {
    /*
     * The broker is recorded before there is anything to file, because the broker is
     * who an operator rings. Asserted because a null-submitted fixture is exactly
     * where a "hide fields that have no value yet" rule would quietly drop it.
     */
    mockLists([clearance()])

    renderSurface(SURFACES.customs, operationsUser, may(operationsUser))

    await screen.findByText('Not filed')
    await userEvent.click((await table()).getByRole('button', { name: /edit/i }))

    expect(await screen.findByDisplayValue('Meridian Freight Services')).toBeDefined()
    expect(await screen.findByDisplayValue('Phytosanitary certificate not presented')).toBeDefined()
  })
})

describe('deliveries surface', () => {
  /** The table alone: the toolbar carries its own "Status" and "Consignment" controls. */
  async function table() {
    return within(await screen.findByRole('table'))
  }

  it('requests the deliveries path, sorted by the booked slot', async () => {
    mockLists()

    renderSurface(SURFACES.deliveries, operationsUser)

    await waitFor(() =>
      expect(
        getPaginatedMock.mock.calls.some(
          ([path, params]) =>
            path === '/deliveries' && (params as { sort?: string })?.sort === 'scheduled_at',
        ),
      ).toBe(true),
    )
  })

  it('reads a row as a reference on a consignment, with the vehicle and driver', async () => {
    mockLists([delivery()])

    renderSurface(SURFACES.deliveries, operationsUser, may(operationsUser))

    const rows = await table()

    expect(rows.getByText('DLV-2026-0001')).toBeDefined()
    expect(rows.getByText('SHP-2026-0003')).toBeDefined()
    // The plate and the driver together, because a driver is identified by the
    // truck they arrived in as often as by name.
    expect(rows.getByText('DXB-4471')).toBeDefined()
    expect(rows.getByText('Sam Okafor')).toBeDefined()
  })

  it('shows the booked slot with its time, not as a bare date', async () => {
    mockLists([delivery()])

    renderSurface(SURFACES.deliveries, operationsUser, may(operationsUser))

    const rows = await table()

    expect(rows.getByText('DLV-2026-0001')).toBeDefined()

    // A delivery list is a diary, so the time is the point of the column. A date
    // alone would not say whether the slot was this morning or tonight.
    expect(rows.getByRole('cell', { name: /\d{1,2}:\d{2}/ })).toBeDefined()
  })

  it('marks a leg whose slot has passed as overdue', async () => {
    mockLists([delivery({ is_overdue: true })])

    renderSurface(SURFACES.deliveries, operationsUser, may(operationsUser))

    const rows = await table()

    expect(rows.getByText('DLV-2026-0001')).toBeDefined()

    // The server's `is_overdue`, beside the status rather than over it: late is a
    // fact about the clock, not another stage of the delivery. Scoped to the table
    // because the toolbar's status dropdown offers these same words.
    expect(rows.getByText('Overdue')).toBeDefined()
    expect(rows.getByText('Scheduled')).toBeDefined()
  })

  it('does not mark a failed attempt overdue, because its slot did come round', async () => {
    mockLists([
      delivery({
        id: 32,
        reference: 'DLV-2026-0002',
        status: 'failed',
        status_label: 'Failed',
        // A month-old failure. Outstanding, because the cargo is still in the
        // vehicle, and not overdue, because nobody needs chasing - a new slot does.
        scheduled_at: hoursAgo(24 * 30),
        is_overdue: false,
        is_outstanding: true,
        has_failed: true,
        failure_reason: 'Site gate closed on the first attempt, no access',
      }),
    ])

    renderSurface(SURFACES.deliveries, operationsUser, may(operationsUser))

    const rows = await table()

    expect(rows.getByText('DLV-2026-0002')).toBeDefined()
    expect(rows.getByText('Failed')).toBeDefined()
    expect(rows.queryByText('Overdue')).toBeNull()
    // The line a worklist is actually read for.
    expect(rows.getByText(/Site gate closed/)).toBeDefined()
  })

  it('shows the failure reason as a dash on a leg that went to plan', async () => {
    mockLists([delivery()])

    renderSurface(SURFACES.deliveries, operationsUser, may(operationsUser))

    const rows = await table()

    expect(rows.getByText('DLV-2026-0001')).toBeDefined()
    /*
     * A dash rather than an empty cell, matching every other absent value in the
     * table. An empty cell reads as a rendering fault, and on a worklist whose rows
     * are mostly fine the eye needs to be told the failure column simply does not
     * apply to this one.
     */
    expect(rows.getAllByText('—').length).toBeGreaterThan(0)
  })

  it('seeds every relation on the edit form, which is three here', async () => {
    mockLists([delivery()])

    renderSurface(SURFACES.deliveries, operationsUser, may(operationsUser))

    await screen.findByText('DLV-2026-0001')
    await userEvent.click((await table()).getByRole('button', { name: /edit/i }))

    // In the dialog rather than on the screen: the row behind it carries the same
    // three labels, so an unscoped query would find two of each.
    const form = within(screen.getByRole('dialog'))

    expect(await form.findByDisplayValue('DLV-2026-0001')).toBeDefined()
    expect(form.getByText('SHP-2026-0003')).toBeDefined()
    expect(form.getByText('DXB-4471')).toBeDefined()
    expect(form.getByText('Sam Okafor')).toBeDefined()
  })

  it('sends the three relations as ids, and the slot with its time intact', async () => {
    mockLists([delivery()])

    renderSurface(SURFACES.deliveries, operationsUser, may(operationsUser))

    await screen.findByText('DLV-2026-0001')
    await userEvent.click((await table()).getByRole('button', { name: /edit/i }))
    await within(screen.getByRole('dialog')).findByDisplayValue('DLV-2026-0001')
    await userEvent.click(screen.getByRole('button', { name: /save changes/i }))

    await waitFor(() => expect(putMock).toHaveBeenCalled())

    const [path, payload] = putMock.mock.calls[0]
    expect(path).toBe('/deliveries/31')
    // Strings, because that is what a `<select>` hands back; the API casts the ids.
    expect(payload).toMatchObject({ shipment_id: '42', truck_id: '3', driver_id: '5' })
    expect(payload).not.toHaveProperty('truck')
    // The whole reason the slot is a datetime: a bare date here would be midnight,
    // and the row would read as overdue from the moment it was saved.
    expect(String((payload as { scheduled_at: string }).scheduled_at)).toMatch(/T\d{2}:\d{2}/)
  })

  it('gives the booked slot a control that can hold a time', async () => {
    mockLists([delivery()])

    renderSurface(SURFACES.deliveries, operationsUser, may(operationsUser))

    await screen.findByText('DLV-2026-0001')
    await userEvent.click((await table()).getByRole('button', { name: /edit/i }))

    const slot = await screen.findByLabelText(/booked slot/i)

    expect(slot).toHaveProperty('type', 'datetime-local')
  })
})

function document(overrides: Partial<Document> = {}): Document {
  return Object.assign<Document, Partial<Document>>(
    {
      id: 42,
      title: 'Maersk Bill of Lading — Shanghai to Rotterdam',
      reference: 'MAEU-4471',
      document_type: 'bill_of_lading',
      document_type_label: 'Bill of lading',
      shipment: { id: 3, reference: 'SHP-2026-0003', status: 'in_transit', status_label: 'In transit', is_closed: false },
      issued_on: '2026-02-01',
      expires_on: null,
      file: {
        name: 'bill-of-lading.pdf',
        mime_type: 'application/pdf',
        size_bytes: 182_400,
        version: 1,
        uploaded_at: '2026-02-01T09:30:00Z',
        uploaded_by: { id: 1, name: 'Ada Admin' },
      },
      version_count: 1,
      download_url: '/api/v1/documents/42/download',
      is_expired: false,
      expires_in_days: null,
      needs_attention: false,
      notes: null,
      created_at: '2026-02-01T09:30:00Z',
      updated_at: '2026-02-01T09:30:00Z',
    },
    overrides,
  )
}

describe('documents surface', () => {
  /** The table alone: the toolbar carries its own "Type" and "Consignment" controls. */
  async function table() {
    return within(await screen.findByRole('table'))
  }

  it('requests the documents path, sorted by title', async () => {
    mockLists([document()])

    renderSurface(SURFACES.documents, documentsUser, may(documentsUser))

    await waitFor(() =>
      expect(
        getPaginatedMock.mock.calls.some(
          ([path, params]) =>
            path === '/documents' && (params as { sort?: string })?.sort === 'title',
        ),
      ).toBe(true),
    )
  })

  it('reads a row as a titled document with its type, file and version count', async () => {
    mockLists([document()])

    renderSurface(SURFACES.documents, documentsUser, may(documentsUser))

    const rows = await table()

    // The title, not the third party's number, is what the register is keyed on.
    expect(rows.getByText('Maersk Bill of Lading — Shanghai to Rotterdam')).toBeDefined()
    expect(rows.getByText('MAEU-4471')).toBeDefined()
    expect(rows.getByText('Bill of lading')).toBeDefined()
    // The file's own name and the version it is, then the count of copies on file.
    expect(rows.getByText('bill-of-lading.pdf')).toBeDefined()
    expect(rows.getByText(/v1/)).toBeDefined()
    expect(rows.getByText(/178 KB/)).toBeDefined()
  })

  it('files a document against the business when it has no consignment, not as a dash', async () => {
    mockLists([document({ shipment: null })])

    renderSurface(SURFACES.documents, documentsUser, may(documentsUser))

    const rows = await table()

    // A fleet certificate belongs to no consignment; that is a fact about it, not
    // missing data, so it must not share the dash an absent value gets.
    expect(rows.getByText('Company-wide')).toBeDefined()
  })

  it('leaves a document that never expires without a countdown', async () => {
    mockLists([document({ expires_on: null })])

    renderSurface(SURFACES.documents, documentsUser, may(documentsUser))

    const rows = await table()

    expect(rows.getByText('Maersk Bill of Lading — Shanghai to Rotterdam')).toBeDefined()
    // An em dash, this table's placeholder for a value that is genuinely absent -
    // which is what "never expires" is. There is no countdown to show.
    expect(rows.getByText('—')).toBeDefined()
  })

  it('counts down a certificate that is close to lapsing', async () => {
    mockLists([
      document({ expires_on: dateInDays(12), is_expired: false, needs_attention: true }),
    ])

    renderSurface(SURFACES.documents, documentsUser, may(documentsUser))

    const rows = await table()

    // Relative to today rather than the calendar date, because the clock is the
    // point: "in 12d" is what prompts a renewal, and the date alone does not.
    expect(rows.getByText('in 12d')).toBeDefined()
  })

  it('marks a lapsed certificate as expired in the past tense', async () => {
    mockLists([
      document({ expires_on: dateInDays(-5), is_expired: true, needs_attention: true }),
    ])

    renderSurface(SURFACES.documents, documentsUser, may(documentsUser))

    const rows = await table()

    expect(rows.getByText('5d ago')).toBeDefined()
  })

  it('offers a download to a user who may take one, and does not ask first', async () => {
    mockLists([document()])
    downloadFileMock.mockResolvedValue(undefined)

    renderSurface(SURFACES.documents, documentsUser, may(documentsUser))

    await screen.findByText('Maersk Bill of Lading — Shanghai to Rotterdam')

    await userEvent.click(await table().then((rows) => rows.getByRole('button', { name: 'Download 42' })))

    // The path comes from the record's own `download_url`, and the file's name is
    // the fallback a server that sends no Content-Disposition would be saved under.
    expect(downloadFileMock).toHaveBeenCalledWith(
      '/api/v1/documents/42/download',
      'bill-of-lading.pdf',
    )
    // A download is not a mutation: there is nothing to confirm, so the button
    // fires on the click rather than opening the action dialog.
    expect(screen.queryByRole('dialog')).toBeNull()
  })

  it('withholds the download from a user who may only read the register', async () => {
    mockLists([document()])

    renderSurface(SURFACES.documents, documentsReader, may(documentsReader))

    const rows = await table()

    expect(rows.getByText('Maersk Bill of Lading — Shanghai to Rotterdam')).toBeDefined()
    // Reading a title is not the authority to take the file away, and the button is
    // absent rather than disabled so the difference is visible.
    expect(rows.queryByRole('button', { name: /download/i })).toBeNull()
    expect(rows.getByText('Read only')).toBeDefined()
  })

  it('separates the expiring window from the already-lapsed question', async () => {
    mockLists()

    renderSurface(SURFACES.documents, documentsUser, may(documentsUser))

    // The toolbar renders on an empty register too, which is the point: the filters
    // are how an empty "expiring" list gets asked for in the first place.
    await screen.findByPlaceholderText(/Search by title, reference, type or consignment/)

    // Two questions, not one collapsed dropdown: due soon is actionable, lapsed is
    // a fact. The labels are the server's window, not a constant in the browser.
    expect(screen.getByRole('option', { name: 'Within the warning window' })).toBeDefined()
    expect(screen.getByRole('option', { name: 'Already lapsed' })).toBeDefined()
    expect(screen.getByRole('option', { name: 'The business, not a consignment' })).toBeDefined()
  })
})

/** Kept so a type error in a surface def fails the suite rather than the build. */
describe('surface registry', () => {
  it('has a page, a path and a distinct write namespace for every surface', () => {
    const surfaces = Object.values(SURFACES)

    // Twenty-one now, and the number itself is the point: it is one more per surface
    // the registry describes, and a typo in a `path` or a `writeNamespace` would
    // make two of them collide on either count below.
    expect(surfaces).toHaveLength(21)
    expect(new Set(surfaces.map((s) => s.path)).size).toBe(21)
    expect(new Set(surfaces.map((s) => s.writeNamespace)).size).toBe(21)
  })

  it('separates the ten lookup tables from the modules that record transactions', () => {
    // The registry serves both, so "ten surfaces" stopped being true. What has to
    // stay true is that the Master Data section is still exactly the lookup
    // tables, and that shipments, bookings, tracking, the three operations
    // modules, the four finance modules and the document register are registered
    // surfaces without being ten of them.
    expect(MASTER_SURFACE_KEYS).toHaveLength(10)
    expect([...MASTER_SURFACE_KEYS]).not.toContain('shipments')
    expect([...MASTER_SURFACE_KEYS]).not.toContain('bookings')
    expect([...MASTER_SURFACE_KEYS]).not.toContain('tracking')
    expect([...MASTER_SURFACE_KEYS]).not.toContain('containers')
    expect([...MASTER_SURFACE_KEYS]).not.toContain('customs')
    expect([...MASTER_SURFACE_KEYS]).not.toContain('deliveries')
    expect(SURFACE_KEYS).toHaveLength(21)
  })

  it('keeps the finance ledger out of the reference dropdowns', () => {
    // A customer, carrier or product is something a charge is *about*, and a
    // reference picker offers those. An invoice is not: it is a result of charges
    // already existing, and letting an operator pick one while creating a charge
    // would be a way to attach a line to a document the charge is meant to be
    // part of. Money flows one way here, so no finance surface is a reference
    // target - which is what `MASTER_SURFACE_KEYS` being exactly the lookup
    // tables is now asserting on both counts.
    for (const key of ['charges', 'invoices', 'payments', 'expenses'] as const) {
      expect([...MASTER_SURFACE_KEYS]).not.toContain(key)
      expect(SURFACE_KEYS).toContain(key)
    }
  })

  it('gives shipments a status rather than an active flag', () => {
    const shipments = SURFACES.shipments

    // The active column reads `is_active`, which a consignment does not have. A
    // surface with a lifecycle has to declare its own status column instead.
    expect(shipments.columns.some((c) => c.key === 'is_active')).toBe(false)
    expect(shipments.columns.some((c) => c.key === 'status')).toBe(true)
    expect(shipments.fields.some((f) => f.name === 'is_active')).toBe(false)
  })

  it('makes reference on shipments a required, status-backed field', () => {
    const reference = SURFACES.shipments.fields.find((f) => f.name === 'reference')

    expect(reference?.required).toBe(true)
    expect(SURFACES.shipments.fields.find((f) => f.name === 'status')?.options?.length).toBe(7)
  })

  it('offers both site kinds for a shipment origin', () => {
    /*
     * The API validates `origin_site_id` against `sites` with no `kind` filter, so
     * a consignment can leave a factory. A reference pinned to one surface would
     * have had to hide half the valid answers.
     */
    const origin = SURFACES.shipments.fields.find((f) => f.name === 'origin_site_id')
    const reference = origin?.reference

    expect(reference && 'surfaces' in reference && reference.surfaces).toEqual([
      'warehouses',
      'factories',
    ])
  })

  it('gives bookings a status rather than an active flag', () => {
    // A booking is not active or inactive, it is confirmed or it is not - the same
    // argument as a consignment, and the same column that would break if the
    // shared machinery assumed `is_active` was always there.
    const bookings = SURFACES.bookings

    expect(bookings.columns.some((c) => c.key === 'is_active')).toBe(false)
    expect(bookings.columns.some((c) => c.key === 'status')).toBe(true)
    expect(bookings.fields.some((f) => f.name === 'is_active')).toBe(false)
  })

  it('offers the booking statuses the API accepts, and no others', () => {
    /*
     * Five options, matching `BookingStatus`. A sixth in the enum and no
     * assertion like this one would leave the dropdown quietly missing a state.
     */
    const options = SURFACES.bookings.fields.find((f) => f.name === 'status')?.options

    expect(options?.map((o) => o.value)).toEqual([
      'draft',
      'requested',
      'confirmed',
      'cancelled',
      'expired',
    ])
  })

  it('offers the booking filters the API implements', () => {
    // `pending` is the one that is not on a shipment: the follow-up list of
    // requests a carrier has not answered.
    const names = SURFACES.bookings.filters.map((f) => f.name)

    expect(names).toEqual(['status', 'open_only', 'pending', 'unassigned', 'carrier_id'])
  })

  it('lets a shipment name the booking it came from', () => {
    /*
     * The one reference that points at a transactional module rather than lookup
     * data. It is optional on both sides: the field is not required, and the
     * booking reference must not ask for active records - bookings have no
     * `is_active` column, so that filter would be one the API ignores.
     */
    const field = SURFACES.shipments.fields.find((f) => f.name === 'booking_id')

    expect(field).toBeDefined()
    expect(field?.required).toBeUndefined()
    expect(field?.reference && 'surface' in field.reference && field.reference.surface).toBe('bookings')
    expect(
      field?.reference && 'query' in field.reference ? field.reference.query : undefined,
    ).not.toHaveProperty('is_active')
  })

  it('maps the booking relation onto the id the write endpoint takes', () => {
    // The API returns `booking` but accepts `booking_id`, and the two cannot be
    // derived from each other by adding a suffix.
    expect(SURFACES.shipments.relationFields?.booking).toBe('booking_id')
  })

  describe('the three operations modules', () => {
    /*
     * Each of these three has a status rather than an active flag, for the same
     * reason shipments and bookings do, and the assertion is the same one: the
     * shared `activeColumn` reads `is_active`, which none of them has, so each
     * declares its own status column instead.
     */
    it.each(['containers', 'customs', 'deliveries'] as const)(
      'gives %s a status rather than an active flag',
      (key) => {
        const surface = SURFACES[key]

        expect(surface.columns.some((c) => c.key === 'is_active')).toBe(false)
        expect(surface.columns.some((c) => c.key === 'status')).toBe(true)
        expect(surface.fields.some((f) => f.name === 'is_active')).toBe(false)
      },
    )

    it.each([
      ['containers', 7],
      ['customs', 6],
      ['deliveries', 6],
    ] as const)('offers %s exactly the statuses the API accepts', (key, count) => {
      /*
       * One assertion per surface rather than the three lists spelled out, so a
       * state added to a backend enum and not to a dropdown is caught here. The
       * count is the useful half; the wording is the API's own and the badge reads
       * the same `status_label` the API sends.
       */
      const options = SURFACES[key].fields.find((f) => f.name === 'status')?.options

      expect(options?.map((o) => o.value).sort()).toHaveLength(count)
      expect(new Set(options?.map((o) => o.value)).size).toBe(count)
    })

    it('makes the consignment required on all three, because it is half the key', () => {
      /*
       * A container number repeats across consignments, so the pair is what the
       * database enforces; the API requires `shipment_id` on all three. A form that
       * let it be blank would trade a 422 that names the field for a manifest with
       * an orphan box on it.
       */
      for (const key of ['containers', 'customs', 'deliveries'] as const) {
        const field = SURFACES[key].fields.find((f) => f.name === 'shipment_id')

        expect(field, `${key} has no consignment field`).toBeDefined()
        expect(field?.required).toBe(true)
        expect(field?.reference && 'surface' in field.reference && field.reference.surface).toBe(
          'shipments',
        )
        expect(SURFACES[key].relationFields?.shipment).toBe('shipment_id')
      }
    })

    it('offers every consignment, closed ones included, because the rows outlive them', () => {
      /*
       * No `is_active` in the query, because a consignment has a status rather than
       * a flag and the API would ignore the filter. Closed consignments are offered
       * deliberately: recording a delivery against a consignment already marked
       * delivered is how a disagreement between two screens gets settled.
       */
      for (const key of ['containers', 'customs', 'deliveries'] as const) {
        const field = SURFACES[key].fields.find((f) => f.name === 'shipment_id')
        const query = field?.reference && 'query' in field.reference ? field.reference.query : undefined

        expect(query, `${key} sends no reference query`).toBeDefined()
        expect(query).not.toHaveProperty('is_active')
      }
    })

    it('offers the container filters the API implements', () => {
      const names = SURFACES.containers.filters.map((f) => f.name)

      // `empty` is the one that is not on a consignment: the boxes waiting to be
      // stuffed, which is a yard list rather than a cargo list.
      expect(names).toEqual([
        'status',
        'shipment_id',
        'needs_attention',
        'open_only',
        'empty',
        'iso_code',
        'carrier_id',
      ])
    })

    it('offers the customs filters the API implements', () => {
      // `held` and `needs_attention` are both here and are not the same filter,
      // which is the distinction the two badges on the row are there to mirror.
      const names = SURFACES.customs.filters.map((f) => f.name)

      expect(names).toEqual([
        'status',
        'shipment_id',
        'port_id',
        'held',
        'needs_attention',
        'open_only',
        'carrier_id',
      ])
    })

    it('offers the delivery filters the API implements', () => {
      /*
       * `overdue`, `outstanding` and `failed` are three separate questions. The
       * first is about the clock, the second about whether the cargo has changed
       * hands, and the third about how the last attempt went - and a failed attempt
       * is outstanding and not overdue, which is why one dropdown cannot do both.
       */
      const names = SURFACES.deliveries.filters.map((f) => f.name)

      expect(names).toEqual([
        'status',
        'shipment_id',
        'truck_id',
        'driver_id',
        'overdue',
        'outstanding',
        'failed',
      ])
    })

    it('sorts each module by the column its worklist is read by', () => {
      /*
       * A manifest by box number, a customs queue by when the filing went in, and
       * a delivery list by the slot - each is a different question and sorting by
       * id would answer none of them. All three are whitelisted by their
       * controllers, so these are real sorts rather than no-ops.
       */
      expect(SURFACES.containers.defaultSort).toBe('container_number')
      expect(SURFACES.customs.defaultSort).toBe('submitted_at')
      expect(SURFACES.deliveries.defaultSort).toBe('scheduled_at')
    })

    it('gives the delivery timestamps a time rather than a bare date', () => {
      /*
       * The one place in the registry where a date column is the wrong control. A
       * slot booked for 14:00 saved as midnight reads as overdue from the moment
       * it is written, and the overdue filter is the alarm this screen exists to
       * raise.
       */
      const slot = SURFACES.deliveries.fields.find((f) => f.name === 'scheduled_at')

      expect(slot?.kind).toBe('datetime')
      // And the customs filings, which are events too - a filing that went in
      // this morning is not the same age as one that went in yesterday.
      expect(SURFACES.customs.fields.find((f) => f.name === 'submitted_at')?.kind).toBe('datetime')
    })

    it('maps every relation these modules write onto the id the API takes', () => {
      /*
       * The API returns `truck` and reads `truck_id`, and the same for the driver
       * and the port. Without these the edit form would open with every dropdown on
       * a delivery blank - which is the exact bug the mapping was introduced for on
       * shipments, repeated three times over if it is not declared.
       */
      expect(SURFACES.deliveries.relationFields).toEqual({
        shipment: 'shipment_id',
        truck: 'truck_id',
        driver: 'driver_id',
      })
      expect(SURFACES.customs.relationFields).toEqual({
        shipment: 'shipment_id',
        port: 'port_id',
      })
      expect(SURFACES.containers.relationFields).toEqual({ shipment: 'shipment_id' })
    })
  })
})

describe('sidebar, router and registry agree', () => {
  const masterNav = NAVIGATION.find((section) => section.label === 'Master Data')?.items ?? []

  it('lists every lookup table exactly once, and no transaction', () => {
    expect(masterNav).toHaveLength(10)
    expect(new Set(masterNav.map((item) => item.to)).size).toBe(10)
    expect(masterNav.some((item) => item.to.includes('shipments'))).toBe(false)
  })

  it('links each surface to the route the registry produces', () => {
    for (const item of masterNav) {
      const key = (Object.keys(SURFACES) as SurfaceKey[]).find((k) => item.to === `/master${SURFACES[k].path}`)
      expect(key, `${item.to} is not a registered route`).toBeDefined()
    }
  })

  it('gates each link on the same read permission the route and API use', () => {
    for (const item of masterNav) {
      const key = (Object.keys(SURFACES) as SurfaceKey[]).find((k) => item.to === `/master${SURFACES[k].path}`)!
      expect(item.permission).toBe(SURFACES[key].viewPermission)
    }
  })

  it('names each link after its surface', () => {
    for (const item of masterNav) {
      const key = (Object.keys(SURFACES) as SurfaceKey[]).find((k) => item.to === `/master${SURFACES[k].path}`)!
      expect(item.label).toBe(SURFACES[key].title)
    }
  })

  it('lists shipments under Operations at the registry path, not under Master Data', () => {
    const operations = NAVIGATION.find((section) => section.label === 'Operations')?.items ?? []
    const item = operations.find((entry) => entry.to === `/operations${SURFACES.shipments.path}`)

    expect(item, 'shipments has no Operations link').toBeDefined()
    expect(item!.permission).toBe(SURFACES.shipments.viewPermission)
  })

  it('lists bookings under Operations too, since a booking is not lookup data', () => {    const operations = NAVIGATION.find((section) => section.label === 'Operations')?.items ?? []
    const item = operations.find((entry) => entry.to === `/operations${SURFACES.bookings.path}`)

    expect(item, 'bookings has no Operations link').toBeDefined()
    expect(item!.permission).toBe(SURFACES.bookings.viewPermission)
    // Bookings are requests and shipments are consignments, so the booking link
    // has to come first: the request is what happens before the consignment.
    expect(operations.findIndex((entry) => entry.to.includes('/bookings'))).toBeLessThan(
      operations.findIndex((entry) => entry.to.includes('/shipments')),
    )
  })

  it('lists tracking under Operations, at the registry path and permission', () => {
    /*
     * The link existed from the first pass, long before the screen did, so this is
     * the assertion that it was pointed at the right place rather than a lucky
     * guess: a nav entry with no page behind it is a 404 one click away, and a
     * page whose path is not the linked one is a 404 the other way.
     */
    const operations = NAVIGATION.find((section) => section.label === 'Operations')?.items ?? []
    const item = operations.find((entry) => entry.to === `/operations${SURFACES.tracking.path}`)

    expect(item, 'tracking has no Operations link').toBeDefined()
    expect(item!.permission).toBe(SURFACES.tracking.viewPermission)
    expect(item!.label).toBe(SURFACES.tracking.title)

    // Tracking belongs to a consignment, so it sits after the consignment list it
    // reports on rather than beside the request that produced it.
    expect(operations.findIndex((entry) => entry.to.includes('/tracking'))).toBeGreaterThan(
      operations.findIndex((entry) => entry.to.includes('/shipments')),
    )
  })

  it('keeps tracking out of the Master Data section', () => {
    expect(masterNav.some((item) => item.to.includes('tracking'))).toBe(false)
  })

  it('links the document register at its own path and read permission', () => {
    /*
     * Documents is not a lookup table and not a consignment, so it lives in a
     * section of its own. The assertion that matters is the one every module gets:
     * the link points at the path the registry serves and is gated by the permission
     * the route and the API use, so a link and a 404 cannot drift apart.
     */
    const documentsNav = NAVIGATION.find((section) => section.label === 'Documents')?.items ?? []
    const item = documentsNav.find((entry) => entry.to === SURFACES.documents.path)

    expect(item, 'documents has no Documents link').toBeDefined()
    expect(item!.permission).toBe(SURFACES.documents.viewPermission)
    expect(item!.label).toBe(SURFACES.documents.title)
  })

  it('keeps the document register out of the Master Data section', () => {
    // The sidebar's Master Data section is keyed over the ten lookup tables, and a
    // register of files is not one of them.
    expect(masterNav.some((item) => item.to.includes('documents'))).toBe(false)
  })

  it.each(['containers', 'customs', 'deliveries'] as const)(
    'lists %s under Operations, at the registry path and permission',
    (key) => {
      /*
       * The links existed from the first pass, before the screens did, which is why
       * this is the assertion that matters: a nav entry with no page behind it is a
       * 404 one click away, and a page at a path nobody linked is a 404 the other
       * way. Both were real risks here and neither shows up in a type check.
       */
      const operations = NAVIGATION.find((section) => section.label === 'Operations')?.items ?? []
      const item = operations.find((entry) => entry.to === `/operations${SURFACES[key].path}`)

      expect(item, `${key} has no Operations link`).toBeDefined()
      expect(item!.permission).toBe(SURFACES[key].viewPermission)
      expect(item!.label).toBe(SURFACES[key].title)
    },
  )

  it('orders the three operations modules after the consignment they hang off', () => {
    /*
     * A box, a filing and a collection are all read by first asking whose it is, so
     * they sit after the consignment list in the sidebar rather than beside the
     * request that produced it - the same reasoning that puts tracking after
     * shipments, and the reason these are written out rather than generated.
     */
    const operations = NAVIGATION.find((section) => section.label === 'Operations')?.items ?? []
    const shipmentsAt = operations.findIndex((entry) => entry.to.includes('/shipments'))

    for (const segment of ['/containers', '/customs', '/deliveries']) {
      expect(operations.findIndex((entry) => entry.to.includes(segment))).toBeGreaterThan(
        shipmentsAt,
      )
    }
  })

  it.each(['containers', 'customs', 'deliveries'] as const)(
    'keeps %s out of the Master Data section',
    (key) => {
      // A manifest is not a lookup table, and the sidebar's Master Data section is
      // keyed over the ten that are. Landing here would put a box on the same shelf
      // as the parties that send it.
      expect(masterNav.some((item) => item.to.includes(SURFACES[key].path))).toBe(false)
    },
  )
})
