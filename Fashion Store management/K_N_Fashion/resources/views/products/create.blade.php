@extends('layouts.admin')
@section('title', 'Create Product')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-3xl">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Create Product</h1>
    @if ($errors->any())
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Basic Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">SKU</label>
                <input type="text" name="sku" value="{{ old('sku') }}" placeholder="Auto-generated if empty" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Category</label>
                <select name="category_id" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
                    <option value="">Select Category</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Supplier</label>
                <select name="supplier_id" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
                    <option value="">Select Supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Pricing</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Cost Price</label>
                <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Selling Price</label>
                <input type="number" step="0.01" name="selling_price" value="{{ old('selling_price') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
        </div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Description</h2>
        <div class="mb-6">
            <textarea name="description" rows="4" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">{{ old('description') }}</textarea>
        </div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Image</h2>
        <div class="mb-6">
            <input type="file" name="image" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
        </div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Status</h2>
        <div class="mb-6">
            <label class="inline-flex items-center">
                <input type="checkbox" name="status" value="active" {{ old('status', 'active') == 'active' ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600">
                <span class="ml-2 text-gray-700 dark:text-gray-300">Active</span>
            </label>
        </div>
        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">Create</button>
            <a href="{{ route('products.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg">Cancel</a>
        </div>
    </form>
</div>
@endsection
