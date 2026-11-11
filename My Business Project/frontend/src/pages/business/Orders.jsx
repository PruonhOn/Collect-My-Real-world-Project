import { useCallback, useMemo, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { Plus, Search, ShoppingCart, Trash2 } from 'lucide-react'
import { businessService } from '../../services/businessService'
import { useBusiness } from '../../context/BusinessContext'
import { useApi } from '../../hooks/useApi'
import { useDebouncedValue } from '../../hooks/useDebouncedValue'
import { apiErrorMessage, isUpgradeRequired } from '../../api/client'
import { formatMoney, formatDate, toMinorUnits } from '../../utils/format'
import { ORDER_STATUSES, PAYMENT_STATUSES, STATUS_TONES } from '../../utils/constants'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock, Pagination } from '../../components/ui/Feedback'
import { StatusBadge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { FormRow, Input, Select, Textarea } from '../../components/ui/Form'
import { Modal } from '../../components/ui/Modal'
import { EmptyState, Table, Td, Th, Tr } from '../../components/ui/Table'

const schema = z.object({
  customer_id: z.string().optional(),
  discount: z.coerce.number().min(0),
  tax: z.coerce.number().min(0),
  status: z.enum(['pending', 'processing', 'completed', 'canceled', 'refunded']),
  payment_status: z.enum(['unpaid', 'paid', 'partial', 'refunded']),
  notes: z.string().max(2000).optional(),
})

export default function Orders() {
  const { activeBusiness } = useBusiness()
  const currency = activeBusiness?.currency ?? 'USD'

  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [creating, setCreating] = useState(false)
  const [saving, setSaving] = useState(false)
  const [formError, setFormError] = useState(null)
  const [upgradeRequired, setUpgradeRequired] = useState(false)
  const [items, setItems] = useState([{ product_id: '', quantity: 1 }])
  const debouncedSearch = useDebouncedValue(search)

  const loader = useCallback(
    () => businessService.orders({ search: debouncedSearch, status, page, per_page: 15 }),
    [debouncedSearch, status, page],
  )
  const { data, loading, error, reload } = useApi(loader, [loader])

  const productsLoader = useCallback(() => businessService.products({ per_page: 100 }), [])
  const { data: productData } = useApi(productsLoader, [productsLoader])

  const customersLoader = useCallback(() => businessService.customers({ per_page: 100 }), [])
  const { data: customerData } = useApi(customersLoader, [customersLoader])

  const products = useMemo(() => productData?.data ?? [], [productData])
  const customers = useMemo(() => customerData?.data ?? [], [customerData])

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: {
      customer_id: '',
      discount: '0',
      tax: '0',
      status: 'pending',
      payment_status: 'unpaid',
      notes: '',
    },
  })

  const total = useMemo(() => {
    const subtotal = items.reduce((sum, item) => {
      const product = products.find((entry) => String(entry.id) === String(item.product_id))

      return sum + (product ? product.price * Number(item.quantity || 0) : 0)
    }, 0)

    return subtotal
  }, [items, products])

  const openCreate = () => {
    setItems([{ product_id: '', quantity: 1 }])
    setFormError(null)
    reset({
      customer_id: '',
      discount: '0',
      tax: '0',
      status: 'pending',
      payment_status: 'unpaid',
      notes: '',
    })
    setCreating(true)
  }

  const updateItem = (index, patch) => {
    setItems((current) => current.map((item, position) => (position === index ? { ...item, ...patch } : item)))
  }

  const onSubmit = async (values) => {
    setSaving(true)
    setFormError(null)
    setUpgradeRequired(false)

    const payload = {
      customer_id: values.customer_id ? Number(values.customer_id) : null,
      items: items
        .filter((item) => item.product_id)
        .map((item) => ({ product_id: Number(item.product_id), quantity: Number(item.quantity) })),
      discount: toMinorUnits(values.discount),
      tax: toMinorUnits(values.tax),
      status: values.status,
      payment_status: values.payment_status,
      notes: values.notes || null,
    }

    if (payload.items.length === 0) {
      setFormError('Add at least one product to the order.')
      setSaving(false)

      return
    }

    try {
      await businessService.createOrder(payload)
      toast.success('Order created.')
      setCreating(false)
      reload()
    } catch (exception) {
      if (isUpgradeRequired(exception)) {
        setUpgradeRequired(true)
      }

      setFormError(apiErrorMessage(exception, 'Unable to create the order.'))
    } finally {
      setSaving(false)
    }
  }

  const updateStatus = async (order, nextStatus) => {
    try {
      await businessService.updateOrder(order.id, { status: nextStatus })
      toast.success('Order updated.')
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to update the order.'))
    }
  }

  const orders = data?.data ?? []

  return (
    <div>
      <PageHeader
        title="Orders"
        description="Sales orders with server calculated totals and stock movements."
        actions={
          <Button onClick={openCreate}>
            <Plus className="size-4" />
            New order
          </Button>
        }
      />

      {upgradeRequired && (
        <div className="mb-4">
          <Alert tone="warning" title="Plan limit reached">
            This business reached its monthly order limit. Upgrade to keep selling.
          </Alert>
        </div>
      )}

      <Card>
        <div className="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-4">
          <div className="relative min-w-56 flex-1">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <Input
              className="pl-9"
              placeholder="Search by order number or customer"
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
            {ORDER_STATUSES.map((option) => (
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

        {!loading && !error && orders.length === 0 && (
          <EmptyState
            icon={ShoppingCart}
            title="No orders yet"
            description="Create an order to record a sale."
            action={<Button onClick={openCreate}>New order</Button>}
          />
        )}

        {!loading && !error && orders.length > 0 && (
          <>
            <Table>
              <thead className="bg-slate-50">
                <tr>
                  <Th>Order</Th>
                  <Th>Customer</Th>
                  <Th>Status</Th>
                  <Th>Payment</Th>
                  <Th>Date</Th>
                  <Th className="text-right">Total</Th>
                </tr>
              </thead>
              <tbody>
                {orders.map((order) => (
                  <Tr key={order.id}>
                    <Td className="font-medium text-slate-900">{order.order_number}</Td>
                    <Td>
                      {order.customer ? `${order.customer.first_name} ${order.customer.last_name ?? ''}` : 'Walk-in customer'}
                    </Td>
                    <Td>
                      <Select
                        className="w-32"
                        value={order.status}
                        onChange={(event) => updateStatus(order, event.target.value)}
                      >
                        {ORDER_STATUSES.map((option) => (
                          <option key={option.value} value={option.value}>
                            {option.label}
                          </option>
                        ))}
                      </Select>
                    </Td>
                    <Td>
                      <StatusBadge status={order.payment_status} tone={STATUS_TONES[order.payment_status]} />
                    </Td>
                    <Td>{formatDate(order.created_at)}</Td>
                    <Td className="text-right font-medium text-slate-900">
                      {formatMoney(order.total, order.currency ?? currency)}
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
        title="New order"
        description="Totals are calculated on the server from the current product prices."
        size="lg"
        footer={
          <>
            <Button variant="secondary" onClick={() => setCreating(false)} disabled={saving}>
              Cancel
            </Button>
            <Button onClick={handleSubmit(onSubmit)} loading={saving}>
              Create order
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
            <FormRow label="Customer" htmlFor="customer_id">
              <Select id="customer_id" {...register('customer_id')}>
                <option value="">Walk-in customer</option>
                {customers.map((customer) => (
                  <option key={customer.id} value={customer.id}>
                    {customer.first_name} {customer.last_name}
                  </option>
                ))}
              </Select>
            </FormRow>

            <FormRow label="Status" htmlFor="status" error={errors.status?.message}>
              <Select id="status" {...register('status')}>
                {ORDER_STATUSES.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </Select>
            </FormRow>

            <FormRow label="Payment status" htmlFor="payment_status" error={errors.payment_status?.message}>
              <Select id="payment_status" {...register('payment_status')}>
                {PAYMENT_STATUSES.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </Select>
            </FormRow>

            <FormRow label="Notes" htmlFor="notes" error={errors.notes?.message}>
              <Input id="notes" {...register('notes')} />
            </FormRow>
          </div>

          <div>
            <p className="mb-2 text-sm font-medium text-slate-700">Items</p>
            <div className="space-y-2">
              {items.map((item, index) => (
                <div key={index} className="flex flex-wrap items-end gap-2 rounded-lg bg-slate-50 p-3">
                  <div className="min-w-48 flex-1">
                    <Select
                      aria-label="Product"
                      value={item.product_id}
                      onChange={(event) => updateItem(index, { product_id: event.target.value })}
                    >
                      <option value="">Select a product</option>
                      {products.map((product) => (
                        <option key={product.id} value={product.id}>
                          {product.name} ({formatMoney(product.price, currency)})
                        </option>
                      ))}
                    </Select>
                  </div>
                  <div className="w-28">
                    <Input
                      aria-label="Quantity"
                      type="number"
                      min="1"
                      value={item.quantity}
                      onChange={(event) => updateItem(index, { quantity: event.target.value })}
                    />
                  </div>
                  <button
                    type="button"
                    className="rounded-md p-2 text-slate-500 transition hover:bg-red-50 hover:text-red-600 disabled:opacity-40"
                    onClick={() => setItems((current) => current.filter((_, position) => position !== index))}
                    disabled={items.length === 1}
                    aria-label="Remove item"
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
              onClick={() => setItems((current) => [...current, { product_id: '', quantity: 1 }])}
            >
              <Plus className="size-4" />
              Add item
            </Button>
          </div>

          <div className="grid gap-4 sm:grid-cols-3">
            <FormRow label="Discount" htmlFor="discount" error={errors.discount?.message}>
              <Input id="discount" type="number" step="0.01" min="0" {...register('discount')} />
            </FormRow>

            <FormRow label="Tax" htmlFor="tax" error={errors.tax?.message}>
              <Input id="tax" type="number" step="0.01" min="0" {...register('tax')} />
            </FormRow>

            <div className="rounded-lg bg-brand-50 px-4 py-3">
              <p className="text-xs uppercase tracking-wide text-brand-700">Subtotal</p>
              <p className="text-lg font-semibold text-brand-900">{formatMoney(total, currency)}</p>
            </div>
          </div>
        </form>
      </Modal>
    </div>
  )
}
