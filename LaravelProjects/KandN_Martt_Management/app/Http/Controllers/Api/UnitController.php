<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Unit::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $units = $query->latest()->paginate($request->per_page ?? 15);
        return $this->paginatedSuccess($units);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'operator' => 'nullable|string|max:10',
            'operation_value' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $unit = Unit::create($request->all());

        return $this->success($unit, 'Unit created successfully', 201);
    }

    public function show(int $id)
    {
        $unit = Unit::with('products:id,name,unit_id')->find($id);

        if (!$unit) {
            return $this->error('Unit not found', 404);
        }

        return $this->success($unit);
    }

    public function update(Request $request, int $id)
    {
        $unit = Unit::find($id);

        if (!$unit) {
            return $this->error('Unit not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'short_name' => 'sometimes|string|max:50',
            'operator' => 'nullable|string|max:10',
            'operation_value' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $unit->update($request->only(['name', 'short_name', 'operator', 'operation_value', 'status']));

        return $this->success($unit->fresh(), 'Unit updated successfully');
    }

    public function destroy(int $id)
    {
        $unit = Unit::find($id);

        if (!$unit) {
            return $this->error('Unit not found', 404);
        }

        if ($unit->products()->count() > 0) {
            return $this->error('Cannot delete unit with associated products', 409);
        }

        $unit->delete();

        return $this->success(null, 'Unit deleted successfully');
    }
}
