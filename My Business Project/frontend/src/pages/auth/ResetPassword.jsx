import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { authService } from '../../services/authService'
import { apiErrorMessage } from '../../api/client'
import { Alert } from '../../components/ui/Feedback'
import { Button } from '../../components/ui/Button'
import { FormRow, Input } from '../../components/ui/Form'

const schema = z
  .object({
    password: z.string().min(8, 'Use at least 8 characters.'),
    password_confirmation: z.string().min(1, 'Confirm your password.'),
  })
  .refine((values) => values.password === values.password_confirmation, {
    message: 'Passwords do not match.',
    path: ['password_confirmation'],
  })

export default function ResetPassword() {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const [serverError, setServerError] = useState(null)

  const token = searchParams.get('token') ?? ''
  const email = searchParams.get('email') ?? ''

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { password: '', password_confirmation: '' },
  })

  const onSubmit = async (values) => {
    setServerError(null)

    try {
      await authService.resetPassword({
        token,
        email,
        password: values.password,
        password_confirmation: values.password_confirmation,
      })
      toast.success('Your password has been reset. Please sign in.')
      navigate('/login', { replace: true })
    } catch (error) {
      setServerError(apiErrorMessage(error, 'Unable to reset your password.'))
    }
  }

  if (!token || !email) {
    return (
      <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h1 className="text-xl font-semibold tracking-tight text-slate-900">Invalid reset link</h1>
        <div className="mt-4">
          <Alert tone="danger" title="Missing reset token">
            This reset link is incomplete. Request a new link to continue.
          </Alert>
        </div>
        <Link
          to="/forgot-password"
          className="mt-6 block rounded-lg bg-brand-600 px-4 py-2 text-center text-sm font-medium text-white transition hover:bg-brand-700"
        >
          Request a new link
        </Link>
      </div>
    )
  }

  return (
    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
      <h1 className="text-xl font-semibold tracking-tight text-slate-900">Choose a new password</h1>
      <p className="mt-1 text-sm text-slate-500">Signing in with {email}.</p>

      {serverError && (
        <div className="mt-4">
          <Alert tone="danger">{serverError}</Alert>
        </div>
      )}

      <form className="mt-6 space-y-4" onSubmit={handleSubmit(onSubmit)} noValidate>
        <FormRow label="New password" htmlFor="password" hint="min. 8 characters" error={errors.password?.message}>
          <Input
            id="password"
            type="password"
            autoComplete="new-password"
            invalid={Boolean(errors.password)}
            {...register('password')}
          />
        </FormRow>

        <FormRow
          label="Confirm new password"
          htmlFor="password_confirmation"
          error={errors.password_confirmation?.message}
        >
          <Input
            id="password_confirmation"
            type="password"
            autoComplete="new-password"
            invalid={Boolean(errors.password_confirmation)}
            {...register('password_confirmation')}
          />
        </FormRow>

        <Button type="submit" className="w-full" loading={isSubmitting}>
          Update password
        </Button>
      </form>
    </div>
  )
}
