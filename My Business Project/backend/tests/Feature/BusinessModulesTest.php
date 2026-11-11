<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Business modules
|--------------------------------------------------------------------------
|
| Products, customers, orders and invoices are the daily work of the
| application. These tests verify the server side calculation of money, the
| inventory side effects and the pagination contract.
|
*/

it('creates a product and rejects a duplicate sku in the same business', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($owner, $business, 'POST', '/api/v1/business/products', [
        'name' => 'Consulting hour',
        'sku' => 'CONS-1',
        'price' => 15000,
    ])->assertCreated()->assertJsonPath('data.sku', 'CONS-1');

    apiAs($owner, $business, 'POST', '/api/v1/business/products', [
        'name' => 'Consulting hour again',
        'sku' => 'CONS-1',
        'price' => 15000,
    ])->assertStatus(422)->assertJsonValidationErrors('sku');
});

it('allows the same sku in two different businesses', function () {
    seedPlans();

    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $mine = makeBusiness($owner);
    $theirs = makeBusiness($otherOwner);

    apiAs($owner, $mine, 'POST', '/api/v1/business/products', [
        'name' => 'Shared sku',
        'sku' => 'SHARED-1',
        'price' => 100,
    ])->assertCreated();

    apiAs($otherOwner, $theirs, 'POST', '/api/v1/business/products', [
        'name' => 'Shared sku',
        'sku' => 'SHARED-1',
        'price' => 100,
    ])->assertCreated();
});

it('searches and paginates products', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    foreach (['Alpha', 'Beta', 'Gamma'] as $name) {
        apiAs($owner, $business, 'POST', '/api/v1/business/products', [
            'name' => $name,
            'price' => 100,
        ])->assertCreated();
    }

    $response = apiAs($owner, $business, 'GET', '/api/v1/business/products?search=Alp&per_page=10');

    $response->assertOk();

    expect(collect($response->json('data'))->pluck('name'))->toContain('Alpha')->not->toContain('Beta');
});

it('calculates the order total on the server', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $product = Product::withoutGlobalScopes()->create([
        'business_id' => $business->id,
        'name' => 'Widget',
        'price' => 2500,
    ]);

    $response = apiAs($owner, $business, 'POST', '/api/v1/business/orders', [
        'items' => [['product_id' => $product->id, 'quantity' => 3]],
        'tax' => 500,
        'discount' => 1000,
    ])->assertCreated();

    // 3 x 2500 = 7500, minus 1000 discount, plus 500 tax.
    expect($response->json('data.subtotal'))->toBe(7500);
    expect($response->json('data.discount'))->toBe(1000);
    expect($response->json('data.tax'))->toBe(500);
    expect($response->json('data.total'))->toBe(7000);
    expect($response->json('data.currency'))->toBe('USD');
});

it('ignores a client supplied price', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $product = Product::withoutGlobalScopes()->create([
        'business_id' => $business->id,
        'name' => 'Widget',
        'price' => 1000,
    ]);

    $response = apiAs($owner, $business, 'POST', '/api/v1/business/orders', [
        'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 1]],
    ])->assertCreated();

    expect($response->json('data.total'))->toBe(1000);
});

it('rejects an order for a quantity below one', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $product = Product::withoutGlobalScopes()->create([
        'business_id' => $business->id,
        'name' => 'Widget',
        'price' => 1000,
    ]);

    apiAs($owner, $business, 'POST', '/api/v1/business/orders', [
        'items' => [['product_id' => $product->id, 'quantity' => 0]],
    ])->assertStatus(422)->assertJsonValidationErrors('items.0.quantity');
});

it('reduces the stock and records a movement when inventory is tracked', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    $product = Product::withoutGlobalScopes()->create([
        'business_id' => $business->id,
        'name' => 'Tracked widget',
        'price' => 1000,
        'track_inventory' => true,
        'stock_quantity' => 10,
    ]);

    apiAs($owner, $business, 'POST', '/api/v1/business/orders', [
        'items' => [['product_id' => $product->id, 'quantity' => 4]],
    ])->assertCreated();

    expect($product->fresh()->stock_quantity)->toBe(6);
    expect(\App\Models\StockMovement::withoutGlobalScopes()->where('product_id', $product->id)->count())->toBe(1);
});

it('refuses to sell more than the available stock', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    $product = Product::withoutGlobalScopes()->create([
        'business_id' => $business->id,
        'name' => 'Scarce widget',
        'price' => 1000,
        'track_inventory' => true,
        'stock_quantity' => 2,
    ]);

    apiAs($owner, $business, 'POST', '/api/v1/business/orders', [
        'items' => [['product_id' => $product->id, 'quantity' => 5]],
    ])->assertStatus(422);

    expect($product->fresh()->stock_quantity)->toBe(2);
});

it('creates a customer and attaches it to an order', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $customer = apiAs($owner, $business, 'POST', '/api/v1/business/customers', [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.test',
    ])->assertCreated()->json('data');

    $product = Product::withoutGlobalScopes()->create([
        'business_id' => $business->id,
        'name' => 'Consulting',
        'price' => 5000,
    ]);

    $order = apiAs($owner, $business, 'POST', '/api/v1/business/orders', [
        'customer_id' => $customer['id'],
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertCreated();

    expect($order->json('data.customer_id'))->toBe($customer['id']);
});

it('creates an invoice and calculates its total', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $response = apiAs($owner, $business, 'POST', '/api/v1/business/invoices', [
        'items' => [
            ['description' => 'Design work', 'quantity' => 2, 'unit_amount' => 5000],
            ['description' => 'Hosting', 'quantity' => 1, 'unit_amount' => 1000],
        ],
        'discount' => 500,
        'tax' => 250,
    ])->assertCreated();

    expect($response->json('data.subtotal'))->toBe(11000);
    expect($response->json('data.discount'))->toBe(500);
    expect($response->json('data.tax'))->toBe(250);
    expect($response->json('data.total'))->toBe(10750);
    expect($response->json('data.invoice_number'))->toStartWith('INV-');
});

it('marks an invoice as paid and stamps the paid date', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $invoice = apiAs($owner, $business, 'POST', '/api/v1/business/invoices', [
        'items' => [['description' => 'Line', 'quantity' => 1, 'unit_amount' => 1000]],
    ])->assertCreated()->json('data');

    $response = apiAs($owner, $business, 'PUT', "/api/v1/business/invoices/{$invoice['id']}", [
        'status' => 'paid',
    ])->assertOk();

    expect($response->json('data.status'))->toBe('paid');
    expect($response->json('data.paid_at'))->not->toBeNull();
});

it('only brands invoices for a business with the branded invoices entitlement', function () {
    seedPlans();

    $owner = User::factory()->create();
    $free = makeBusiness($owner);
    $pro = makeBusiness($owner, proPlan());

    $freeInvoice = apiAs($owner, $free, 'POST', '/api/v1/business/invoices', [
        'items' => [['description' => 'Line', 'quantity' => 1, 'unit_amount' => 1000]],
    ])->assertCreated();

    $proInvoice = apiAs($owner, $pro, 'POST', '/api/v1/business/invoices', [
        'items' => [['description' => 'Line', 'quantity' => 1, 'unit_amount' => 1000]],
    ])->assertCreated();

    expect($freeInvoice->json('data.is_branded'))->toBeFalse();
    expect($proInvoice->json('data.is_branded'))->toBeTrue();
});

it('reports the dashboard totals of the business only', function () {
    seedPlans();

    $owner = User::factory()->create();
    $mine = makeBusiness($owner);
    $theirs = makeBusiness(User::factory()->create());

    Order::withoutGlobalScopes()->create([
        'business_id' => $mine->id,
        'order_number' => 'ORD-MINE-00001',
        'subtotal' => 1000,
        'total' => 1000,
        'currency' => 'USD',
        'status' => 'completed',
        'payment_status' => 'paid',
    ]);

    Order::withoutGlobalScopes()->create([
        'business_id' => $theirs->id,
        'order_number' => 'ORD-THEIRS-00001',
        'subtotal' => 5000,
        'total' => 5000,
        'currency' => 'USD',
        'status' => 'completed',
        'payment_status' => 'paid',
    ]);

    $response = apiAs($owner, $mine, 'GET', '/api/v1/business/dashboard');

    $response->assertOk();

    expect((int) $response->json('data.month_sales'))->toBe(1000);
    expect((int) $response->json('data.order_count'))->toBe(1);
});

it('builds a sales report for the requested period', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    Order::withoutGlobalScopes()->create([
        'business_id' => $business->id,
        'order_number' => 'ORD-REPORT-00001',
        'subtotal' => 3000,
        'tax' => 0,
        'discount' => 0,
        'total' => 3000,
        'currency' => 'USD',
        'status' => 'completed',
        'payment_status' => 'paid',
    ]);

    $response = apiAs($owner, $business, 'GET', '/api/v1/business/reports/sales');

    $response->assertOk();

    expect($response->json('data.totals.orders'))->toBe(1);
    expect($response->json('data.totals.sales'))->toBe(3000);
});

it('deletes a customer and releases the usage counter', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $customer = apiAs($owner, $business, 'POST', '/api/v1/business/customers', [
        'first_name' => 'Temp',
    ])->assertCreated()->json('data');

    $gate = app(\App\Services\Features\FeatureGate::class);
    expect($gate->usage($business, 'max_customers'))->toBe(1);

    apiAs($owner, $business, 'DELETE', "/api/v1/business/customers/{$customer['id']}")->assertOk();

    expect($gate->usage($business, 'max_customers'))->toBe(0);
});
