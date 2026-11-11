import { useCallback, useMemo, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { Download, FileText, Plus, Search, Trash2 } from 'lucide-react'
import { businessService } from '../../services/businessService'
import { useBusiness } from '../../context/BusinessContext'
import { useApi } from '../../hooks/useApi'
import { useDebouncedValue } from '../../hooks/useDebouncedValue'
import { apiErrorMessage, downloadFile } from '../../api/client'
import { formatMoney, formatDate, toMinorUnits } from '../../utils/format'
import { INVOICE_STATUSES } from '../../utils/constants'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock, Pagination } from '../../components/ui/Feedback'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { FormRow, Input, Select } from '../../components/ui/Form'
import { ConfirmDialog, Modal } from '../../components/ui/Modal'
import { EmptyState, Table, Td, Th, Tr } from '../../components/ui/Table'

const schema = z.object({
  customer_id: z.string().optional(),
  discount: z.coerce.number().min(0),
  tax: z.coerce.number().min(0),
  due_date: z.string().optional(),
  status: z.enum(['draft', 'unpaid', 'paid', 'overdue', 'canceled']),
})

export default function Invoices() {
  const { activeBusiness } = useBusiness()
  const currency = activeBusiness?.currency ?? 'USD'

  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [creating, setCreating] = useState(false)
  const [saving, setSaving] = useState(false)
  const [formError, setFormError] = useState(null)
  const [deleting, setDeleting] = useState(null)
  const [items, setItems] = useState([{ description: '', quantity: 1, unit_amount: '0' }])
  const debouncedSearch = useDebouncedValue(search)

  const loader = useCallback(
    () => businessService.invoices({ search: debouncedSearch, status, page, per_page: 15 }),
    [debouncedSearch, status, page],
  )
  const { data, loading, error, reload } = useApi(loader, [loader])

  const customersLoader = useCallback(() => businessService.customers({ per_page: 100 }), [])
  const { data: customerData } = useApi(customersLoader, [customersLoader])
  const customers = customerData?.data ?? []

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { customer_id: '', discount: '0', tax: '0', due_date: '', status: 'unpaid' },
  })

  const subtotal = useMemo(
    () =>
      items.reduce(
        (sum, item) => sum + toMinorUnits(item.unit_amount) * Number(item.quantity || 0),
        0,
      ),
    [items],
  )

  const openCreate = () => {
    setItems([{ description: '', quantity: 1, unit_amount: '0' }])
    setFormError(null)
    reset({ customer_id: '', discount: '0', tax: '0', due_date: '', status: 'unpaid' })
    setCreating(true)
  }

  const updateItem = (index, patch) => {
    setItems((current) => current.map((item, position) => (position === index ? { ...item, ...patch } : item)))
  }

  const onSubmit = async (values) => {
    setSaving(true)
    setFormError(null)

    const payload = {
      customer_id: values.customer_id ? Number(values.customer_id) : null,
      items: items
        .filter((item) => item.description)
        .map((item) => ({
          description: item.description,
          quantity: Number(item.quantity),
          unit_amount: toMinorUnits(item.unit_amount),
        })),
      discount: toMinorUnits(values.discount),
      tax: toMinorUnits(values.tax),
      due_date: values.due_date || null,
      status: values.status,
    }

    if (payload.items.length === 0) {
      setFormError('Add at least one invoice line.')
      setSaving(false)

      return
    }

    try {
      await businessService.createInvoice(payload)
      toast.success('Invoice created.')
      setCreating(false)
      reload()
    } catch (exception) {
      setFormError(apiErrorMessage(exception, 'Unable to create the invoice.'))
    } finally {
      setSaving(false)
    }
  }

  const updateStatus = async (invoice, nextStatus) => {
    try {
      await businessService.updateInvoice(invoice.id, { status: nextStatus })
      toast.success('Invoice updated.')
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to update the invoice.'))
    }
  }

  const downloadPdf = async (invoice) => {
    try {
      await downloadFile(`/business/invoices/${invoice.id}/pdf`, `${invoice.invoice_number}.pdf`)
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to download the PDF.'))
    }
  }

  const confirmDelete = async () => {
    if (!deleting) {
      return
    }

    try {
      await businessService.deleteInvoice(deleting.id)
      toast.success('Invoice deleted.')
      setDeleting(null)
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to delete the invoice.'))
    }
  }

  const invoices = data?.data ?? []

  return (
    <div>
      <PageHeader
        title="Invoices"
        description="Customer invoices with PDF documents and payment tracking."
        actions={
          <Button onClick={openCreate}>
            <Plus className="size-4" />
            New invoice
          </Button>
        }
      />

      <Card>
        <div className="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-4">
          <div className="relative min-w-56 flex-1">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <Input
              className="pl-9"
              placeholder="Search by invoice number"
              value={search}
              onChange={(event) => {
                setSearch(event.target.value)
                setPage(1)
              }}
            />
          </div>
          <Select
            className="w-44"
            value={status}
            onChange={(event) => {
              setStatus(event.target.value)
              setPage(1)
            }}
          >
            <option value="">All statuses</option>
            {INVOICE_STATUSES.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </Select>
        </div>

        {loading && <LoadingBlock />}

        {error && !loading && (
          <div className="p-5">
            <Alert tone="danger">{apiErrorMessage(error)}</Alert>
          </div>
        )}

        {!loading && !error && invoices.length === 0 && (
          <EmptyState
            icon={FileText}
            title="No invoices yet"
            description="Create an invoice to bill a customer."
            action={<Button onClick={openCreate}>New invoice</Button>}
          />
        )}

        {!loading && !error && invoices.length > 0 && (
          <>
            <Table>
              <thead className="bg-slate-50">
                <tr>
                  <Th>Invoice</Th>
                  <Th>Customer</Th>
                  <Th>Status</Th>
                  <Th>Issued</Th>
                  <Th>Due</Th>
                  <Th className="text-right">Total</Th>
                  <Th className="text-right">Actions</Th>
                </tr>
              </thead>
              <tbody>
                {invoices.map((invoice) => (
                  <Tr key={invoice.id}>
                    <Td className="font-medium text-slate-900">{invoice.invoice_number}</Td>
                    <Td>
                      {invoice.customer ? `${invoice.customer.first_name} ${invoice.customer.last_name ?? ''}` : '—'}
                    </Td>
                    <Td>
                      <Select
                        className="w-32"
                        value={invoice.status}
                        onChange={(event) => updateStatus(invoice, event.target.value)}
                      >
                        {INVOICE_STATUSES.map((option) => (
                          <option key={option.value} value={option.value}>
                            {option.label}
                          </option>
                        ))}
                      </Select>
                    </Td>
                    <Td>{formatDate(invoice.issue_date)}</Td>
                    <Td>{formatDate(invoice.due_date)}</Td>
                    <Td className="text-right font-medium text-slate-900">
                      {formatMoney(invoice.total, invoice.currency ?? currency)}
                    </Td>
                    <Td className="text-right">
                      <div className="inline-flex gap-1">
                        <button
                          type="button"
                          className="rounded-md p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                          onClick={() => downloadPdf(invoice)}
                          aria-label="Download PDF"
                        >
                          <Download className="size-4" />
                        </button>
                        <button
                          type="button"
                          className="rounded-md p-1.5 text-slate-500 transition hover:bg-red-50 hover:text-red-600"
                          onClick={() => setDeleting(invoice)}
                          aria-label="Delete invoice"
                        >
                          <Trash2 className="size-4" />
                        </button>
                      </div>
                    </Td>
                  </Tr>
                ))}
              </tbody>
            </Table>
            <Pagination meta={data?.meta} onPageChange={setPage} />
          </>
        )}
      </Card>

      <Modal
        open={creating}
        onClose={() => setCreating(false)}
        title="New invoice"
        size="lg"
        footer={
          <>
            <Button variant="secondary" onClick={() => setCreating(false)} disabled={saving}>
              Cancel
            </Button>
            <Button onClick={handleSubmit(onSubmit)} loading={saving}>
              Create invoice
            </Button>
          </>
        }
      >
        {formError && (
          <div className="mb-4">
            <Alert tone="danger">{formError}</Alert>
          </div>
        )}

        <form className="space-y-5" onSubmit={handleSubmit(onSubmit)} noValidate>
          <div className="grid gap-4 sm:grid-cols-2">
            <FormRow label="Customer" htmlFor="invoice-customer">
              <Select id="invoice-customer" {...register('customer_id')}>
                <option value="">No customer</option>
                {customers.map((customer) => (
                  <option key={customer.id} value={customer.id}>
                    {customer.first_name} {customer.last_name}
                  </option>
                ))}
              </Select>
            </FormRow>

            <FormRow label="Status" htmlFor="invoice-status" error={errors.status?.message}>
              <Select id="invoice-status" {...register('status')}>
                {INVOICE_STATUSES.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </Select>
            </FormRow>

            <FormRow label="Due date" htmlFor="invoice-due" error={errors.due_date?.message}>
              <Input id="invoice-due" type="date" {...register('due_date')} />
            </FormRow>

            <FormRow label="Discount" htmlFor="invoice-discount" error={errors.discount?.message}>
              <Input id="invoice-discount" type="number" step="0.01" min="0" {...register('discount')} />
            </FormRow>

            <FormRow label="Tax" htmlFor="invoice-tax" error={errors.tax?.message}>
              <Input id="invoice-tax" type="number" step="0.01" min="0" {...register('tax')} />
            </FormRow>
          </div>

          <div>
            <p className="mb-2 text-sm font-medium text-slate-700">Lines</p>
            <div className="space-y-2">
              {items.map((item, index) => (
                <div key={index} className="flex flex-wrap items-end gap-2 rounded-lg bg-slate-50 p-3">
                  <div className="min-w-48 flex-1">
                    <Input
                      aria-label="Description"
                      placeholder="Description"
                      value={item.description}
                      onChange={(event) => updateItem(index, { description: event.target.value })}
                    />
                  </div>
                  <div className="w-24">
                    <Input
                      aria-label="Quantity"
                      type="number"
                      min="1"
                      value={item.quantity}
                      onChange={(event) => updateItem(index, { quantity: event.target.value })}
                    />
                  </div>
                  <div className="w-32">
                    <Input
                      aria-label="Unit amount"
                      type="number"
                      step="0.01"
                      min="0"
                      value={item.unit_amount}
                      onChange={(event) => updateItem(index, { unit_amount: event.target.value })}
                    />
                  </div>
                  <button
                    type="button"
                    className="rounded-md p-2 text-slate-500 transition hover:bg-red-50 hover:text-red-600 disabled:opacity-40"
                    onClick={() => setItems((current) => current.filter((_, position) => position !== index))}
                    disabled={items.length === 1}
                    aria-label="Remove line"
                  >
                    <Trash2 className="size-4" />
                  </button>
                </div>
              ))}
            </div>
            <Button
              variant="secondary"
              size="sm"
              className="mt-2"
              onClick={() => setItems((current) => [...current, { description: '', quantity: 1, unit_amount: '0' }])}
            >
              <Plus className="size-4" />
              Add line
            </Button>
          </div>

          <div className="rounded-lg bg-brand-50 px-4 py-3">
            <p className="text-xs uppercase tracking-wide text-brand-700">Subtotal</p>
            <p className="text-lg font-semibold text-brand-900">{formatMoney(subtotal, currency)}</p>
          </div>
        </form>
      </Modal>

      <ConfirmDialog
        open={Boolean(deleting)}
        onClose={() => setDeleting(null)}
        onConfirm={confirmDelete}
        title="Delete invoice"
        description={deleting ? `${deleting.invoice_number} will be permanently removed.` : ''}
        confirmLabel="Delete"
      />
    </div>
  )
}
