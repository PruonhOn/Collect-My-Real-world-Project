<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt - {{ $sale->invoice_number }}</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .receipt {
            max-width: 300px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: left;
            border-bottom: 1px solid #000;
            padding: 5px 0;
        }
        td {
            padding: 3px 0;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row td {
            padding-top: 5px;
            font-weight: bold;
        }
        .total-line {
            border-top: 1px dashed #000;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <h2>{{ config('app.name') }}</h2>
            <p>Mini Mart Management System</p>
            <p>Invoice: {{ $sale->invoice_number }}</p>
            <p>Date: {{ $sale->created_at->format('M d, Y h:i A') }}</p>
            <p>Cashier: {{ $sale->user?->name ?? 'N/A' }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td>{{ $item->product?->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-line">
            <table>
                <tr class="total-row">
                    <td colspan="3">Total Amount</td>
                    <td class="text-right">${{ number_format($sale->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3">Paid</td>
                    <td class="text-right">${{ number_format($sale->paid_amount, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3">Change</td>
                    <td class="text-right">${{ number_format($sale->change_amount, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="text-center">Payment: {{ ucfirst($sale->payment_method) }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Thank you for shopping!</p>
            <p>Have a great day!</p>
        </div>

        <div class="text-center no-print" style="margin-top: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">Print Receipt</button>
            <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">Close</button>
        </div>
    </div>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
