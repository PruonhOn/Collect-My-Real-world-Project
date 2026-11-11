@extends('layouts.admin')
@section('title', 'Cars - K & N AutoCar')
@section('header')
<div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="w-9 h-9 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 17v2a1 1 0 001 1h1a1 1 0 001-1v-2M16 17v2a1 1 0 001 1h1a1 1 0 001-1v-2M3 11l2-5h14l2 5M3 11h18v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5z"/></svg>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Cars</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400">Manage your inventory</p>
        </div>
    </div>
    <a href="{{ route('admin.cars.create') }}" class="inline-flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow-md">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Car
    </a>
</div>
@endsection

@section('content')
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50 text-left">
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Image</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Name</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Brand</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Price</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Featured</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cars as $car)
                    <tr class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3">
                            @if($car->image)
                                <img src="{{ asset('storage/' . $car->image) }}" class="w-11 h-11 object-cover rounded-xl">
                            @else
                                <div class="w-11 h-11 bg-gray-100 dark:bg-gray-800 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 17v2a1 1 0 001 1h1a1 1 0 001-1v-2M16 17v2a1 1 0 001 1h1a1 1 0 001-1v-2M3 11l2-5h14l2 5M3 11h18v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5z"/></svg>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.cars.edit', $car) }}" class="font-medium text-gray-900 dark:text-white hover:text-red-600 dark:hover:text-red-400">{{ $car->name }}</a>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $car->model }} &middot; {{ $car->year }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $car->brand }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900 dark:text-white">${{ number_format($car->price) }}</div>
                            @if($car->sale_price)
                                <div class="text-xs text-red-600">Sale: ${{ number_format($car->sale_price) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium
                                {{ $car->status === 'available' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : '' }}
                                {{ $car->status === 'sold' ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' : '' }}
                                {{ $car->status === 'reserved' ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300' : '' }}">
                                <span class="w-1.5 h-1.5 rounded-full
                                    {{ $car->status === 'available' ? 'bg-emerald-500' : '' }}
                                    {{ $car->status === 'sold' ? 'bg-red-500' : '' }}
                                    {{ $car->status === 'reserved' ? 'bg-amber-500' : '' }}"></span>
                                {{ ucfirst($car->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($car->is_featured)
                                <span class="text-amber-500 text-lg">&#9733;</span>
                            @else
                                <span class="text-gray-300 dark:text-gray-600 text-lg">&#9733;</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.cars.edit', $car) }}" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form action="{{ route('admin.cars.destroy', $car) }}" method="POST" onsubmit="return confirm('Delete this car permanently?')">
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
                        <td colspan="7" class="px-4 py-16 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 17v2a1 1 0 001 1h1a1 1 0 001-1v-2M16 17v2a1 1 0 001 1h1a1 1 0 001-1v-2M3 11l2-5h14l2 5M3 11h18v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5z"/></svg>
                            <p class="text-gray-500 dark:text-gray-400 text-sm mb-1">No cars in inventory</p>
                            <p class="text-gray-400 dark:text-gray-500 text-xs">Get started by adding your first car.</p>
                            <a href="{{ route('admin.cars.create') }}" class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700">+ Add Car</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($cars, 'links'))
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
            {{ $cars->links() }}
        </div>
    @endif
</div>
@endsection
