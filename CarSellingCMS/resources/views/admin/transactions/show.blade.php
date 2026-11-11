@extends('layouts.admin')
@section('title', 'Transaction - K & N AutoCar')
@section('header', 'Transaction Details')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
        <div class="p-6 md:p-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-semibold text-gray-900 dark:text-white">Transaction Information</h3>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium
                    {{ $transaction->status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : '' }}
                    {{ $transaction->status === 'pending' ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300' : '' }}
                    {{ $transaction->status === 'failed' ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' : '' }}
                    {{ $transaction->status === 'processing' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                    <span class="w-1.5 h-1.5 rounded-full
                        {{ $transaction->status === 'completed' ? 'bg-emerald-500' : '' }}
                        {{ $transaction->status === 'pending' ? 'bg-amber-500' : '' }}
                        {{ $transaction->status === 'failed' ? 'bg-red-500' : '' }}
                        {{ $transaction->status === 'processing' ? 'bg-blue-500' : '' }}"></span>
                    {{ ucfirst($transaction->status) }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Transaction ID</label>
                    <p class="font-mono font-semibold text-gray-900 dark:text-white mt-1 text-sm">{{ $transaction->transaction_id }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PayWay Transaction ID</label>
                    <p class="font-mono font-semibold text-gray-900 dark:text-white mt-1 text-sm">{{ $transaction->payway_transaction_id ?? 'N/A' }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer Name</label>
                    <p class="font-semibold text-gray-900 dark:text-white mt-1">{{ $transaction->customer_name }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer Email</label>
                    <a href="mailto:{{ $transaction->customer_email }}" class="font-semibold text-red-600 dark:text-red-400 hover:text-red-700 mt-1 block">{{ $transaction->customer_email }}</a>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer Phone</label>
                    <p class="font-semibold text-gray-900 dark:text-white mt-1">{{ $transaction->customer_phone ?? 'N/A' }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Car</label>
                    <p class="font-semibold text-gray-900 dark:text-white mt-1">{{ $transaction->car->name ?? 'N/A' }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</label>
                    <p class="font-bold text-2xl text-gray-900 dark:text-white mt-1">${{ number_format($transaction->amount) }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Currency</label>
                    <p class="font-semibold text-gray-900 dark:text-white mt-1">{{ $transaction->currency }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Payment Method</label>
                    <p class="font-semibold text-gray-900 dark:text-white mt-1">{{ $transaction->payment_method ?? 'N/A' }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</label>
                    <p class="font-semibold text-gray-900 dark:text-white mt-1">{{ $transaction->created_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>

            @if($transaction->notes)
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 mb-6">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Notes</label>
                    <p class="text-gray-700 dark:text-gray-300 mt-2">{{ $transaction->notes }}</p>
                </div>
            @endif

            <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-800">
                <a href="{{ route('admin.transactions.index') }}" class="inline-flex items-center gap-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to Transactions
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
