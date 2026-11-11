<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRouteRequest;
use App\Http\Requests\UpdateRouteRequest;
use App\Models\Branch;
use App\Models\DeliveryRoute;
use Illuminate\Http\Request;

class DeliveryRouteController extends Controller
{
    public function index(Request $request)
    {
        $query = DeliveryRoute::with('branch');

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('branch_id') && $request->branch_id !== null) {
            $query->where('branch_id', $request->branch_id);
        }

        $routes = $query->latest()->paginate(15)->withQueryString();
        $branches = Branch::where('is_active', true)->get();

        if ($request->ajax()) {
            return response()->json(['routes' => $routes, 'branches' => $branches]);
        }

        return view('routes.index', compact('routes', 'branches'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        return view('routes.create', compact('branches'));
    }

    public function store(StoreRouteRequest $request)
    {
        $validated = $request->validated();
        $validated['status'] = 'active';

        $route = DeliveryRoute::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Route created successfully', 'route' => $route], 201);
        }

        return redirect()->route('routes.index')->with('success', 'Route created successfully');
    }

    public function show(DeliveryRoute $deliveryRoute)
    {
        $deliveryRoute->load(['branch', 'routeStops', 'dispatches']);

        if (request()->ajax()) {
            return response()->json(['route' => $deliveryRoute]);
        }

        return view('routes.show', ['route' => $deliveryRoute]);
    }

    public function edit(DeliveryRoute $deliveryRoute)
    {
        $branches = Branch::where('is_active', true)->get();
        return view('routes.edit', ['route' => $deliveryRoute, 'branches' => $branches]);
    }

    public function update(UpdateRouteRequest $request, DeliveryRoute $deliveryRoute)
    {
        $validated = $request->validated();
        $deliveryRoute->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Route updated successfully', 'route' => $deliveryRoute]);
        }

        return redirect()->route('routes.index')->with('success', 'Route updated successfully');
    }

    public function destroy(DeliveryRoute $deliveryRoute)
    {
        $deliveryRoute->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Route deleted successfully']);
        }

        return redirect()->route('routes.index')->with('success', 'Route deleted successfully');
    }
}
