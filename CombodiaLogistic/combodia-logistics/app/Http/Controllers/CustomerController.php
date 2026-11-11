<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with('user');

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->is_active !== null) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $customers = $query->latest()->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return response()->json(['customers' => $customers]);
        }

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $customer = Customer::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Customer created successfully', 'customer' => $customer], 201);
        }

        return redirect()->route('customers.index')->with('success', 'Customer created successfully');
    }

    public function show(Customer $customer)
    {
        $customer->load(['customerAddresses', 'shipments', 'invoices']);

        if (request()->ajax()) {
            return response()->json(['customer' => $customer]);
        }

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', $customer->is_active);

        $customer->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Customer updated successfully', 'customer' => $customer]);
        }

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Customer deleted successfully']);
        }

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully');
    }
}
