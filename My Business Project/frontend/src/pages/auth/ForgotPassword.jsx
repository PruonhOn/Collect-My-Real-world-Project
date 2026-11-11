import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { authService } from '../../services/authService'
import { apiErrorMessage } from '../../api/client'
import { Alert } from '../../components/ui/Feedback'
import { Button } from '../../components/ui/Button'
import { FormRow, Input } from '../../components/ui/Form'

const schema = z.object({
  email: z.string().min(1, 'Email is required.').email('Enter a valid email address.'),
})

export default function ForgotPassword() {
  const [sent, setSent] = useState(false)
  const [serverError, setServerError] = useState(null)

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { email: '' },
  })

  const onSubmit = async (values) => {
    setServerError(null)

    try {
      const result = await authService.sendResetLink(values.email)
      setSent(true)
      toast.success(result?.message ?? 'Reset link sent.')
    } catch (error) {
      setServerError(apiErrorMessage(error, 'Unable to send the reset link.'))
    }
  }

  return (
    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
      <h1 className="text-xl font-semibold tracking-tight text-slate-900">Reset your password</h1>
      <p className="mt-1 text-sm text-slate-500">
        We will email you a secure link to choose a new password.
      </p>

      {serverError && (
        <div className="mt-4">
          <Alert tone="danger">{serverError}</Alert>
        </div>
      )}

      {sent ? (
        <div className="mt-6 space-y-4">
          <Alert tone="success" title="Check your inbox">
            If an account exists for that address, a password reset link is on its way.
          </Alert>
          <Link
            to="/login"
            className="block rounded-lg bg-brand-600 px-4 py-2 text-center text-sm font-medium text-white transition hover:bg-brand-700"
          >
            Back to sign in
          </Link>
        </div>
      ) : (
        <form className="mt-6 space-y-4" onSubmit={handleSubmit(onSubmit)} noValidate>
          <FormRow label="Email address" htmlFor="email" error={errors.email?.message}>
            <Input id="email" type="email" autoComplete="email" {...register('email')} />
          </FormRow>

          <Button type="submit" className="w-full" loading={isSubmitting}>
            Send reset link
          </Button>

          <Link to="/login" className="block text-center text-sm text-brand-700 hover:underline">
            Back to sign in
          </Link>
        </form>
      )}
    </div>
  )
}
