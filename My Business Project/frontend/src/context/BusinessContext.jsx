import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { BUSINESS_STORAGE_KEY } from '../api/client'
import { businessService } from '../services/businessService'
import { useAuth } from './AuthContext'

const BusinessContext = createContext(null)

export function BusinessProvider({ children }) {
  const { isAuthenticated } = useAuth()

  const [businesses, setBusinesses] = useState([])
  const [activeBusinessId, setActiveBusinessId] = useState(() => {
    const stored = localStorage.getItem(BUSINESS_STORAGE_KEY)

    return stored ? Number(stored) : null
  })
  const [billing, setBilling] = useState(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)

  const refreshBusinesses = useCallback(async () => {
    if (!isAuthenticated) {
      return []
    }

    try {
      const list = await businessService.businesses()

      setError(null)
      setBusinesses(list)
      setActiveBusinessId((current) => {
        if (current && list.some((business) => business.id === current)) {
          return current
        }

        const fallback = list[0]?.id ?? null

        if (fallback) {
          localStorage.setItem(BUSINESS_STORAGE_KEY, String(fallback))
        }

        return fallback
      })

      return list
    } catch (exception) {
      setError(exception)

      return []
    } finally {
      setLoading(false)
    }
  }, [isAuthenticated])

  useEffect(() => {
    Promise.resolve()
      .then(() => refreshBusinesses())
      .catch(() => {})
  }, [refreshBusinesses])

  const visibleBusinesses = useMemo(
    () => (isAuthenticated ? businesses : []),
    [businesses, isAuthenticated],
  )
  const activeBusiness = useMemo(
    () => visibleBusinesses.find((business) => business.id === activeBusinessId) ?? null,
    [visibleBusinesses, activeBusinessId],
  )

  const refreshBilling = useCallback(async () => {
    if (!isAuthenticated || !activeBusinessId) {
      return null
    }

    try {
      const data = await businessService.billing()
      setBilling(data)

      return data
    } catch {
      setBilling(null)

      return null
    }
  }, [isAuthenticated, activeBusinessId])

  useEffect(() => {
    Promise.resolve()
      .then(() => refreshBilling())
      .catch(() => {})
  }, [refreshBilling])

  const switchBusiness = useCallback(async (businessId) => {
    const result = await businessService.switchBusiness(businessId)
    localStorage.setItem(BUSINESS_STORAGE_KEY, String(businessId))
    setActiveBusinessId(businessId)

    return result
  }, [])

  const createBusiness = useCallback(async (payload) => {
    const business = await businessService.createBusiness(payload)
    await refreshBusinesses()
    localStorage.setItem(BUSINESS_STORAGE_KEY, String(business.id))
    setActiveBusinessId(business.id)

    return business
  }, [refreshBusinesses])

  /**
   * Entitlements of the active business. The backend stays authoritative; this
   * map only drives presentation (upgrade prompts, disabled buttons).
   */
  const entitlements = useMemo(() => {
    const map = {}

    if (!billing?.subscription?.plan_slug) {
      return map
    }

    const plan = (billing.plans || []).find((item) => item.slug === billing.subscription.plan_slug)

    if (!plan) {
      return map
    }

    for (const feature of plan.features || []) {
      map[feature.key] = { enabled: Boolean(feature.enabled), limit: feature.limit ?? null }
    }

    return map
  }, [billing])

  const allows = useCallback((key) => Boolean(entitlements[key]?.enabled), [entitlements])

  const limitOf = useCallback((key) => entitlements[key]?.limit ?? null, [entitlements])

  const value = useMemo(
    () => ({
      businesses: visibleBusinesses,
      activeBusiness,
      activeBusinessId,
      billing,
      entitlements,
      loading: loading && isAuthenticated,
      error,
      allows,
      limitOf,
      refreshBusinesses,
      refreshBilling,
      switchBusiness,
      createBusiness,
    }),
    [
      visibleBusinesses,
      activeBusiness,
      activeBusinessId,
      billing,
      entitlements,
      loading,
      isAuthenticated,
      error,
      allows,
      limitOf,
      refreshBusinesses,
      refreshBilling,
      switchBusiness,
      createBusiness,
    ],
  )

  return <BusinessContext.Provider value={value}>{children}</BusinessContext.Provider>
}

export function useBusiness() {
  const context = useContext(BusinessContext)

  if (!context) {
    throw new Error('useBusiness must be used inside a BusinessProvider.')
  }

  return context
}

export default BusinessContext
