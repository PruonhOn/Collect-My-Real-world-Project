<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\BranchService;
use App\Services\CustomerService;
use App\Services\DriverService;
use App\Services\ShipmentService;
use App\Services\VehicleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService,
        protected CustomerService $customerService,
        protected BranchService $branchService,
        protected DriverService $driverService,
        protected VehicleService $vehicleService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $shipments = $this->shipmentService->getAll($request->only([
            'search', 'status', 'customer_id', 'origin_branch_id', 'date_from', 'date_to',
        ]));
        $statuses = $this->shipmentService->getStatuses();
        return view('shipments.index', compact('shipments', 'statuses'));
    }

    public function create(): View
    {
        $customers = $this->customerService->getAll(['is_active' => true]);
        $branches = $this->branchService->getActiveBranches();
        $drivers = $this->driverService->getAvailableDrivers();
        $vehicles = $this->vehicleService->getActiveVehicles();
        $statuses = $this->shipmentService->getStatuses();
        return view('shipments.create', compact('customers', 'branches', 'drivers', 'vehicles', 'statuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shipment_type' => 'required|string|max:50',
            'status' => 'required|string|max:50',
            'origin_address' => 'nullable|string|max:500',
            'origin_city' => 'nullable|string|max:100',
            'origin_province' => 'nullable|string|max:100',
            'destination_address' => 'nullable|string|max:500',
            'destination_city' => 'nullable|string|max:100',
            'destination_province' => 'nullable|string|max:100',
            'shipper_name' => 'nullable|string|max:255',
            'shipper_phone' => 'nullable|string|max:20',
            'shipper_email' => 'nullable|email|max:255',
            'consignee_name' => 'required|string|max:255',
            'consignee_phone' => 'required|string|max:20',
            'consignee_email' => 'nullable|email|max:255',
            'weight' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'pieces' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'declared_value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'shipment_date' => 'nullable|date',
            'estimated_delivery' => 'nullable|date',
            'is_express' => 'boolean',
            'is_insured' => 'boolean',
            'insurance_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'customer_id' => 'nullable|exists:customers,id',
            'origin_branch_id' => 'nullable|exists:branches,id',
            'destination_branch_id' => 'nullable|exists:branches,id',
            'assigned_driver_id' => 'nullable|exists:drivers,id',
            'assigned_vehicle_id' => 'nullable|exists:vehicles,id',
            'items' => 'nullable|array',
            'items.*.name' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.volume' => 'nullable|numeric|min:0',
            'items.*.value' => 'nullable|numeric|min:0',
        ]);

        try {
            $validated['is_express'] = $request->boolean('is_express');
            $validated['is_insured'] = $request->boolean('is_insured');
            $validated['created_by'] = auth()->id();
            $shipment = $this->shipmentService->create($validated);

            $this->auditLogService->log('created', 'shipments', "Shipment created: {$shipment->tracking_number}", $shipment, null, $validated, auth()->id());

            return redirect()->route('shipments.index')
                ->with('success', "Shipment {$shipment->tracking_number} created successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create shipment: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $shipment = $this->shipmentService->getById($id);
        return view('shipments.show', compact('shipment'));
    }

    public function edit(int $id): View
    {
        $shipment = $this->shipmentService->getById($id);
        $customers = $this->customerService->getAll(['is_active' => true]);
        $branches = $this->branchService->getActiveBranches();
        $drivers = $this->driverService->getAvailableDrivers();
        $vehicles = $this->vehicleService->getActiveVehicles();
        $statuses = $this->shipmentService->getStatuses();
        return view('shipments.edit', compact('shipment', 'customers', 'branches', 'drivers', 'vehicles', 'statuses'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'shipment_type' => 'required|string|max:50',
            'status' => 'required|string|max:50',
            'origin_address' => 'nullable|string|max:500',
            'origin_city' => 'nullable|string|max:100',
            'origin_province' => 'nullable|string|max:100',
            'destination_address' => 'nullable|string|max:500',
            'destination_city' => 'nullable|string|max:100',
            'destination_province' => 'nullable|string|max:100',
            'shipper_name' => 'nullable|string|max:255',
            'shipper_phone' => 'nullable|string|max:20',
            'shipper_email' => 'nullable|email|max:255',
            'consignee_name' => 'required|string|max:255',
            'consignee_phone' => 'required|string|max:20',
            'consignee_email' => 'nullable|email|max:255',
            'weight' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'pieces' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'declared_value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'shipment_date' => 'nullable|date',
            'estimated_delivery' => 'nullable|date',
            'is_express' => 'boolean',
            'is_insured' => 'boolean',
            'insurance_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'customer_id' => 'nullable|exists:customers,id',
            'origin_branch_id' => 'nullable|exists:branches,id',
            'destination_branch_id' => 'nullable|exists:branches,id',
            'assigned_driver_id' => 'nullable|exists:drivers,id',
            'assigned_vehicle_id' => 'nullable|exists:vehicles,id',
            'items' => 'nullable|array',
            'items.*.name' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.volume' => 'nullable|numeric|min:0',
            'items.*.value' => 'nullable|numeric|min:0',
        ]);

        try {
            $validated['is_express'] = $request->boolean('is_express');
            $validated['is_insured'] = $request->boolean('is_insured');
            $shipment = $this->shipmentService->update($id, $validated);

            $this->auditLogService->log('updated', 'shipments', "Shipment updated: {$shipment->tracking_number}", $shipment, null, $validated, auth()->id());

            return redirect()->route('shipments.index')
                ->with('success', "Shipment {$shipment->tracking_number} updated successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update shipment: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $shipment = $this->shipmentService->getById($id);
            $this->shipmentService->delete($id);

            $this->auditLogService->log('deleted', 'shipments', "Shipment deleted: {$shipment->tracking_number}", $shipment, null, null, auth()->id());

            return redirect()->route('shipments.index')
                ->with('success', 'Shipment deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete shipment: ' . $e->getMessage());
        }
    }

    public function statusUpdate(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        try {
            $location = [
                'location' => $validated['location'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ];

            $this->shipmentService->updateStatus(
                $id,
                $validated['status'],
                $validated['description'] ?? null,
                auth()->id(),
                $location
            );

            $this->auditLogService->log('status_changed', 'shipments', "Shipment status changed to {$validated['status']}", null, null, $validated, auth()->id());

            return back()->with('success', 'Shipment status updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    public function printLabel(int $id): View
    {
        $shipment = $this->shipmentService->getById($id);
        return view('shipments.label', compact('shipment'));
    }
}
