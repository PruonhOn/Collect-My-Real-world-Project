<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('dashboard') }}" class="sidebar-logo">
            <i class="bi bi-box-seam" style="font-size: 1.5rem; color: var(--primary-color);"></i>
            <span>Combodia</span>
        </a>
    </div>

    <div class="sidebar-nav py-2">
        <div class="sidebar-section-title">Main</div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-section-title">Master Data</div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/branches*') ? 'active' : '' }}" href="#collapseBranches" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/branches*') ? 'true' : 'false' }}">
                    <i class="bi bi-building"></i>
                    <span>Branches</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/branches*') ? 'show' : '' }}" id="collapseBranches">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.branches.index') ? 'active' : '' }}" href="{{ route('admin.branches.index') }}">All Branches</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.branches.create') ? 'active' : '' }}" href="{{ route('admin.branches.create') }}">Add Branch</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/warehouses*') ? 'active' : '' }}" href="#collapseWarehouses" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/warehouses*') ? 'true' : 'false' }}">
                    <i class="bi bi-warehouse"></i>
                    <span>Warehouses</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/warehouses*') ? 'show' : '' }}" id="collapseWarehouses">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.warehouses.index') ? 'active' : '' }}" href="{{ route('admin.warehouses.index') }}">All Warehouses</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.warehouses.create') ? 'active' : '' }}" href="{{ route('admin.warehouses.create') }}">Add Warehouse</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/customers*') ? 'active' : '' }}" href="#collapseCustomers" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/customers*') ? 'true' : 'false' }}">
                    <i class="bi bi-people"></i>
                    <span>Customers</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/customers*') ? 'show' : '' }}" id="collapseCustomers">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.customers.index') ? 'active' : '' }}" href="{{ route('admin.customers.index') }}">All Customers</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.customers.create') ? 'active' : '' }}" href="{{ route('admin.customers.create') }}">Add Customer</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/suppliers*') ? 'active' : '' }}" href="#collapseSuppliers" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/suppliers*') ? 'true' : 'false' }}">
                    <i class="bi bi-truck"></i>
                    <span>Suppliers</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/suppliers*') ? 'show' : '' }}" id="collapseSuppliers">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.suppliers.index') ? 'active' : '' }}" href="{{ route('admin.suppliers.index') }}">All Suppliers</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.suppliers.create') ? 'active' : '' }}" href="{{ route('admin.suppliers.create') }}">Add Supplier</a></li>
                    </ul>
                </div>
            </li>
        </ul>

        <div class="sidebar-section-title">Fleet Management</div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/vehicles*') ? 'active' : '' }}" href="#collapseVehicles" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/vehicles*') ? 'true' : 'false' }}">
                    <i class="bi bi-car-front"></i>
                    <span>Vehicles</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/vehicles*') ? 'show' : '' }}" id="collapseVehicles">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.vehicles.index') ? 'active' : '' }}" href="{{ route('admin.vehicles.index') }}">All Vehicles</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.vehicles.create') ? 'active' : '' }}" href="{{ route('admin.vehicles.create') }}">Add Vehicle</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/vehicle-types*') ? 'active' : '' }}" href="#collapseVehicleTypes" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/vehicle-types*') ? 'true' : 'false' }}">
                    <i class="bi bi-truck"></i>
                    <span>Vehicle Types</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/vehicle-types*') ? 'show' : '' }}" id="collapseVehicleTypes">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.vehicle-types.index') ? 'active' : '' }}" href="{{ route('vehicle-types.index') }}">All Vehicle Types</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.vehicle-types.create') ? 'active' : '' }}" href="{{ route('vehicle-types.create') }}">Add Vehicle Type</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/vehicle-maintenances*') ? 'active' : '' }}" href="#collapseVehicleMaintenances" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/vehicle-maintenances*') ? 'true' : 'false' }}">
                    <i class="bi bi-wrench"></i>
                    <span>Vehicle Maintenance</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/vehicle-maintenances*') ? 'show' : '' }}" id="collapseVehicleMaintenances">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.vehicle-maintenances.index') ? 'active' : '' }}" href="{{ route('vehicle-maintenances.index') }}">All Maintenances</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.vehicle-maintenances.create') ? 'active' : '' }}" href="{{ route('vehicle-maintenances.create') }}">Add Maintenance</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/drivers*') ? 'active' : '' }}" href="#collapseDrivers" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/drivers*') ? 'true' : 'false' }}">
                    <i class="bi bi-person-badge"></i>
                    <span>Drivers</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/drivers*') ? 'show' : '' }}" id="collapseDrivers">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.drivers.index') ? 'active' : '' }}" href="{{ route('admin.drivers.index') }}">All Drivers</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.drivers.create') ? 'active' : '' }}" href="{{ route('admin.drivers.create') }}">Add Driver</a></li>
                    </ul>
                </div>
            </li>
        </ul>

        <div class="sidebar-section-title">Shipments</div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/shipments*') ? 'active' : '' }}" href="#collapseShipments" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/shipments*') ? 'true' : 'false' }}">
                    <i class="bi bi-box-seam"></i>
                    <span>Shipments</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/shipments*') ? 'show' : '' }}" id="collapseShipments">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('shipments.index') ? 'active' : '' }}" href="{{ route('shipments.index') }}">All Shipments</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('shipments.create') ? 'active' : '' }}" href="{{ route('shipments.create') }}">Add Shipment</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/pickup-requests*') ? 'active' : '' }}" href="#collapsePickup" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/pickup-requests*') ? 'true' : 'false' }}">
                    <i class="bi bi-hand-index"></i>
                    <span>Pickup Requests</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/pickup-requests*') ? 'show' : '' }}" id="collapsePickup">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('pickup-requests.index') ? 'active' : '' }}" href="{{ route('pickup-requests.index') }}">All Requests</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('pickup-requests.create') ? 'active' : '' }}" href="{{ route('pickup-requests.create') }}">New Request</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/packages*') ? 'active' : '' }}" href="#collapsePackages" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/packages*') ? 'true' : 'false' }}">
                    <i class="bi bi-box"></i>
                    <span>Packages</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/packages*') ? 'show' : '' }}" id="collapsePackages">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('packages.index') ? 'active' : '' }}" href="{{ route('packages.index') }}">All Packages</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('packages.create') ? 'active' : '' }}" href="{{ route('packages.create') }}">Add Package</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/dispatches*') ? 'active' : '' }}" href="#collapseDispatching" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/dispatches*') ? 'true' : 'false' }}">
                    <i class="bi bi-diagram-3"></i>
                    <span>Dispatching</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/dispatches*') ? 'show' : '' }}" id="collapseDispatching">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('dispatches.index') ? 'active' : '' }}" href="{{ route('dispatches.index') }}">Active Dispatches</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('dispatches.create') ? 'active' : '' }}" href="{{ route('dispatches.create') }}">Create Dispatch</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/returns*') ? 'active' : '' }}" href="#collapseReturns" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/returns*') ? 'true' : 'false' }}">
                    <i class="bi bi-arrow-return-left"></i>
                    <span>Returns</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/returns*') ? 'show' : '' }}" id="collapseReturns">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('returns.index') ? 'active' : '' }}" href="{{ route('returns.index') }}">All Returns</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('returns.create') ? 'active' : '' }}" href="{{ route('returns.create') }}">Create Return</a></li>
                    </ul>
                </div>
            </li>
        </ul>

        <div class="sidebar-section-title">Routes & Tracking</div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/routes*') ? 'active' : '' }}" href="#collapseRoutes" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/routes*') ? 'true' : 'false' }}">
                    <i class="bi bi-signpost-2"></i>
                    <span>Routes</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/routes*') ? 'show' : '' }}" id="collapseRoutes">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('routes.index') ? 'active' : '' }}" href="{{ route('routes.index') }}">All Routes</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('routes.create') ? 'active' : '' }}" href="{{ route('routes.create') }}">Add Route</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/tracking*') ? 'active' : '' }}" href="#collapseTracking" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/tracking*') ? 'true' : 'false' }}">
                    <i class="bi bi-geo-alt"></i>
                    <span>Tracking</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/tracking*') ? 'show' : '' }}" id="collapseTracking">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('tracking.index') ? 'active' : '' }}" href="{{ route('tracking.index') }}">Live Tracking</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('tracking.index') ? 'active' : '' }}" href="{{ route('tracking.index') }}">History</a></li>
                    </ul>
                </div>
            </li>
        </ul>

        <div class="sidebar-section-title">Warehouse</div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/inventories*') ? 'active' : '' }}" href="#collapseInventory" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/inventories*') ? 'true' : 'false' }}">
                    <i class="bi bi-inboxes"></i>
                    <span>Inventory</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/inventories*') ? 'show' : '' }}" id="collapseInventory">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('inventories.index') ? 'active' : '' }}" href="{{ route('inventories.index') }}">Stock Overview</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('inventories.create') ? 'active' : '' }}" href="{{ route('inventories.create') }}">Add Item</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/stock-movements*') ? 'active' : '' }}" href="#collapseStockMovements" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/stock-movements*') ? 'true' : 'false' }}">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Stock Movements</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/stock-movements*') ? 'show' : '' }}" id="collapseStockMovements">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('stock-movements.index') ? 'active' : '' }}" href="{{ route('stock-movements.index') }}">Movements Log</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('stock-movements.create') ? 'active' : '' }}" href="{{ route('stock-movements.create') }}">New Movement</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/warehouse-zones*') ? 'active' : '' }}" href="#collapseWarehouseZones" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/warehouse-zones*') ? 'true' : 'false' }}">
                    <i class="bi bi-grid"></i>
                    <span>Warehouse Zones</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/warehouse-zones*') ? 'show' : '' }}" id="collapseWarehouseZones">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('warehouse-zones.index') ? 'active' : '' }}" href="{{ route('warehouse-zones.index') }}">All Zones</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('warehouse-zones.create') ? 'active' : '' }}" href="{{ route('warehouse-zones.create') }}">Add Zone</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/warehouse-shelves*') ? 'active' : '' }}" href="#collapseWarehouseShelves" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/warehouse-shelves*') ? 'true' : 'false' }}">
                    <i class="bi bi-columns-gap"></i>
                    <span>Warehouse Shelves</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/warehouse-shelves*') ? 'show' : '' }}" id="collapseWarehouseShelves">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('warehouse-shelves.index') ? 'active' : '' }}" href="{{ route('warehouse-shelves.index') }}">All Shelves</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('warehouse-shelves.create') ? 'active' : '' }}" href="{{ route('warehouse-shelves.create') }}">Add Shelf</a></li>
                    </ul>
                </div>
            </li>
        </ul>

        <div class="sidebar-section-title">Finance</div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/invoices*') ? 'active' : '' }}" href="#collapseInvoices" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/invoices*') ? 'true' : 'false' }}">
                    <i class="bi bi-receipt"></i>
                    <span>Invoices</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/invoices*') ? 'show' : '' }}" id="collapseInvoices">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('invoices.index') ? 'active' : '' }}" href="{{ route('invoices.index') }}">All Invoices</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('invoices.create') ? 'active' : '' }}" href="{{ route('invoices.create') }}">Create Invoice</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/payments*') ? 'active' : '' }}" href="#collapsePayments" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/payments*') ? 'true' : 'false' }}">
                    <i class="bi bi-credit-card"></i>
                    <span>Payments</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/payments*') ? 'show' : '' }}" id="collapsePayments">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('payments.index') ? 'active' : '' }}" href="{{ route('payments.index') }}">All Payments</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('payments.create') ? 'active' : '' }}" href="{{ route('payments.create') }}">Record Payment</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/expenses*') ? 'active' : '' }}" href="#collapseExpenses" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/expenses*') ? 'true' : 'false' }}">
                    <i class="bi bi-cash-stack"></i>
                    <span>Expenses</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/expenses*') ? 'show' : '' }}" id="collapseExpenses">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('expenses.index') ? 'active' : '' }}" href="{{ route('expenses.index') }}">All Expenses</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('expenses.create') ? 'active' : '' }}" href="{{ route('expenses.create') }}">Add Expense</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/incomes*') ? 'active' : '' }}" href="#collapseIncome" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/incomes*') ? 'true' : 'false' }}">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Income</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/incomes*') ? 'show' : '' }}" id="collapseIncome">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('incomes.index') ? 'active' : '' }}" href="{{ route('incomes.index') }}">Income Overview</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('incomes.create') ? 'active' : '' }}" href="{{ route('incomes.create') }}">Record Income</a></li>
                    </ul>
                </div>
            </li>
        </ul>

        <div class="sidebar-section-title">Reports</div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/reports/daily*') ? 'active' : '' }}" href="#collapseShipmentReports" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/reports/daily*') ? 'true' : 'false' }}">
                    <i class="bi bi-file-text"></i>
                    <span>Shipment Reports</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/reports/daily*') ? 'show' : '' }}" id="collapseShipmentReports">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.daily') ? 'active' : '' }}" href="{{ route('reports.daily') }}">Daily Reports</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.monthly') ? 'active' : '' }}" href="{{ route('reports.monthly') }}">Monthly Reports</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/reports/financial*') ? 'active' : '' }}" href="#collapseFinancialReports" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/reports/financial*') ? 'true' : 'false' }}">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Financial Reports</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/reports/financial*') ? 'show' : '' }}" id="collapseFinancialReports">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.profit-loss') ? 'active' : '' }}" href="{{ route('reports.profit-loss') }}">Profit & Loss</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.financial') ? 'active' : '' }}" href="{{ route('reports.financial') }}">Revenue Analysis</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/reports/inventory*') ? 'active' : '' }}" href="#collapseInventoryReports" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/reports/inventory*') ? 'true' : 'false' }}">
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                    <span>Inventory Reports</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/reports/inventory*') ? 'show' : '' }}" id="collapseInventoryReports">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.inventory') ? 'active' : '' }}" href="{{ route('reports.inventory') }}">Stock Summary</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.inventory') ? 'active' : '' }}" href="{{ route('reports.inventory') }}">Movement History</a></li>
                    </ul>
                </div>
            </li>
        </ul>

        <div class="sidebar-section-title">System</div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/activity-logs*') ? 'active' : '' }}" href="#collapseActivityLog" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/activity-logs*') ? 'true' : 'false' }}">
                    <i class="bi bi-clock-history"></i>
                    <span>Activity Log</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/activity-logs*') ? 'show' : '' }}" id="collapseActivityLog">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('activity-logs.index') ? 'active' : '' }}" href="{{ route('activity-logs.index') }}">Log History</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/backups*') ? 'active' : '' }}" href="#collapseBackups" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/backups*') ? 'true' : 'false' }}">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <span>Backups</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/backups*') ? 'show' : '' }}" id="collapseBackups">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('backups.index') ? 'active' : '' }}" href="{{ route('backups.index') }}">All Backups</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}" href="#collapseSettings" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/settings*') ? 'true' : 'false' }}">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                    <i class="bi bi-chevron-right menu-arrow"></i>
                </a>
                <div class="collapse {{ request()->is('admin/settings*') ? 'show' : '' }}" id="collapseSettings">
                    <ul class="sub-menu nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}" href="{{ route('settings.index') }}">General</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}" href="{{ route('settings.index') }}">Company</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}" href="{{ route('settings.index') }}">Email</a></li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</aside>
