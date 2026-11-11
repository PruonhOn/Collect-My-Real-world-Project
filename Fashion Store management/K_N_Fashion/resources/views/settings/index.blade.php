@extends('layouts.admin')
@section('title', 'Settings')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Store Settings</h1>
    @if (session('success'))
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-4">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('settings.update') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Store Name</label>
            <input type="text" name="store_name" value="{{ old('store_name', $settings->store_name ?? '') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Store Address</label>
            <textarea name="store_address" rows="2" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">{{ old('store_address', $settings->store_address ?? '') }}</textarea>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Store Phone</label>
            <input type="text" name="store_phone" value="{{ old('store_phone', $settings->store_phone ?? '') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Store Email</label>
            <input type="email" name="store_email" value="{{ old('store_email', $settings->store_email ?? '') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Currency</label>
            <select name="currency" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
                <option value="USD" {{ old('currency', $settings->currency ?? '') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                <option value="EUR" {{ old('currency', $settings->currency ?? '') == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                <option value="GBP" {{ old('currency', $settings->currency ?? '') == 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                <option value="BDT" {{ old('currency', $settings->currency ?? '') == 'BDT' ? 'selected' : '' }}>BDT (৳)</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Tax Rate (%)</label>
            <input type="number" step="0.01" name="tax_rate" value="{{ old('tax_rate', $settings->tax_rate ?? 0) }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
        </div>
        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">Update Settings</button>
        </div>
    </form>
</div>
@endsection
