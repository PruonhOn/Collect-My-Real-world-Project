<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Tenant isolation
|--------------------------------------------------------------------------
|
| Every tenant-scoped resource must be invisible to, and immutable by, any
| other business. These tests exercise the cross-tenant attack surface.
|
*/

it('never returns another tenant products', function () {
    seedPlans();

    $owner = User::factory()->create();
    $mine = makeBusiness($owner);
    $theirs = makeBusiness(User::factory()->create());

    Product::withoutGlobalScopes()->create([
        'business_id' => $mine->id,
        'name' => 'My Product',
        'price' => 1000,
    ]);

    Product::withoutGlobalScopes()->create([
        'business_id' => $theirs->id,
        'name' => 'Their Product',
        'price' => 2000,
    ]);

    $response = apiAs($owner, $mine, 'GET', '/api/v1/business/products');

    $response->assertOk();

    $names = collect($response->json('data'))->pluck('name');

    expect($names)->toContain('My Product');
    expect($names)->not->toContain('Their Product');
});

it('returns 404 when reading a product from another tenant', function () {
    seedPlans();

    $owner = User::factory()->create();
    $mine = makeBusiness($owner);
    $theirs = makeBusiness(User::factory()->create());

    $foreign = Product::withoutGlobalScopes()->create([
        'business_id' => $theirs->id,
        'name' => 'Their Product',
        'price' => 2000,
    ]);

    apiAs($owner, $mine, 'GET', "/api/v1/business/products/{$foreign->id}")->assertNotFound();
});

it('refuses to update or delete a product from another tenant', function () {
    seedPlans();

    $owner = User::factory()->create();
    $mine = makeBusiness($owner);
    $theirs = makeBusiness(User::factory()->create());

    $foreign = Product::withoutGlobalScopes()->create([
        'business_id' => $theirs->id,
        'name' => 'Their Product',
        'price' => 2000,
    ]);

    apiAs($owner, $mine, 'PUT', "/api/v1/business/products/{$foreign->id}", ['price' => 1])->assertNotFound();
    apiAs($owner, $mine, 'DELETE', "/api/v1/business/products/{$foreign->id}")->assertNotFound();

    expect($foreign->fresh()->price)->toBe(2000);
});

it('rejects a request that carries a business the user does not belong to', function () {
    seedPlans();

    $owner = User::factory()->create();
    makeBusiness($owner);

    $foreign = makeBusiness(User::factory()->create());

    $this->actingAs($owner)
        ->withHeader('X-Business-Id', (string) $foreign->id)
        ->getJson('/api/v1/business/dashboard')
        ->assertForbidden();
});

it('requires a business context header', function () {
    seedPlans();

    $owner = User::factory()->create();
    makeBusiness($owner);

    $this->actingAs($owner)
        ->getJson('/api/v1/business/dashboard')
        ->assertStatus(400);
});

it('never returns another tenant customers or orders', function () {
    seedPlans();

    $owner = User::factory()->create();
    $mine = makeBusiness($owner);
    $theirs = makeBusiness(User::factory()->create());

    Customer::withoutGlobalScopes()->create([
        'business_id' => $mine->id,
        'first_name' => 'Mine',
        'last_name' => 'Customer',
    ]);
    Customer::withoutGlobalScopes()->create([
        'business_id' => $theirs->id,
        'first_name' => 'Theirs',
        'last_name' => 'Customer',
    ]);

    $customers = apiAs($owner, $mine, 'GET', '/api/v1/business/customers');
    $customers->assertOk();
    expect(collect($customers->json('data'))->pluck('first_name'))->toContain('Mine')
        ->not->toContain('Theirs');

    Order::withoutGlobalScopes()->create([
        'business_id' => $theirs->id,
        'order_number' => 'ORD-OTHER-00001',
        'subtotal' => 100,
        'total' => 100,
        'currency' => 'USD',
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $orders = apiAs($owner, $mine, 'GET', '/api/v1/business/orders');
    $orders->assertOk();
    expect(collect($orders->json('data'))->pluck('order_number'))->not->toContain('ORD-OTHER-00001');
});

it('cannot attach an order to a customer owned by another tenant', function () {
    seedPlans();

    $owner = User::factory()->create();
    $mine = makeBusiness($owner);
    $theirs = makeBusiness(User::factory()->create());

    $foreignCustomer = Customer::withoutGlobalScopes()->create([
        'business_id' => $theirs->id,
        'first_name' => 'Theirs',
        'last_name' => 'Customer',
    ]);

    $product = Product::withoutGlobalScopes()->create([
        'business_id' => $mine->id,
        'name' => 'My Product',
        'price' => 1000,
    ]);

    apiAs($owner, $mine, 'POST', '/api/v1/business/orders', [
        'customer_id' => $foreignCustomer->id,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertStatus(422)->assertJsonValidationErrors('customer_id');
});

it('cannot sell a product owned by another tenant', function () {
    seedPlans();

    $owner = User::factory()->create();
    $mine = makeBusiness($owner);
    $theirs = makeBusiness(User::factory()->create());

    $foreignProduct = Product::withoutGlobalScopes()->create([
        'business_id' => $theirs->id,
        'name' => 'Their Product',
        'price' => 1000,
    ]);

    apiAs($owner, $mine, 'POST', '/api/v1/business/orders', [
        'items' => [['product_id' => $foreignProduct->id, 'quantity' => 1]],
    ])->assertStatus(422);
});

it('cannot attach an invoice to a customer or order from another tenant', function () {
    seedPlans();

    $owner = User::factory()->create();
    $mine = makeBusiness($owner);
    $theirs = makeBusiness(User::factory()->create());

    $foreignCustomer = Customer::withoutGlobalScopes()->create([
        'business_id' => $theirs->id,
        'first_name' => 'Theirs',
        'last_name' => 'Customer',
    ]);

    $foreignOrder = Order::withoutGlobalScopes()->create([
        'business_id' => $theirs->id,
        'order_number' => 'ORD-OTHER-00002',
        'subtotal' => 100,
        'total' => 100,
        'currency' => 'USD',
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $base = [
        'items' => [['description' => 'Line', 'quantity' => 1, 'unit_amount' => 100]],
    ];

    apiAs($owner, $mine, 'POST', '/api/v1/business/invoices', $base + ['customer_id' => $foreignCustomer->id])
        ->assertStatus(422)->assertJsonValidationErrors('customer_id');

    apiAs($owner, $mine, 'POST', '/api/v1/business/invoices', $base + ['order_id' => $foreignOrder->id])
        ->assertStatus(422)->assertJsonValidationErrors('order_id');
});

it('cannot attach a product to a category owned by another tenant', function () {
    seedPlans();

    $owner = User::factory()->create();
    $mine = makeBusiness($owner);
    $theirs = makeBusiness(User::factory()->create());

    $foreignCategory = \App\Models\Category::withoutGlobalScopes()->create([
        'business_id' => $theirs->id,
        'name' => 'Their Category',
    ]);

    apiAs($owner, $mine, 'POST', '/api/v1/business/products', [
        'name' => 'My Product',
        'price' => 1000,
        'category_id' => $foreignCategory->id,
    ])->assertStatus(422)->assertJsonValidationErrors('category_id');
});

it('keeps business scoped queries isolated from the global scope', function () {
    seedPlans();

    $owner = User::factory()->create();
    $mine = makeBusiness($owner);
    $theirs = makeBusiness(User::factory()->create());

    Product::withoutGlobalScopes()->create(['business_id' => $mine->id, 'name' => 'Mine', 'price' => 1]);
    Product::withoutGlobalScopes()->create(['business_id' => $theirs->id, 'name' => 'Theirs', 'price' => 1]);

    // Without a bound tenant the global scope must not hide rows from an
    // explicit query, but a scoped query must only return the bound tenant.
    app()->instance('current.business', $mine);

    $scoped = Product::query()->pluck('name');

    expect($scoped)->toContain('Mine')->not->toContain('Theirs');
});

it('denies access to a suspended business', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $business->update(['is_suspended' => true]);

    apiAs($owner, $business->fresh(), 'GET', '/api/v1/business/dashboard')->assertForbidden();
});
