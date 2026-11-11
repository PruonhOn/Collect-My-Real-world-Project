import { Pencil, Plus, Trash2 } from 'lucide-react'
import { useCallback, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { Can } from '@/components/guards/Can'
import { PageHeader } from '@/components/layout/PageHeader'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Input } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Pagination } from '@/components/ui/Pagination'
import { SearchInput } from '@/components/ui/SearchInput'
import { Select } from '@/components/ui/Select'
import { EmptyState, ErrorState } from '@/components/ui/States'
import { Skeleton } from '@/components/ui/Skeleton'
import { Table, TBody, TD, TH, THead, TR } from '@/components/ui/Table'
import { useToast } from '@/components/ui/toast-context'
import { useConfirm } from '@/components/ui/useConfirm'
import { ApiRequestError, downloadFile } from '@/lib/api'
import { useAuth } from '@/features/auth/auth-context'
import { useCreateRecord, useDeleteRecord, useMasterList, useRecordAction, useUpdateRecord } from '@/features/master-data/api'
import { buildPayload, currentFileOf, initialValues, type FormValues } from '@/features/master-data/form-state'
import { RecordForm } from '@/features/master-data/RecordForm'
import { ReferenceSelect } from '@/features/master-data/ReferenceSelect'
import type { FilterDef, RowActionDef, SurfaceDef } from '@/features/master-data/registry'
import { writePermissions } from '@/features/master-data/registry'
import type { AnyRecord } from '@/features/master-data/types'
import type { ListQuery } from '@/types/api'

/**
 * One screen for all ten master-data modules.
 *
 * The page owns search, filtering, sorting, paging and the three write flows;
 * everything module-specific is read off the surface definition. The reason for
 * one component rather than ten is the same as the reason the backend has one
 * `MasterDataController`: a fix to paging or error handling has to be made once
 * and hold everywhere, and a bug found in the trucks list should not still be
 * present in the drivers list three months later.
 */
export function MasterDataPage<T extends AnyRecord>({ surface }: { surface: SurfaceDef<T> }) {
  const { can } = useAuth()
  const toast = useToast()

  /*
   * A `?q=` in the URL seeds the search box.
   *
   * This is what makes a dashboard card actionable: "Overdue Invoices" links here
   * carrying the invoice it was showing, and arriving at an unfiltered list would
   * make the link a lie about where it goes. Read once, on mount - it is a
   * deep-link entry point rather than a two-way binding, so typing in the box
   * does not rewrite the URL on every keystroke, and a filter is a control state
   * rather than something worth putting in a shareable link.
   */
  const [searchParams] = useSearchParams()
  const [search, setSearch] = useState(() => searchParams.get('q') ?? '')
  const [filters, setFilters] = useState<Record<string, string>>({})
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)
  const [sort, setSort] = useState(surface.defaultSort)
  const [direction, setDirection] = useState<'asc' | 'desc'>('asc')

  const query = buildListQuery(surface, { page, perPage, search, filters, sort, direction })
  const listing = useMasterList(surface.key, surface.path, query, surface.refetchInterval)

  const permissions = writePermissions(surface)
  const rows = (listing.data?.data ?? []) as T[]

  /* ---------------------------------------------------------------- writes */

  const [editing, setEditing] = useState<T | null>(null)
  const [isCreating, setIsCreating] = useState(false)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({})
  const [formError, setFormError] = useState<string | null>(null)

  const create = useCreateRecord(surface.path)
  const update = useUpdateRecord(surface.path)
  const [deleting, setDeleting] = useState<T | null>(null)
  const remove = useDeleteRecord(surface.path)
  const confirm = useConfirm()

  /*
   * A lifecycle action and the record it was aimed at, held together because the
   * confirmation dialog has to name the record and the request has to carry its
   * id. Two pieces of state would be one bug waiting to happen - a dialog left
   * open across a page change, confirming against whatever row had moved under it.
   */
  const [pendingAction, setPendingAction] = useState<{ record: T; action: RowActionDef } | null>(null)
  const act = useRecordAction(surface.path)

  /*
   * The row a download is in flight for, so only that row's button is disabled.
   *
   * A single boolean would disable every row's download for the duration of one,
   * which on a paged register is a screen full of dead buttons while one file comes
   * down; and no state at all would let somebody click the same document four times
   * and get four copies of it in their downloads folder.
   */
  const [downloadingId, setDownloadingId] = useState<string | number | null>(null)

  const closeForm = useCallback(() => {
    setIsCreating(false)
    setEditing(null)
    setFieldErrors({})
    setFormError(null)
  }, [])

  async function handleSubmit(payload: Record<string, unknown> | FormData) {
    setFieldErrors({})
    setFormError(null)

    try {
      if (editing) {
        await update.mutateAsync({ id: editing.id, payload })
        toast.success(`${capitalise(surface.singular)} updated`, surface.label(editing))
      } else {
        await create.mutateAsync(payload)
        toast.success(`${capitalise(surface.singular)} created`)
      }

      closeForm()
    } catch (caught) {
      if (caught instanceof ApiRequestError) {
        setFieldErrors(caught.fieldErrors)
        // A 422 has already explained itself on the fields. Anything else - a 403,
        // a 409, a 500 - has no field to attach to, so it goes above the form.
        if (Object.keys(caught.fieldErrors).length === 0) {
          setFormError(caught.message)
        }
      } else {
        setFormError('Unable to save. Please try again.')
      }
    }
  }

  async function handleDelete() {
    if (!deleting) return

    try {
      await remove.mutateAsync(deleting.id)
      toast.success(`${capitalise(surface.singular)} deleted`, surface.label(deleting))
      setDeleting(null)
      confirm.close()
    } catch (caught) {
      toast.error(
        `Could not delete ${surface.singular}`,
        caught instanceof ApiRequestError ? caught.message : 'Please try again.',
      )
    }
  }

  /**
   * Runs a row action, with or without the confirmation.
   *
   * The action's own permission is checked here rather than left to the button,
   * because the row it fires on can change between rendering and clicking - a
   * permission that has been revoked server-side has to fail the same way it
   * would anywhere else, and the 403 is a clearer answer than a silently absent
   * button.
   */
  async function runRowAction(record: T, action: RowActionDef) {
    if (!can(action.permission)) return

    /*
     * A download is not a mutation.
     *
     * Two things separate it from a lifecycle action and both are forced, not
     * stylistic. It is a GET of bytes rather than a POST to a nested endpoint, so it
     * goes through `downloadFile` and not the record-action mutation - a POST would
     * both change the wrong verb and invalidate the list cache, which would refetch
     * every row because somebody saved a file. And it never confirms: the action the
     * user is taking is reading a document they can already see listed, and asking
     * "Download?" is a dialog that exists to warn about consequences this one does
     * not have.
     */
    if (action.href !== undefined) {
      setDownloadingId(record.id)

      try {
        await downloadFile(action.href, currentFileOf(record)?.name ?? `${surface.singular}-${record.id}`)
      } catch (caught) {
        /*
         * The API's own wording, because on this endpoint it is the useful part. A
         * file that has gone missing answers 409 explaining that it needs uploading
         * again, and replacing that with "Please try again" would leave the user
         * retrying a button that can never work.
         */
        toast.error(
          `Could not download ${surface.singular}`,
          caught instanceof ApiRequestError ? caught.message : 'Please try again.',
        )
      } finally {
        setDownloadingId(null)
      }

      return
    }

    /*
     * A lifecycle action is a POST to a nested endpoint, and an action declaring
     * neither that nor an `href` has nothing to run. The union makes that a case the
     * compiler demands rather than one that silently posts to `/undefined`.
     */
    if (action.endpoint === undefined) return

    try {
      await act.mutateAsync({ id: record.id, endpoint: action.endpoint })
      toast.success(
        `${capitalise(surface.singular)} ${action.successLabel}`,
        surface.label(record),
      )
    } catch (caught) {
      toast.error(
        `Could not ${action.key} ${surface.singular}`,
        caught instanceof ApiRequestError ? caught.message : 'Please try again.',
      )
    }
  }

  async function handleRowAction() {
    if (!pendingAction) return

    const { record, action } = pendingAction

    // Closed first, so a failure does not leave the dialog up over a form whose
    // record has already changed. The toast is the answer either way.
    setPendingAction(null)
    await runRowAction(record, action)
  }

  /* ----------------------------------------------------------------- query */

  // A new search or filter invalidates the current page number: staying on page
  // 4 of a result set that now has one page shows an empty table and reads as a
  // bug rather than as an over-narrow filter.
  function updateSearch(value: string) {
    setSearch(value)
    setPage(1)
  }

  function updateFilter(name: string, value: string) {
    setFilters((current) => ({ ...current, [name]: value }))
    setPage(1)
  }

  function handleSort(key: string) {
    if (key === sort) {
      setDirection((current) => (current === 'asc' ? 'desc' : 'asc'))
      return
    }

    setSort(key)
    setDirection('asc')
    setPage(1)
  }

  function resetFilters() {
    setFilters({})
    setSearch('')
    setPage(1)
  }

  const hasFilters = search !== '' || Object.values(filters).some((value) => value !== '')
  const isSaving = create.isPending || update.isPending

  /* ------------------------------------------------------------------ view */

  return (
    <div className="flex flex-col">
      <PageHeader
        title={surface.title}
        description={surface.description}
        actions={
          <Can permission={permissions.create}>
            <Button
              icon={<Plus className="size-4" aria-hidden />}
              onClick={() => {
                setEditing(null)
                setIsCreating(true)
                setFieldErrors({})
                setFormError(null)
              }}
            >
              New {surface.singular}
            </Button>
          </Can>
        }
      >
        <div className="flex flex-wrap items-end gap-3">
          <div className="min-w-56 flex-1 sm:max-w-sm">
            <SearchInput
              value={search}
              onChange={updateSearch}
              placeholder={surface.searchPlaceholder}
            />
          </div>

          {surface.filters.map((filter) => (
            <FilterControl
              key={filter.name}
              filter={filter}
              value={filters[filter.name] ?? ''}
              onChange={(value) => updateFilter(filter.name, value)}
            />
          ))}

          {hasFilters && (
            <Button variant="ghost" size="sm" onClick={resetFilters}>
              Clear
            </Button>
          )}
        </div>
      </PageHeader>

      <div className="p-4 sm:p-6">
        <Card>
          {listing.isError ? (
            <ErrorState
              title={`Could not load ${surface.title.toLowerCase()}`}
              message={listing.error instanceof Error ? listing.error.message : undefined}
              onRetry={() => void listing.refetch()}
              retrying={listing.isRefetching}
            />
          ) : listing.isPending ? (
            <TableSkeleton />
          ) : rows.length === 0 ? (
            <EmptyState
              title={hasFilters ? 'Nothing matches those filters' : `No ${surface.title.toLowerCase()} yet`}
              description={
                hasFilters
                  ? 'Try a broader search, or clear the filters to see everything.'
                  : `Add the first ${surface.singular} to get started.`
              }
              action={
                hasFilters ? (
                  <Button variant="outline" size="sm" onClick={resetFilters}>
                    Clear filters
                  </Button>
                ) : (
                  can(permissions.create) && (
                    <Button
                      size="sm"
                      onClick={() => {
                        setEditing(null)
                        setIsCreating(true)
                      }}
                    >
                      New {surface.singular}
                    </Button>
                  )
                )
              }
            />
          ) : (
            <>
              <Table>
                <THead>
                  <TR>
                    {surface.columns.map((column) => (
                      <TH
                        key={column.key}
                        align={column.align}
                        sortKey={column.sortKey}
                        sortBy={sort}
                        sortDir={direction}
                        onSort={handleSort}
                      >
                        {column.header}
                      </TH>
                    ))}
                    <TH align="right" className="w-24">
                      Actions
                    </TH>
                  </TR>
                </THead>
                <TBody>
                  {rows.map((record) => (
                    <TR key={record.id}>
                      {surface.columns.map((column) => (
                        <TD key={column.key} align={column.align}>
                          {column.render(record)}
                        </TD>
                      ))}
                      <TD align="right">
                        <RowActions
                          record={record}
                          /*
                           * The server's own verdict, not a guess from the status:
                           * an issued invoice is refused by both PATCH and DELETE,
                           * so a pencil on a bill the customer already holds is a
                           * form somebody fills in only to be told no.
                           */
                          canModify={surface.canModify ? surface.canModify(record) : true}
                          canUpdate={can(permissions.update)}
                          canDelete={can(permissions.delete)}
extra={(surface.rowActions?.(record) ?? []).filter((action) =>
          can(action.permission),
        )}
          busyId={downloadingId}
                          onEdit={() => {
                            setFieldErrors({})
                            setFormError(null)
                            setIsCreating(false)
                            setEditing(record)
                          }}
                          onDelete={() => {
                            confirm.open()
                            setDeleting(record)
                          }}
                          onRun={(action) => {
          /*
           * No confirmation means the action fires on the click, which is what
           * `RowActionDef.confirm`'s absence has always promised. It needs saying
           * because the confirm dialog is the only way a pending action is run, so
           * an action without a `confirm` would otherwise do nothing at all - which
           * every existing row action hid, because all of them confirm. A download is
           * the first to rely on it, and it is exactly the case that should not
           * ask.
           */
            if (action.confirm === undefined) {
              void runRowAction(record, action)
              return
            }

            setPendingAction({ record, action })
          }}
                        />
                      </TD>
                    </TR>
                  ))}
                </TBody>
              </Table>

              {listing.data?.meta && (
                <Pagination
                  meta={listing.data.meta}
                  perPage={perPage}
                  onPageChange={setPage}
                  onPerPageChange={(value) => {
                    setPerPage(value)
                    setPage(1)
                  }}
                />
              )}
            </>
          )}
        </Card>
      </div>

      <RecordDialog
        /*
         * Load-bearing. The form seeds its state from `record` exactly once, so
         * changing what is being edited has to remount it: this key is what
         * stops the create form reopening with the last record's values, and
         * what seeds the edit form from a different record.
         */
        key={editing ? `edit-${editing.id}` : isCreating ? 'create' : 'closed'}
        surface={surface}
        open={isCreating || editing !== null}
        record={editing}
        saving={isSaving}
        fieldErrors={fieldErrors}
        formError={formError}
        onClose={closeForm}
        onSubmit={handleSubmit}
      />

      <ConfirmDialog
        open={confirm.isOpen && deleting !== null}
        title={`Delete this ${surface.singular}?`}
        confirmLabel="Delete"
        loading={remove.isPending}
        onConfirm={() => void handleDelete()}
        onCancel={() => {
          setDeleting(null)
          confirm.close()
        }}
        message={
          deleting ? (
            <>
              <strong className="font-medium text-ink-900">{surface.label(deleting)}</strong> will be
              removed from {surface.title.toLowerCase()}. It will stop appearing in pickers and on
              existing records, but history that already references it is kept.
            </>
          ) : null
        }
      />
      <ConfirmDialog
        /*
         * A lifecycle action rather than a delete, so it names the record and
         * repeats the consequence the surface declared. The surface owns the
         * wording: "the due-date clock starts today" is a fact about invoices that
         * this file has no way of knowing, and a generic "are you sure?" would be
         * the one dialog in the app that says less than the button did.
         */
        open={pendingAction !== null && pendingAction.action.confirm !== undefined}
        title={pendingAction ? `${pendingAction.action.label}?` : ''}
        confirmLabel={pendingAction?.action.confirmLabel ?? pendingAction?.action.label ?? 'Confirm'}
        loading={act.isPending}
        onConfirm={() => void handleRowAction()}
        onCancel={() => setPendingAction(null)}
        message={
          pendingAction ? (
            <>
              {pendingAction.action.confirm}
              <br />
              <strong className="font-medium text-ink-900">{surface.label(pendingAction.record)}</strong>
            </>
          ) : null
        }
      />
    </div>
  )
}

/* -------------------------------------------------------------------------- */
/* Query construction                                                           */
/* -------------------------------------------------------------------------- */

interface ListControls {
  page: number
  perPage: number
  search: string
  filters: Record<string, string>
  sort: string
  direction: 'asc' | 'desc'
}

/**
 * Builds the request query with a fixed key order.
 *
 * React Query keys a query by hashing its value, and object key order is part of
 * that hash, so `{ page, q }` and `{ q, page }` are different cache entries. A
 * stable order is what stops the same request being cached twice under two keys.
 *
 * Empty filters are dropped rather than sent as `''`. The API reads
 * `is_active` with `has()`, which is true even for an empty string, so sending
 * `is_active=` would silently narrow the list to inactive records only.
 */
function buildListQuery(surface: SurfaceDef<never>, controls: ListControls): ListQuery {
  const query: ListQuery = {
    page: controls.page,
    per_page: controls.perPage,
  }

  if (controls.search.trim() !== '') {
    query.q = controls.search.trim()
  }

  query.sort = controls.sort
  query.direction = controls.direction

  for (const filter of surface.filters) {
    const value = controls.filters[filter.name]?.trim() ?? ''
    if (value !== '') {
      query[filter.name] = filter.kind === 'select' && filter.name === 'is_active' ? Number(value) : value
    }
  }

  return query
}

/* -------------------------------------------------------------------------- */
/* Pieces                                                                      */
/* -------------------------------------------------------------------------- */

function FilterControl({
  filter,
  value,
  onChange,
}: {
  filter: FilterDef
  value: string
  onChange: (value: string) => void
}) {
  const [draft, setDraft] = useState(value)
  const [lastValue, setLastValue] = useState(value)

  // A reset from the parent has to be reflected in the box, not just in the
  // query, or the user clears the filters and still sees the old value typed in.
  if (value !== lastValue) {
    setLastValue(value)
    setDraft(value)
  }

  if (filter.kind === 'select' && filter.reference) {
    // A filter on another module, e.g. trucks by carrier. The option list is
    // fetched like any other reference, plus a leading option meaning "no
    // filter" - the filter is cleared by choosing it, not by an empty page.
    return (
      <div className="w-48">
        <ReferenceSelect
          reference={filter.reference}
          ariaLabel={filter.label}
          value={value}
          onChange={onChange}
          emptyLabel={`All ${filter.label.toLowerCase()}`}
        />
      </div>
    )
  }

  if (filter.kind === 'select') {
    return (
      <div className="w-44">
        <Select
          aria-label={filter.label}
          value={value}
          onChange={(event) => onChange(event.target.value)}
          options={filter.options ?? []}
        />
      </div>
    )
  }

  return (
    <div className="w-28">
      <Input
        aria-label={filter.label}
        value={draft}
        maxLength={filter.name === 'country_code' ? 2 : undefined}
        placeholder={filter.placeholder}
        onChange={(event) => setDraft(event.target.value)}
        onBlur={() => onChange(draft)}
        onKeyDown={(event) => {
          if (event.key === 'Enter') onChange(draft)
        }}
      />
    </div>
  )
}

function RowActions({
  record,
  canModify,
  canUpdate,
  canDelete,
  extra,
  busyId,
  onEdit,
  onDelete,
  onRun,
}: {
  record: AnyRecord
  canModify: boolean
  canUpdate: boolean
  canDelete: boolean
  extra: RowActionDef[]
  /** Id of the record whose download is in flight, so only that row waits. */
  busyId?: string | number | null
  onEdit: () => void
  onDelete: () => void
  onRun: (action: RowActionDef) => void
}) {
  /*
   * A read-only user still gets an actions column so the table does not reflow
   * when the same screen is opened by someone who can edit.
   *
   * `extra` counts towards the decision, because a row that can only be acted on
   * and not edited is a real case - a collections clerk who may void a bill but not
   * change it - and telling them "read only" over a button that would have worked
   * would be wrong in the other direction.
   */
  const editable = canModify && canUpdate
  const deletable = canModify && canDelete

  if (!editable && !deletable && extra.length === 0) {
    return <span className="text-ink-300 text-xs">Read only</span>
  }

  return (
    <div className="flex items-center justify-end gap-1">
      {extra.map((action) => (
        <Button
          key={action.key}
          variant="ghost"
          size="icon"
          /*
           * Only the row whose own file is coming down waits. Keyed on the record
           * rather than a page-wide flag, because a register is read by scanning for
           * the one document you want and a screen of spinners is a screen nobody
           * can scan.
           */
          loading={action.href !== undefined && busyId === record.id}
          onClick={() => onRun(action)}
          className={action.destructive ? 'hover:text-danger-600' : undefined}
          aria-label={`${action.label} ${record.id}`}
          title={action.label}
        >
          {action.icon}
        </Button>
      ))}

      {editable && (
        <Button
          variant="ghost"
          size="icon"
          onClick={onEdit}
          aria-label={`Edit ${record.id}`}
          title="Edit"
        >
          <Pencil className="size-4" aria-hidden />
        </Button>
      )}

      {deletable && (
        <Button
          variant="ghost"
          size="icon"
          className="hover:text-danger-600"
          onClick={onDelete}
          aria-label={`Delete ${record.id}`}
          title="Delete"
        >
          <Trash2 className="size-4" aria-hidden />
        </Button>
      )}
    </div>
  )
}

function RecordDialog<T extends AnyRecord>({
  surface,
  open,
  record,
  saving,
  fieldErrors,
  formError,
  onClose,
  onSubmit,
}: {
  surface: SurfaceDef<T>
  open: boolean
  record: T | null
  saving: boolean
  fieldErrors: Record<string, string>
  formError: string | null
  onClose: () => void
  onSubmit: (payload: Record<string, unknown> | FormData) => Promise<void>
}) {
  /**
   * Seeded once, from the record the dialog was opened for.
   *
   * Nothing resets this afterwards: the caller remounts the dialog by keying it
   * on the record, so a second "New driver" always starts blank. Resetting in an
   * effect instead would also clear a half-typed form a moment after every
   * keystroke-triggered re-render of the parent, and would flash the old values
   * before the reset landed.
   */
  const [values, setValues] = useState<FormValues>(() => initialValues(surface, record))

  return (
    <Modal
      open={open}
      onClose={onClose}
      dismissible={!saving}
      size="xl"
      title={record ? `Edit ${surface.singular}` : `New ${surface.singular}`}
      description={
        record
          ? `Changes apply to ${surface.title.toLowerCase()} immediately.`
          : `Add a ${surface.singular} to ${surface.title.toLowerCase()}.`
      }
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={saving}>
            Cancel
          </Button>
          <Button
            loading={saving}
            onClick={() => void onSubmit(buildPayload(surface, values))}
          >
            {record ? 'Save changes' : `Create ${surface.singular}`}
          </Button>
        </>
      }
    >
      {formError && (
        <div
          role="alert"
          className="border-danger-200 bg-danger-50 text-danger-700 mb-4 rounded-lg border p-3 text-sm"
        >
          {formError}
        </div>
      )}

      <RecordForm
        surface={surface}
        record={record}
        values={values}
        errors={fieldErrors}
        disabled={saving}
        onChange={(name, value) => setValues({ ...values, [name]: value })}
      />
    </Modal>
  )
}

function TableSkeleton() {
  return (
    <div className="space-y-2 p-4">
      {Array.from({ length: 6 }, (_, index) => (
        <Skeleton key={index} className="h-9 w-full" />
      ))}
    </div>
  )
}

function capitalise(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1)
}
