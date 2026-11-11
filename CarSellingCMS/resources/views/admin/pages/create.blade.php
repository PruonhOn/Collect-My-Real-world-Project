@extends('layouts.admin')
@section('title', 'Create Page - K & N AutoCar')
@section('header', 'Create Page')
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 md:p-8">
        <form action="{{ route('admin.pages.store') }}" method="POST">
            @csrf
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title *</label>
                    <input type="text" name="title" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Content</label>
                    <textarea name="content" rows="12" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Meta Title</label>
                        <input type="text" name="meta_title" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Template</label>
                        <select name="template" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                            <option value="default">Default</option>
                            <option value="full-width">Full Width</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Published</label>
                        <select name="is_published" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Meta Description</label>
                        <textarea name="meta_description" rows="2" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all"></textarea>
                    </div>
                </div>
            </div>
            <div class="mt-8 flex items-center gap-3 pt-6 border-t border-gray-100 dark:border-gray-800">
                <button type="submit" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create Page
                </button>
                <a href="{{ route('admin.pages.index') }}" class="inline-flex items-center gap-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 px-6 py-2.5 rounded-xl text-sm font-semibold transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
