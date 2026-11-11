@extends('layouts.admin')
@section('title', 'Expenses')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Expenses</h1>
        <a href="{{ route('expenses.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">+ New Expense</a>
    </div>
    <form method="GET" action="{{ route('expenses.index') }}" class="mb-4">
        <div class="flex gap-4 items-end">
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Category</label>
                <select name="category" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
                    <option value="">All</option>
                    <option value="Rent" {{ request('category') == 'Rent' ? 'selected' : '' }}>Rent</option>
                    <option value="Utilities" {{ request('category') == 'Utilities' ? 'selected' : '' }}>Utilities</option>
                    <option value="Salaries" {{ request('category') == 'Salaries' ? 'selected' : '' }}>Salaries</option>
                    <option value="Marketing" {{ request('category') == 'Marketing' ? 'selected' : '' }}>Marketing</option>
                    <option value="Maintenance" {{ request('category') == 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="Other" {{ request('category') == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">Filter</button>
            <a href="{{ route('expenses.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Reset</a>
        </div>
    </form>
    <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
        <p class="text-lg font-semibold text-gray-900 dark:text-white">Total Expenses: <span class="text-red-600">{{ number_format($totalAmount ?? 0, 2) }}</span></p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Created By</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($expenses as $expense)
                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-3">{{ $expense->category }}</td>
                    <td class="px-4 py-3">{{ number_format($expense->amount, 2) }}</td>
                    <td class="px-4 py-3">{{ $expense->description ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $expense->date ? $expense->date->format('d M Y') : $expense->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">{{ $expense->createdBy ? $expense->createdBy->name : '-' }}</td>
                    <td class="px-4 py-3 flex gap-2">
                        <a href="{{ route('expenses.edit', $expense) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">Edit</a>
                        <form action="{{ route('expenses.destroy', $expense) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">No expenses found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $expenses->links() }}
    </div>
</div>
@endsection
