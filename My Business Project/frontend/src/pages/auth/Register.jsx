import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { useAuth } from '../../context/AuthContext'
import { apiErrorMessage, apiFieldErrors } from '../../api/client'
import { Alert } from '../../components/ui/Feedback'
import { Button } from '../../components/ui/Button'
import { FormRow, Input } from '../../components/ui/Form'

const schema = z
  .object({
    name: z.string().min(2, 'Enter your name.').max(255),
    email: z.string().min(1, 'Email is required.').email('Enter a valid email address.'),
    password: z.string().min(8, 'Use at least 8 characters.'),
    password_confirmation: z.string().min(1, 'Confirm your password.'),
  })
  .refine((values) => values.password === values.password_confirmation, {
    message: 'Passwords do not match.',
    path: ['password_confirmation'],
  })

export default function Register() {
  const { register: registerAccount } = useAuth()
  const navigate = useNavigate()
  const [serverError, setServerError] = useState(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { name: '', email: '', password: '', password_confirmation: '' },
  })

  const onSubmit = async (values) => {
    setServerError(null)

    try {
      await registerAccount(values)
      toast.success('Your account is ready. Welcome to My Business!')
      navigate('/dashboard', { replace: true })
    } catch (error) {
      const fieldErrors = apiFieldErrors(error)

      Object.entries(fieldErrors).forEach(([field, messages]) => {
        setError(field, { message: messages[0] })
      })

      if (Object.keys(fieldErrors).length === 0) {
        setServerError(apiErrorMessage(error, 'Unable to create your account.'))
      }
    }
  }

  return (
    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
      <h1 className="text-xl font-semibold tracking-tight text-slate-900">Create your account</h1>
      <p className="mt-1 text-sm text-slate-500">Start with a free workspace, upgrade whenever you are ready.</p>

      {serverError && (
        <div className="mt-4">
          <Alert tone="danger">{serverError}</Alert>
        </div>
      )}

      <form className="mt-6 space-y-4" onSubmit={handleSubmit(onSubmit)} noValidate>
        <FormRow label="Full name" htmlFor="name" error={errors.name?.message}>
          <Input id="name" autoComplete="name" invalid={Boolean(errors.name)} {...register('name')} />
        </FormRow>

        <FormRow label="Email address" htmlFor="email" error={errors.email?.message}>
          <Input
            id="email"
            type="email"
            autoComplete="email"
            invalid={Boolean(errors.email)}
            {...register('email')}
          />
        </FormRow>

        <FormRow label="Password" htmlFor="password" hint="min. 8 characters" error={errors.password?.message}>
          <Input
            id="password"
            type="password"
            autoComplete="new-password"
            invalid={Boolean(errors.password)}
            {...register('password')}
          />
        </FormRow>

        <FormRow
          label="Confirm password"
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
          Create account
        </Button>
      </form>

      <p className="mt-6 text-center text-sm text-slate-500">
        Already registered?{' '}
        <Link to="/login" className="font-medium text-brand-700 hover:underline">
          Sign in
        </Link>
      </p>
    </div>
  )
}
