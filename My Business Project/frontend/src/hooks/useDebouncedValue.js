import { useEffect, useState } from 'react'

/**
 * Debounce a rapidly changing value (search inputs).
 */
export function useDebouncedValue(value, delay = 350) {
  const [debounced, setDebounced] = useState(value)

  useEffect(() => {
    const timer = setTimeout(() => setDebounced(value), delay)

    return () => clearTimeout(timer)
  }, [value, delay])

  return debounced
}

export default useDebouncedValue
