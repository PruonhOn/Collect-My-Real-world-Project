@extends('layouts.public')
@section('title', $car->name . ' - K & N AutoCar')
@section('meta_description', Str::limit($car->description, 160))

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="{{ route('home') }}" class="hover:text-red-600">Home</a> /
        <a href="{{ route('cars.index') }}" class="hover:text-red-600">Cars</a> /
        <span class="text-gray-900">{{ $car->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
        {{-- Images --}}
        <div>
            <div class="rounded-xl overflow-hidden bg-gray-100 mb-4">
                @if($car->image)
                    <img src="{{ asset('storage/' . $car->image) }}" alt="{{ $car->name }}" class="w-full h-96 object-cover">
                @else
                    <div class="w-full h-96 bg-gray-200 flex items-center justify-center text-gray-400">
                        <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    </div>
                @endif
            </div>
            @if($car->images->count() > 0)
                <div class="grid grid-cols-4 gap-2">
                    @if($car->image)
                        <div class="rounded-lg overflow-hidden h-20 cursor-pointer border-2 border-red-500">
                            <img src="{{ asset('storage/' . $car->image) }}" class="w-full h-full object-cover">
                        </div>
                    @endif
                    @foreach($car->images as $image)
                        <div class="rounded-lg overflow-hidden h-20 cursor-pointer border hover:border-red-500 transition">
                            <img src="{{ asset('storage/' . $image->image) }}" class="w-full h-full object-cover">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Details --}}
        <div>
            <div class="flex items-center space-x-2 mb-2">
                <span class="text-sm text-gray-500">{{ $car->brand }}</span>
                <span class="text-gray-300">|</span>
                <span class="text-sm text-gray-500">{{ $car->year }}</span>
                @if($car->condition === 'new')
                    <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5 rounded">New</span>
                @else
                    <span class="bg-orange-100 text-orange-800 text-xs font-semibold px-2 py-0.5 rounded">Used</span>
                @endif
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $car->name }}</h1>

            <div class="mb-6">
                @if($car->sale_price)
                    <span class="text-3xl font-bold text-red-600">${{ number_format($car->sale_price) }}</span>
                    <span class="text-xl text-gray-400 line-through ml-2">${{ number_format($car->price) }}</span>
                    <span class="text-sm text-green-600 ml-2">Save ${{ number_format($car->price - $car->sale_price) }}</span>
                @else
                    <span class="text-3xl font-bold text-gray-900">${{ number_format($car->price) }}</span>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-3">
                    <span class="text-xs text-gray-500 block">Mileage</span>
                    <span class="font-semibold">{{ $car->mileage ? number_format($car->mileage) . ' km' : 'N/A' }}</span>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <span class="text-xs text-gray-500 block">Fuel Type</span>
                    <span class="font-semibold">{{ $car->fuel_type ?? 'N/A' }}</span>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <span class="text-xs text-gray-500 block">Transmission</span>
                    <span class="font-semibold">{{ $car->transmission ?? 'N/A' }}</span>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <span class="text-xs text-gray-500 block">Engine</span>
                    <span class="font-semibold">{{ $car->engine ?? 'N/A' }}</span>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <span class="text-xs text-gray-500 block">Color</span>
                    <span class="font-semibold">{{ $car->color ?? 'N/A' }}</span>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <span class="text-xs text-gray-500 block">VIN</span>
                    <span class="font-semibold">{{ $car->vin ?? 'N/A' }}</span>
                </div>
            </div>

            @if($car->features && count($car->features) > 0)
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Features</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($car->features as $feature)
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-1 rounded">{{ $feature }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($car->isAvailable())
                <a href="{{ route('payment.checkout', $car->id) }}" class="block w-full bg-red-600 hover:bg-red-700 text-white text-center py-3 rounded-lg font-bold text-lg transition shadow-lg mb-3">
                    Buy Now - ${{ number_format($car->getDisplayPrice()) }}
                </a>
                <p class="text-xs text-gray-500 text-center">Secure payment via PayWay. Auto-pay enabled.</p>
            @else
                <div class="bg-gray-100 text-gray-500 text-center py-3 rounded-lg font-semibold">
                    @if($car->status === 'sold') SOLD @else RESERVED @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Description --}}
    @if($car->description)
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Description</h2>
            <div class="prose max-w-none text-gray-700 leading-relaxed">{{ nl2br(e($car->description)) }}</div>
        </div>
    @endif

    {{-- Related Cars --}}
    @if($relatedCars->count() > 0)
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Similar Cars</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedCars as $related)
                    <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition group">
                        <div class="overflow-hidden h-36">
                            @if($related->image)
                                <img src="{{ asset('storage/' . $related->image) }}" class="w-full h-full object-cover group-hover:scale-105 transition">
                            @else
                                <div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                </div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-gray-900 truncate">{{ $related->name }}</h3>
                            <p class="text-sm text-gray-600">${{ number_format($related->getDisplayPrice()) }}</p>
                            <a href="{{ route('cars.show', $related->slug) }}" class="text-red-600 text-sm font-medium">View &rarr;</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
@endsection