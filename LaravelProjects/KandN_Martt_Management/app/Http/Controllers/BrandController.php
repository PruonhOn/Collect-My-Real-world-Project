<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::latest()->paginate(20);
        return view('brands.index', compact('brands'));
    }

    public function create()
    {
        return view('brands.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['slug'] = Str::slug($request->name) . '-' . Str::random(4);

        Brand::create($validated);

        return redirect()->route('brands.index')->with('success', 'Brand created successfully');
    }

    public function show(Brand $brand)
    {
        $brand->load('products');
        return view('brands.show', compact('brand'));
    }

    public function edit(Brand $brand)
    {
        return view('brands.form', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $brand->update($validated);

        return redirect()->route('brands.index')->with('success', 'Brand updated successfully');
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();
        return redirect()->route('brands.index')->with('success', 'Brand deleted successfully');
    }
}
