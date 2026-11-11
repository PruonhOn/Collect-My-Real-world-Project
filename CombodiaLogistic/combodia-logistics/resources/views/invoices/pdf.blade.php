<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
            padding: 30px;
        }
        .header-table { width: 100%; margin-bottom: 30px; }
        .header-table td { vertical-align: top; padding-bottom: 15px; }
        .company-name { font-size: 22px; font-weight: 700; color: #1a1a2e; }
        .company-tagline { font-size: 11px; color: #888; margin-top: 2px; }
        .company-details { font-size: 11px; color: #666; margin-top: 10px; line-height: 1.8; }
        .invoice-title { font-size: 28px; font-weight: 700; color: #4361ee; text-align: right; }
        .invoice-meta { text-align: right; font-size: 11px; color: #666; margin-top: 8px; line-height: 1.8; }
        .invoice-meta strong { color: #333; }
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-draft { background: #e9ecef; color: #6c757d; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-paid { background: #d1e7dd; color: #0f5132; }
        .badge-overdue { background: #f8d7da; color: #842029; }
        .badge-cancelled { background: #f8d7da; color: #842029; }

        .section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
            margin-bottom: 6px;
        }
        .bill-to { margin-bottom: 25px; }
        .bill-to h4 { font-size: 13px; font-weight: 700; color: #333; margin-bottom: 3px; }
        .bill-to p { font-size: 11px; color: #666; line-height: 1.7; margin: 0; }
        .shipment-ref { text-align: right; margin-bottom: 25px; }
        .shipment-ref p { font-size: 11px; color: #666; line-height: 1.7; margin: 0; }
        .shipment-ref strong { color: #333; }

        .divider {
            border: none;
            border-top: 2px solid #4361ee;
            margin-bottom: 20px;
        }

        /* Items Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .items-table thead th {
            background: #1a1a2e;
            color: #fff;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
        }
        .items-table thead th:last-child,
        .items-table thead th:nth-child(3),
        .items-table thead th:nth-child(4) {
            text-align: right;
        }
        .items-table thead th:nth-child(1) { text-align: center; width: 35px; }
        .items-table tbody td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }
        .items-table tbody tr:nth-child(even) { background: #f9f9fb; }
        .items-table tbody td:last-child,
        .items-table tbody td:nth-child(3),
        .items-table tbody td:nth-child(4) { text-align: right; }
        .items-table tbody td:nth-child(1) { text-align: center; }

        /* Summary */
        .summary-table { width: 100%; margin-top: 10px; }
        .summary-table td { padding: 3px 0; font-size: 11px; }
        .summary-table .label { color: #666; text-align: right; padding-right: 10px; width: 65%; }
        .summary-table .value { text-align: right; font-weight: 600; color: #333; width: 35%; }
        .summary-table .total-row td {
            border-top: 2px solid #1a1a2e;
            padding-top: 8px;
            font-size: 14px;
            font-weight: 700;
            color: #4361ee;
        }
        .summary-table .paid-row td { color: #0f5132; }
        .summary-table .balance-row td { color: #842029; font-weight: 700; }

        .section-row td { vertical-align: top; padding-bottom: 10px; }

        /* Payment History */
        .payment-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .payment-table thead th {
            background: #f0f0f5;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 10px;
            text-align: left;
            color: #555;
        }
        .payment-table thead th:last-child,
        .payment-table thead th:nth-child(3) { text-align: right; }
        .payment-table tbody td {
            padding: 6px 10px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
            color: #444;
        }
        .payment-table tbody td:last-child,
        .payment-table tbody td:nth-child(3) { text-align: right; }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
        .terms-section { margin-top: 25px; padding: 12px 15px; background: #f5f5fa; border-radius: 4px; }
        .terms-section p { font-size: 10px; color: #666; line-height: 1.7; white-space: pre-line; }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td>
                <div class="company-name">Combodia Logistics</div>
                <div class="company-tagline">Logistics & Transportation</div>
                <div class="company-details">
                    123 Logistics Street<br>
                    Phnom Penh, Cambodia<br>
                    Phone: +855 12 345 6789<br>
                    Email: info@combodialogistics.com
                </div>
            </td>
            <td>
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-meta">
                    <strong>Invoice #:</strong> {{ $invoice->invoice_number }}<br>
                    <strong>Issue Date:</strong> {{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('d M Y') : '-' }}<br>
                    <strong>Due Date:</strong> {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : '-' }}<br>
                    <strong>Status:</strong>
                    <span class="badge badge-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
                </div>
            </td>
        </tr>
    </table>

    <hr class="divider">

    <!-- Bill To & Shipment Reference -->
    <table width="100%">
        <tr>
            <td class="section-row" width="50%">
                <div class="bill-to">
                    <div class="section-title">Bill To</div>
                    <h4>{{ $invoice->customer->company_name ?? $invoice->customer->name ?? 'N/A' }}</h4>
                    @if($invoice->customer->name && $invoice->customer->company_name)
                        <p>Attn: {{ $invoice->customer->name }}</p>
                    @endif
                    @if($invoice->customer->email)
                        <p>{{ $invoice->customer->email }}</p>
                    @endif
                    @if($invoice->customer->phone)
                        <p>Phone: {{ $invoice->customer->phone }}</p>
                    @endif
                    @if($invoice->customer->tax_number)
                        <p>Tax ID: {{ $invoice->customer->tax_number }}</p>
                    @endif
                </div>
            </td>
            <td class="section-row" width="50%">
                @if($invoice->shipment)
                <div class="shipment-ref">
                    <div class="section-title">Shipment Reference</div>
                    <p><strong>{{ $invoice->shipment->shipment_number }}</strong></p>
                    <p>{{ $invoice->shipment->sender_city }} &rarr; {{ $invoice->shipment->recipient_city }}</p>
                    @if($invoice->shipment->recipient_name)
                        <p>Recipient: {{ $invoice->shipment->recipient_name }}</p>
                    @endif
                </div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->invoiceItems as $idx => $item)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td>${{ number_format($item->unit_price, 2) }}</td>
                <td>${{ number_format($item->total, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center; color:#999; padding: 15px;">No items on this invoice</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Summary & Terms -->
    <table width="100%">
        <tr>
            <td class="section-row" width="55%" valign="top">
                @if($invoice->terms_conditions)
                <div class="terms-section">
                    <div class="section-title" style="margin-bottom: 6px;">Terms & Conditions</div>
                    <p>{{ $invoice->terms_conditions }}</p>
                </div>
                @endif
                @if($invoice->notes)
                <div style="margin-top: 10px;">
                    <div class="section-title">Notes</div>
                    <p style="font-size: 11px; color: #666;">{{ $invoice->notes }}</p>
                </div>
                @endif
            </td>
            <td class="section-row" width="45%" valign="top">
                <table class="summary-table">
                    <tr>
                        <td class="label">Subtotal:</td>
                        <td class="value">${{ number_format($invoice->subtotal ?? 0, 2) }}</td>
                    </tr>
                    @if(($invoice->tax_rate ?? 0) > 0)
                    <tr>
                        <td class="label">Tax ({{ number_format($invoice->tax_rate, 1) }}%):</td>
                        <td class="value">${{ number_format($invoice->tax_amount ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($invoice->discount_amount ?? 0) > 0)
                    <tr>
                        <td class="label">Discount:</td>
                        <td class="value" style="color:#842029;">-${{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td class="label">Total:</td>
                        <td class="value">${{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                    <tr class="paid-row">
                        <td class="label">Paid:</td>
                        <td class="value">${{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                    </tr>
                    <tr class="balance-row">
                        <td class="label">Balance Due:</td>
                        <td class="value">${{ number_format($invoice->balance_due ?? ($invoice->total_amount - ($invoice->paid_amount ?? 0)), 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Payment History -->
    @if($invoice->payments->count())
    <div style="margin-top: 25px;">
        <div class="section-title">Payment History</div>
        <table class="payment-table">
            <thead>
                <tr>
                    <th>Payment #</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->payments as $payment)
                <tr>
                    <td>{{ $payment->payment_number }}</td>
                    <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : '-' }}</td>
                    <td>{{ $payment->paymentMethod->name ?? 'N/A' }}</td>
                    <td>{{ $payment->reference_number ?? '-' }}</td>
                    <td>${{ number_format($payment->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>Combodia Logistics &mdash; Phnom Penh, Cambodia | +855 12 345 6789 | info@combodialogistics.com</p>
    </div>

</body>
</html>
