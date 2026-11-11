@extends('layouts.admin')
@section('title', 'Edit Supplier')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Edit Supplier</h1>
    @if ($errors->any())
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Name</label>
                <input type="text" name="name" value="{{ old('name', $supplier->name) }}" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Contact Person</label>
                <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email', $supplier->email) }}" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Address</label>
                <textarea name="address" rows="2" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">{{ old('address', $supplier->address) }}</textarea>
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">City</label>
                <input type="text" name="city" value="{{ old('city', $supplier->city) }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="status" value="active" {{ old('status', $supplier->status) == 'active' ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600">
                    <span class="ml-2 text-gray-700 dark:text-gray-300">Active</span>
                </label>
            </div>
        </div>
        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">Update</button>
            <a href="{{ route('suppliers.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg">Cancel</a>
        </div>
    </form>
</div>
@endsection
