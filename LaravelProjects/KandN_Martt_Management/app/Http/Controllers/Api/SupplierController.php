<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Supplier::with('creator:id,name');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $suppliers = $query->latest()->paginate($request->per_page ?? 15);
        return $this->paginatedSuccess($suppliers);
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
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->all();
        $data['created_by'] = auth()->id();

        $supplier = Supplier::create($data);

        return $this->success($supplier->load('creator:id,name'), 'Supplier created successfully', 201);
    }

    public function show(int $id)
    {
        $supplier = Supplier::with(['creator:id,name', 'purchases'])->find($id);

        if (!$supplier) {
            return $this->error('Supplier not found', 404);
        }

        return $this->success($supplier);
    }

    public function update(Request $request, int $id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return $this->error('Supplier not found', 404);
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
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:100',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $supplier->update($request->all());

        return $this->success($supplier->fresh()->load('creator:id,name'), 'Supplier updated successfully');
    }

    public function destroy(int $id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return $this->error('Supplier not found', 404);
        }

        if ($supplier->purchases()->count() > 0) {
            return $this->error('Cannot delete supplier with associated purchases', 409);
        }

        $supplier->delete();

        return $this->success(null, 'Supplier deleted successfully');
    }
}
