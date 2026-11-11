import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'

/**
 * Conditional class names with Tailwind conflict resolution.
 *
 * `clsx` handles conditionals, `twMerge` makes the last utility win, so a
 * component can accept `className="px-2"` and still be overridden by the
 * caller's `px-4` instead of both ending up in the DOM.
 */
export function cn(...inputs: ClassValue[]): string {
  return twMerge(clsx(inputs))
}
