@extends('layouts.admin')
@section('title', 'Edit Car - K & N AutoCar')
@section('header', 'Edit Car')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 md:p-8">
        <form action="{{ route('admin.cars.update', $car) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name *</label>
                    <input type="text" name="name" value="{{ $car->name }}" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Category *</label>
                    <select name="category_id" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $car->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Brand *</label>
                    <input type="text" name="brand" value="{{ $car->brand }}" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Model *</label>
                    <input type="text" name="model" value="{{ $car->model }}" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Year *</label>
                    <input type="number" name="year" value="{{ $car->year }}" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Price *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium text-sm">$</span>
                        <input type="number" step="0.01" name="price" value="{{ $car->price }}" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl pl-8 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Sale Price</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium text-sm">$</span>
                        <input type="number" step="0.01" name="sale_price" value="{{ $car->sale_price }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl pl-8 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Mileage (km)</label>
                    <input type="number" step="0.01" name="mileage" value="{{ $car->mileage }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Fuel Type</label>
                    <select name="fuel_type" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                        <option value="">Select</option>
                        <option value="Petrol" {{ $car->fuel_type == 'Petrol' ? 'selected' : '' }}>Petrol</option>
                        <option value="Diesel" {{ $car->fuel_type == 'Diesel' ? 'selected' : '' }}>Diesel</option>
                        <option value="Electric" {{ $car->fuel_type == 'Electric' ? 'selected' : '' }}>Electric</option>
                        <option value="Hybrid" {{ $car->fuel_type == 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Transmission</label>
                    <select name="transmission" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                        <option value="">Select</option>
                        <option value="Automatic" {{ $car->transmission == 'Automatic' ? 'selected' : '' }}>Automatic</option>
                        <option value="Manual" {{ $car->transmission == 'Manual' ? 'selected' : '' }}>Manual</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Engine</label>
                    <input type="text" name="engine" value="{{ $car->engine }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Color</label>
                    <input type="text" name="color" value="{{ $car->color }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">VIN</label>
                    <input type="text" name="vin" value="{{ $car->vin }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Condition *</label>
                    <select name="condition" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                        <option value="used" {{ $car->condition == 'used' ? 'selected' : '' }}>Used</option>
                        <option value="new" {{ $car->condition == 'new' ? 'selected' : '' }}>New</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status *</label>
                    <select name="status" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                        <option value="available" {{ $car->status == 'available' ? 'selected' : '' }}>Available</option>
                        <option value="sold" {{ $car->status == 'sold' ? 'selected' : '' }}>Sold</option>
                        <option value="reserved" {{ $car->status == 'reserved' ? 'selected' : '' }}>Reserved</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Featured</label>
                    <select name="is_featured" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                        <option value="0" {{ !$car->is_featured ? 'selected' : '' }}>No</option>
                        <option value="1" {{ $car->is_featured ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Image</label>
                    <div class="relative">
                        <input type="file" name="image" accept="image/*" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-red-50 dark:file:bg-red-900/20 file:text-red-700 dark:file:text-red-300 file:text-xs file:font-semibold hover:file:bg-red-100 dark:hover:file:bg-red-900/30 transition-all">
                    </div>
                    @if($car->image)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Current: {{ $car->image }}
                        </p>
                    @endif
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                    <textarea name="description" rows="4" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">{{ $car->description }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Features (one per line)</label>
                    <textarea name="features" rows="4" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">{{ $car->features ? implode("\n", $car->features) : '' }}</textarea>
                </div>
            </div>
            <div class="mt-8 flex items-center gap-3 pt-6 border-t border-gray-100 dark:border-gray-800">
                <button type="submit" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Update Car
                </button>
                <a href="{{ route('admin.cars.index') }}" class="inline-flex items-center gap-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 px-6 py-2.5 rounded-xl text-sm font-semibold transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
