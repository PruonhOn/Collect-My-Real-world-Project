{{-- resources/views/reports/pdf/work-orders.blade.php --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a2e; }
    .header { background: #0f172a; color: white; padding: 16px 20px; margin-bottom: 16px; }
    .header h1 { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
    .header p { font-size: 10px; opacity: 0.7; }
    .summary { display: flex; gap: 12px; padding: 0 20px; margin-bottom: 16px; }
    .stat { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; text-align: center; }
    .stat-value { font-size: 18px; font-weight: bold; color: #1e40af; }
    .stat-label { font-size: 9px; color: #64748b; margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; padding: 0 20px; font-size: 9px; }
    th { background: #1e293b; color: white; padding: 6px 8px; text-align: left; font-weight: 600; }
    td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; }
    tr:nth-child(even) td { background: #f8fafc; }
    .badge { padding: 2px 6px; border-radius: 10px; font-size: 8px; font-weight: 600; }
    .badge-high   { background: #fee2e2; color: #991b1b; }
    .badge-medium { background: #fef9c3; color: #92400e; }
    .badge-low    { background: #dcfce7; color: #166534; }
    .badge-done   { background: #dcfce7; color: #166534; }
    .badge-open   { background: #dbeafe; color: #1e40af; }
    .footer { margin-top: 20px; padding: 10px 20px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 8px; color: #94a3b8; }
</style>
</head>
<body>

<div class="header">
    <h1>MaintainPro — Work Order Report</h1>
    <p>Generated {{ now()->format('M d, Y H:i') }} · Total: {{ $orders->count() }} orders</p>
</div>

<div class="summary">
    <div class="stat"><div class="stat-value">{{ $orders->count() }}</div><div class="stat-label">Total Orders</div></div>
    <div class="stat"><div class="stat-value" style="color:#166534">{{ $orders->where('status','completed')->count() }}</div><div class="stat-label">Completed</div></div>
    <div class="stat"><div class="stat-value" style="color:#dc2626">{{ $orders->filter->is_overdue->count() }}</div><div class="stat-label">Overdue</div></div>
    <div class="stat"><div class="stat-value" style="color:#d97706">{{ $orders->where('status','in_progress')->count() }}</div><div class="stat-label">In Progress</div></div>
</div>

<table>
    <thead>
        <tr>
            <th>Code</th><th>Title</th><th>Asset</th><th>Priority</th><th>Status</th><th>Technician</th><th>Due</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($orders as $wo)
        <tr>
            <td style="font-family:monospace;color:#64748b">{{ $wo->code }}</td>
            <td>{{ Str::limit($wo->title, 45) }}</td>
            <td>{{ $wo->asset->name }}</td>
            <td>
                <span class="badge badge-{{ in_array($wo->priority,['critical','high']) ? 'high' : ($wo->priority === 'medium' ? 'medium' : 'low') }}">
                    {{ ucfirst($wo->priority) }}
                </span>
            </td>
            <td>
                <span class="badge {{ $wo->status === 'completed' ? 'badge-done' : 'badge-open' }}">
                    {{ ucwords(str_replace('_',' ',$wo->status)) }}
                </span>
            </td>
            <td>{{ $wo->technician?->name ?? '—' }}</td>
            <td style="color:{{ $wo->is_overdue ? '#dc2626' : '#64748b' }}">
                {{ $wo->due_at?->format('M d, Y') ?? '—' }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">MaintainPro MMS · Confidential · {{ config('app.name') }}</div>

</body>
</html>
