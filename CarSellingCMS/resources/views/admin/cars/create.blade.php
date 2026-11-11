@extends('layouts.admin')
@section('title', 'Add Car - K & N AutoCar')
@section('header', 'Add New Car')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 md:p-8">
        <form action="{{ route('admin.cars.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name *</label>
                    <input type="text" name="name" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 dark:placeholder-gray-500 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Category *</label>
                    <select name="category_id" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Brand *</label>
                    <input type="text" name="brand" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 dark:placeholder-gray-500 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Model *</label>
                    <input type="text" name="model" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 dark:placeholder-gray-500 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Year *</label>
                    <input type="number" name="year" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 dark:placeholder-gray-500 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Price *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium text-sm">$</span>
                        <input type="number" step="0.01" name="price" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl pl-8 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Sale Price</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium text-sm">$</span>
                        <input type="number" step="0.01" name="sale_price" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl pl-8 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Mileage (km)</label>
                    <input type="number" step="0.01" name="mileage" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Fuel Type</label>
                    <select name="fuel_type" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                        <option value="">Select</option>
                        <option value="Petrol">Petrol</option>
                        <option value="Diesel">Diesel</option>
                        <option value="Electric">Electric</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Transmission</label>
                    <select name="transmission" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                        <option value="">Select</option>
                        <option value="Automatic">Automatic</option>
                        <option value="Manual">Manual</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Engine</label>
                    <input type="text" name="engine" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Color</label>
                    <input type="text" name="color" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">VIN</label>
                    <input type="text" name="vin" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Condition *</label>
                    <select name="condition" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                        <option value="used">Used</option>
                        <option value="new">New</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status *</label>
                    <select name="status" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                        <option value="available">Available</option>
                        <option value="sold">Sold</option>
                        <option value="reserved">Reserved</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Featured</label>
                    <select name="is_featured" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Image</label>
                    <div class="relative">
                        <input type="file" name="image" accept="image/*" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-red-50 dark:file:bg-red-900/20 file:text-red-700 dark:file:text-red-300 file:text-xs file:font-semibold hover:file:bg-red-100 dark:hover:file:bg-red-900/30 transition-all">
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                    <textarea name="description" rows="4" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 dark:placeholder-gray-500 transition-all"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Features (one per line)</label>
                    <textarea name="features" rows="4" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all" placeholder="Sunroof&#10;Leather Seats&#10;Backup Camera"></textarea>
                </div>
            </div>
            <div class="mt-8 flex items-center gap-3 pt-6 border-t border-gray-100 dark:border-gray-800">
                <button type="submit" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create Car
                </button>
                <a href="{{ route('admin.cars.index') }}" class="inline-flex items-center gap-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 px-6 py-2.5 rounded-xl text-sm font-semibold transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
