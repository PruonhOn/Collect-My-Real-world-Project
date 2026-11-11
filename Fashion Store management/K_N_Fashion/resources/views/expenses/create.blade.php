@extends('layouts.admin')
@section('title', 'Create Expense')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Create Expense</h1>
    @if ($errors->any())
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('expenses.store') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Category</label>
            <select name="category" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
                <option value="">Select Category</option>
                <option value="Rent" {{ old('category') == 'Rent' ? 'selected' : '' }}>Rent</option>
                <option value="Utilities" {{ old('category') == 'Utilities' ? 'selected' : '' }}>Utilities</option>
                <option value="Salaries" {{ old('category') == 'Salaries' ? 'selected' : '' }}>Salaries</option>
                <option value="Marketing" {{ old('category') == 'Marketing' ? 'selected' : '' }}>Marketing</option>
                <option value="Maintenance" {{ old('category') == 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                <option value="Other" {{ old('category') == 'Other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Amount</label>
            <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Description</label>
            <textarea name="description" rows="3" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">{{ old('description') }}</textarea>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Date</label>
            <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
        </div>
        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">Create</button>
            <a href="{{ route('expenses.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg">Cancel</a>
        </div>
    </form>
</div>
@endsection
