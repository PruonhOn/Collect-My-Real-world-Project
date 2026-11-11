import { useCallback, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { Pencil, Plus, Search, Trash2, Users } from 'lucide-react'
import { businessService } from '../../services/businessService'
import { useApi } from '../../hooks/useApi'
import { useDebouncedValue } from '../../hooks/useDebouncedValue'
import { apiErrorMessage, apiFieldErrors, isUpgradeRequired } from '../../api/client'
import { formatDate } from '../../utils/format'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock, Pagination } from '../../components/ui/Feedback'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { FormRow, Input, Textarea } from '../../components/ui/Form'
import { ConfirmDialog, Modal } from '../../components/ui/Modal'
import { EmptyState, Table, Td, Th, Tr } from '../../components/ui/Table'

const schema = z.object({
  first_name: z.string().min(1, 'First name is required.').max(100),
  last_name: z.string().max(100).optional(),
  email: z.string().email('Enter a valid email address.').or(z.literal('')).optional(),
  phone: z.string().max(50).optional(),
  address: z.string().max(1000).optional(),
})

export default function Customers() {
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [editing, setEditing] = useState(null)
  const [deleting, setDeleting] = useState(null)
  const [saving, setSaving] = useState(false)
  const [formError, setFormError] = useState(null)
  const [upgradeRequired, setUpgradeRequired] = useState(false)
  const debouncedSearch = useDebouncedValue(search)

  const loader = useCallback(
    () => businessService.customers({ search: debouncedSearch, page, per_page: 15 }),
    [debouncedSearch, page],
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
    defaultValues: { first_name: '', last_name: '', email: '', phone: '', address: '' },
  })

  const openCreate = () => {
    setEditing({ id: null })
    setFormError(null)
    reset({ first_name: '', last_name: '', email: '', phone: '', address: '' })
  }

  const openEdit = (customer) => {
    setEditing(customer)
    setFormError(null)
    reset({
      first_name: customer.first_name ?? '',
      last_name: customer.last_name ?? '',
      email: customer.email ?? '',
      phone: customer.phone ?? '',
      address: customer.address ?? '',
    })
  }

  const onSubmit = async (values) => {
    setSaving(true)
    setFormError(null)
    setUpgradeRequired(false)

    const payload = {
      first_name: values.first_name,
      last_name: values.last_name || null,
      email: values.email || null,
      phone: values.phone || null,
      address: values.address || null,
    }

    try {
      if (editing?.id) {
        await businessService.updateCustomer(editing.id, payload)
        toast.success('Customer updated.')
      } else {
        await businessService.createCustomer(payload)
        toast.success('Customer created.')
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

      setFormError(apiErrorMessage(exception, 'Unable to save the customer.'))
    } finally {
      setSaving(false)
    }
  }

  const confirmDelete = async () => {
    if (!deleting) {
      return
    }

    try {
      await businessService.deleteCustomer(deleting.id)
      toast.success('Customer deleted.')
      setDeleting(null)
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to delete the customer.'))
    }
  }

  const customers = data?.data ?? []

  return (
    <div>
      <PageHeader
        title="Customers"
        description="Everyone who buys from your business."
        actions={
          <Button onClick={openCreate}>
            <Plus className="size-4" />
            Add customer
          </Button>
        }
      />

      {upgradeRequired && (
        <div className="mb-4">
          <Alert tone="warning" title="Plan limit reached">
            Your plan does not allow more customers right now. Upgrade the business to keep adding customers.
          </Alert>
        </div>
      )}

      <Card>
        <div className="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-4">
          <div className="relative min-w-56 flex-1">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <Input
              className="pl-9"
              placeholder="Search by name, email or phone"
              value={search}
              onChange={(event) => {
                setSearch(event.target.value)
                setPage(1)
              }}
            />
          </div>
        </div>

        {loading && <LoadingBlock />}

        {error && !loading && (
          <div className="p-5">
            <Alert tone="danger">{apiErrorMessage(error)}</Alert>
          </div>
        )}

        {!loading && !error && customers.length === 0 && (
          <EmptyState
            icon={Users}
            title="No customers yet"
            description="Add your first customer to start tracking sales."
            action={<Button onClick={openCreate}>Add customer</Button>}
          />
        )}

        {!loading && !error && customers.length > 0 && (
          <>
            <Table>
              <thead className="bg-slate-50">
                <tr>
                  <Th>Customer</Th>
                  <Th>Email</Th>
                  <Th>Phone</Th>
                  <Th>Joined</Th>
                  <Th className="text-right">Actions</Th>
                </tr>
              </thead>
              <tbody>
                {customers.map((customer) => (
                  <Tr key={customer.id}>
                    <Td className="font-medium text-slate-900">
                      {customer.first_name} {customer.last_name}
                    </Td>
                    <Td>{customer.email ?? '—'}</Td>
                    <Td>{customer.phone ?? '—'}</Td>
                    <Td>{formatDate(customer.created_at)}</Td>
                    <Td className="text-right">
                      <div className="inline-flex gap-1">
                        <button
                          type="button"
                          className="rounded-md p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                          onClick={() => openEdit(customer)}
                          aria-label="Edit customer"
                        >
                          <Pencil className="size-4" />
                        </button>
                        <button
                          type="button"
                          className="rounded-md p-1.5 text-slate-500 transition hover:bg-red-50 hover:text-red-600"
                          onClick={() => setDeleting(customer)}
                          aria-label="Delete customer"
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
        title={editing?.id ? 'Edit customer' : 'New customer'}
        footer={
          <>
            <Button variant="secondary" onClick={() => setEditing(null)} disabled={saving}>
              Cancel
            </Button>
            <Button onClick={handleSubmit(onSubmit)} loading={saving}>
              {editing?.id ? 'Save changes' : 'Create customer'}
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
          <FormRow label="First name" htmlFor="first_name" error={errors.first_name?.message}>
            <Input id="first_name" invalid={Boolean(errors.first_name)} {...register('first_name')} />
          </FormRow>

          <FormRow label="Last name" htmlFor="last_name" error={errors.last_name?.message}>
            <Input id="last_name" {...register('last_name')} />
          </FormRow>

          <FormRow label="Email" htmlFor="email" error={errors.email?.message}>
            <Input id="email" type="email" {...register('email')} />
          </FormRow>

          <FormRow label="Phone" htmlFor="phone" error={errors.phone?.message}>
            <Input id="phone" {...register('phone')} />
          </FormRow>

          <div className="sm:col-span-2">
            <FormRow label="Address" htmlFor="address" error={errors.address?.message}>
              <Textarea id="address" {...register('address')} />
            </FormRow>
          </div>
        </form>
      </Modal>

      <ConfirmDialog
        open={Boolean(deleting)}
        onClose={() => setDeleting(null)}
        onConfirm={confirmDelete}
        title="Delete customer"
        description={deleting ? `“${deleting.first_name} ${deleting.last_name ?? ''}” will be removed.` : ''}
        confirmLabel="Delete"
      />
    </div>
  )
}
