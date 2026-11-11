import { Check } from 'lucide-react'
import { THEMES } from '@/lib/themes'
import { useTheme } from '@/features/theme/theme-context'
import { cn } from '@/lib/cn'

/**
 * Theme template picker.
 *
 * Three selectable cards, each showing its own accent colours ahead of its
 * label. Selection is immediate - there is no Save button and no page reload,
 * because the choice only affects this browser and is written to localStorage
 * by the provider.
 */
export function ThemePicker({ className }: { className?: string }) {
  const { theme, setTheme } = useTheme()

  return (
    <div
      role="radiogroup"
      aria-label="Theme template"
      className={cn('grid gap-3 sm:grid-cols-3', className)}
    >
      {THEMES.map((option) => {
        const selected = option.id === theme

        return (
          <button
            key={option.id}
            type="button"
            role="radio"
            aria-checked={selected}
            onClick={() => setTheme(option.id)}
            className={cn(
              'group relative rounded-lg border bg-white p-3 text-left transition-all',
              'focus-visible:outline-brand-500 focus-visible:outline-2 focus-visible:outline-offset-2',
              selected
                ? 'border-brand-500 ring-2 ring-brand-500/25'
                : 'border-ink-200 hover:border-ink-400 hover:shadow-xs',
            )}
          >
            {/* The preview is built from the theme's own literal colours, so a
                card always advertises what selecting it will look like. */}
            <div
              className="mb-3 h-14 overflow-hidden rounded-md border border-ink-200/60"
              style={{ backgroundColor: option.swatch }}
            >
              <div className="flex h-full">
                <div className="w-1/3" style={{ backgroundColor: option.swatchDeep }} />
                <div className="flex-1 space-y-1.5 p-2">
                  <div className="h-1.5 w-2/3 rounded-full bg-white/70" />
                  <div className="h-1.5 w-1/2 rounded-full bg-white/45" />
                  <div className="h-1.5 w-3/4 rounded-full bg-white/30" />
                </div>
              </div>
            </div>

            <span className="flex items-center gap-1.5">
              <span className="text-sm font-medium text-ink-900">{option.label}</span>
              {selected && <Check className="text-brand-600 size-4 shrink-0" aria-hidden />}
            </span>
            <span className="mt-0.5 block text-xs text-ink-500">{option.description}</span>
          </button>
        )
      })}
    </div>
  )
}
