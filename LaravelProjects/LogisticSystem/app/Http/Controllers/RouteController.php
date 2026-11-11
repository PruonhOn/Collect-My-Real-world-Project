<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\RouteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RouteController extends Controller
{
    public function __construct(
        protected RouteService $routeService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $routes = $this->routeService->getAll($request->only(['search', 'is_active']));
        return view('routes.index', compact('routes'));
    }

    public function create(): View
    {
        return view('routes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:routes,code',
            'name' => 'required|string|max:255',
            'origin_city' => 'required|string|max:100',
            'destination_city' => 'required|string|max:100',
            'distance_km' => 'nullable|numeric|min:0',
            'estimated_hours' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'waypoints' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $validated['created_by'] = auth()->id();
            $route = $this->routeService->create($validated);

            $this->auditLogService->log('created', 'routes', "Route created: {$route->name}", $route, null, $validated, auth()->id());

            return redirect()->route('routes.index')
                ->with('success', 'Route created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create route: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $route = $this->routeService->getById($id);
        return view('routes.show', compact('route'));
    }

    public function edit(int $id): View
    {
        $route = $this->routeService->getById($id);
        return view('routes.edit', compact('route'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:routes,code,' . $id,
            'name' => 'required|string|max:255',
            'origin_city' => 'required|string|max:100',
            'destination_city' => 'required|string|max:100',
            'distance_km' => 'nullable|numeric|min:0',
            'estimated_hours' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'waypoints' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $route = $this->routeService->update($id, $validated);

            $this->auditLogService->log('updated', 'routes', "Route updated: {$route->name}", $route, null, $validated, auth()->id());

            return redirect()->route('routes.index')
                ->with('success', 'Route updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update route: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $route = $this->routeService->getById($id);
            $this->routeService->delete($id);

            $this->auditLogService->log('deleted', 'routes', "Route deleted: {$route->name}", $route, null, null, auth()->id());

            return redirect()->route('routes.index')
                ->with('success', 'Route deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete route: ' . $e->getMessage());
        }
    }
}
