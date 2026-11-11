import { Link } from 'react-router-dom'
import { FileQuestion, ShieldAlert } from 'lucide-react'
import { Button } from '@/components/ui/Button'

export function ForbiddenPage() {
  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center px-6 text-center">
      <div className="bg-warning-50 text-warning-600 mb-4 flex size-14 items-center justify-center rounded-full">
        <ShieldAlert className="size-7" aria-hidden />
      </div>

      <h1 className="text-lg font-semibold text-ink-900">You do not have access to this page</h1>
      <p className="mt-2 max-w-md text-sm text-ink-500">
        Your role does not include the permission required for this area. If you believe this is
        a mistake, ask an administrator to review your permissions.
      </p>

      <div className="mt-6 flex gap-2">
        <Link to="/dashboard">
          <Button variant="primary">Back to dashboard</Button>
        </Link>
        <Link to="/">
          <Button variant="outline">Go to my landing page</Button>
        </Link>
      </div>
    </div>
  )
}

export function NotFoundPage() {
  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center px-6 text-center">
      <div className="bg-ink-100 text-ink-500 mb-4 flex size-14 items-center justify-center rounded-full">
        <FileQuestion className="size-7" aria-hidden />
      </div>

      <h1 className="text-lg font-semibold text-ink-900">Page not found</h1>
      <p className="mt-2 max-w-md text-sm text-ink-500">
        The page you are looking for does not exist or has been moved.
      </p>

      <Link to="/dashboard" className="mt-6">
        <Button variant="primary">Back to dashboard</Button>
      </Link>
    </div>
  )
}
