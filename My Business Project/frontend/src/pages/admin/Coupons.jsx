import { useCallback, useState } from "react"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { z } from "zod"
import { toast } from "sonner"
import { Pencil, Plus, Tag, Trash2 } from "lucide-react"
import { adminService } from "../../services/adminService"
import { useApi } from "../../hooks/useApi"
import { apiErrorMessage } from "../../api/client"
import { formatDate } from "../../utils/format"
import { PageHeader } from "../../components/PageHeader"
import { Alert, LoadingBlock } from "../../components/ui/Feedback"
import { Badge } from "../../components/ui/Badge"
import { Button } from "../../components/ui/Button"
import { Card } from "../../components/ui/Card"
import { FormRow, Input, Select } from "../../components/ui/Form"
import { ConfirmDialog, Modal } from "../../components/ui/Modal"
import { EmptyState, Table, Td, Th, Tr } from "../../components/ui/Table"

const schema = z.object({
  code: z.string().min(3, "Enter a code.").max(64).regex(/^[A-Za-z0-9-]+$/, "Use letters, numbers and dashes."),
  type: z.enum(["percentage", "fixed"]),
  value: z.coerce.number().int().min(0),
  min_amount: z.coerce.number().int().min(0).optional().or(z.literal("")),
  max_uses: z.coerce.number().int().min(0).optional().or(z.literal("")),
  starts_at: z.string().optional().or(z.literal("")),
  expires_at: z.string().optional().or(z.literal("")),
  is_active: z.boolean(),
})

const emptyValues = {
  code: "",
  type: "percentage",
  value: 10,
  min_amount: "",
  max_uses: "",
  starts_at: "",
  expires_at: "",
  is_active: true,
}

export default function AdminCoupons() {
  const [editing, setEditing] = useState(null)
  const [creating, setCreating] = useState(false)
  const [removing, setRemoving] = useState(null)
  const [saving, setSaving] = useState(false)
  const [formError, setFormError] = useState(null)

  const loader = useCallback(() => adminService.coupons(), [])
  const { data, loading, error, reload } = useApi(loader, [loader])

  const coupons = data?.data ?? []

  const { register, handleSubmit, reset, formState: { errors } } = useForm({
    resolver: zodResolver(schema),
    defaultValues: emptyValues,
  })

  const openCreate = () => {
    setFormError(null)
    reset(emptyValues)
    setCreating(true)
  }

  const openEdit = (coupon) => {
    setFormError(null)
    reset({
      code: coupon.code,
      type: coupon.type,
      value: coupon.value,
      min_amount: coupon.min_amount ?? "",
      max_uses: coupon.max_uses ?? "",
      starts_at: coupon.starts_at ?? "",
      expires_at: coupon.expires_at ?? "",
      is_active: coupon.is_active,
    })
    setEditing(coupon)
  }

  const onSubmit = async (values) => {
    setSaving(true)
    setFormError(null)
    try {
      const payload = {
        code: values.code,
        type: values.type,
        value: Number(values.value),
        min_amount: values.min_amount === "" ? null : Number(values.min_amount),
        max_uses: values.max_uses === "" ? null : Number(values.max_uses),
        starts_at: values.starts_at === "" ? null : values.starts_at,
        expires_at: values.expires_at === "" ? null : values.expires_at,
        is_active: values.is_active,
      }
      if (editing) {
        await adminService.updateCoupon(editing.id, payload)
        toast.success("Coupon updated.")
      } else {
        await adminService.createCoupon(payload)
        toast.success("Coupon created.")
      }
      setCreating(false)
      setEditing(null)
      await reload()
    } catch (exception) {
      setFormError(apiErrorMessage(exception, "Unable to save the coupon."))
    } finally {
      setSaving(false)
    }
  }

  const confirmDelete = async () => {
    if (!removing) return
    setSaving(true)
    setFormError(null)
    try {
      await adminService.deleteCoupon(removing.id)
      toast.success("Coupon deleted.")
      setRemoving(null)
      await reload()
    } catch (exception) {
      setFormError(apiErrorMessage(exception, "Unable to delete the coupon."))
    } finally {
      setSaving(false)
    }
  }

  return (
    <div>
      <PageHeader
        title="Coupons"
        description="Create and manage discount coupons for paid subscriptions."
        actions={
          <Button onClick={openCreate}>
            <Plus className="size-4" />
            Add coupon
          </Button>
        }
      />

      {error && <Alert tone="danger" className="mb-4">{apiErrorMessage(error)}</Alert>}
      {formError && <Alert tone="danger" className="mb-4">{formError}</Alert>}

      <Card>
        {loading ? (
          <LoadingBlock label="Loading coupons..." />
        ) : coupons.length === 0 ? (
          <EmptyState
            icon={Tag}
            title="No coupons yet"
            description="Create your first coupon to offer discounts at checkout."
            action={
              <Button onClick={openCreate}>
                <Plus className="size-4" />
                Add coupon
              </Button>
            }
          />
        ) : (
          <Table>
            <thead className="bg-slate-50">
              <tr>
                <Th>Code</Th>
                <Th>Type</Th>
                <Th>Value</Th>
                <Th>Uses</Th>
                <Th>Valid</Th>
                <Th>Status</Th>
                <Th className="text-right">Actions</Th>
              </tr>
            </thead>
            <tbody>
              {coupons.map((coupon) => (
                <Tr key={coupon.id}>
                  <Td className="font-mono font-semibold text-slate-900">{coupon.code}</Td>
                  <Td className="capitalize">{coupon.type}</Td>
                  <Td>{coupon.type === "percentage" ? `${coupon.value}%` : coupon.value}</Td>
                  <Td>
                    {coupon.uses_count}
                    {coupon.max_uses !== null ? ` / ${coupon.max_uses}` : " / ∞"}
                  </Td>
                  <Td className="text-xs text-slate-500">
                    {coupon.starts_at ? `From ${formatDate(coupon.starts_at)}` : "No start"}
                    {" · "}
                    {coupon.expires_at ? `To ${formatDate(coupon.expires_at)}` : "No expiry"}
                  </Td>
                  <Td>{coupon.is_active ? <Badge tone="success">Active</Badge> : <Badge>Inactive</Badge>}</Td>
                  <Td className="text-right">
                    <div className="flex justify-end gap-2">
                      <Button variant="ghost" size="sm" onClick={() => openEdit(coupon)}>
                        <Pencil className="size-4" />
                      </Button>
                      <Button variant="ghost" size="sm" onClick={() => setRemoving(coupon)}>
                        <Trash2 className="size-4" />
                      </Button>
                    </div>
                  </Td>
                </Tr>
              ))}
            </tbody>
          </Table>
        )}
      </Card>

      <Modal
        open={creating || Boolean(editing)}
        onClose={() => {
          setCreating(false)
          setEditing(null)
        }}
        title={editing ? `Edit ${editing.code}` : "Create coupon"}
        description="Coupons apply at checkout for eligible paid plans."
        footer={
          <div className="flex justify-end gap-2">
            <Button variant="secondary" onClick={() => { setCreating(false); setEditing(null); }} disabled={saving}>
              Cancel
            </Button>
            <Button onClick={handleSubmit(onSubmit)} loading={saving}>
              Save
            </Button>
          </div>
        }
      >
        <form className="space-y-4" onSubmit={handleSubmit(onSubmit)}>
          <FormRow label="Code" error={errors.code?.message}>
            <Input {...register("code")} placeholder="WELCOME20" />
          </FormRow>
          <div className="grid gap-4 sm:grid-cols-2">
            <FormRow label="Type" error={errors.type?.message}>
              <Select {...register("type")}>
                <option value="percentage">Percentage</option>
                <option value="fixed">Fixed amount</option>
              </Select>
            </FormRow>
            <FormRow label="Value" error={errors.value?.message}>
              <Input type="number" min="0" {...register("value")} />
            </FormRow>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <FormRow label="Minimum amount" error={errors.min_amount?.message}>
              <Input type="number" min="0" {...register("min_amount")} placeholder="Optional" />
            </FormRow>
            <FormRow label="Max uses" error={errors.max_uses?.message}>
              <Input type="number" min="0" {...register("max_uses")} placeholder="Unlimited" />
            </FormRow>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <FormRow label="Starts at" error={errors.starts_at?.message}>
              <Input type="date" {...register("starts_at")} />
            </FormRow>
            <FormRow label="Expires at" error={errors.expires_at?.message}>
              <Input type="date" {...register("expires_at")} />
            </FormRow>
          </div>
          <FormRow label="Active">
            <label className="flex items-center gap-2 text-sm text-slate-600">
              <input type="checkbox" {...register("is_active")} className="rounded border-slate-300" />
              Enable this coupon
            </label>
          </FormRow>
        </form>
      </Modal>

      <ConfirmDialog
        open={Boolean(removing)}
        title="Delete coupon"
        description={removing ? `Delete ${removing.code}? This action cannot be undone.` : ""}
        confirmLabel="Delete"
        tone="danger"
        onConfirm={confirmDelete}
        onClose={() => setRemoving(null)}
        loading={saving}
      />
    </div>
  )
}
