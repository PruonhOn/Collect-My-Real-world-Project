<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Customer::with('creator:id,name');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('membership_card_no', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $customers = $query->latest()->paginate($request->per_page ?? 15);
        return $this->paginatedSuccess($customers);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'membership_card_no' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->all();
        $data['created_by'] = auth()->id();

        $customer = Customer::create($data);

        return $this->success($customer->load('creator:id,name'), 'Customer created successfully', 201);
    }

    public function show(int $id)
    {
        $customer = Customer::with(['creator:id,name', 'sales'])->find($id);

        if (!$customer) {
            return $this->error('Customer not found', 404);
        }

        return $this->success($customer);
    }

    public function update(Request $request, int $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->error('Customer not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'membership_card_no' => 'nullable|string|max:100',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $customer->update($request->all());

        return $this->success($customer->fresh()->load('creator:id,name'), 'Customer updated successfully');
    }

    public function destroy(int $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->error('Customer not found', 404);
        }

        $customer->delete();

        return $this->success(null, 'Customer deleted successfully');
    }

    public function addPoints(Request $request, int $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->error('Customer not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'points' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $customer->increment('points', $request->points);

        return $this->success([
            'customer' => $customer->fresh(),
            'points_added' => $request->points,
            'total_points' => $customer->fresh()->points,
        ], 'Loyalty points added successfully');
    }
}
