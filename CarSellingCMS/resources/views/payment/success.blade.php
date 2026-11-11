@extends('layouts.public')
@section('title', 'Payment Successful - K & N AutoCar')

@section('content')
<section class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
    <div class="bg-white rounded-xl shadow-md p-8">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Payment Successful!</h1>
        <p class="text-gray-600 mb-6">Thank you for your purchase. Your transaction has been completed.</p>
        <div class="bg-gray-50 rounded-lg p-6 text-left mb-6">
            <h3 class="font-bold text-gray-900 mb-3">Transaction Details</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-600">Transaction ID</span><span class="font-mono font-medium">{{ $transaction->transaction_id }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Car</span><span>{{ $transaction->car->name }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Amount</span><span class="font-bold">${{ number_format($transaction->amount) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Status</span><span class="text-green-600 font-semibold">Completed</span></div>
            </div>
        </div>
        <div class="flex justify-center space-x-4">
            <a href="{{ route('cars.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg font-semibold hover:bg-gray-300 transition">Browse More Cars</a>
            <a href="{{ route('home') }}" class="bg-red-600 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-red-700 transition">Go Home</a>
        </div>
    </div>
</section>
@endsection