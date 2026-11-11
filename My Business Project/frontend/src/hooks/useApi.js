import { useCallback, useEffect, useRef, useState } from 'react'

/**
 * Small data fetching helper used by every page.
 *
 * The loader is expected to be stable (useCallback in the caller). The hook
 * exposes loading/error state and a manual reload function.
 *
 * `deps` is serialised into a primitive key so the effect dependency list
 * stays a literal array, which keeps the hook compatible with the React
 * Compiler lint rules.
 */
export function useApi(loader, deps = [], { initialData = null, immediate = true } = {}) {
  const [data, setData] = useState(initialData)
  const [loading, setLoading] = useState(immediate)
  const [error, setError] = useState(null)
  const mounted = useRef(true)
  const loaderRef = useRef(loader)

  const depsKey = JSON.stringify(deps ?? [])

  useEffect(() => {
    loaderRef.current = loader
  }, [loader])

  useEffect(() => {
    mounted.current = true

    return () => {
      mounted.current = false
    }
  }, [])

  const reload = useCallback(async () => {
    setLoading(true)
    setError(null)

    try {
      const result = await loaderRef.current()

      if (mounted.current) {
        setData(result)
      }

      return result
    } catch (exception) {
      if (mounted.current) {
        setError(exception)
      }

      return null
    } finally {
      if (mounted.current) {
        setLoading(false)
      }
    }
  }, [])

  useEffect(() => {
    if (!immediate) {
      return undefined
    }

    Promise.resolve()
      .then(() => reload())
      .catch(() => {})

    return undefined
  }, [depsKey, immediate, reload])

  return { data, setData, loading, error, reload }
}

export default useApi
