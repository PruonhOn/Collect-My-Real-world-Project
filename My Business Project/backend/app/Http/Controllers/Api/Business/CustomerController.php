<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Api\Controller;
use App\Models\Customer;
use App\Services\Features\FeatureGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(protected FeatureGate $gate) {}

    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $sort = $request->query('sort', 'created_at');
        $direction = $request->query('direction', 'desc');
        if (! in_array($sort, ['first_name', 'last_name', 'email', 'created_at'], true)) {
            $sort = 'created_at';
        }
        $query->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc');

        $customers = $query->paginate(min(100, (int) $request->query('per_page', 15)));

        return response()->json($customers);
    }

    public function store(Request $request): JsonResponse
    {
        $business = app('current.business');

        if (! $this->gate->checkLimit($business, 'max_customers')) {
            return response()->json([
                'message' => 'Customer limit reached for your current plan.',
                'limit' => $this->gate->limit($business, 'max_customers'),
                'upgrade_required' => true,
            ], 422);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['business_id'] = $business->id;
        $customer = Customer::create($validated);
        $this->gate->incrementUsage($business, 'max_customers');

        return response()->json(['data' => $customer], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorizeSameBusiness($customer);

        return response()->json([
            'data' => $customer->load(['orders' => fn ($q) => $q->latest()->limit(20)]),
        ]);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeSameBusiness($customer);

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer->update($validated);

        return response()->json(['data' => $customer->fresh()]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $business = app('current.business');
        $this->authorizeSameBusiness($customer);

        $customer->delete();
        $this->gate->incrementUsage($business, 'max_customers', -1);

        return response()->json(['message' => 'Customer deleted.']);
    }

    protected function authorizeSameBusiness(Customer $customer): void
    {
        $business = app('current.business');
        if ($customer->business_id !== $business->id) {
            abort(404, 'Customer not found.');
        }
    }
}
