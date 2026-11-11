@php
    $categories = $categories ?? App\Models\Category::where('is_active', true)->get();
    $brands = $brands ?? App\Models\Car::where('status','available')->select('brand')->distinct()->pluck('brand');
@endphp
@extends('layouts.public')
@section('title', 'Inventory - K & N AutoCar')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col md:flex-row gap-8">
        {{-- Sidebar Filters --}}
        <aside class="md:w-72 flex-shrink-0">
            <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                <h3 class="font-bold text-lg text-gray-900 mb-4">Filters</h3>
                <form action="{{ route('search') }}" method="GET">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Keyword</label>
                            <input type="text" name="keyword" value="{{ request('keyword') }}" class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select name="category_id" class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500">
                                <option value="">All</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                            <select name="brand" class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500">
                                <option value="">All</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand }}" {{ request('brand') == $brand ? 'selected' : '' }}>{{ $brand }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Condition</label>
                            <select name="condition" class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500">
                                <option value="">All</option>
                                <option value="new" {{ request('condition') == 'new' ? 'selected' : '' }}>New</option>
                                <option value="used" {{ request('condition') == 'used' ? 'selected' : '' }}>Used</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fuel Type</label>
                            <select name="fuel_type" class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500">
                                <option value="">All</option>
                                <option value="Petrol" {{ request('fuel_type') == 'Petrol' ? 'selected' : '' }}>Petrol</option>
                                <option value="Diesel" {{ request('fuel_type') == 'Diesel' ? 'selected' : '' }}>Diesel</option>
                                <option value="Electric" {{ request('fuel_type') == 'Electric' ? 'selected' : '' }}>Electric</option>
                                <option value="Hybrid" {{ request('fuel_type') == 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Transmission</label>
                            <select name="transmission" class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500">
                                <option value="">All</option>
                                <option value="Automatic" {{ request('transmission') == 'Automatic' ? 'selected' : '' }}>Automatic</option>
                                <option value="Manual" {{ request('transmission') == 'Manual' ? 'selected' : '' }}>Manual</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Min Price</label>
                                <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="0" class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Max Price</label>
                                <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="999999" class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-red-600 text-white py-2.5 rounded-lg font-semibold hover:bg-red-700 transition">Apply Filters</button>
                        <a href="{{ route('cars.index') }}" class="block text-center text-sm text-gray-500 hover:text-red-600 mt-2">Clear Filters</a>
                    </div>
                </form>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Our Inventory</h1>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600">{{ $cars->total() }} cars found</span>
                    <select name="sort" onchange="window.location.href='{{ request()->url() }}?sort='+this.value+'{{ request()->except('sort') ? '&'.http_build_query(request()->except('sort')) : '' }}'" class="border-gray-300 rounded-lg text-sm px-3 py-1.5">
                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                        <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="year_desc" {{ request('sort') == 'year_desc' ? 'selected' : '' }}>Year: Newest</option>
                        <option value="year_asc" {{ request('sort') == 'year_asc' ? 'selected' : '' }}>Year: Oldest</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($cars as $car)
                    <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition group">
                        <div class="relative overflow-hidden h-44">
                            @if($car->image)
                                <img src="{{ asset('storage/' . $car->image) }}" alt="{{ $car->name }}" class="w-full h-full object-cover group-hover:scale-105 transition">
                            @else
                                <div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                </div>
                            @endif
                            @if($car->sale_price)
                                <div class="absolute top-2 left-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">SALE</div>
                            @endif
                            @if($car->condition === 'new')
                                <div class="absolute top-2 right-2 bg-green-600 text-white text-xs font-bold px-2 py-1 rounded">NEW</div>
                            @endif
                        </div>
                        <div class="p-4">
                            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ $car->brand }} {{ $car->year }}</div>
                            <h3 class="font-bold text-gray-900 mb-1 truncate">{{ $car->name }}</h3>
                            <div class="flex items-center space-x-3 text-xs text-gray-600 mb-3">
                                <span>{{ $car->mileage ? number_format($car->mileage) . ' km' : 'N/A' }}</span>
                                <span>{{ $car->fuel_type }}</span>
                                <span>{{ $car->transmission }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div>
                                    @if($car->sale_price)
                                        <span class="font-bold text-red-600">${{ number_format($car->sale_price) }}</span>
                                        <span class="text-xs text-gray-400 line-through ml-1">${{ number_format($car->price) }}</span>
                                    @else
                                        <span class="font-bold text-gray-900">${{ number_format($car->price) }}</span>
                                    @endif
                                </div>
                                <a href="{{ route('cars.show', $car->slug) }}" class="text-sm text-red-600 hover:text-red-700 font-medium">Details &rarr;</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-16">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        <p class="text-gray-500 text-lg">No cars found matching your criteria.</p>
                        <a href="{{ route('cars.index') }}" class="text-red-600 hover:text-red-700 mt-2 inline-block">Clear filters &rarr;</a>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $cars->links() }}
            </div>
        </div>
    </div>
</section>
@endsection