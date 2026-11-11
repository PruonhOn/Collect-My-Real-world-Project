@php($school = \App\Services\SettingsService::publicSettings())
<table style="width:100%;font-family:DejaVu Sans,sans-serif;font-size:12px;">
    <tr>
        <td>
            <h2 style="margin:0;">{{ $school['school_name'] ?? 'School Receipt' }}</h2>
            <p style="margin:2px 0;">{{ $school['school_address'] ?? '' }}</p>
            <p style="margin:2px 0;">{{ $school['school_phone'] ?? '' }} {{ $school['school_email'] ?? '' }}</p>
        </td>
        <td style="text-align:right;">
            <h3>Payment Receipt</h3>
            <p>Receipt #: {{ $payment->receipt?->receipt_no ?? 'N/A' }}</p>
            <p>Date: {{ $payment->paid_on?->format('d M Y') ?? $payment->created_at->format('d M Y') }}</p>
        </td>
    </tr>
</table>

<hr>

<h4>Student</h4>
<p>{{ $payment->student?->user?->name ?? $payment->student?->student_code }}</p>
<p>{{ $payment->student?->student_code }}</p>

<h4>Payment Details</h4>
<table style="width:100%;border-collapse:collapse;" border="1">
    <tr>
        <th style="padding:6px;text-align:left;">Invoice</th>
        <td style="padding:6px;">{{ $payment->studentFee?->invoice_no }}</td>
    </tr>
    <tr>
        <th style="padding:6px;text-align:left;">Description</th>
        <td style="padding:6px;">{{ $payment->studentFee?->title }}</td>
    </tr>
    <tr>
        <th style="padding:6px;text-align:left;">Amount Paid</th>
        <td style="padding:6px;">$ {{ number_format((float) $payment->amount, 2) }}</td>
    </tr>
    <tr>
        <th style="padding:6px;text-align:left;">Payment Method</th>
        <td style="padding:6px;">{{ ucfirst($payment->method) }}</td>
    </tr>
    <tr>
        <th style="padding:6px;text-align:left;">Transaction ID</th>
        <td style="padding:6px;">{{ $payment->transaction_id }}</td>
    </tr>
    <tr>
        <th style="padding:6px;text-align:left;">Status</th>
        <td style="padding:6px;">{{ ucfirst($payment->status->value) }}</td>
    </tr>
</table>

<p style="margin-top:24px;">Thank you.</p>