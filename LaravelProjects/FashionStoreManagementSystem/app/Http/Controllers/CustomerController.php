<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->withCount(['orders as order_count' => fn ($q) => $q->where('status', '!=', 'voided')])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim($request->input('q'));
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create');
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::create($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer "'.$customer->name.'" created.');
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $customer->load([
            'orders' => fn ($q) => $q->with(['items', 'user'])->orderByDesc('created_at')->limit(20),
        ]);

        $totalSpent = (float) $customer->orders()
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->sum(DB::raw('total - refunded_amount'));

        return view('customers.show', compact('customer', 'totalSpent'));
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer deleted.');
    }
}
