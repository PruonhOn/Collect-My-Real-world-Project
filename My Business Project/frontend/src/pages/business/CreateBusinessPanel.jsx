import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { useBusiness } from '../../context/BusinessContext'
import { apiErrorMessage, apiFieldErrors } from '../../api/client'
import { Alert } from '../../components/ui/Feedback'
import { Button } from '../../components/ui/Button'
import { Card, CardBody, CardHeader } from '../../components/ui/Card'
import { FormRow, Input, Select } from '../../components/ui/Form'

const schema = z.object({
  name: z.string().min(2, 'Enter a business name.').max(255),
  business_type: z.string().optional(),
  country: z.string().optional(),
  currency: z.string().length(3, 'Use a 3 letter currency code.'),
  timezone: z.string().optional(),
  phone: z.string().optional(),
  email: z.string().email('Enter a valid email address.').or(z.literal('')).optional(),
  website: z.string().url('Enter a valid URL.').or(z.literal('')).optional(),
  address: z.string().optional(),
})

export default function CreateBusinessPanel() {
  const { createBusiness } = useBusiness()
  const [serverError, setServerError] = useState(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: {
      name: '',
      business_type: '',
      country: '',
      currency: 'USD',
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
      phone: '',
      email: '',
      website: '',
      address: '',
    },
  })

  const onSubmit = async (values) => {
    setServerError(null)

    try {
      const business = await createBusiness(Object.fromEntries(Object.entries(values).filter(([, value]) => value !== '')))
      toast.success(`${business.name} is ready.`)
    } catch (error) {
      const fieldErrors = apiFieldErrors(error)

      Object.entries(fieldErrors).forEach(([field, messages]) => {
        setError(field, { message: messages[0] })
      })

      if (Object.keys(fieldErrors).length === 0) {
        setServerError(apiErrorMessage(error, 'Unable to create the business.'))
      }
    }
  }

  return (
    <div className="mx-auto max-w-2xl">
      <Card>
        <CardHeader
          title="Create your business workspace"
          description="Every subscription, product, customer and invoice lives inside a business workspace."
        />
        <CardBody>
          {serverError && (
            <div className="mb-4">
              <Alert tone="danger">{serverError}</Alert>
            </div>
          )}

          <form className="grid gap-4 sm:grid-cols-2" onSubmit={handleSubmit(onSubmit)} noValidate>
            <div className="sm:col-span-2">
              <FormRow label="Business name" htmlFor="name" error={errors.name?.message}>
                <Input id="name" invalid={Boolean(errors.name)} {...register('name')} />
              </FormRow>
            </div>

            <FormRow label="Business type" htmlFor="business_type" error={errors.business_type?.message}>
              <Input id="business_type" placeholder="Retail, Services…" {...register('business_type')} />
            </FormRow>

            <FormRow label="Country" htmlFor="country" error={errors.country?.message}>
              <Input id="country" {...register('country')} />
            </FormRow>

            <FormRow label="Currency" htmlFor="currency" error={errors.currency?.message}>
              <Select id="currency" {...register('currency')}>
                <option value="USD">USD - US Dollar</option>
                <option value="EUR">EUR - Euro</option>
                <option value="GBP">GBP - British Pound</option>
                <option value="KHR">KHR - Cambodian Riel</option>
                <option value="AUD">AUD - Australian Dollar</option>
              </Select>
            </FormRow>

            <FormRow label="Timezone" htmlFor="timezone" error={errors.timezone?.message}>
              <Input id="timezone" {...register('timezone')} />
            </FormRow>

            <FormRow label="Business email" htmlFor="email" error={errors.email?.message}>
              <Input id="email" type="email" {...register('email')} />
            </FormRow>

            <FormRow label="Phone" htmlFor="phone" error={errors.phone?.message}>
              <Input id="phone" {...register('phone')} />
            </FormRow>

            <div className="sm:col-span-2">
              <FormRow label="Website" htmlFor="website" error={errors.website?.message}>
                <Input id="website" placeholder="https://" {...register('website')} />
              </FormRow>
            </div>

            <div className="sm:col-span-2">
              <FormRow label="Address" htmlFor="address" error={errors.address?.message}>
                <Input id="address" {...register('address')} />
              </FormRow>
            </div>

            <div className="sm:col-span-2">
              <Button type="submit" loading={isSubmitting}>
                Create business
              </Button>
            </div>
          </form>
        </CardBody>
      </Card>
    </div>
  )
}
