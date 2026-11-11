@extends('layouts.admin')

@section('title', 'POS')

@section('content')
    <div x-data="posApp()" class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-8rem)]">
        {{-- Left: Products Section --}}
        <div class="flex-1 flex flex-col min-h-0">
            {{-- Search & Category Filters --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-4">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model="search" @@input.debounce="filterProducts()" placeholder="Search products..." class="w-full pl-10 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 mt-3">
                    <button @@click="selectedCategory = null; filterProducts()" :class="selectedCategory === null ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors">
                        All
                    </button>
                    <template x-for="category in categories" :key="category.id">
                        <button @@click="selectedCategory = category.id; filterProducts()" :class="selectedCategory === category.id ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'" class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors" x-text="category.name"></button>
                    </template>
                </div>
            </div>

            {{-- Products Grid --}}
            <div class="flex-1 overflow-y-auto">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div @@click="addToCart(product)" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-3 cursor-pointer hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-600 transition-all">
                            <div class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg mb-2 flex items-center justify-center overflow-hidden">
                                <img x-show="product.image" :src="'/storage/' + product.image" :alt="product.name" class="w-full h-full object-cover">
                                <div x-show="!product.image" class="text-gray-400">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-sm font-medium text-gray-800 dark:text-white truncate" x-text="product.name"></p>
                            <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400 mt-1" x-text="'$' + parseFloat(product.price).toFixed(2)"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="'Stock: ' + product.stock"></p>
                        </div>
                    </template>
                    <template x-if="filteredProducts.length === 0">
                        <div class="col-span-full text-center py-12 text-gray-400 dark:text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <p>No products found</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Right: Cart Panel --}}
        <div class="w-full lg:w-96 flex flex-col bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            {{-- Cart Header --}}
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Cart</h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400" x-text="cart.length + ' item(s)'"></span>
                </div>
            </div>

            {{-- Customer Selection --}}
            <div class="px-4 pt-4">
                <select x-model="customerId" class="w-full text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">Walk-in Customer</option>
                    <template x-for="customer in customers" :key="customer.id">
                        <option :value="customer.id" x-text="customer.name"></option>
                    </template>
                </select>
            </div>

            {{-- Cart Items --}}
            <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2">
                <template x-for="(item, index) in cart" :key="item.product_id">
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-white truncate" x-text="item.name"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="'$' + parseFloat(item.price).toFixed(2) + ' each'"></p>
                            <div class="flex items-center space-x-2 mt-2">
                                <button @@click="updateQuantity(index, -1)" class="w-7 h-7 flex items-center justify-center rounded bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-500 text-sm font-bold">-</button>
                                <span class="text-sm font-medium text-gray-800 dark:text-white w-6 text-center" x-text="item.quantity"></span>
                                <button @@click="updateQuantity(index, 1)" class="w-7 h-7 flex items-center justify-center rounded bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-500 text-sm font-bold">+</button>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-bold text-gray-800 dark:text-white" x-text="'$' + (item.price * item.quantity).toFixed(2)"></p>
                            <button @@click="removeFromCart(index)" class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 mt-1">Remove</button>
                        </div>
                    </div>
                </template>
                <template x-if="cart.length === 0">
                    <div class="flex flex-col items-center justify-center py-12 text-gray-400 dark:text-gray-500">
                        <svg class="w-12 h-12 mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                        </svg>
                        <p class="text-sm">Cart is empty</p>
                        <p class="text-xs mt-1">Click on a product to add</p>
                    </div>
                </template>
            </div>

            {{-- Discount --}}
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-2">
                    <input type="number" x-model="discount" @@input.debounce="calculateTotals()" placeholder="Discount ($)" min="0" step="0.01" class="w-full text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
            </div>

            {{-- Totals --}}
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 space-y-1.5">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                    <span class="text-gray-800 dark:text-gray-200 font-medium" x-text="'$' + subtotal.toFixed(2)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Discount</span>
                    <span class="text-red-600 dark:text-red-400 font-medium" x-text="'-$' + discountValue.toFixed(2)"></span>
                </div>
                <div class="flex justify-between text-lg font-bold border-t border-gray-200 dark:border-gray-700 pt-1.5">
                    <span class="text-gray-800 dark:text-white">Grand Total</span>
                    <span class="text-indigo-600 dark:text-indigo-400" x-text="'$' + grandTotal.toFixed(2)"></span>
                </div>
            </div>

            {{-- Checkout --}}
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
                <div class="grid grid-cols-3 gap-2">
                    <label class="flex items-center justify-center px-2 py-2 text-xs font-medium rounded-lg border-2 cursor-pointer transition-colors" :class="paymentMethod === 'cash' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-500'">
                        <input type="radio" name="payment_method" value="cash" x-model="paymentMethod" class="sr-only">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Cash
                    </label>
                    <label class="flex items-center justify-center px-2 py-2 text-xs font-medium rounded-lg border-2 cursor-pointer transition-colors" :class="paymentMethod === 'card' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-500'">
                        <input type="radio" name="payment_method" value="card" x-model="paymentMethod" class="sr-only">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        Card
                    </label>
                    <label class="flex items-center justify-center px-2 py-2 text-xs font-medium rounded-lg border-2 cursor-pointer transition-colors" :class="paymentMethod === 'transfer' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-500'">
                        <input type="radio" name="payment_method" value="transfer" x-model="paymentMethod" class="sr-only">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Transfer
                    </label>
                </div>
                <button @@click="checkout()" :disabled="cart.length === 0" :class="cart.length === 0 ? 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700'" class="w-full py-3 text-sm font-bold text-white rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    Place Order
                </button>
            </div>
        </div>
    </div>

    {{-- Hidden form for submission --}}
    <form id="checkoutForm" method="POST" action="{{ route('pos.checkout') }}" class="hidden">
        @csrf
        <input type="hidden" name="cart_data" id="cartData">
        <input type="hidden" name="customer_id" id="checkoutCustomerId">
        <input type="hidden" name="discount" id="checkoutDiscount">
        <input type="hidden" name="payment_method" id="checkoutPaymentMethod">
    </form>
@endsection

@push('scripts')
<script>
    function posApp() {
        return {
            search: '',
            selectedCategory: null,
            categories: @json($categories ?? []),
            products: @json($products ?? []),
            customers: @json($customers ?? []),
            filteredProducts: [],
            cart: @json(session('cart', [])),
            customerId: '',
            discount: 0,
            paymentMethod: 'cash',
            subtotal: 0,
            discountValue: 0,
            grandTotal: 0,

            init() {
                this.filteredProducts = [...this.products];
                this.calculateTotals();
            },

            filterProducts() {
                let filtered = this.products;
                if (this.selectedCategory) {
                    filtered = filtered.filter(p => p.category_id === this.selectedCategory);
                }
                if (this.search.trim()) {
                    const q = this.search.toLowerCase().trim();
                    filtered = filtered.filter(p =>
                        p.name.toLowerCase().includes(q) ||
                        (p.sku && p.sku.toLowerCase().includes(q))
                    );
                }
                this.filteredProducts = filtered;
            },

            addToCart(product) {
                if (product.stock <= 0) return;
                fetch('{{ route('pos.add-to-cart') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ product_id: product.id })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.cart) {
                        this.cart = data.cart;
                        this.calculateTotals();
                    }
                });
            },

            updateQuantity(index, delta) {
                const item = this.cart[index];
                if (!item) return;
                const newQty = item.quantity + delta;
                if (newQty <= 0) {
                    this.removeFromCart(index);
                    return;
                }
                fetch('{{ route('pos.update-cart', ['key' => '__KEY__']) }}'.replace('__KEY__', item.product_id), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ quantity: newQty })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.cart) {
                        this.cart = data.cart;
                        this.calculateTotals();
                    }
                });
            },

            removeFromCart(index) {
                const item = this.cart[index];
                if (!item) return;
                fetch('{{ route('pos.remove-from-cart', ['key' => '__KEY__']) }}'.replace('__KEY__', item.product_id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.cart) {
                        this.cart = data.cart;
                        this.calculateTotals();
                    }
                });
            },

            calculateTotals() {
                this.subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                this.discountValue = parseFloat(this.discount) || 0;
                this.grandTotal = Math.max(0, this.subtotal - this.discountValue);
            },

            checkout() {
                if (this.cart.length === 0) return;
                document.getElementById('cartData').value = JSON.stringify(this.cart);
                document.getElementById('checkoutCustomerId').value = this.customerId;
                document.getElementById('checkoutDiscount').value = this.discount;
                document.getElementById('checkoutPaymentMethod').value = this.paymentMethod;
                document.getElementById('checkoutForm').submit();
            }
        }
    }
</script>
@endpush
