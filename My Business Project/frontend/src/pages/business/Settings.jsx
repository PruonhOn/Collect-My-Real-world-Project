import { useCallback, useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { businessService } from '../../services/businessService'
import { useBusiness } from '../../context/BusinessContext'
import { useAuth } from '../../context/AuthContext'
import { useApi } from '../../hooks/useApi'
import { apiErrorMessage, apiFieldErrors } from '../../api/client'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock } from '../../components/ui/Feedback'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card, CardBody, CardHeader } from '../../components/ui/Card'
import { FormRow, Input, Select } from '../../components/ui/Form'

const profileSchema = z.object({
  name: z.string().min(2, 'Enter a business name.').max(255),
  business_type: z.string().max(100).optional(),
  country: z.string().max(100).optional(),
  currency: z.string().length(3, 'Use a 3 letter currency code.'),
  timezone: z.string().max(64).optional(),
  address: z.string().max(500).optional(),
  phone: z.string().max(64).optional(),
  email: z.string().email('Enter a valid email address.').or(z.literal('')).optional(),
  website: z.string().url('Enter a valid URL.').or(z.literal('')).optional(),
})

const settingsSchema = z.object({
  invoice_template: z.enum(['platform', 'branded']),
  tax_rate: z.coerce.number().int().min(0).max(10000),
  date_format: z.enum(['Y-m-d', 'd/m/Y', 'm/d/Y']),
  low_stock_alerts: z.boolean(),
  auto_invoice: z.boolean(),
})

export default function Settings() {
  const { activeBusiness, activeBusinessId, refreshBusinesses } = useBusiness()
  const { user } = useAuth()

  const [savingProfile, setSavingProfile] = useState(false)
  const [savingSettings, setSavingSettings] = useState(false)
  const [profileError, setProfileError] = useState(null)
  const [settingsError, setSettingsError] = useState(null)

  const businessLoader = useCallback(
    () => (activeBusinessId ? businessService.business(activeBusinessId) : Promise.resolve(null)),
    [activeBusinessId],
  )
  const { data: business, loading: businessLoading } = useApi(businessLoader, [businessLoader])

  const settingsLoader = useCallback(() => businessService.settings(), [])
  const { data: settingsData, loading: settingsLoading } = useApi(settingsLoader, [settingsLoader])

  const profileForm = useForm({
    resolver: zodResolver(profileSchema),
    defaultValues: {
      name: '',
      business_type: '',
      country: '',
      currency: 'USD',
      timezone: 'UTC',
      address: '',
      phone: '',
      email: '',
      website: '',
    },
  })

  const settingsForm = useForm({
    resolver: zodResolver(settingsSchema),
    defaultValues: {
      invoice_template: 'platform',
      tax_rate: 0,
      date_format: 'Y-m-d',
      low_stock_alerts: false,
      auto_invoice: false,
    },
  })

  useEffect(() => {
    if (!business) {
      return
    }

    profileForm.reset({
      name: business.name ?? '',
      business_type: business.business_type ?? '',
      country: business.country ?? '',
      currency: business.currency ?? 'USD',
      timezone: business.timezone ?? 'UTC',
      address: business.address ?? '',
      phone: business.phone ?? '',
      email: business.email ?? '',
      website: business.website ?? '',
    })
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [business])

  useEffect(() => {
    if (!settingsData?.settings) {
      return
    }

    settingsForm.reset({
      invoice_template: settingsData.settings.invoice_template ?? 'platform',
      tax_rate: settingsData.settings.tax_rate ?? 0,
      date_format: settingsData.settings.date_format ?? 'Y-m-d',
      low_stock_alerts: Boolean(settingsData.settings.low_stock_alerts),
      auto_invoice: Boolean(settingsData.settings.auto_invoice),
    })
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [settingsData])

  const saveProfile = async (values) => {
    setSavingProfile(true)
    setProfileError(null)

    try {
      await businessService.updateBusiness(activeBusinessId, {
        ...values,
        business_type: values.business_type || null,
        country: values.country || null,
        address: values.address || null,
        phone: values.phone || null,
        email: values.email || null,
        website: values.website || null,
      })

      await refreshBusinesses()
      toast.success('Business profile updated.')
    } catch (exception) {
      const fieldErrors = apiFieldErrors(exception)

      Object.entries(fieldErrors).forEach(([field, messages]) => {
        profileForm.setError(field, { message: messages[0] })
      })

      if (Object.keys(fieldErrors).length === 0) {
        setProfileError(apiErrorMessage(exception, 'Unable to update the business.'))
      }
    } finally {
      setSavingProfile(false)
    }
  }

  const saveSettings = async (values) => {
    setSavingSettings(true)
    setSettingsError(null)

    try {
      await businessService.updateSettings(values)
      toast.success('Workspace settings saved.')
    } catch (exception) {
      setSettingsError(apiErrorMessage(exception, 'Unable to save the settings.'))
    } finally {
      setSavingSettings(false)
    }
  }

  if (businessLoading || settingsLoading) {
    return <LoadingBlock label="Loading settings…" />
  }

  const profileErrors = profileForm.formState.errors
  const settingsErrors = settingsForm.formState.errors

  return (
    <div>
      <PageHeader
        title="Settings"
        description="Business profile, workspace preferences and your account."
      />

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader title="Business profile" description="Shown on invoices and customer documents." />
          <CardBody>
            {profileError && (
              <div className="mb-4">
                <Alert tone="danger">{profileError}</Alert>
              </div>
            )}

            <form className="space-y-4" onSubmit={profileForm.handleSubmit(saveProfile)} noValidate>
              <FormRow label="Business name" htmlFor="name" error={profileErrors.name?.message}>
                <Input id="name" {...profileForm.register('name')} />
              </FormRow>

              <div className="grid gap-4 sm:grid-cols-2">
                <FormRow label="Business type" htmlFor="business_type" error={profileErrors.business_type?.message}>
                  <Input id="business_type" {...profileForm.register('business_type')} />
                </FormRow>

                <FormRow label="Country" htmlFor="country" error={profileErrors.country?.message}>
                  <Input id="country" {...profileForm.register('country')} />
                </FormRow>

                <FormRow label="Currency" htmlFor="currency" error={profileErrors.currency?.message}>
                  <Select id="currency" {...profileForm.register('currency')}>
                    <option value="USD">USD - US Dollar</option>
                    <option value="EUR">EUR - Euro</option>
                    <option value="GBP">GBP - British Pound</option>
                    <option value="KHR">KHR - Cambodian Riel</option>
                    <option value="AUD">AUD - Australian Dollar</option>
                  </Select>
                </FormRow>

                <FormRow label="Timezone" htmlFor="timezone" error={profileErrors.timezone?.message}>
                  <Input id="timezone" {...profileForm.register('timezone')} />
                </FormRow>

                <FormRow label="Business email" htmlFor="email" error={profileErrors.email?.message}>
                  <Input id="email" type="email" {...profileForm.register('email')} />
                </FormRow>

                <FormRow label="Phone" htmlFor="phone" error={profileErrors.phone?.message}>
                  <Input id="phone" {...profileForm.register('phone')} />
                </FormRow>
              </div>

              <FormRow label="Website" htmlFor="website" error={profileErrors.website?.message}>
                <Input id="website" placeholder="https://" {...profileForm.register('website')} />
              </FormRow>

              <FormRow label="Address" htmlFor="address" error={profileErrors.address?.message}>
                <Input id="address" {...profileForm.register('address')} />
              </FormRow>

              <Button type="submit" loading={savingProfile}>
                Save profile
              </Button>
            </form>
          </CardBody>
        </Card>

        <div className="space-y-6">
          <Card>
            <CardHeader title="Workspace settings" description="Tax, invoice template and automation preferences." />
            <CardBody>
              {settingsError && (
                <div className="mb-4">
                  <Alert tone="danger">{settingsError}</Alert>
                </div>
              )}

              <form className="space-y-4" onSubmit={settingsForm.handleSubmit(saveSettings)} noValidate>
                <FormRow label="Invoice template" htmlFor="invoice_template" error={settingsErrors.invoice_template?.message}>
                  <Select id="invoice_template" {...settingsForm.register('invoice_template')}>
                    <option value="platform">Platform branding</option>
                    <option value="branded">Business branding</option>
                  </Select>
                </FormRow>

                <FormRow
                  label="Default tax rate"
                  htmlFor="tax_rate"
                  hint="in basis points"
                  error={settingsErrors.tax_rate?.message}
                >
                  <Input id="tax_rate" type="number" min="0" max="10000" {...settingsForm.register('tax_rate')} />
                </FormRow>

                <FormRow label="Date format" htmlFor="date_format" error={settingsErrors.date_format?.message}>
                  <Select id="date_format" {...settingsForm.register('date_format')}>
                    <option value="Y-m-d">2026-01-31</option>
                    <option value="d/m/Y">31/01/2026</option>
                    <option value="m/d/Y">01/31/2026</option>
                  </Select>
                </FormRow>

                <FormRow label="Low stock alerts" htmlFor="low_stock_alerts">
                  <label className="flex items-center gap-2 text-sm text-slate-700">
                    <input
                      id="low_stock_alerts"
                      type="checkbox"
                      className="size-4 rounded border-slate-300"
                      {...settingsForm.register('low_stock_alerts')}
                    />
                    Notify the team when stock runs low
                  </label>
                </FormRow>

                <FormRow label="Automatic invoices" htmlFor="auto_invoice">
                  <label className="flex items-center gap-2 text-sm text-slate-700">
                    <input
                      id="auto_invoice"
                      type="checkbox"
                      className="size-4 rounded border-slate-300"
                      {...settingsForm.register('auto_invoice')}
                    />
                    Create an invoice for every completed order
                  </label>
                </FormRow>

                <Button type="submit" loading={savingSettings}>
                  Save settings
                </Button>
              </form>
            </CardBody>
          </Card>

          <Card>
            <CardHeader title="Your account" description="The identity that owns this workspace." />
            <CardBody className="space-y-3 text-sm">
              <div className="flex items-center justify-between">
                <span className="text-slate-500">Name</span>
                <span className="font-medium text-slate-900">{user?.name}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-slate-500">Email</span>
                <span className="font-medium text-slate-900">{user?.email}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-slate-500">Email verified</span>
                {user?.email_verified_at ? (
                  <Badge tone="success">verified</Badge>
                ) : (
                  <Badge tone="warning">not verified</Badge>
                )}
              </div>
              <div className="flex items-center justify-between">
                <span className="text-slate-500">Role in this business</span>
                <Badge tone="brand">{activeBusiness?.role ?? '—'}</Badge>
              </div>
            </CardBody>
          </Card>
        </div>
      </div>
    </div>
  )
}
