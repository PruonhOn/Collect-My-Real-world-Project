import { useCallback, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { Package, Pencil, Plus, Search, Trash2 } from 'lucide-react'
import { businessService } from '../../services/businessService'
import { useBusiness } from '../../context/BusinessContext'
import { useApi } from '../../hooks/useApi'
import { useDebouncedValue } from '../../hooks/useDebouncedValue'
import { apiErrorMessage, apiFieldErrors, isUpgradeRequired } from '../../api/client'
import { formatMoney, toMinorUnits, fromMinorUnits } from '../../utils/format'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock, Pagination } from '../../components/ui/Feedback'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { FormRow, Input, Select, Textarea } from '../../components/ui/Form'
import { ConfirmDialog, Modal } from '../../components/ui/Modal'
import { EmptyState, Table, Td, Th, Tr } from '../../components/ui/Table'

const schema = z.object({
  name: z.string().min(1, 'Name is required.').max(255),
  sku: z.string().max(100).optional(),
  description: z.string().max(2000).optional(),
  price: z.coerce.number().min(0, 'Price cannot be negative.'),
  status: z.enum(['active', 'inactive']),
  track_inventory: z.boolean(),
  stock_quantity: z.coerce.number().int().min(0).optional(),
  low_stock_threshold: z.coerce.number().int().min(0).optional(),
})

export default function Products() {
  const { activeBusiness } = useBusiness()
  const currency = activeBusiness?.currency ?? 'USD'

  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const debouncedSearch = useDebouncedValue(search)

  const [editing, setEditing] = useState(null)
  const [deleting, setDeleting] = useState(null)
  const [saving, setSaving] = useState(false)
  const [formError, setFormError] = useState(null)
  const [upgradeRequired, setUpgradeRequired] = useState(false)

  const loader = useCallback(
    () => businessService.products({ search: debouncedSearch, status, page, per_page: 15 }),
    [debouncedSearch, status, page],
  )
  const { data, loading, error, reload } = useApi(loader, [loader])

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: {
      name: '',
      sku: '',
      description: '',
      price: '0',
      status: 'active',
      track_inventory: false,
      stock_quantity: 0,
      low_stock_threshold: 0,
    },
  })

  const openCreate = () => {
    setEditing({ id: null })
    setFormError(null)
    reset({
      name: '',
      sku: '',
      description: '',
      price: '0',
      status: 'active',
      track_inventory: false,
      stock_quantity: 0,
      low_stock_threshold: 0,
    })
  }

  const openEdit = (product) => {
    setEditing(product)
    setFormError(null)
    reset({
      name: product.name ?? '',
      sku: product.sku ?? '',
      description: product.description ?? '',
      price: fromMinorUnits(product.price),
      status: product.status ?? 'active',
      track_inventory: Boolean(product.track_inventory),
      stock_quantity: product.stock_quantity ?? 0,
      low_stock_threshold: product.low_stock_threshold ?? 0,
    })
  }

  const onSubmit = async (values) => {
    setSaving(true)
    setFormError(null)
    setUpgradeRequired(false)

    const payload = {
      name: values.name,
      sku: values.sku || null,
      description: values.description || null,
      price: toMinorUnits(values.price),
      status: values.status,
      track_inventory: values.track_inventory,
      stock_quantity: values.track_inventory ? Number(values.stock_quantity ?? 0) : null,
      low_stock_threshold: values.track_inventory ? Number(values.low_stock_threshold ?? 0) : null,
    }

    try {
      if (editing?.id) {
        await businessService.updateProduct(editing.id, payload)
        toast.success('Product updated.')
      } else {
        await businessService.createProduct(payload)
        toast.success('Product created.')
      }

      setEditing(null)
      reload()
    } catch (exception) {
      const fieldErrors = apiFieldErrors(exception)

      Object.entries(fieldErrors).forEach(([field, messages]) => {
        setError(field, { message: messages[0] })
      })

      if (isUpgradeRequired(exception)) {
        setUpgradeRequired(true)
      }

      setFormError(apiErrorMessage(exception, 'Unable to save the product.'))
    } finally {
      setSaving(false)
    }
  }

  const confirmDelete = async () => {
    if (!deleting) {
      return
    }

    try {
      await businessService.deleteProduct(deleting.id)
      toast.success('Product deleted.')
      setDeleting(null)
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to delete the product.'))
    }
  }

  const products = data?.data ?? []

  return (
    <div>
      <PageHeader
        title="Products"
        description="Everything you sell, with prices, stock levels and availability."
        actions={
          <Button onClick={openCreate}>
            <Plus className="size-4" />
            Add product
          </Button>
        }
      />

      {upgradeRequired && (
        <div className="mb-4">
          <Alert tone="warning" title="Plan limit reached">
            Your plan does not allow more products right now. Upgrade the business to keep adding products.
          </Alert>
        </div>
      )}

      <Card>
        <div className="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-4">
          <div className="relative min-w-56 flex-1">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <Input
              className="pl-9"
              placeholder="Search by name or SKU"
              value={search}
              onChange={(event) => {
                setSearch(event.target.value)
                setPage(1)
              }}
            />
          </div>
          <Select
            className="w-40"
            value={status}
            onChange={(event) => {
              setStatus(event.target.value)
              setPage(1)
            }}
          >
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </Select>
        </div>

        {loading && <LoadingBlock />}

        {error && !loading && (
          <div className="p-5">
            <Alert tone="danger">{apiErrorMessage(error)}</Alert>
          </div>
        )}

        {!loading && !error && products.length === 0 && (
          <EmptyState
            icon={Package}
            title="No products yet"
            description="Add your first product or service to start selling."
            action={<Button onClick={openCreate}>Add product</Button>}
          />
        )}

        {!loading && !error && products.length > 0 && (
          <>
            <Table>
              <thead className="bg-slate-50">
                <tr>
                  <Th>Product</Th>
                  <Th>SKU</Th>
                  <Th>Status</Th>
                  <Th>Stock</Th>
                  <Th className="text-right">Price</Th>
                  <Th className="text-right">Actions</Th>
                </tr>
              </thead>
              <tbody>
                {products.map((product) => (
                  <Tr key={product.id}>
                    <Td>
                      <p className="font-medium text-slate-900">{product.name}</p>
                      {product.description && (
                        <p className="mt-0.5 max-w-md truncate text-xs text-slate-500">{product.description}</p>
                      )}
                    </Td>
                    <Td className="font-mono text-xs">{product.sku ?? '—'}</Td>
                    <Td>
                      <Badge tone={product.status === 'active' ? 'success' : 'neutral'}>{product.status}</Badge>
                    </Td>
                    <Td>
                      {product.track_inventory ? (
                        <span className={product.stock_quantity <= (product.low_stock_threshold ?? 0) ? 'font-medium text-amber-600' : ''}>
                          {product.stock_quantity ?? 0}
                        </span>
                      ) : (
                        <span className="text-slate-400">Not tracked</span>
                      )}
                    </Td>
                    <Td className="text-right font-medium text-slate-900">{formatMoney(product.price, currency)}</Td>
                    <Td className="text-right">
                      <div className="inline-flex gap-1">
                        <button
                          type="button"
                          className="rounded-md p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                          onClick={() => openEdit(product)}
                          aria-label="Edit product"
                        >
                          <Pencil className="size-4" />
                        </button>
                        <button
                          type="button"
                          className="rounded-md p-1.5 text-slate-500 transition hover:bg-red-50 hover:text-red-600"
                          onClick={() => setDeleting(product)}
                          aria-label="Delete product"
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
        open={Boolean(editing)}
        onClose={() => setEditing(null)}
        title={editing?.id ? 'Edit product' : 'New product'}
        description="Prices are stored in the smallest currency unit to keep the calculations exact."
        footer={
          <>
            <Button variant="secondary" onClick={() => setEditing(null)} disabled={saving}>
              Cancel
            </Button>
            <Button onClick={handleSubmit(onSubmit)} loading={saving}>
              {editing?.id ? 'Save changes' : 'Create product'}
            </Button>
          </>
        }
      >
        {formError && (
          <div className="mb-4">
            <Alert tone="danger">{formError}</Alert>
          </div>
        )}

        <form className="grid gap-4 sm:grid-cols-2" onSubmit={handleSubmit(onSubmit)} noValidate>
          <div className="sm:col-span-2">
            <FormRow label="Name" htmlFor="product-name" error={errors.name?.message}>
              <Input id="product-name" invalid={Boolean(errors.name)} {...register('name')} />
            </FormRow>
          </div>

          <FormRow label="SKU" htmlFor="product-sku" error={errors.sku?.message}>
            <Input id="product-sku" {...register('sku')} />
          </FormRow>

          <FormRow label="Price" htmlFor="product-price" error={errors.price?.message}>
            <Input id="product-price" type="number" step="0.01" min="0" {...register('price')} />
          </FormRow>

          <div className="sm:col-span-2">
            <FormRow label="Description" htmlFor="product-description" error={errors.description?.message}>
              <Textarea id="product-description" {...register('description')} />
            </FormRow>
          </div>

          <FormRow label="Status" htmlFor="product-status" error={errors.status?.message}>
            <Select id="product-status" {...register('status')}>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </Select>
          </FormRow>

          <FormRow label="Track inventory" htmlFor="product-track">
            <label className="flex items-center gap-2 text-sm text-slate-700">
              <input id="product-track" type="checkbox" className="size-4 rounded border-slate-300" {...register('track_inventory')} />
              Keep stock levels
            </label>
          </FormRow>

          <FormRow label="Stock quantity" htmlFor="product-stock" error={errors.stock_quantity?.message}>
            <Input id="product-stock" type="number" min="0" {...register('stock_quantity')} />
          </FormRow>

          <FormRow label="Low stock threshold" htmlFor="product-threshold" error={errors.low_stock_threshold?.message}>
            <Input id="product-threshold" type="number" min="0" {...register('low_stock_threshold')} />
          </FormRow>
        </form>
      </Modal>

      <ConfirmDialog
        open={Boolean(deleting)}
        onClose={() => setDeleting(null)}
        onConfirm={confirmDelete}
        title="Delete product"
        description={deleting ? `“${deleting.name}” will be removed from your catalog.` : ''}
        confirmLabel="Delete"
      />
    </div>
  )
}
