@extends('layouts.admin')
@section('title', 'Profit Report')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Profit Report</h1>
    <form method="GET" action="{{ route('reports.profit') }}" class="mb-6">
        <div class="flex gap-4 items-end">
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                <input type="date" name="from" value="{{ request('from') }}" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                <input type="date" name="to" value="{{ request('to') }}" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">Generate Report</button>
        </div>
    </form>
    @if (request('from') || request('to'))
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                <p class="text-sm text-green-600 dark:text-green-300">Total Revenue</p>
                <p class="text-xl font-bold text-green-800 dark:text-green-100">{{ number_format($totalRevenue ?? 0, 2) }}</p>
            </div>
            <div class="bg-red-50 dark:bg-red-900 p-4 rounded-lg">
                <p class="text-sm text-red-600 dark:text-red-300">Total Cost</p>
                <p class="text-xl font-bold text-red-800 dark:text-red-100">{{ number_format($totalCost ?? 0, 2) }}</p>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                <p class="text-sm text-yellow-600 dark:text-yellow-300">Total Expenses</p>
                <p class="text-xl font-bold text-yellow-800 dark:text-yellow-100">{{ number_format($totalExpenses ?? 0, 2) }}</p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                <p class="text-sm text-blue-600 dark:text-blue-300">Gross Profit</p>
                <p class="text-xl font-bold text-blue-800 dark:text-blue-100">{{ number_format($grossProfit ?? 0, 2) }}</p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg">
                <p class="text-sm text-purple-600 dark:text-purple-300">Profit Margin</p>
                <p class="text-xl font-bold text-purple-800 dark:text-purple-100">{{ number_format($profitMargin ?? 0, 1) }}%</p>
            </div>
        </div>
        @if (isset($dailyData) && count($dailyData) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Sales</th>
                            <th class="px-4 py-3">Cost</th>
                            <th class="px-4 py-3">Expenses</th>
                            <th class="px-4 py-3">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dailyData as $data)
                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3">{{ $data['date'] }}</td>
                            <td class="px-4 py-3">{{ number_format($data['sales'], 2) }}</td>
                            <td class="px-4 py-3">{{ number_format($data['cost'], 2) }}</td>
                            <td class="px-4 py-3">{{ number_format($data['expenses'], 2) }}</td>
                            <td class="px-4 py-3">{{ number_format($data['profit'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @else
        <p class="text-gray-500 dark:text-gray-400 text-center py-8">Select a date range and click Generate Report.</p>
    @endif
</div>
@endsection
