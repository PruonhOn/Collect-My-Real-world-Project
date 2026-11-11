@extends('layouts.admin')
@section('title', 'Create Purchase')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-4xl">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Create Purchase</h1>
    @if ($errors->any())
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('purchases.store') }}" method="POST">
        @csrf
        <div class="mb-6">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Supplier</label>
            <select name="supplier_id" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
                <option value="">Select Supplier</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Products</h2>
        <div id="product-rows">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 items-end product-row">
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Product</label>
                    <select name="products[0][product_id]" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
                        <option value="">Select Product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                    <input type="number" name="products[0][quantity]" min="1" value="1" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Unit Cost</label>
                    <input type="number" step="0.01" name="products[0][unit_cost]" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
                </div>
                <div class="flex items-end">
                    <button type="button" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded add-row">+</button>
                    <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded ml-2 remove-row" style="display:none;">-</button>
                </div>
            </div>
        </div>
        <div class="mb-6">
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Notes</label>
            <textarea name="notes" rows="3" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">{{ old('notes') }}</textarea>
        </div>
        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">Create Purchase</button>
            <a href="{{ route('purchases.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg">Cancel</a>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script>
    let rowIndex = 1;
    document.querySelector('.add-row').addEventListener('click', function() {
        const container = document.getElementById('product-rows');
        const firstRow = container.querySelector('.product-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('select, input').forEach(el => {
            const name = el.getAttribute('name');
            if (name) {
                el.setAttribute('name', name.replace(/\[\d+\]/, '[' + rowIndex + ']'));
                el.value = '';
            }
        });
        newRow.querySelector('[name$="[quantity]"]').value = 1;
        newRow.querySelector('.remove-row').style.display = 'inline-block';
        container.appendChild(newRow);
        rowIndex++;
    });
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('.product-row').remove();
        }
    });
</script>
@endpush
