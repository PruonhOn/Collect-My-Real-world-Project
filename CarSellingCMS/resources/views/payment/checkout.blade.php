@extends('layouts.public')
@section('title', 'Checkout - K & N AutoCar')

@section('content')
<section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="bg-gray-900 text-white p-6">
            <h1 class="text-2xl font-bold">Secure Checkout</h1>
            <p class="text-gray-400 text-sm mt-1">Complete your purchase securely via PayWay</p>
        </div>
        <div class="p-6">
            <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg mb-6">
                @if($car->image)
                    <img src="{{ asset('storage/' . $car->image) }}" class="w-20 h-20 object-cover rounded-lg">
                @endif
                <div>
                    <h3 class="font-bold text-gray-900">{{ $car->name }}</h3>
                    <p class="text-sm text-gray-600">{{ $car->brand }} {{ $car->year }} | {{ $car->mileage ? number_format($car->mileage) . ' km' : '' }}</p>
                    <p class="font-bold text-red-600 mt-1">${{ number_format($amount) }}</p>
                </div>
            </div>

            <form action="{{ route('payment.process') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" name="customer_name" required class="w-full border-gray-300 rounded-lg px-4 py-2.5 focus:ring-red-500 focus:border-red-500" value="{{ old('customer_name') }}">
                        @error('customer_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="customer_email" required class="w-full border-gray-300 rounded-lg px-4 py-2.5 focus:ring-red-500 focus:border-red-500" value="{{ old('customer_email') }}">
                        @error('customer_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                        <input type="text" name="customer_phone" required class="w-full border-gray-300 rounded-lg px-4 py-2.5 focus:ring-red-500 focus:border-red-500" value="{{ old('customer_phone') }}">
                        @error('customer_phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="border-t pt-6">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-600">Car Price</span>
                        <span class="font-semibold">${{ number_format($amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-600">Processing Fee</span>
                        <span class="font-semibold">$0.00</span>
                    </div>
                    <div class="flex justify-between items-center text-lg font-bold border-t pt-4">
                        <span>Total</span>
                        <span class="text-red-600">${{ number_format($amount) }}</span>
                    </div>
                </div>

                <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 rounded text-sm">
                    <strong>Auto-Pay Enabled:</strong> By clicking "Pay Now", you agree to the terms and your payment will be processed securely via PayWay payment gateway.
                </div>

                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-bold text-lg transition mt-6">
                    Pay ${{ number_format($amount) }} via PayWay
                </button>
            </form>
        </div>
    </div>
</section>
@endsection