import { RequirePermission } from '@/components/guards/ProtectedRoute'
import { MasterDataPage } from '@/features/master-data/MasterDataPage'
import { SURFACES } from '@/features/master-data/registry'

/**
 * One file per master-data surface.
 *
 * Each is three lines: pick the surface, hand it to the shared page, gate it on
 * the permission the API requires. The surface registry carries the module's
 * columns, fields, filters and paths, so there is nothing screen-specific left
 * to write here.
 */

export function CustomersPage() {
  return (
    <RequirePermission permission={SURFACES.customers.viewPermission}>
      <MasterDataPage surface={SURFACES.customers} />
    </RequirePermission>
  )
}

export function ShippersPage() {
  return (
    <RequirePermission permission={SURFACES.shippers.viewPermission}>
      <MasterDataPage surface={SURFACES.shippers} />
    </RequirePermission>
  )
}

export function ConsigneesPage() {
  return (
    <RequirePermission permission={SURFACES.consignees.viewPermission}>
      <MasterDataPage surface={SURFACES.consignees} />
    </RequirePermission>
  )
}

export function CarriersPage() {
  return (
    <RequirePermission permission={SURFACES.carriers.viewPermission}>
      <MasterDataPage surface={SURFACES.carriers} />
    </RequirePermission>
  )
}

export function VesselsPage() {
  return (
    <RequirePermission permission={SURFACES.vessels.viewPermission}>
      <MasterDataPage surface={SURFACES.vessels} />
    </RequirePermission>
  )
}

export function PortsPage() {
  return (
    <RequirePermission permission={SURFACES.ports.viewPermission}>
      <MasterDataPage surface={SURFACES.ports} />
    </RequirePermission>
  )
}

export function WarehousesPage() {
  return (
    <RequirePermission permission={SURFACES.warehouses.viewPermission}>
      <MasterDataPage surface={SURFACES.warehouses} />
    </RequirePermission>
  )
}

export function FactoriesPage() {
  return (
    <RequirePermission permission={SURFACES.factories.viewPermission}>
      <MasterDataPage surface={SURFACES.factories} />
    </RequirePermission>
  )
}

export function TrucksPage() {
  return (
    <RequirePermission permission={SURFACES.trucks.viewPermission}>
      <MasterDataPage surface={SURFACES.trucks} />
    </RequirePermission>
  )
}

export function DriversPage() {
  return (
    <RequirePermission permission={SURFACES.drivers.viewPermission}>
      <MasterDataPage surface={SURFACES.drivers} />
    </RequirePermission>
  )
}
