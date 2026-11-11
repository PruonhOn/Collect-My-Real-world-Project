import { useMemo } from 'react'
import { Select, type SelectOption } from '@/components/ui/Select'
import { useReferenceList } from '@/features/master-data/api'
import type {
  ReferenceDef,
  SingleReferenceDef,
  SiteReferenceDef,
} from '@/features/master-data/registry'
import { SURFACES } from '@/features/master-data/registry'
import type {
  AnyRecord,
  Booking,
  Carrier,
  Driver,
  Party,
  Port,
  Shipment,
  Site,
  Truck,
  Vessel,
} from '@/features/master-data/types'
import type { ListQuery } from '@/types/api'

/**
 * A select whose options are another module's records.
 *
 * Shared by the create/edit form and the toolbar filters, because both need the
 * same three things: fetch the referenced list, keep the value that is already
 * selected visible even when the query does not return it, and load lazily so
 * opening a trucks form does not also fetch every carrier.
 */
export function ReferenceSelect({
  reference,
  value,
  onChange,
  label,
  ariaLabel,
  placeholder = 'Select…',
  hint,
  error,
  required,
  disabled,
  selectedLabel,
  emptyLabel,
}: ReferenceSelectProps & { reference: ReferenceDef }) {
  /*
   * Two components rather than one hook that branches.
   *
   * A reference spanning two surfaces needs one fetch per surface, and a hook
   * cannot be called a variable number of times - so it would have to fire both
   * requests for every field in the form and throw away the one it did not need.
   * Dispatching to a component instead keeps each one calling a fixed set of
   * hooks, which is also legal because a component's hooks are not conditional on
   * the props of the element that rendered it.
   */
  if ('surfaces' in reference) {
    return (
      <SiteReferenceSelect
        reference={reference}
        value={value}
        onChange={onChange}
        label={label}
        ariaLabel={ariaLabel}
        placeholder={placeholder}
        hint={hint}
        error={error}
        required={required}
        disabled={disabled}
        selectedLabel={selectedLabel}
        emptyLabel={emptyLabel}
      />
    )
  }

  return (
    <SingleReferenceSelect
      reference={reference}
      value={value}
      onChange={onChange}
      label={label}
      ariaLabel={ariaLabel}
      placeholder={placeholder}
      hint={hint}
      error={error}
      required={required}
      disabled={disabled}
      selectedLabel={selectedLabel}
      emptyLabel={emptyLabel}
    />
  )
}

interface ReferenceSelectProps {
  value: string | boolean | undefined
  onChange: (value: string) => void
  /** A visible label, for use inside a form. */
  label?: string
  /** An accessible name for a filter, which has no visible label of its own. */
  ariaLabel?: string
  placeholder?: string
  hint?: string
  error?: string
  required?: boolean
  disabled?: boolean
  /** Label of the value already held, for when it is missing from the list. */
  selectedLabel?: string
  /** Adds a leading "no filter" option. Only a filter wants one. */
  emptyLabel?: string
}

function SingleReferenceSelect({
  reference,
  ...props
}: ReferenceSelectProps & { reference: SingleReferenceDef }) {
  const options = useSingleOptions(reference)

  return <SelectShell options={options} {...props} />
}

/**
 * Options from both site surfaces, merged.
 *
 * The two lists arrive independently, so the merged list waits for both. Showing
 * warehouses as soon as they land would make a factory-only origin look like it
 * had no options, and the user would stop there believing the field is
 * warehouse-only.
 */
function SiteReferenceSelect({
  reference,
  ...props
}: ReferenceSelectProps & { reference: SiteReferenceDef }) {
  const query = reference.query ?? DEFAULT_REFERENCE_QUERY
  const warehouses = useReferenceList('warehouses', SURFACES.warehouses.path, query)
  const factories = useReferenceList('factories', SURFACES.factories.path, query)

  const options: SelectOption[] | undefined =
    warehouses.data === undefined || factories.data === undefined
      ? undefined
      : [
          ...warehouses.data.data.map((record) => ({
            value: record.id,
            label: reference.label(record as Site),
          })),
          ...factories.data.data.map((record) => ({
            value: record.id,
            label: reference.label(record as Site),
          })),
        ]

  return <SelectShell options={options} {...props} />
}

function SelectShell({
  options,
  value,
  onChange,
  label,
  ariaLabel,
  placeholder = 'Select…',
  hint,
  error,
  required,
  disabled,
  selectedLabel,
  emptyLabel,
}: ReferenceSelectProps & { options: SelectOption[] | undefined }) {
  const selected = typeof value === 'string' ? value : ''
  const loading = options === undefined

  const resolved = useMemo(() => {
    const loaded = options ?? []

    /**
     * The selected id is kept in the option list even when the reference query
     * did not return it. That happens legitimately: the query asks for active
     * records only, so a driver whose truck has just been deactivated would
     * otherwise render as "Not yet assigned" and read as if nothing were assigned.
     * Showing the id alone would be worse, so the relation's own label is used.
     */
    const withCurrent =
      selected !== '' && !loaded.some((option) => String(option.value) === selected)
        ? [{ value: Number(selected), label: selectedLabel ?? `#${selected}` }, ...loaded]
        : loaded

    if (emptyLabel === undefined) return withCurrent

    return [{ value: '', label: emptyLabel }, ...withCurrent]
  }, [options, selected, selectedLabel, emptyLabel])

  return (
    <Select
      label={label}
      aria-label={ariaLabel}
      required={required}
      hint={hint}
      error={error}
      disabled={disabled || loading}
      value={selected}
      onChange={(event) => onChange(event.target.value)}
      /*
       * `Select` draws its placeholder as a *disabled* `value=""` option. Once
       * there is a real, selectable empty option the two would both be `value=""`,
       * and a select with two options of the same value resolves to the first -
       * the disabled one - so clearing the field would not take. The empty
       * option replaces the placeholder instead of joining it.
       *
       * "Loading…" still goes through, so a control whose options have not
       * arrived says so rather than looking like a list with one blank entry.
       */
      placeholder={loading ? 'Loading…' : emptyLabel === undefined ? placeholder : undefined}
      options={resolved}
    />
  )
}

const DEFAULT_REFERENCE_QUERY: ListQuery = { per_page: 100, is_active: 1 }

/** Options for one reference, or `undefined` while loading or after a failure. */
function useSingleOptions(reference: SingleReferenceDef): SelectOption[] | undefined {
  const target = SURFACES[reference.surface]
  const query = useReferenceList(
    reference.surface,
    target.path,
    reference.query ?? DEFAULT_REFERENCE_QUERY,
  )

  if (query.data === undefined) return undefined

  return query.data.data.map((record) => ({
    value: record.id,
    label: referenceLabel(reference, record),
  }))
}

/**
 * Narrows the reference union so `label` is called with the right record type.
 *
 * `ReferenceDef` is discriminated on `surface` precisely so this switch can
 * exist; the casts are the price of the union, and they are confined here rather
 * than spread across every call site. The grouped cases are the surfaces that
 * share a record type - the three party screens and the two site screens - which
 * is why one cast serves several keys.
 */
function referenceLabel(reference: SingleReferenceDef, record: AnyRecord): string {
  switch (reference.surface) {
    case 'customers':
    case 'shippers':
    case 'consignees':
      return reference.label(record as Party)
    case 'carriers':
      return reference.label(record as Carrier)
    case 'vessels':
      return reference.label(record as Vessel)
    case 'ports':
      return reference.label(record as Port)
    case 'warehouses':
    case 'factories':
      return reference.label(record as Site)
    case 'trucks':
      return reference.label(record as Truck)
    case 'drivers':
      return reference.label(record as Driver)
    case 'bookings':
      return reference.label(record as Booking)
    // The other direction of the same relationship: the three operations modules
    // point back at the consignment they belong to. Listed before the exhaustiveness
    // check below so adding a fourth module that points at a consignment is a build
    // error rather than a blank dropdown.
    case 'shipments':
      return reference.label(record as Shipment)
    default:
      /*
       * Unreachable while `ReferenceSurfaceKey` and this switch agree, which the
       * compiler cannot see across the two. Falling back to the raw reference rather
       * than throwing keeps a newly registered surface from taking a whole form
       * down: the dropdown still works, it just reads `SHP-2026-0001` instead of
       * `SHP-2026-0001 — In transit`.
       */
      return (record as { reference?: string }).reference ?? `#${record.id}`
  }
}
