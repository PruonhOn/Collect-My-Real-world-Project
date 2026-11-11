import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { formatBytes, formatDateTime } from '@/lib/format'
import type { FormValue, FormValues } from '@/features/master-data/form-state'
import { currentFileOf } from '@/features/master-data/form-state'
import { ReferenceSelect } from '@/features/master-data/ReferenceSelect'
import type { FieldDef, SurfaceDef } from '@/features/master-data/registry'
import type { AnyRecord } from '@/features/master-data/types'

/**
 * The create/edit form, rendered from a surface's field list.
 *
 * This file is only the rendering. What to seed the form with, and what to send
 * when it is submitted, live in `form-state.ts`; both are pure functions over a
 * record and a surface, and neither needs React to be tested.
 */

/**
 * Maps a validation error key onto the form.
 *
 * Laravel keys a `site_type` failure as `site_type`, matching the request key, so
 * the lookup is a straight read. The indirection is kept because that is a
 * property of the API rather than of this form, and the day the API prefixes
 * something the change belongs in one place.
 */
function fieldError(errors: Record<string, string>, name: string): string | undefined {
  return errors[name]
}

/**
 * A form value narrowed to what a text-shaped control accepts.
 *
 * `ReferenceSelect` is shared with the operation forms and takes `string | boolean |
 * undefined`, because it was written before the file kind existed. A `File` is never a
 * reference id, and `null` is the reference field's own "not set", so both collapse to
 * the control's absent value instead of being cast across as something they are not.
 * The Select, Input and Textarea below narrow the same way inline; this is pulled out
 * only because a shared prop has to be handed one of the two types it declares.
 */
function textValue(value: FormValue | undefined): string | boolean | undefined {
  return typeof value === 'string' || typeof value === 'boolean' ? value : undefined
}

export function RecordForm<T extends AnyRecord>({
  surface,
  record,
  values,
  errors,
  disabled = false,
  onChange,
}: {
  surface: SurfaceDef<T>
  record: T | null
  values: FormValues
  errors: Record<string, string>
  disabled?: boolean
  onChange: (name: string, value: FormValue) => void
}) {
  return (
    <form onSubmit={(event) => event.preventDefault()} noValidate className="grid gap-4 sm:grid-cols-2">
      {surface.fields.map((field) => (
        <div key={field.name} className={field.wide ? 'sm:col-span-2' : undefined}>
          <Field
            surface={surface}
            field={field}
            record={record}
            value={values[field.name]}
            error={fieldError(errors, field.name)}
            disabled={disabled}
            onChange={(next) => onChange(field.name, next)}
          />
        </div>
      ))}

      {record && (
        <p className="text-ink-500 col-span-full mt-1 text-xs">
          Editing {surface.singular} #{record.id}. Blanking a field clears it.
        </p>
      )}
    </form>
  )
}

function Field<T extends AnyRecord>({
  surface,
  field,
  record,
  value,
  error,
  disabled,
  onChange,
}: {
  surface: SurfaceDef<T>
  field: FieldDef
  record: T | null
  value: FormValue | undefined
  error: string | undefined
  disabled: boolean
  onChange: (value: FormValue) => void
}) {
  if (field.kind === 'file') {
    return (
      <FileField field={field} record={record} value={value} error={error} disabled={disabled} onChange={onChange} />
    )
  }

  if (field.kind === 'checkbox') {
    return (
      <label className="text-ink-700 flex cursor-pointer items-center gap-2 pt-6 text-sm">
        <input
          type="checkbox"
          checked={value === true}
          disabled={disabled}
          onChange={(event) => onChange(event.target.checked)}
          className="border-ink-300 text-brand-600 focus:ring-brand-500/30 rounded"
        />
        {field.label}
      </label>
    )
  }

  if (field.kind === 'select' && field.reference) {
    return (
      <ReferenceSelect
        reference={field.reference}
        label={field.label}
        required={field.required}
        hint={field.hint}
        error={error}
        disabled={disabled}
        placeholder={field.placeholder}
        /*
         * An optional reference gets a real, selectable empty option, using the
         * field's own placeholder as its wording. Without it the only `value=""`
         * entry is the disabled placeholder, so a user cannot clear the field at
         * all - the existing selection is the only thing the control offers. That
         * is a live bug for a truck's carrier or a driver's truck, and it would
         * have been far worse across a shipment's ten nullable partners, where
         * "unassigned" is the normal state rather than an edge case.
         */
        emptyLabel={field.required ? undefined : (field.placeholder ?? 'Not set')}
        value={textValue(value)}
        selectedLabel={record ? surface.relationLabels(record)[field.name] : undefined}
        onChange={onChange}
      />
    )
  }

  if (field.kind === 'select') {
    return (
      <Select
        label={field.label}
        required={field.required}
        placeholder={field.placeholder}
        hint={field.hint}
        error={error}
        disabled={disabled}
        value={typeof value === 'string' ? value : ''}
        onChange={(event) => onChange(event.target.value)}
        options={field.options ?? []}
      />
    )
  }

  if (field.kind === 'textarea') {
    return (
      <Textarea
        label={field.label}
        required={field.required}
        placeholder={field.placeholder}
        hint={field.hint}
        error={error}
        disabled={disabled}
        maxLength={field.maxLength}
        value={typeof value === 'string' ? value : ''}
        onChange={(event) => onChange(event.target.value)}
      />
    )
  }

  const numeric = field.kind === 'number'
  const decimal = field.kind === 'decimal'

  return (
    <Input
      label={field.label}
      required={field.required}
      placeholder={field.placeholder}
      hint={field.hint}
      error={error}
      disabled={disabled}
      type={inputType(field.kind)}
      // `maxLength` is ignored on a number input, and passing it is noise.
      maxLength={numeric || decimal ? undefined : field.maxLength}
      min={numeric ? field.min : undefined}
      max={numeric ? field.max : undefined}
      step={numeric ? field.step : undefined}
      // A decimal is text with a numeric keypad, deliberately. A
      // `type="number"` control normalises what it is given - hand it
      // `54.500` from a `decimal(10,3)` column and it holds `54.5` - so merely
      // opening and saving a form would quietly rewrite the column to fewer
      // decimals than it is defined with. The API validates the value anyway,
      // and it sends a typo back as a 422 on the right field.
      inputMode={decimal ? 'decimal' : undefined}
      value={typeof value === 'string' ? value : ''}
      onChange={(event) => onChange(event.target.value)}
    />
  )
}

/**
 * A file picker, and - when the record already has one - what it currently holds.
 *
 * The two are shown together because they are genuinely different things and
 * conflating them is how an upload form loses data. The input holds a file the user
 * has chosen *this session*; the summary below it describes the copy already on the
 * server. Seeding the input with the existing file's name would show a filename
 * against a control holding zero bytes and post a string where the API expects an
 * upload, so the name is rendered as text beside the picker and never as its value.
 *
 * Which makes the required marker conditional, and it has to be read from the record
 * rather than declared on the field: a file is mandatory to *create* a document and
 * optional to amend one, because correcting an expiry date without rescanning the
 * paperwork is an ordinary thing to do. `field.required` alone would mark it
 * mandatory on both and either nag about a file that is not needed or leave the
 * create form able to submit a document with nothing on it.
 */
function FileField({
  field,
  record,
  value,
  error,
  disabled,
  onChange,
}: {
  field: FieldDef
  record: AnyRecord | null
  value: FormValue | undefined
  error: string | undefined
  disabled: boolean
  onChange: (value: FormValue) => void
}) {
  const current = currentFileOf(record)
  const required = field.required && current === null
  const chosen = value instanceof File ? value : null

  return (
    <div className="space-y-2">
      <label className="text-ink-700 block text-sm font-medium" htmlFor={`field-${field.name}`}>
        {field.label}
        {required && <span className="text-danger-600 ml-1">*</span>}
        {/* A selected file needs the input's own name cleared, because an input
            showing "bill-of-lading.pdf" while that file also sits in state is the
            form disagreeing with itself about what will be sent. */}
        <span className="sr-only">{chosen ? ` selected: ${chosen.name}` : ''}</span>
      </label>

      <input
        id={`field-${field.name}`}
        type="file"
        disabled={disabled}
        required={required}
        accept={field.accept}
        aria-describedby={error ? `${field.name}-error` : field.hint ? `${field.name}-hint` : undefined}
        onChange={(event) => onChange(event.target.files?.[0] ?? null)}
        className="text-ink-700 file:border-ink-300 file:bg-ink-50 file:text-ink-700 hover:file:bg-ink-100 focus:ring-brand-500/30 w-full cursor-pointer rounded-md border text-sm file:mr-3 file:cursor-pointer file:rounded-l-md file:border-0 file:px-4 file:py-2"
      />

      {current && (
        <p className="text-ink-600 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
          <span className="text-ink-300">Currently on file:</span>
          <span className="font-medium text-ink-700">{current.name}</span>
          <span className="tabular">v{current.version}</span>
          {current.size_bytes !== null && <span>{formatBytes(current.size_bytes)}</span>}
          {current.uploaded_at && <span>uploaded {formatDateTime(current.uploaded_at)}</span>}
          {current.uploaded_by && <span>by {current.uploaded_by.name}</span>}
        </p>
      )}

      {chosen && (
        <p className="text-ink-600 flex flex-wrap items-center gap-x-2 text-xs">
          <span className="text-ink-300">Will upload:</span>
          <span className="font-medium text-ink-700">{chosen.name}</span>
          <span>{formatBytes(chosen.size)}</span>
          {current && (
            <span>
              — kept as version {current.version + 1}, and the current copy stays downloadable
            </span>
          )}
        </p>
      )}

      {field.hint && !error && (
        <p id={`${field.name}-hint`} className="text-ink-500 text-xs">
          {field.hint}
        </p>
      )}

      {error && (
        <p id={`${field.name}-error`} className="text-danger-600 text-xs">
          {error}
        </p>
      )}
    </div>
  )
}

function inputType(
  kind: FieldDef['kind'],
): 'text' | 'email' | 'tel' | 'number' | 'date' | 'datetime-local' {
  switch (kind) {
    case 'email':
      return 'email'
    case 'tel':
      return 'tel'
    case 'number':
      return 'number'
    // Its own type rather than `date`, for a booked delivery slot. A date-only
    // control would store 09:00 as midnight, and a slot booked for the afternoon
    // would then read as overdue from the moment it was saved - the same class of
    // mistake as the bare-date window bound on the API's side.
    case 'datetime':
      return 'datetime-local'
    // Not 'number'. See the `inputMode` note above.
    case 'decimal':
    case 'date':
      return kind === 'date' ? 'date' : 'text'
    default:
      return 'text'
  }
}
