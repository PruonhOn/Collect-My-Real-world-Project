<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ ucfirst($period) }} Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { text-align: center; color: #333; }
        h3 { color: #555; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        .text-right { text-align: right; }
        .summary { margin: 20px 0; }
        .summary td { border: none; }
        .footer { text-align: center; margin-top: 30px; color: #999; font-size: 10px; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }}</h1>
    <h3>{{ ucfirst($period) }} Report - {{ now()->format('F d, Y') }}</h3>

    @if($startDate && $endDate)
    <p>Period: {{ $startDate }} to {{ $endDate }}</p>
    @endif

    <table class="summary">
        <tr><td><strong>Total Sales</strong></td><td class="text-right">${{ number_format($report['sales'], 2) }}</td></tr>
        <tr><td><strong>Total Expenses</strong></td><td class="text-right">${{ number_format($report['expenses'], 2) }}</td></tr>
        <tr><td><strong>Net Profit</strong></td><td class="text-right">${{ number_format($report['profit'], 2) }}</td></tr>
        <tr><td><strong>Transactions</strong></td><td class="text-right">{{ $report['sales_count'] }}</td></tr>
    </table>

    <h3>Sales</h3>
    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Date</th>
                <th>Items</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
            <tr>
                <td>{{ $sale->invoice_number }}</td>
                <td>{{ $sale->created_at->format('M d, Y') }}</td>
                <td>{{ $sale->items->sum('quantity') }}</td>
                <td class="text-right">${{ number_format($sale->total_amount, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center">No sales</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Expenses</h3>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Date</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
            <tr>
                <td>{{ $expense->description }}</td>
                <td>{{ $expense->date->format('M d, Y') }}</td>
                <td class="text-right">${{ number_format($expense->amount, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="3" class="text-center">No expenses</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y h:i A') }} by {{ auth()->user()->name }}</p>
        <p>{{ config('app.name') }} - MiniMart Management System</p>
    </div>
</body>
</html>
