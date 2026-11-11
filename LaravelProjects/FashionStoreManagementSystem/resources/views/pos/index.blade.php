@extends('layouts.pos')

@section('title', 'Point of Sale')

@section('content')
    <div class="pos-layout">
        <div class="pos-catalog">
            <header class="pos-header">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="btn btn-soft btn-sm rounded-circle" title="Back">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="h5 mb-0">Point of Sale</h1>
                        <small class="text-muted">{{ auth()->user()->name }}</small>
                    </div>
                </div>
                <form id="posSearchForm" class="flex-grow-1 mx-3 position-relative">
                    <i class="bi bi-search pos-search-icon"></i>
                    <input type="text" id="posSearch" class="form-control form-control-lg ps-5 rounded-pill"
                           placeholder="Search by name, SKU or scan barcode..." autofocus autocomplete="off">
                </form>
                <x-theme-toggle />
                <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="bi bi-receipt me-1"></i> My Sales
                </a>
            </header>

            <div class="pos-categories">
                <button class="chip chip-active" data-category="">All</button>
                @foreach ($categories as $category)
                    <button class="chip" data-category="{{ $category->id }}">{{ $category->name }}</button>
                @endforeach
            </div>

            <div class="pos-grid-wrap">
                <div id="posResultsCount" class="small text-muted mb-2 px-1"></div>
                <div id="posGrid" class="pos-grid">
                    @include('pos.partials.product-grid', ['products' => $products])
                </div>
                <div id="posLoader" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2">Searching...</p>
                </div>
            </div>
        </div>

        <aside class="pos-cart" id="posCart">
            <div class="pos-cart-header">
                <h2 class="h6 mb-0"><i class="bi bi-cart3 me-2"></i>Current Order</h2>
                <button class="btn btn-sm btn-outline-danger rounded-pill" id="clearCart"><i class="bi bi-trash3 me-1"></i>Clear</button>
            </div>

            <div class="pos-cart-items" id="cartItems">
                <div class="empty-cart" id="emptyCart">
                    <i class="bi bi-basket3"></i>
                    <p>Your cart is empty.<br>Tap a product variant to add it.</p>
                </div>
            </div>

            <div class="pos-cart-footer">
                <div class="pos-customer mb-3">
                    <label class="form-label small mb-1">Customer (optional)</label>
                    <select id="cartCustomer" class="form-select form-select-sm">
                        <option value="">Walk-in customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }} — {{ $customer->phone }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small mb-1">Discount type</label>
                        <select id="discountType" class="form-select form-select-sm">
                            <option value="none">None</option>
                            <option value="percent">Percentage (%)</option>
                            <option value="fixed">Fixed ({{ currency_symbol() }})</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Discount value</label>
                        <input type="number" id="discountValue" class="form-control form-control-sm" min="0" step="0.01" value="0">
                    </div>
                </div>

                <div class="pos-payment mb-3">
                    <label class="form-label small mb-1">Payment method</label>
                    <div class="btn-group w-100 pos-pay-btns" id="paymentMethods">
                        <input type="radio" class="btn-check" name="payment_method" id="pay-cash" value="cash" checked>
                        <label class="btn btn-outline-primary" for="pay-cash"><i class="bi bi-cash me-1"></i>Cash</label>
                        <input type="radio" class="btn-check" name="payment_method" id="pay-card" value="card">
                        <label class="btn btn-outline-primary" for="pay-card"><i class="bi bi-credit-card me-1"></i>Card</label>
                        <input type="radio" class="btn-check" name="payment_method" id="pay-qr" value="qr">
                        <label class="btn btn-outline-primary" for="pay-qr"><i class="bi bi-qr-code me-1"></i>QR</label>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small mb-1">Amount tendered</label>
                        <input type="number" id="amountTendered" class="form-control form-control-sm" min="0" step="0.01" placeholder="—">
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Change due</label>
                        <input type="text" id="changeDue" class="form-control form-control-sm bg-body-tertiary" value="—" readonly>
                    </div>
                </div>

                <div class="pos-totals mb-3">
                    <div class="d-flex justify-content-between"><span>Subtotal</span><strong id="totSubtotal">—</strong></div>
                    <div class="d-flex justify-content-between"><span>Discount</span><strong id="totDiscount" class="text-success">—</strong></div>
                    <div class="d-flex justify-content-between"><span>Tax ({{ tax_rate() }}%)</span><strong id="totTax">—</strong></div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fs-5"><span>Total</span><strong id="totTotal">—</strong></div>
                </div>

                <button class="btn btn-success btn-lg w-100 rounded-pill" id="checkoutBtn" disabled>
                    <i class="bi bi-check2-circle me-1"></i> Complete Sale
                </button>
            </div>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>window.TaxRate = {{ tax_rate() }};</script>
    @vite('resources/js/pos.js')
@endpush
