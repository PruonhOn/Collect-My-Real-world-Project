import { useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { useAuth } from '../../context/AuthContext'
import { apiErrorMessage, apiFieldErrors } from '../../api/client'
import { Alert } from '../../components/ui/Feedback'
import { Button } from '../../components/ui/Button'
import { FormRow, Input } from '../../components/ui/Form'

const schema = z.object({
  email: z.string().min(1, 'Email is required.').email('Enter a valid email address.'),
  password: z.string().min(1, 'Password is required.'),
})

export default function Login() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [serverError, setServerError] = useState(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '' },
  })

  const onSubmit = async (values) => {
    setServerError(null)

    try {
      await login(values)
      toast.success('Welcome back!')
      navigate(location.state?.from ?? '/dashboard', { replace: true })
    } catch (error) {
      const fieldErrors = apiFieldErrors(error)

      Object.entries(fieldErrors).forEach(([field, messages]) => {
        setError(field, { message: messages[0] })
      })

      if (Object.keys(fieldErrors).length === 0) {
        setServerError(apiErrorMessage(error, 'Unable to sign in.'))
      }
    }
  }

  return (
    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
      <h1 className="text-xl font-semibold tracking-tight text-slate-900">Sign in</h1>
      <p className="mt-1 text-sm text-slate-500">Access your business workspace.</p>

      {serverError && (
        <div className="mt-4">
          <Alert tone="danger">{serverError}</Alert>
        </div>
      )}

      <form className="mt-6 space-y-4" onSubmit={handleSubmit(onSubmit)} noValidate>
        <FormRow label="Email address" htmlFor="email" error={errors.email?.message}>
          <Input
            id="email"
            type="email"
            autoComplete="email"
            placeholder="you@company.com"
            invalid={Boolean(errors.email)}
            {...register('email')}
          />
        </FormRow>

        <FormRow label="Password" htmlFor="password" error={errors.password?.message}>
          <Input
            id="password"
            type="password"
            autoComplete="current-password"
            invalid={Boolean(errors.password)}
            {...register('password')}
          />
        </FormRow>

        <div className="flex items-center justify-between">
          <Link to="/forgot-password" className="text-sm text-brand-700 hover:underline">
            Forgot password?
          </Link>
        </div>

        <Button type="submit" className="w-full" loading={isSubmitting}>
          Sign in
        </Button>
      </form>

      <p className="mt-6 text-center text-sm text-slate-500">
        No account yet?{' '}
        <Link to="/register" className="font-medium text-brand-700 hover:underline">
          Create one
        </Link>
      </p>
    </div>
  )
}
