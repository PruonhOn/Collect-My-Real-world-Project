import { useCallback, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { Layers, Pencil, Plus, Trash2 } from 'lucide-react'
import { adminService } from '../../services/adminService'
import { useApi } from '../../hooks/useApi'
import { apiErrorMessage } from '../../api/client'
import { formatMoney } from '../../utils/format'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock } from '../../components/ui/Feedback'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { FormRow, Input, Select, Textarea } from '../../components/ui/Form'
import { ConfirmDialog, Modal } from '../../components/ui/Modal'
import { EmptyState } from '../../components/ui/Table'

const schema = z.object({
  name: z.string().min(2, 'Enter a plan name.').max(255),
  slug: z.string().min(2, 'Enter a slug.').max(255).regex(/^[a-z0-9-]+$/, 'Use lowercase letters, numbers and dashes.'),
  description: z.string().max(2000).optional(),
  type: z.enum(['free', 'pro']),
  price_monthly: z.coerce.number().int().min(0),
  price_yearly: z.coerce.number().int().min(0),
  currency: z.string().length(3),
  is_active: z.boolean(),
  sort_order: z.coerce.number().int().min(0),
  trial_enabled: z.boolean(),
  trial_days: z.coerce.number().int().min(0).max(365),
})

const emptyValues = {
  name: '',
  slug: '',
  description: '',
  type: 'pro',
  price_monthly: 0,
  price_yearly: 0,
  currency: 'USD',
  is_active: true,
  sort_order: 0,
  trial_enabled: true,
  trial_days: 14,
}

export default function AdminPlans() {
  const [editing, setEditing] = useState(null)
  const [creating, setCreating] = useState(false)
  const [removing, setRemoving] = useState(null)
  const [saving, setSaving] = useState(false)
  const [formError, setFormError] = useState(null)
  const [featureState, setFeatureState] = useState({})

  const loader = useCallback(() => adminService.plans(), [])
  const { data, loading, error, reload } = useApi(loader, [loader])

  const plans = data ?? []

  const {
    register,
    handleSubmit,
    reset,
    setValue,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: emptyValues,
  })

  const openCreate = () => {
    setFormError(null)
    setFeatureState({})
    reset(emptyValues)
    setCreating(true)
  }

  const openEdit = (plan) => {
    setFormError(null)
    const features = {}

    ;(plan.features ?? []).forEach((feature) => {
      features[feature.feature_id] = { enabled: feature.enabled, limit: feature.limit }
    })

    setFeatureState(features)
    reset({
      name: plan.name,
      slug: plan.slug,
      description: plan.description ?? '',
      type: plan.type,
      price_monthly: plan.price_monthly,
      price_yearly: plan.price_yearly,
      currency: plan.currency,
      is_active: plan.is_active,
      sort_order: plan.sort_order,
      trial_enabled: plan.trial_enabled,
      trial_days: plan.trial_days,
    })
    setEditing(plan)
  }

  const submit = async (values) => {
    setSaving(true)
    setFormError(null)

    const payload = {
      ...values,
      description: values.description || null,
      features: Object.entries(featureState).map(([featureId, state]) => ({
        feature_id: Number(featureId),
        enabled: state.enabled,
        limit_value: state.limit === '' || state.limit === null ? null : Number(state.limit),
      })),
    }

    try {
      if (editing) {
        await adminService.updatePlan(editing.id, payload)
        toast.success('Plan updated.')
      } else {
        await adminService.createPlan(payload)
        toast.success('Plan created.')
      }

      setCreating(false)
      setEditing(null)
      reload()
    } catch (exception) {
      setFormError(apiErrorMessage(exception, 'Unable to save the plan.'))
    } finally {
      setSaving(false)
    }
  }

  const confirmDelete = async () => {
    if (!removing) {
      return
    }

    try {
      await adminService.deletePlan(removing.id)
      toast.success('Plan deleted.')
      setRemoving(null)
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to delete the plan.'))
    }
  }

  const toggleFeature = (featureId, enabled) => {
    setFeatureState((current) => ({
      ...current,
      [featureId]: { enabled, limit: current[featureId]?.limit ?? null },
    }))
  }

  const setFeatureLimit = (featureId, limit) => {
    setFeatureState((current) => ({
      ...current,
      [featureId]: { enabled: current[featureId]?.enabled ?? true, limit },
    }))
  }

  const featureCatalog = (plans.find((plan) => (plan.features ?? []).length > 0)?.features ?? [])

  return (
    <div>
      <PageHeader
        title="Plans"
        description="Pricing, trial policy and the feature matrix that drives entitlements."
        actions={
          <Button onClick={openCreate}>
            <Plus className="size-4" />
            New plan
          </Button>
        }
      />

      {loading && <LoadingBlock />}

      {error && !loading && (
        <div className="mb-4">
          <Alert tone="danger">{apiErrorMessage(error)}</Alert>
        </div>
      )}

      {!loading && !error && plans.length === 0 && (
        <Card>
          <EmptyState
            icon={Layers}
            title="No plans yet"
            description="Create the free plan first, then add paid tiers."
            action={<Button onClick={openCreate}>New plan</Button>}
          />
        </Card>
      )}

      <div className="grid gap-5 lg:grid-cols-3">
        {plans.map((plan) => (
          <Card key={plan.id}>
            <div className="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
              <div>
                <div className="flex items-center gap-2">
                  <h3 className="text-base font-semibold text-slate-900">{plan.name}</h3>
                  <Badge tone={plan.type === 'free' ? 'neutral' : 'brand'}>{plan.type}</Badge>
                </div>
                <p className="mt-1 text-xs text-slate-500">{plan.slug}</p>
              </div>
              <Badge tone={plan.is_active ? 'success' : 'danger'}>{plan.is_active ? 'active' : 'inactive'}</Badge>
            </div>

            <div className="space-y-2 px-5 py-4 text-sm">
              <div className="flex items-center justify-between">
                <span className="text-slate-500">Monthly</span>
                <span className="font-semibold text-slate-900">
                  {formatMoney(plan.price_monthly, plan.currency)}
                </span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-slate-500">Yearly</span>
                <span className="font-semibold text-slate-900">
                  {formatMoney(plan.price_yearly, plan.currency)}
                </span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-slate-500">Trial</span>
                <span className="font-semibold text-slate-900">
                  {plan.trial_enabled ? `${plan.trial_days} days` : 'disabled'}
                </span>
              </div>
            </div>

            <div className="border-t border-slate-100 px-5 py-3">
              <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Features</p>
              <div className="flex flex-wrap gap-1.5">
                {(plan.features ?? []).length === 0 && (
                  <span className="text-xs text-slate-400">No features attached</span>
                )}
                {(plan.features ?? []).map((feature) => (
                  <Badge key={feature.feature_id} tone={feature.enabled ? 'info' : 'neutral'}>
                    {feature.key}
                    {feature.limit !== null ? ` (${feature.limit})` : ''}
                  </Badge>
                ))}
              </div>
            </div>

            <div className="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-3">
              <Button variant="ghost" size="sm" onClick={() => openEdit(plan)}>
                <Pencil className="size-4" />
                Edit
              </Button>
              <Button variant="ghost" size="sm" onClick={() => setRemoving(plan)}>
                <Trash2 className="size-4" />
                Delete
              </Button>
            </div>
          </Card>
        ))}
      </div>

      <Modal
        open={creating || Boolean(editing)}
        onClose={() => {
          setCreating(false)
          setEditing(null)
        }}
        title={editing ? `Edit ${editing.name}` : 'Create plan'}
        description="Prices are stored in minor units (cents)."
        size="lg"
        footer={
          <>
            <Button
              variant="secondary"
              onClick={() => {
                setCreating(false)
                setEditing(null)
              }}
              disabled={saving}
            >
              Cancel
            </Button>
            <Button onClick={handleSubmit(submit)} loading={saving}>
              {editing ? 'Save changes' : 'Create plan'}
            </Button>
          </>
        }
      >
        {formError && (
          <div className="mb-4">
            <Alert tone="danger">{formError}</Alert>
          </div>
        )}

        <form className="space-y-4" onSubmit={handleSubmit(submit)} noValidate>
          <div className="grid gap-4 sm:grid-cols-2">
            <FormRow label="Name" htmlFor="plan-name" error={errors.name?.message}>
              <Input
                id="plan-name"
                {...register('name', {
                  onChange: (event) => {
                    if (!editing) {
                      setValue(
                        'slug',
                        event.target.value
                          .toLowerCase()
                          .replace(/[^a-z0-9]+/g, '-')
                          .replace(/^-|-$/g, ''),
                      )
                    }
                  },
                })}
              />
            </FormRow>

            <FormRow label="Slug" htmlFor="plan-slug" error={errors.slug?.message}>
              <Input id="plan-slug" {...register('slug')} />
            </FormRow>

            <FormRow label="Type" htmlFor="plan-type" error={errors.type?.message}>
              <Select id="plan-type" {...register('type')}>
                <option value="free">Free</option>
                <option value="pro">Pro</option>
              </Select>
            </FormRow>

            <FormRow label="Currency" htmlFor="plan-currency" error={errors.currency?.message}>
              <Input id="plan-currency" maxLength={3} {...register('currency')} />
            </FormRow>

            <FormRow label="Monthly price" htmlFor="price-monthly" error={errors.price_monthly?.message}>
              <Input id="price-monthly" type="number" min="0" step="1" {...register('price_monthly')} />
            </FormRow>

            <FormRow label="Yearly price" htmlFor="price-yearly" error={errors.price_yearly?.message}>
              <Input id="price-yearly" type="number" min="0" step="1" {...register('price_yearly')} />
            </FormRow>

            <FormRow label="Sort order" htmlFor="plan-sort" error={errors.sort_order?.message}>
              <Input id="plan-sort" type="number" min="0" {...register('sort_order')} />
            </FormRow>

            <FormRow label="Trial days" htmlFor="plan-trial-days" error={errors.trial_days?.message}>
              <Input id="plan-trial-days" type="number" min="0" max="365" {...register('trial_days')} />
            </FormRow>
          </div>

          <FormRow label="Description" htmlFor="plan-description" error={errors.description?.message}>
            <Textarea id="plan-description" rows={2} {...register('description')} />
          </FormRow>

          <div className="flex flex-wrap gap-4">
            <label className="flex items-center gap-2 text-sm text-slate-700">
              <input type="checkbox" className="size-4 rounded border-slate-300" {...register('is_active')} />
              Plan is purchasable
            </label>
            <label className="flex items-center gap-2 text-sm text-slate-700">
              <input type="checkbox" className="size-4 rounded border-slate-300" {...register('trial_enabled')} />
              Trial enabled
            </label>
          </div>

          {featureCatalog.length > 0 && (
            <div className="rounded-lg border border-slate-200 p-4">
              <p className="mb-3 text-sm font-semibold text-slate-800">Feature matrix</p>
              <div className="space-y-2">
                {featureCatalog.map((feature) => {
                  const state = featureState[feature.feature_id]

                  return (
                    <div key={feature.feature_id} className="flex items-center gap-3 text-sm">
                      <label className="flex flex-1 items-center gap-2 text-slate-700">
                        <input
                          type="checkbox"
                          className="size-4 rounded border-slate-300"
                          checked={state?.enabled ?? false}
                          onChange={(event) => toggleFeature(feature.feature_id, event.target.checked)}
                        />
                        <span className="font-mono text-xs">{feature.key}</span>
                        <span className="text-xs text-slate-400">{feature.name}</span>
                      </label>
                      <input
                        type="number"
                        min="0"
                        placeholder="limit"
                        value={state?.limit ?? ''}
                        onChange={(event) => setFeatureLimit(feature.feature_id, event.target.value)}
                        className="w-24 rounded-lg border border-slate-300 px-2 py-1 text-xs outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                      />
                    </div>
                  )
                })}
              </div>
            </div>
          )}
        </form>
      </Modal>

      <ConfirmDialog
        open={Boolean(removing)}
        onClose={() => setRemoving(null)}
        onConfirm={confirmDelete}
        title="Delete plan"
        description={
          removing
            ? `${removing.name} can only be deleted when no live subscription uses it.`
            : ''
        }
        confirmLabel="Delete"
      />
    </div>
  )
}
