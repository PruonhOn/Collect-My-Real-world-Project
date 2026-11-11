@extends('layouts.admin')
@section('title', 'Edit Category')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Edit Category</h1>
    @if ($errors->any())
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('categories.update', $category) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Name</label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Description</label>
            <textarea name="description" rows="3" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">{{ old('description', $category->description) }}</textarea>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Parent Category</label>
            <select name="parent_id" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
                <option value="">None</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('parent_id', $category->parent_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Status</label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="status" value="active" {{ old('status', $category->status) == 'active' ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600">
                <span class="ml-2 text-gray-700 dark:text-gray-300">Active</span>
            </label>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Sort Order</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
        </div>
        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">Update</button>
            <a href="{{ route('categories.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg">Cancel</a>
        </div>
    </form>
</div>
@endsection
