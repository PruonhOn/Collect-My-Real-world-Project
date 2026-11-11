import { useCallback, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { Building2, Pencil, Plus, Trash2 } from 'lucide-react'
import { businessService } from '../../services/businessService'
import { useApi } from '../../hooks/useApi'
import { apiErrorMessage, apiFieldErrors, isUpgradeRequired } from '../../api/client'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock, Pagination } from '../../components/ui/Feedback'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { FormRow, Input } from '../../components/ui/Form'
import { ConfirmDialog, Modal } from '../../components/ui/Modal'
import { EmptyState, Table, Td, Th, Tr } from '../../components/ui/Table'

const schema = z.object({
  name: z.string().min(1, 'Name is required.').max(255),
  address: z.string().max(1000).optional(),
  phone: z.string().max(64).optional(),
  is_active: z.boolean(),
})

export default function Branches() {
  const [page, setPage] = useState(1)
  const [editing, setEditing] = useState(null)
  const [deleting, setDeleting] = useState(null)
  const [saving, setSaving] = useState(false)
  const [formError, setFormError] = useState(null)
  const [upgradeRequired, setUpgradeRequired] = useState(false)

  const loader = useCallback(() => businessService.branches({ page, per_page: 15 }), [page])
  const { data, loading, error, reload } = useApi(loader, [loader])

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { name: '', address: '', phone: '', is_active: true },
  })

  const openCreate = () => {
    setEditing({ id: null })
    setFormError(null)
    reset({ name: '', address: '', phone: '', is_active: true })
  }

  const openEdit = (branch) => {
    setEditing(branch)
    setFormError(null)
    reset({
      name: branch.name ?? '',
      address: branch.address ?? '',
      phone: branch.phone ?? '',
      is_active: Boolean(branch.is_active),
    })
  }

  const onSubmit = async (values) => {
    setSaving(true)
    setFormError(null)
    setUpgradeRequired(false)

    const payload = {
      name: values.name,
      address: values.address || null,
      phone: values.phone || null,
      is_active: values.is_active,
    }

    try {
      if (editing?.id) {
        await businessService.updateBranch(editing.id, payload)
        toast.success('Branch updated.')
      } else {
        await businessService.createBranch(payload)
        toast.success('Branch created.')
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

      setFormError(apiErrorMessage(exception, 'Unable to save the branch.'))
    } finally {
      setSaving(false)
    }
  }

  const confirmDelete = async () => {
    if (!deleting) {
      return
    }

    try {
      await businessService.deleteBranch(deleting.id)
      toast.success('Branch deleted.')
      setDeleting(null)
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to delete the branch.'))
    }
  }

  const branches = data?.data ?? []

  return (
    <div>
      <PageHeader
        title="Branches"
        description="Manage multiple locations of your business."
        actions={
          <Button onClick={openCreate}>
            <Plus className="size-4" />
            Add branch
          </Button>
        }
      />

      {upgradeRequired && (
        <div className="mb-4">
          <Alert tone="warning" title="Branch limit reached">
            Your plan does not allow more branches. Upgrade to add locations.
          </Alert>
        </div>
      )}

      <Card>
        {loading && <LoadingBlock />}

        {error && !loading && (
          <div className="p-5">
            <Alert tone="danger">{apiErrorMessage(error)}</Alert>
          </div>
        )}

        {!loading && !error && branches.length === 0 && (
          <EmptyState
            icon={Building2}
            title="No branches yet"
            description="Add a branch to split your business across locations."
            action={<Button onClick={openCreate}>Add branch</Button>}
          />
        )}

        {!loading && !error && branches.length > 0 && (
          <>
            <Table>
              <thead className="bg-slate-50">
                <tr>
                  <Th>Branch</Th>
                  <Th>Address</Th>
                  <Th>Phone</Th>
                  <Th>Status</Th>
                  <Th className="text-right">Actions</Th>
                </tr>
              </thead>
              <tbody>
                {branches.map((branch) => (
                  <Tr key={branch.id}>
                    <Td className="font-medium text-slate-900">{branch.name}</Td>
                    <Td>{branch.address ?? '—'}</Td>
                    <Td>{branch.phone ?? '—'}</Td>
                    <Td>
                      <Badge tone={branch.is_active ? 'success' : 'neutral'}>
                        {branch.is_active ? 'active' : 'inactive'}
                      </Badge>
                    </Td>
                    <Td className="text-right">
                      <div className="inline-flex gap-1">
                        <button
                          type="button"
                          className="rounded-md p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                          onClick={() => openEdit(branch)}
                          aria-label="Edit branch"
                        >
                          <Pencil className="size-4" />
                        </button>
                        <button
                          type="button"
                          className="rounded-md p-1.5 text-slate-500 transition hover:bg-red-50 hover:text-red-600"
                          onClick={() => setDeleting(branch)}
                          aria-label="Delete branch"
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
        title={editing?.id ? 'Edit branch' : 'New branch'}
        footer={
          <>
            <Button variant="secondary" onClick={() => setEditing(null)} disabled={saving}>
              Cancel
            </Button>
            <Button onClick={handleSubmit(onSubmit)} loading={saving}>
              {editing?.id ? 'Save changes' : 'Create branch'}
            </Button>
          </>
        }
      >
        {formError && (
          <div className="mb-4">
            <Alert tone="danger">{formError}</Alert>
          </div>
        )}

        <form className="space-y-4" onSubmit={handleSubmit(onSubmit)} noValidate>
          <FormRow label="Name" htmlFor="branch-name" error={errors.name?.message}>
            <Input id="branch-name" invalid={Boolean(errors.name)} {...register('name')} />
          </FormRow>

          <FormRow label="Address" htmlFor="branch-address" error={errors.address?.message}>
            <Input id="branch-address" {...register('address')} />
          </FormRow>

          <FormRow label="Phone" htmlFor="branch-phone" error={errors.phone?.message}>
            <Input id="branch-phone" {...register('phone')} />
          </FormRow>

          <FormRow label="Active" htmlFor="branch-active">
            <label className="flex items-center gap-2 text-sm text-slate-700">
              <input id="branch-active" type="checkbox" className="size-4 rounded border-slate-300" {...register('is_active')} />
              Branch is operating
            </label>
          </FormRow>
        </form>
      </Modal>

      <ConfirmDialog
        open={Boolean(deleting)}
        onClose={() => setDeleting(null)}
        onConfirm={confirmDelete}
        title="Delete branch"
        description={deleting ? `“${deleting.name}” will be removed.` : ''}
        confirmLabel="Delete"
      />
    </div>
  )
}
