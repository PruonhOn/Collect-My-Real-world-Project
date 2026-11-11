<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminProductController extends Controller
{
    public function index()
    {
        return Product::with('category')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|string',
            'status' => 'boolean',
            'featured' => 'boolean',
            'stock' => 'integer|min:0',
            'size' => 'nullable|string',
            'color' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('image_file')) {
            $data['image'] = $request->file('image_file')->store('products', 'public');
        }

        $data['status'] = $data['status'] ?? true;
        $data['featured'] = $data['featured'] ?? false;

        $product = Product::create($data);
        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return $product->load('category');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|string',
            'status' => 'boolean',
            'featured' => 'boolean',
            'stock' => 'integer|min:0',
            'size' => 'nullable|string',
            'color' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('image_file')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image_file')->store('products', 'public');
        }

        $product->update($data);
        return $product->load('category');
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        if ($product->images) {
            foreach ($product->images as $img) {
                Storage::disk('public')->delete($img);
            }
        }
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }

    public function toggleStatus(Product $product)
    {
        $product->update(['status' => !$product->status]);
        return $product;
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);
        $path = $request->file('image')->store('products', 'public');
        return response()->json([
            'url' => Storage::url($path),
            'path' => $path,
        ]);
    }
}
