<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Brand::with('creator:id,name');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $brands = $query->latest()->paginate($request->per_page ?? 15);
        return $this->paginatedSuccess($brands);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $brand = Brand::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(4),
            'description' => $request->description,
            'logo' => $request->logo,
            'status' => $request->status,
            'created_by' => auth()->id(),
        ]);

        return $this->success($brand->load('creator:id,name'), 'Brand created successfully', 201);
    }

    public function show(int $id)
    {
        $brand = Brand::with(['creator:id,name', 'products'])->find($id);

        if (!$brand) {
            return $this->error('Brand not found', 404);
        }

        return $this->success($brand);
    }

    public function update(Request $request, int $id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return $this->error('Brand not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string|max:255',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->only(['name', 'description', 'logo', 'status']);

        if ($request->has('name') && $request->name !== $brand->name) {
            $data['slug'] = Str::slug($request->name) . '-' . Str::random(4);
        }

        $brand->update($data);

        return $this->success($brand->fresh()->load('creator:id,name'), 'Brand updated successfully');
    }

    public function destroy(int $id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return $this->error('Brand not found', 404);
        }

        if ($brand->products()->count() > 0) {
            return $this->error('Cannot delete brand with associated products', 409);
        }

        $brand->delete();

        return $this->success(null, 'Brand deleted successfully');
    }
}
