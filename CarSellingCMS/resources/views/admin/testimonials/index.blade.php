@extends('layouts.admin')
@section('title', 'Testimonials - K & N AutoCar')
@section('header')
<div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="w-9 h-9 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Testimonials</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400">Customer reviews and feedback</p>
        </div>
    </div>
    <a href="{{ route('admin.testimonials.create') }}" class="inline-flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow-md">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Testimonial
    </a>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
    @forelse($testimonials as $t)
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 hover:shadow-md transition-all">
            <div class="flex items-center gap-1 mb-3">
                @for($i = 1; $i <= 5; $i++)
                    <svg class="w-4 h-4 {{ $i <= $t->rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                @endfor
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed line-clamp-3">&ldquo;{{ $t->content }}&rdquo;</p>
            <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600 rounded-full flex items-center justify-center text-xs font-bold text-gray-600 dark:text-gray-300 flex-shrink-0">
                        {{ strtoupper(substr($t->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $t->name }}</p>
                        @if($t->position)
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t->position }}</p>
                        @endif
                    </div>
                </div>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-medium
                    {{ $t->is_active ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $t->is_active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                    {{ $t->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div class="flex items-center justify-end gap-2 mt-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                <a href="{{ route('admin.testimonials.edit', $t) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
                <form action="{{ route('admin.testimonials.destroy', $t) }}" method="POST" onsubmit="return confirm('Delete this testimonial?')">
                    @csrf @method('DELETE')
                    <button class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="lg:col-span-3 text-center py-16 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            <p class="text-gray-500 dark:text-gray-400 text-sm">No testimonials yet</p>
        </div>
    @endforelse
</div>
@if(method_exists($testimonials, 'links'))
    <div class="mt-4">{{ $testimonials->links() }}</div>
@endif
@endsection
