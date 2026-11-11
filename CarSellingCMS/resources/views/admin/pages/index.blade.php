@extends('layouts.admin')
@section('title', 'Pages - K & N AutoCar')
@section('header')
<div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="w-9 h-9 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">CMS Pages</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400">Manage website content pages</p>
        </div>
    </div>
    <a href="{{ route('admin.pages.create') }}" class="inline-flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow-md">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Page
    </a>
</div>
@endsection

@section('content')
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50 text-left">
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Title</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Slug</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Template</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Published</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pages as $page)
                    <tr class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3.5 font-medium text-gray-900 dark:text-white">{{ $page->title }}</td>
                        <td class="px-4 py-3.5 text-gray-500 dark:text-gray-400 font-mono text-xs">/{{ $page->slug }}</td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                {{ $page->template ?? 'default' }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5">
                            @if($page->is_published)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Published
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    Draft
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" onsubmit="return confirm('Delete this page?')">
                                    @csrf @method('DELETE')
                                    <button class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-16 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">No pages yet</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($pages, 'links'))
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
            {{ $pages->links() }}
        </div>
    @endif
</div>
@endsection
