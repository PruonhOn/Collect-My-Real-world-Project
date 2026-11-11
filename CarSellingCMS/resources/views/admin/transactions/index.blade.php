@extends('layouts.admin')
@section('title', 'Transactions - K & N AutoCar')
@section('header')
<div class="flex items-center gap-3">
    <div class="w-9 h-9 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
    </div>
    <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Transactions</h2>
        <p class="text-xs text-gray-500 dark:text-gray-400">Sales and payment history</p>
    </div>
</div>
@endsection

@section('content')
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50 text-left">
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Transaction ID</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Customer</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Car</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Amount</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Method</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                    <tr class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3.5 font-mono text-xs text-gray-600 dark:text-gray-400 font-medium">#{{ substr($t->transaction_id, 0, 10) }}</td>
                        <td class="px-4 py-3.5">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $t->customer_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t->customer_email }}</p>
                        </td>
                        <td class="px-4 py-3.5 text-gray-600 dark:text-gray-400">{{ $t->car->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3.5 font-semibold text-gray-900 dark:text-white">${{ number_format($t->amount) }}</td>
                        <td class="px-4 py-3.5 text-gray-600 dark:text-gray-400 text-xs">{{ $t->payment_method ?? 'N/A' }}</td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium
                                {{ $t->status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : '' }}
                                {{ $t->status === 'pending' ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300' : '' }}
                                {{ $t->status === 'failed' ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' : '' }}
                                {{ $t->status === 'processing' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                <span class="w-1.5 h-1.5 rounded-full
                                    {{ $t->status === 'completed' ? 'bg-emerald-500' : '' }}
                                    {{ $t->status === 'pending' ? 'bg-amber-500' : '' }}
                                    {{ $t->status === 'failed' ? 'bg-red-500' : '' }}
                                    {{ $t->status === 'processing' ? 'bg-blue-500' : '' }}"></span>
                                {{ ucfirst($t->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400">{{ $t->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3.5">
                            <a href="{{ route('admin.transactions.show', $t) }}" class="inline-flex items-center gap-1 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 transition-colors">
                                View
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">No transactions yet</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($transactions, 'links'))
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection
