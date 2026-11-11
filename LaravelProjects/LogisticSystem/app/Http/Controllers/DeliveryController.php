<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\DeliveryService;
use App\Services\DriverService;
use App\Services\RouteService;
use App\Services\ShipmentService;
use App\Services\VehicleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService,
        protected ShipmentService $shipmentService,
        protected DriverService $driverService,
        protected VehicleService $vehicleService,
        protected RouteService $routeService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $deliveries = $this->deliveryService->getAll($request->only([
            'search', 'status', 'driver_id', 'shipment_id', 'date_from', 'date_to',
        ]));
        return view('deliveries.index', compact('deliveries'));
    }

    public function create(): View
    {
        $shipments = $this->shipmentService->getAll(['status' => 'out_for_delivery']);
        $routes = $this->routeService->getActiveRoutes();
        $drivers = $this->driverService->getAvailableDrivers();
        $vehicles = $this->vehicleService->getActiveVehicles();
        return view('deliveries.create', compact('shipments', 'routes', 'drivers', 'vehicles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_date' => 'required|date',
            'delivery_notes' => 'nullable|string|max:1000',
            'shipment_id' => 'required|exists:shipments,id',
            'route_id' => 'nullable|exists:routes,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        try {
            $validated['status'] = 'pending';
            $delivery = $this->deliveryService->create($validated);

            $this->auditLogService->log('created', 'deliveries', "Delivery created: {$delivery->delivery_number}", $delivery, null, $validated, auth()->id());

            return redirect()->route('deliveries.index')
                ->with('success', "Delivery {$delivery->delivery_number} created successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create delivery: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $delivery = $this->deliveryService->getById($id);
        return view('deliveries.show', compact('delivery'));
    }

    public function edit(int $id): View
    {
        $delivery = $this->deliveryService->getById($id);
        $shipments = $this->shipmentService->getAll(['status' => 'out_for_delivery']);
        $routes = $this->routeService->getActiveRoutes();
        $drivers = $this->driverService->getAvailableDrivers();
        $vehicles = $this->vehicleService->getActiveVehicles();
        return view('deliveries.edit', compact('delivery', 'shipments', 'routes', 'drivers', 'vehicles'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_date' => 'required|date',
            'delivery_notes' => 'nullable|string|max:1000',
            'shipment_id' => 'required|exists:shipments,id',
            'route_id' => 'nullable|exists:routes,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        try {
            $delivery = $this->deliveryService->update($id, $validated);

            $this->auditLogService->log('updated', 'deliveries', "Delivery updated: {$delivery->delivery_number}", $delivery, null, $validated, auth()->id());

            return redirect()->route('deliveries.index')
                ->with('success', "Delivery {$delivery->delivery_number} updated successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update delivery: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $delivery = $this->deliveryService->getById($id);
            $this->deliveryService->delete($id);

            $this->auditLogService->log('deleted', 'deliveries', "Delivery deleted: {$delivery->delivery_number}", $delivery, null, null, auth()->id());

            return redirect()->route('deliveries.index')
                ->with('success', 'Delivery deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete delivery: ' . $e->getMessage());
        }
    }

    public function assignDriver(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
        ]);

        try {
            $delivery = $this->deliveryService->assignDriver($id, $validated['driver_id'], auth()->id());

            $this->auditLogService->log('assigned', 'deliveries', "Driver assigned to delivery {$delivery->delivery_number}", $delivery, null, $validated, auth()->id());

            return back()->with('success', 'Driver assigned successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to assign driver: ' . $e->getMessage());
        }
    }

    public function markDelivered(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'nullable|string|max:20',
            'recipient_signature' => 'nullable|string',
            'delivery_notes' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        try {
            $delivery = $this->deliveryService->markDelivered($id, $validated);

            $this->shipmentService->updateStatus(
                $delivery->shipment_id,
                'delivered',
                'Package delivered successfully',
                auth()->id()
            );

            $this->auditLogService->log('status_changed', 'deliveries', "Delivery completed: {$delivery->delivery_number}", $delivery, null, $validated, auth()->id());

            return back()->with('success', 'Delivery marked as delivered successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to mark delivery: ' . $e->getMessage());
        }
    }

    public function uploadProof(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'proof_photo' => 'nullable|image|max:5120',
            'proof_document' => 'nullable|file|max:10240',
            'recipient_signature' => 'nullable|string',
        ]);

        try {
            $data = [];

            if ($request->hasFile('proof_photo')) {
                $data['proof_photo'] = $request->file('proof_photo')->store('delivery-proofs/photos', 'public');
            }

            if ($request->hasFile('proof_document')) {
                $data['proof_document'] = $request->file('proof_document')->store('delivery-proofs/documents', 'public');
            }

            if (!empty($validated['recipient_signature'])) {
                $data['recipient_signature'] = $validated['recipient_signature'];
            }

            $this->deliveryService->uploadProof($id, $data);

            return back()->with('success', 'Delivery proof uploaded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to upload proof: ' . $e->getMessage());
        }
    }
}
