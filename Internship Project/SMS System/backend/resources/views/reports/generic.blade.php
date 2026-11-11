<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="muted">{{ __('Generated') }} {{ now()->format('d M Y H:i') }}</p>
    <table>
        <thead>
            <tr>@foreach ($headers as $header) <th>{{ $header }}</th> @endforeach</tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>@foreach ($row as $cell) <td>{{ $cell }}</td> @endforeach</tr>
            @endforeach
        </tbody>
    </table>
    <p class="muted">{{ __('Total :count records', ['count' => count($rows)]) }}</p>
</body>
</html>