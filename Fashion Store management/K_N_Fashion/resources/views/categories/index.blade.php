@extends('layouts.admin')
@section('title', 'Categories')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Categories</h1>
        <a href="{{ route('categories.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">+ New Category</a>
    </div>
    <form method="GET" action="{{ route('categories.index') }}" class="mb-4">
        <div class="flex gap-4">
            <input type="text" name="search" placeholder="Search categories..." value="{{ request('search') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">Search</button>
        </div>
    </form>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Parent Category</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-3">{{ $category->name }}</td>
                    <td class="px-4 py-3">{{ $category->parent ? $category->parent->name : '-' }}</td>
                    <td class="px-4 py-3">
                        @if ($category->status == 'active')
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Active</span>
                        @else
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Inactive</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 flex gap-2">
                        <a href="{{ route('categories.edit', $category) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">Edit</a>
                        <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">No categories found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $categories->links() }}
    </div>
</div>
@endsection
