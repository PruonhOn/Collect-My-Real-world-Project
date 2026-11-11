import type { SurfaceDef } from '@/features/master-data/registry'
import type { AnyRecord } from '@/features/master-data/types'

/**
 * Form state, as data.
 *
 * Deliberately free of React and JSX: these three functions decide exactly what
 * the SPA sends to the API, which is the part most worth testing directly and
 * the part most worth reading without a component tree in the way.
 *
 * Two rules hold throughout:
 *
 * - A field's `name` is the API's *request* key, and that is the only thing that
 *   crosses the wire. Nothing else the record carries is ever sent, which is what
 *   keeps a client from posting `type: customer` to /shippers: the discriminator
 *   is not a field, so it cannot be in the payload.
 * - The form is the source of truth for the fields it owns. Clearing a field
 *   sends an explicit `null` rather than omitting the key, because on the API an
 *   absent key means "leave this alone" and would silently ignore the edit.
 */

/**
 * What one form field can hold.
 *
 * `string` for the text-shaped kinds, `boolean` for a checkbox, and `File` for an
 * upload. `null` is the absent case for a file and the cleared case for a file:
 * `null` means "nothing is chosen right now", which is a different statement from
 * an empty string and has to stay one, because a document whose title is `''` is a
 * form that has not been filled in while a document with no file chosen is a form
 * that has been filled in except for the part the user did not add.
 */
export type FormValue = string | boolean | File | null

/**
 * Text inputs hold strings, checkboxes hold booleans, and the payload type is
 * inferred per field. Keeping the two apart avoids the `string | boolean` union
 * leaking into every handler.
 */
export type FormValues = Record<string, FormValue>

/**
 * Flattens a record into the request shape the form edits.
 *
 * The API presents the address nested and the relations as objects but accepts
 * the write flat, so a form cannot read `record.address.city` directly. Nested
 * values are never copied verbatim, so a relation added later cannot leak into a
 * payload that did not ask for it.
 *
 * `relationFields` is the surface's own `{ carrier: 'carrier_id' }` map. The two
 * cannot be derived from each other - the API returns `port_of_loading` and reads
 * `port_of_loading_id`, and `vessel` has no suffix at all - so the mapping is
 * declared where the fields are and passed in here, rather than hardcoded for the
 * references that happened to exist when this was written.
 */
export function flattenRecord(
  record: AnyRecord,
  relationFields?: Record<string, string>,
): Record<string, unknown> {
  const flat: Record<string, unknown> = {}

  for (const [key, value] of Object.entries(record)) {
    if (value !== null && typeof value === 'object') continue
    flat[key] = value
  }

  const addressed = record as { address?: Record<string, unknown> }
  if (addressed.address) {
    // Read the keys out rather than spreading the object: the resource nests the
    // address, the request is flat, and `line_1` is not a key the API reads. A
    // spread would copy it across and quietly send a field that does not exist.
    flat.city = addressed.address.city
    flat.state = addressed.address.state
    flat.postal_code = addressed.address.postal_code
    flat.country_code = addressed.address.country_code
    flat.address_line_1 = addressed.address.line_1
    flat.address_line_2 = addressed.address.line_2
  }

  // A relation is written as an id, under the name the surface declared for it.
  const relations = record as unknown as Record<string, unknown>

  for (const [relation, field] of Object.entries(relationFields ?? {})) {
    const related = relations[relation] as { id?: number } | null | undefined

    // An absent relation stays absent from the flat record, so the form seeds the
    // field empty and clearing it sends an explicit null. Lifting a missing
    // relation to `null` instead would make `undefined` and `null` the same value,
    // and the edit could never distinguish "not loaded" from "not set".
    if (related?.id !== undefined) flat[field] = related.id
  }

  return flat
}

/**
 * Puts an API timestamp into the shape a date control accepts.
 *
 * The API emits ISO-8601 with an offset, or a bare `Y-m-d` for a date column, and
 * an `<input type="date">` rejects both of the first kind outright: a value that is
 * not a valid date string renders as blank, so a record that plainly has a date on
 * it opens with an empty box and saving it silently clears the field. This is the
 * translation that stops that happening, and it is the reason the two are told apart
 * rather than trimmed.
 *
 * Local getters, not `toISOString()`: a `2026-03-21T23:30Z` timestamp is the 22nd
 * in Dubai and the 21st in London, and a form that shifted the day on the way in
 * would move a booked slot by a day depending on where the desk is.
 */
function toDateControl(value: unknown, kind: 'date' | 'datetime'): string {
  if (value === null || value === undefined) return ''

  const text = String(value)
  if (text === '') return ''

  // A date column arrives as `Y-m-d` already, and is the one case where there is no
  // instant to convert - reinterpreting it as UTC midnight would shift it west of
  // Greenwich. Passed through untouched.
  if (/^\d{4}-\d{2}-\d{2}$/.test(text)) return text

  const parsed = new Date(text)
  if (Number.isNaN(parsed.getTime())) return ''

  const pad = (n: number) => String(n).padStart(2, '0')
  const day = `${parsed.getFullYear()}-${pad(parsed.getMonth() + 1)}-${pad(parsed.getDate())}`

  if (kind === 'date') return day

  return `${day}T${pad(parsed.getHours())}:${pad(parsed.getMinutes())}`
}

/** Seeds the form from a record, or from sensible defaults for a new one. */
export function initialValues<T extends AnyRecord>(
  surface: SurfaceDef<T>,
  record: T | null,
): FormValues {
  const flat = record ? flattenRecord(record, surface.relationFields) : {}
  const values: FormValues = {}

  for (const field of surface.fields) {
    if (field.kind === 'file') {
      /*
       * Always null, even for a record that has a file.
       *
       * A record's file is a summary on another table - a name, a version, a size -
       * and none of that is a file that can be re-sent, so seeding the input with it
       * would show a filename in a control that holds nothing and then post a string
       * where the API expects bytes. The current file is shown beside the input
       * instead, from the record itself; this field means "a file the user has just
       * chosen", and only that.
       */
      values[field.name] = null
      continue
    }

    if (field.kind === 'checkbox') {
      // New records default to active, matching the column default, so the common
      // case needs no interaction with the checkbox at all.
      values[field.name] = record ? flat[field.name] === true || flat[field.name] === 1 : true
      continue
    }

    if (field.kind === 'date' || field.kind === 'datetime') {
      values[field.name] = toDateControl(flat[field.name], field.kind)
      continue
    }

    const raw = flat[field.name]
    values[field.name] = raw === null || raw === undefined ? '' : String(raw)
  }

  return values
}

/**
 * Turns form state into the request body the API expects.
 *
 * Returns a plain object for every surface that has no upload, which is all of
 * them but one. Only a surface with a `file` field *and* a file chosen returns a
 * `FormData`, because `multipart/form-data` is the only encoding that can carry
 * bytes and switching a JSON request to it for the sake of one screen would give up
 * the JSON error envelope - a 422 from a multipart request arrives as HTML, and the
 * form's field-level messages come from that envelope.
 */
export function buildPayload<T extends AnyRecord>(
  surface: SurfaceDef<T>,
  values: FormValues,
): Record<string, unknown> | FormData {
  const payload: Record<string, unknown> = {}

  // A `File` is not a form value to be stringified, and its field name is needed
  // again below, so uploads are collected rather than appended as they come up.
  const uploads = surface.fields
    .filter((field) => field.kind === 'file' && values[field.name] instanceof File)
    .map((field) => ({ name: field.name, file: values[field.name] as File }))

  for (const field of surface.fields) {
    // An upload is attached below rather than encoded here: a `File` stringifies to
    // `[object File]`, which the API would accept as a filename and then fail to
    // read. Omitting it here keeps the loop about the plain fields it has always
    // handled, so a file field is invisible to it.
    if (field.kind === 'file') continue

    const value = values[field.name]

    if (field.kind === 'checkbox') {
      // 1 and 0 rather than true/false: `boolean` accepts both, and 0/1 is what
      // the rest of this API sends.
      payload[field.name] = value === true ? 1 : 0
      continue
    }

    const text = typeof value === 'string' ? value.trim() : ''

    if (text === '') {
      // A required field is left out so the API answers 422 with a message that
      // names the field, rather than the client guessing at a value.
      if (field.required) continue
      payload[field.name] = null
      continue
    }

    // Numbers go as text on purpose. Laravel's `integer` and `numeric` rules
    // accept a numeric string and coerce it, and a non-numeric one comes back as
    // a 422 on the right field. `Number(text)` would turn that typo into NaN,
    // which serialises to null and passes silently as "cleared".
    payload[field.name] = text
  }

  // No file was chosen, so this is an ordinary JSON update. A file field left empty
  // is deliberately not an error here: amending a document's expiry without
  // re-uploading the scan is an ordinary correction, and the API's rule is the one
  // that knows whether a file is required for this record.
  if (uploads.length === 0) return payload

  const form = new FormData()

  for (const [key, value] of Object.entries(payload)) {
    /*
     * `null` becomes an empty string, and it has to be sent rather than skipped.
     * The rule at the top of this file is that a cleared field is an explicit null
     * because an absent key means "leave this alone" - and that distinction cannot
     * survive into multipart, where there is no null and a missing key is the only
     * other option. Laravel's global `ConvertEmptyStringsToNull` turns the empty
     * string back into a null before validation, so `''` here means the same thing
     * the JSON path's `null` means: clear the column. Dropping the key instead
     * would leave a stale reference on the document while the screen showed it
     * cleared, which is the exact failure this line exists to prevent.
     */
    form.append(key, value === null ? '' : String(value))
  }

  for (const upload of uploads) {
    // The filename is passed explicitly so a browser that would otherwise send the
    // generic "blob" as the name - which is what a file read through
    // `DataTransfer` or renamed in memory arrives as - reaches the API with the
    // name the user recognises. It is what a download is later saved under.
    form.append(upload.name, upload.file, upload.file.name)
  }

  return form
}

/** Whether a payload has to be sent as multipart rather than JSON. */
export function isMultipartPayload(payload: Record<string, unknown> | FormData): payload is FormData {
  return typeof FormData !== 'undefined' && payload instanceof FormData
}

/** A stored copy, as the API describes it. */
export interface CurrentFile {
  name: string
  version: number
  size_bytes: number | null
  uploaded_at: string | null
  uploaded_by: { id: number; name: string } | null
}

/**
 * The copy already on the server, or null when there is none.
 *
 * Read by shape rather than typed to `Document`, because everything in this file is
 * generic over `AnyRecord` and a `Document` cast would be a claim it cannot make. A
 * surface with a file field whose records carry no `file` key gets null, which is the
 * right answer - the caller is asking what exists, not asserting a schema.
 *
 * Lives here rather than in the two components that need it, because the form showing
 * the current file and the row action saving it under its name have to agree on which
 * file is current, and one reader is how they do.
 */
export function currentFileOf(record: AnyRecord | null): CurrentFile | null {
  if (!record) return null

  const file = (record as { file?: unknown }).file

  if (typeof file !== 'object' || file === null) return null
  if (typeof (file as { name?: unknown }).name !== 'string') return null

  return file as CurrentFile
}
