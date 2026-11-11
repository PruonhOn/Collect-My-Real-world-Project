import { describe, expect, it } from 'vitest'
import { buildPayload, currentFileOf, flattenRecord, initialValues, isMultipartPayload } from '@/features/master-data/form-state'
import { SURFACES } from '@/features/master-data/registry'
import type { Document, Driver, Party, Shipment } from '@/features/master-data/types'

/**
 * The wire contract, tested without React.
 *
 * Everything asserted here is a promise to the backend rather than a property of
 * the UI, which is why it is worth pinning down directly: a regression shows up as
 * a failed assertion instead of a 422 that only shows up once someone opens the
 * screen.
 */

/**
 * The JSON body, with the multipart case asserted away.
 *
 * `buildPayload` returns a union because a surface with an upload has to send
 * `FormData`. Every surface but documents is JSON, so the narrowing is an assertion
 * about the contract rather than a convenience: if a form that has no file field
 * ever started producing multipart, the API would answer it as an empty body and the
 * mistake would surface here first.
 */
function jsonBody(payload: Record<string, unknown> | FormData): Record<string, unknown> {
  expect(isMultipartPayload(payload)).toBe(false)

  return payload as Record<string, unknown>
}

const party: Party = {
  id: 7,
  type: 'customer',
  code: 'ACME',
  name: 'Acme Industries',
  tax_number: 'TRN-001',
  contact_name: 'Sam Rivers',
  contact_email: 'sam@acme.test',
  contact_phone: '+971 4 555 0100',
  address: {
    line_1: 'Warehouse 4',
    line_2: 'Gate 2',
    city: 'Dubai',
    state: 'Dubai',
    postal_code: '00000',
    country_code: 'AE',
  },
  is_active: true,
  notes: 'Prefers morning deliveries.',
  created_at: '2026-01-04T08:00:00Z',
  updated_at: '2026-01-04T08:00:00Z',
}

const driver: Driver = {
  id: 3,
  code: 'DRV-003',
  name: 'Ravi Menon',
  license_number: 'IN-DL-99221',
  phone: '+91 98 0000 0000',
  email: 'ravi@test.test',
  nationality: 'IN',
  carrier: { id: 1, name: 'Maersk', code: 'MAEU' },
  truck: { id: 9, plate_number: 'TRK-009' },
  license_expiry: '2027-01-31',
  medical_expiry: null,
  is_active: false,
  notes: null,
  created_at: '2026-01-04T08:00:00Z',
  updated_at: '2026-01-04T08:00:00Z',
}

const baseShipment: Shipment = {
  id: 1,
  reference: 'SHP-2026-0001',
  status: 'in_transit',
  status_label: 'In transit',
  is_closed: false,
  shipper: null,
  consignee: null,
  carrier: null,
  vessel: null,
  port_of_loading: null,
  port_of_discharge: null,
  origin_site: null,
  destination_site: null,
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
}

/**
 * A document with an amended file, so the version fields are all exercised.
 *
 * `expires_on` is null on purpose: most of this paperwork never expires, and the
 * form has to be able to leave the date empty rather than inventing one.
 */
const document: Document = {
  id: 12,
  title: 'Maersk Bill of Lading — Shanghai to Rotterdam',
  reference: 'MBL-4471',
  document_type: 'bill_of_lading',
  document_type_label: 'Bill of lading',
  shipment: {
    id: 4,
    reference: 'SHP-2026-0004',
    status: 'in_transit',
    status_label: 'In transit',
    is_closed: false,
  },
  issued_on: '2026-03-02',
  expires_on: null,
  file: {
    name: 'bill-of-lading.pdf',
    mime_type: 'application/pdf',
    size_bytes: 412_000,
    version: 3,
    uploaded_at: '2026-03-03T10:15:00Z',
    uploaded_by: { id: 2, name: 'Amina Yusuf' },
  },
  version_count: 3,
  download_url: '/api/v1/documents/12/download',
  is_expired: false,
  expires_in_days: null,
  needs_attention: false,
  notes: null,
  created_at: '2026-03-03T10:15:00Z',
  updated_at: '2026-03-03T10:15:00Z',
}

/** A file to upload, built at test time because jsdom has no real filesystem. */
function pdf(name = 'scan.pdf'): File {
  return new File(['%PDF-1.7 scan'], name, { type: 'application/pdf' })
}

describe('flattenRecord', () => {
  it('lifts the nested address onto the flat keys the API accepts', () => {
    const flat = flattenRecord(party)

    expect(flat.city).toBe('Dubai')
    expect(flat.country_code).toBe('AE')
    expect(flat.address_line_1).toBe('Warehouse 4')
    expect(flat.address_line_2).toBe('Gate 2')
    // The resource nests these; the request does not. Leaving the nested keys
    // behind would send `line_1` to a controller that never reads it.
    expect(flat.line_1).toBeUndefined()
  })

  it('reduces relations to the ids the write endpoints take', () => {
    const flat = flattenRecord(driver, SURFACES.drivers.relationFields)

    expect(flat.carrier_id).toBe(1)
    expect(flat.truck_id).toBe(9)
    expect(flat.carrier).toBeUndefined()
    expect(flat.truck).toBeUndefined()
  })

  it('omits relation ids entirely when the relation is null', () => {
    // Named first so the object literal is checked against Driver rather than
    // against the shared base shape, which has no `carrier` key to check.
    const unassigned: Driver = { ...driver, carrier: null, truck: null }
    const flat = flattenRecord(unassigned, SURFACES.drivers.relationFields)

    expect('carrier_id' in flat).toBe(false)
    expect('truck_id' in flat).toBe(false)
  })

  /*
   * The mapping used to be hardcoded for carrier and truck, so a surface with a
   * third reference would have had its edit form open with every dropdown blank.
   * These two exist to keep that from coming back silently.
   */
  it('lifts only the relations a surface declares', () => {
    const flat = flattenRecord(driver, { carrier: 'carrier_id' })

    expect(flat.carrier_id).toBe(1)
    expect('truck_id' in flat).toBe(false)
  })

  it('writes each relation under the request key the surface names', () => {
    const shipment: Shipment = {
      ...baseShipment,
      port_of_loading: { id: 7, un_locode: 'SGSIN', name: 'Singapore' },
      vessel: { id: 3, name: 'MV Meridian', imo_number: 'IMO 9123456' },
    }

    const flat = flattenRecord(shipment, SURFACES.shipments.relationFields)

    // The response keys are `port_of_loading` and `vessel`; the request keys are
    // `port_of_loading_id` and `vessel_id`. Neither is derivable from the other -
    // `vessel_id` has no suffix to add - so the surface has to name both.
    expect(flat.port_of_loading_id).toBe(7)
    expect(flat.vessel_id).toBe(3)
    expect(flat.port_of_loading).toBeUndefined()
    expect(flat.vessel).toBeUndefined()
  })

  /*
   * A booking is the one relation that is not lookup data, and it is optional in
   * both directions. If the mapping were missing, the edit form would open with
   * the booking dropdown blank on a consignment that plainly has one - and because
   * the field is nullable, the save that followed would quietly detach it.
   */
  it('reduces a shipment’s booking to the id, and omits it when there is none', () => {
    const linked: Shipment = {
      ...baseShipment,
      booking: { id: 77, reference: 'BKG-2026-0001', status: 'confirmed' },
    }

    const flat = flattenRecord(linked, SURFACES.shipments.relationFields)

    expect(flat.booking_id).toBe(77)
    expect(flat.booking).toBeUndefined()

    const unlinked: Shipment = { ...baseShipment, booking: null }
    const withoutBooking = flattenRecord(unlinked, SURFACES.shipments.relationFields)

    // Omitted rather than sent as null, so an absent link is left alone on a PATCH
    // that was only revising something else.
    expect('booking_id' in withoutBooking).toBe(false)
  })
})

describe('initialValues', () => {
  it('seeds every declared field, so a form never renders an undefined control', () => {
    const values = initialValues(SURFACES.customers, party)

    for (const field of SURFACES.customers.fields) {
      expect(Object.keys(values)).toContain(field.name)
    }

    expect(values.code).toBe('ACME')
    expect(values.city).toBe('Dubai')
  })

  it('renders a null as an empty string rather than the text "null"', () => {
    const values = initialValues(SURFACES.drivers, driver)

    expect(values.notes).toBe('')
    expect(values.medical_expiry).toBe('')
  })

  it('stringifies a value the API returns as a number, e.g. a truck year', () => {
    // `trucks.year` is an uncast `string(4)`, so SQLite can hand back the number
    // 2021 while MySQL hands back '2021'. A text input needs a string either way.
    const values = initialValues(SURFACES.trucks, {
      id: 1,
      code: 'TRK-001',
      plate_number: 'TRK-001',
      truck_type: 'reefer',
      capacity_kg: 7500,
      capacity_pallets: null,
      make: 'Volvo',
      model: 'FH',
      year: 2021,
      carrier: null,
      is_active: true,
      notes: null,
      created_at: '2026-01-04T08:00:00Z',
      updated_at: '2026-01-04T08:00:00Z',
    })

    expect(values.year).toBe('2021')
  })

  it('defaults a new record to active, matching the column default', () => {
    expect(initialValues(SURFACES.customers, null).is_active).toBe(true)
  })

  it('reflects an existing inactive record instead of defaulting it on', () => {
    expect(initialValues(SURFACES.drivers, driver).is_active).toBe(false)
  })
})

describe('buildPayload', () => {
  it('sends only the fields the surface declares', () => {
    const payload = jsonBody(buildPayload(SURFACES.factories, { code: 'FAC-1', name: 'Factory One', is_active: true }))

    // The discriminator is not a field, so it cannot be posted, and the
    // warehouse-only keys the API marks `prohibited` on a factory are absent
    // because this surface never declares them.
    expect(Object.keys(payload)).not.toContain('kind')
    expect(Object.keys(payload)).not.toContain('site_type')
    expect(Object.keys(payload)).not.toContain('capacity_pallets')
    expect(payload.code).toBe('FAC-1')
  })

  it('cannot post a discriminator that the route owns', () => {
    // The API returns `type` on every party so the client can tell them apart,
    // and `buildPayload` is handed a form state that still has no such key.
    // Asserting it cannot appear is what keeps /shippers from being handed a
    // customer.
    const values = { ...initialValues(SURFACES.shippers, { ...party, type: 'shipper' }), type: 'customer' }
    const payload = jsonBody(buildPayload(SURFACES.shippers, values))

    expect(Object.keys(payload)).not.toContain('type')
  })

  it('sends an explicit null when an optional field is cleared', () => {
    const payload = jsonBody(
      buildPayload(SURFACES.drivers, {
        code: 'DRV-003',
        name: 'Ravi Menon',
        phone: '',
        notes: '',
        is_active: true,
      }),
    )

    // Omitting `phone` would mean "leave it alone", so clearing a field has to be
    // stated rather than implied by its absence.
    expect(payload.phone).toBeNull()
    expect(payload.notes).toBeNull()
    expect('phone' in payload).toBe(true)
  })

  it('leaves a required field out when it is empty, so the API can name it', () => {
    const payload = jsonBody(buildPayload(SURFACES.customers, { code: 'ACME', name: '   ', is_active: true }))

    // Guessing at a value here would defeat the point of the server's message.
    expect('name' in payload).toBe(false)
  })

  it('trims whitespace, since a padded code is a different string to the API', () => {
    const payload = jsonBody(buildPayload(SURFACES.customers, { code: '  ACME  ', name: ' Acme ', is_active: true }))

    expect(payload.code).toBe('ACME')
    expect(payload.name).toBe('Acme')
  })

  it('keeps a number as text so a typo comes back as a 422 rather than a silent null', () => {
    const payload = jsonBody(
      buildPayload(SURFACES.vessels, {
        name: 'MSC Tessa',
        imo_number: '9744458',
        length_meters: 'not-a-number',
        is_active: true,
      }),
    )

    // `Number('not-a-number')` is NaN, which serialises to null and would clear
    // the field instead of reporting the mistake.
    expect(payload.length_meters).toBe('not-a-number')
  })

  it('encodes a checkbox as 1 or 0', () => {
    const on = jsonBody(buildPayload(SURFACES.customers, { code: 'A', name: 'A', is_active: true }))
    const off = jsonBody(buildPayload(SURFACES.customers, { code: 'A', name: 'A', is_active: false }))

    expect(on.is_active).toBe(1)
    expect(off.is_active).toBe(0)
  })

  it('round-trips an existing record without losing or inventing fields', () => {
    const surface = SURFACES.drivers
    const payload = jsonBody(buildPayload(surface, initialValues(surface, driver)))

    expect(payload.code).toBe('DRV-003')
    expect(payload.carrier_id).toBe('1')
    expect(payload.truck_id).toBe('9')
    expect(payload.license_expiry).toBe('2027-01-31')
    expect(payload.medical_expiry).toBeNull()
    expect(payload.is_active).toBe(0)
  })
})

describe('document uploads', () => {
  it('seeds the file field empty even when the record already has a file', () => {
    const values = initialValues(SURFACES.documents, document)

    // The current copy is a summary on another table, not something this control can
    // hold. Seeding its name would show a filename against an input holding no bytes
    // and then post a string where the API reads an upload.
    expect(values.file).toBeNull()
    expect(values.title).toBe(document.title)
    expect(values.shipment_id).toBe('4')
  })

  it('leaves the expiry empty when the document does not expire', () => {
    // A bill of lading has no expiry, and the form must be able to say so rather
    // than inventing a date and then flagging it.
    expect(initialValues(SURFACES.documents, document).expires_on).toBe('')
  })

  it('stays a JSON update when no file is chosen', () => {
    const payload = buildPayload(SURFACES.documents, {
      ...initialValues(SURFACES.documents, document),
      title: 'Amended title',
    })

    // Correcting an expiry date without rescanning the paperwork is an ordinary
    // edit, and it must not become multipart - a JSON error envelope is what the
    // form reads its field messages from.
    expect(isMultipartPayload(payload)).toBe(false)
    expect((payload as Record<string, unknown>).title).toBe('Amended title')
    expect('file' in (payload as Record<string, unknown>)).toBe(false)
  })

  it('sends multipart carrying the chosen file when one is picked', () => {
    const file = pdf('bill-of-lading-v4.pdf')
    const payload = buildPayload(SURFACES.documents, {
      ...initialValues(SURFACES.documents, document),
      title: 'Maersk Bill of Lading',
      file,
    })

    expect(isMultipartPayload(payload)).toBe(true)

    const form = payload as FormData

    expect(form.get('title')).toBe('Maersk Bill of Lading')
    expect(form.get('shipment_id')).toBe('4')

    const sent = form.get('file')
    expect(sent).toBeInstanceOf(File)
    // Sent under the name the user chose, so a download is later saved under a name
    // that means something rather than under the generic "blob".
    expect((sent as File).name).toBe('bill-of-lading-v4.pdf')
  })

  it('spells a cleared optional field as an empty string in multipart', () => {
    const payload = buildPayload(SURFACES.documents, {
      ...initialValues(SURFACES.documents, document),
      reference: '',
      file: pdf(),
    })

    const form = payload as FormData

    // Multipart has no null, so the explicit clear the JSON path writes as null
    // arrives as an empty string - which Laravel's ConvertEmptyStringsToNull turns
    // back into null. The column has to be present: omitting it would mean "leave it
    // alone" and would leave a stale reference on the document while the screen
    // showed it cleared.
    expect(form.has('reference')).toBe(true)
    expect(form.get('reference')).toBe('')
  })

  it('omits a required-but-unchosen file so the API can name the field', () => {
    const payload = buildPayload(SURFACES.documents, {
      title: 'A document with no scan',
      document_type: 'other',
      file: null,
    })

    // On create the file is required, and guessing at one is impossible - so the key
    // is left out and the 422 comes back naming `file`, exactly as an empty required
    // text field does.
    expect(isMultipartPayload(payload)).toBe(false)
    expect('file' in (payload as Record<string, unknown>)).toBe(false)
  })
})

describe('currentFileOf', () => {
  it('reads the stored copy off a record', () => {
    expect(currentFileOf(document)?.name).toBe('bill-of-lading.pdf')
    expect(currentFileOf(document)?.version).toBe(3)
    expect(currentFileOf(document)?.size_bytes).toBe(412_000)
  })

  it('reports no copy when there is none to read', () => {
    const withoutFile: Document = { ...document, file: null }

    expect(currentFileOf(null)).toBeNull()
    expect(currentFileOf(withoutFile)).toBeNull()
  })
})
