import { Link } from 'react-router-dom'
import { Lock } from 'lucide-react'
import { useBusiness } from '../context/BusinessContext'
import { FEATURE_LABELS } from '../utils/constants'

/**
 * Presentation only helper: it hides Pro-only interface elements and shows an
 * upgrade prompt. The backend always enforces the real restriction.
 */
export function FeatureGate({ feature, children, fallback = null, showPrompt = true }) {
  const { allows } = useBusiness()

  if (allows(feature)) {
    return children
  }

  if (fallback !== null) {
    return fallback
  }

  if (!showPrompt) {
    return null
  }

  return <UpgradePrompt feature={feature} />
}

export function UpgradePrompt({ feature, message }) {
  const label = FEATURE_LABELS[feature] ?? feature

  return (
    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
      <span className="flex size-11 items-center justify-center rounded-full bg-amber-50 text-amber-600">
        <Lock className="size-5" />
      </span>
      <div>
        <p className="text-sm font-semibold text-slate-900">{label} is a Pro feature</p>
        <p className="mt-1 max-w-md text-sm text-slate-500">
          {message ?? `Upgrade this business to a Pro plan to unlock ${label.toLowerCase()}.`}
        </p>
      </div>
      <Link
        to="/billing"
        className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700"
      >
        View upgrade options
      </Link>
    </div>
  )
}

export default FeatureGate
