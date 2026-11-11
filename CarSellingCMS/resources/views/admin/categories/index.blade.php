@extends('layouts.admin')
@section('title', 'Categories - K & N AutoCar')
@section('header')
<div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="w-9 h-9 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Categories</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400">Organize your car inventory</p>
        </div>
    </div>
    <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow-md">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Category
    </a>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
    @forelse($categories as $cat)
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 hover:shadow-md transition-all">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 bg-gray-100 dark:bg-gray-800 rounded-xl flex items-center justify-center flex-shrink-0 overflow-hidden">
                    @if($cat->image)
                        <img src="{{ asset('storage/' . $cat->image) }}" class="w-full h-full object-cover">
                    @else
                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $cat->name }}</h3>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-medium
                            {{ $cat->is_active ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $cat->is_active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                            {{ $cat->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $cat->description ?? 'No description' }}</p>
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $cat->cars_count }}</span> cars
                        </span>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.categories.edit', $cat) }}" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST" onsubmit="return confirm('Delete this category?')">
                                @csrf @method('DELETE')
                                <button class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="lg:col-span-2 text-center py-16 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-1">No categories yet</p>
            <p class="text-gray-400 dark:text-gray-500 text-xs">Create your first category to organize cars.</p>
        </div>
    @endforelse
</div>
@if(method_exists($categories, 'links'))
    <div class="mt-4">{{ $categories->links() }}</div>
@endif
@endsection
