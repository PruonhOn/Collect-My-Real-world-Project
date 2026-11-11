<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Barcode - {{ $product->name }}</title>
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; font-family: Arial, sans-serif; background: #fff; }
        .barcode-container { text-align: center; padding: 20px; }
        .barcode-container h4 { margin-bottom: 5px; }
        .barcode-container p { color: #666; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="barcode-container">
        <h4>{{ $product->name }}</h4>
        <p>SKU: {{ $product->sku }} | {{ $product->barcode ?? 'No barcode' }}</p>
        {!! $barcodeSvg !!}
        <p style="margin-top:15px;font-size:12px;color:#999;">{{ $product->barcode ?? $product->sku }}</p>
    </div>
</body>
</html>
