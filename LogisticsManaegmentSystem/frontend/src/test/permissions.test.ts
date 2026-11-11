import { describe, expect, it } from 'vitest'
import { can, canAll, hasRole } from '@/lib/permissions'

describe('permission helpers', () => {
  describe('can', () => {
    it('grants a single permission the user holds', () => {
      expect(can(['shipments.view'], 'shipments.view')).toBe(true)
    })

    it('denies a single permission the user lacks', () => {
      expect(can(['shipments.view'], 'shipments.delete')).toBe(false)
    })

    it('treats an array as "any of"', () => {
      expect(can(['shipments.view'], ['shipments.delete', 'shipments.view'])).toBe(true)
      expect(can(['shipments.view'], ['shipments.delete', 'invoices.view'])).toBe(false)
    })

    it('denies when the user has no permissions at all', () => {
      expect(can([], 'shipments.view')).toBe(false)
      expect(can(undefined, 'shipments.view')).toBe(false)
    })

    it('is case sensitive, so a typo never grants access', () => {
      expect(can(['shipments.view'], 'Shipments.View')).toBe(false)
    })
  })

  describe('canAll', () => {
    it('requires every listed permission', () => {
      expect(canAll(['a', 'b', 'c'], ['a', 'b'])).toBe(true)
      expect(canAll(['a', 'b'], ['a', 'b', 'c'])).toBe(false)
    })

    it('denies an empty requirement set for an unprivileged user', () => {
      // `every` on an empty array is true, which would otherwise leak a UI
      // element to a user who holds nothing.
      expect(canAll([], [])).toBe(false)
      expect(canAll(undefined, [])).toBe(false)
    })
  })

  describe('hasRole', () => {
    it('matches an assigned role', () => {
      expect(hasRole(['admin', 'ops'], 'ops')).toBe(true)
      expect(hasRole(['admin'], 'ops')).toBe(false)
      expect(hasRole(undefined, 'ops')).toBe(false)
    })
  })
})
