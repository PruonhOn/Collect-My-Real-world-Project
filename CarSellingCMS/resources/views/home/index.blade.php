@extends('layouts.public')
@section('title', config('app.name'))

@section('hero')
<section class="relative bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 text-white overflow-hidden">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute inset-0" style="background-image: url('https://images.unsplash.com/photo-1487754180451-c456f719a1fc?auto=format&fit=crop&w=2000&q=80'); background-size: cover; background-position: center;"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
        <div class="max-w-3xl">
            <h1 class="text-4xl md:text-6xl font-extrabold leading-tight mb-6">Find Your <span class="text-red-500">Dream Car</span> Today</h1>
            <p class="text-lg md:text-xl text-gray-300 mb-8 leading-relaxed">Premium quality vehicles at competitive prices. We make car buying simple, transparent, and enjoyable.</p>
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('cars.index') }}" class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg font-semibold transition shadow-lg">Browse Inventory</a>
                <a href="{{ route('contact.index') }}" class="border-2 border-white hover:bg-white hover:text-gray-900 text-white px-8 py-3 rounded-lg font-semibold transition">Contact Us</a>
            </div>
        </div>
    </div>
    <div class="relative bg-gray-900 border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <div><div class="text-3xl font-bold text-red-500">{{ $latestCars->count() }}+</div><div class="text-gray-400 text-sm mt-1">Cars Available</div></div>
                <div><div class="text-3xl font-bold text-red-500">{{ $categories->count() }}</div><div class="text-gray-400 text-sm mt-1">Categories</div></div>
                <div><div class="text-3xl font-bold text-red-500">100%</div><div class="text-gray-400 text-sm mt-1">Satisfaction</div></div>
                <div><div class="text-3xl font-bold text-red-500">5+</div><div class="text-gray-400 text-sm mt-1">Years Experience</div></div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('content')
{{-- Search Section --}}
<section class="bg-white shadow-md -mt-10 relative z-10 max-w-5xl mx-auto rounded-xl p-6 mb-12">
    <form action="{{ route('search') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Keyword</label>
            <input type="text" name="keyword" placeholder="Search cars..." class="w-full border-gray-300 rounded-lg px-4 py-2.5 focus:ring-red-500 focus:border-red-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select name="category_id" class="w-full border-gray-300 rounded-lg px-4 py-2.5 focus:ring-red-500 focus:border-red-500">
                <option value="">All</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
            <select name="brand" class="w-full border-gray-300 rounded-lg px-4 py-2.5 focus:ring-red-500 focus:border-red-500">
                <option value="">All Brands</option>
                @php $brands = App\Models\Car::where('status','available')->select('brand')->distinct()->pluck('brand'); @endphp
                @foreach($brands as $brand)
                    <option value="{{ $brand }}">{{ $brand }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Condition</label>
            <select name="condition" class="w-full border-gray-300 rounded-lg px-4 py-2.5 focus:ring-red-500 focus:border-red-500">
                <option value="">All</option>
                <option value="new">New</option>
                <option value="used">Used</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-lg font-semibold transition">Search</button>
        </div>
    </form>
</section>

{{-- Featured Cars --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-16">
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-900">Featured Vehicles</h2>
            <p class="text-gray-600 mt-2">Handpicked selection of our best vehicles</p>
        </div>
        <a href="{{ route('cars.index') }}" class="text-red-600 hover:text-red-700 font-semibold">View All &rarr;</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($featuredCars as $car)
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition group">
                <div class="relative overflow-hidden h-48">
                    @if($car->image)
                        <img src="{{ asset('storage/' . $car->image) }}" alt="{{ $car->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
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
                <div class="p-5">
                    <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ $car->brand }} {{ $car->year }}</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $car->name }}</h3>
                    <div class="flex items-center space-x-4 text-sm text-gray-600 mb-3">
                        <span>{{ $car->mileage ? number_format($car->mileage) . ' km' : 'N/A' }}</span>
                        <span>{{ $car->fuel_type }}</span>
                        <span>{{ $car->transmission }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <div>
                            @if($car->sale_price)
                                <span class="text-lg font-bold text-red-600">${{ number_format($car->sale_price) }}</span>
                                <span class="text-sm text-gray-400 line-through ml-2">${{ number_format($car->price) }}</span>
                            @else
                                <span class="text-lg font-bold text-gray-900">${{ number_format($car->price) }}</span>
                            @endif
                        </div>
                        <a href="{{ route('cars.show', $car->slug) }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">View</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-gray-500">
                <p class="text-lg">No featured cars available at the moment.</p>
            </div>
        @endforelse
    </div>
</section>

{{-- Categories --}}
<section class="bg-gray-100 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-gray-900">Browse by Category</h2>
            <p class="text-gray-600 mt-2">Find exactly what you're looking for</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach($categories as $cat)
                <a href="{{ route('cars.index', ['category_id' => $cat->id]) }}" class="bg-white rounded-xl p-6 text-center hover:shadow-lg transition group">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3 group-hover:bg-red-600 transition">
                        <span class="text-2xl group-hover:text-white transition">{{ substr($cat->name, 0, 1) }}</span>
                    </div>
                    <h3 class="font-semibold text-gray-900">{{ $cat->name }}</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ $cat->cars_count ?? $cat->activeCars()->count() }} cars</p>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- Latest Cars --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center mb-10">
        <h2 class="text-3xl font-bold text-gray-900">Latest Arrivals</h2>
        <p class="text-gray-600 mt-2">Recently added to our inventory</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($latestCars as $car)
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition group">
                <div class="relative overflow-hidden h-40">
                    @if($car->image)
                        <img src="{{ asset('storage/' . $car->image) }}" alt="{{ $car->name }}" class="w-full h-full object-cover group-hover:scale-105 transition">
                    @else
                        <div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        </div>
                    @endif
                </div>
                <div class="p-4">
                    <div class="text-xs text-gray-500 mb-1">{{ $car->brand }} {{ $car->year }}</div>
                    <h3 class="font-bold text-gray-900 mb-2 truncate">{{ $car->name }}</h3>
                    <div class="text-sm text-gray-600 mb-3">{{ $car->mileage ? number_format($car->mileage) . ' km' : 'N/A' }} | {{ $car->fuel_type }}</div>
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-red-600">${{ number_format($car->getDisplayPrice()) }}</span>
                        <a href="{{ route('cars.show', $car->slug) }}" class="text-red-600 hover:text-red-700 text-sm font-medium">Details &rarr;</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-gray-500">
                <p>No cars available yet.</p>
            </div>
        @endforelse
    </div>
</section>

{{-- Testimonials --}}
@if($testimonials->count() > 0)
<section class="bg-gray-900 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold">What Our Customers Say</h2>
            <p class="text-gray-400 mt-2">Trusted by hundreds of satisfied customers</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($testimonials as $testimonial)
                <div class="bg-gray-800 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-red-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                            {{ substr($testimonial->name, 0, 1) }}
                        </div>
                        <div class="ml-3">
                            <h4 class="font-semibold">{{ $testimonial->name }}</h4>
                            @if($testimonial->position)
                                <p class="text-sm text-gray-400">{{ $testimonial->position }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex mb-3">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-5 h-5 {{ $i <= $testimonial->rating ? 'text-yellow-400' : 'text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <p class="text-gray-300 text-sm leading-relaxed">"{{ $testimonial->content }}"</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Call to Action --}}
<section class="bg-gradient-to-r from-red-600 to-red-800 py-16">
    <div class="max-w-4xl mx-auto text-center px-4">
        <h2 class="text-3xl font-bold text-white mb-4">Ready to Find Your Dream Car?</h2>
        <p class="text-red-100 mb-8 text-lg">Visit our showroom or browse our online inventory today.</p>
        <div class="flex justify-center space-x-4">
            <a href="{{ route('cars.index') }}" class="bg-white text-red-600 px-8 py-3 rounded-lg font-bold hover:bg-gray-100 transition">Browse Cars</a>
            <a href="{{ route('contact.index') }}" class="border-2 border-white text-white px-8 py-3 rounded-lg font-bold hover:bg-white hover:text-red-600 transition">Get In Touch</a>
        </div>
    </div>
</section>
@endsection