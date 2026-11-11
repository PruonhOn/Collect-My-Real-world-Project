@extends('layouts.admin')
@section('title', 'Edit Testimonial - K & N AutoCar')
@section('header', 'Edit Testimonial')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 md:p-8">
        <form action="{{ route('admin.testimonials.update', $testimonial) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name *</label>
                        <input type="text" name="name" value="{{ $testimonial->name }}" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Position</label>
                        <input type="text" name="position" value="{{ $testimonial->position }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Content *</label>
                    <textarea name="content" rows="4" required class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">{{ $testimonial->content }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Rating</label>
                        <select name="rating" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                            <option value="5" {{ $testimonial->rating == 5 ? 'selected' : '' }}>5 Stars</option>
                            <option value="4" {{ $testimonial->rating == 4 ? 'selected' : '' }}>4 Stars</option>
                            <option value="3" {{ $testimonial->rating == 3 ? 'selected' : '' }}>3 Stars</option>
                            <option value="2" {{ $testimonial->rating == 2 ? 'selected' : '' }}>2 Stars</option>
                            <option value="1" {{ $testimonial->rating == 1 ? 'selected' : '' }}>1 Star</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Active</label>
                        <select name="is_active" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                            <option value="1" {{ $testimonial->is_active ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ !$testimonial->is_active ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Image</label>
                        <input type="file" name="image" accept="image/*" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-red-50 dark:file:bg-red-900/20 file:text-red-700 dark:file:text-red-300 file:text-xs file:font-semibold hover:file:bg-red-100 dark:hover:file:bg-red-900/30 transition-all">
                    </div>
                </div>
            </div>
            <div class="mt-8 flex items-center gap-3 pt-6 border-t border-gray-100 dark:border-gray-800">
                <button type="submit" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Update Testimonial
                </button>
                <a href="{{ route('admin.testimonials.index') }}" class="inline-flex items-center gap-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 px-6 py-2.5 rounded-xl text-sm font-semibold transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
