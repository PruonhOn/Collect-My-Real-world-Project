<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Api\Controller;
use App\Models\Product;
use App\Services\Features\FeatureGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(protected FeatureGate $gate) {}

    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with('category');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->query('category_id')) {
            $query->where('category_id', $category);
        }

        $sort = $request->query('sort', 'created_at');
        $direction = $request->query('direction', 'desc');
        $allowed = ['name', 'price', 'created_at', 'stock_quantity', 'sku'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'created_at';
        }
        $query->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc');

        $products = $query->paginate(min(100, (int) $request->query('per_page', 15)));

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $business = app('current.business');

        if (! $this->gate->checkLimit($business, 'max_products')) {
            return response()->json([
                'message' => 'Product limit reached for your current plan.',
                'limit' => $this->gate->limit($business, 'max_products'),
                'upgrade_required' => true,
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('business_id', $business->id),
            ],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'track_inventory' => ['nullable', 'boolean'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! empty($validated['sku'])) {
            $exists = Product::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('sku', $validated['sku'])
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'SKU already used in this business.', 'errors' => ['sku' => ['SKU already used in this business.']]], 422);
            }
        }

        $validated['business_id'] = $business->id;

        $product = Product::create($validated);
        $this->gate->incrementUsage($business, 'max_products');

        return response()->json(['data' => $product->load('category')], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $this->authorizeSameBusiness($product);

        return response()->json(['data' => $product->load('category')]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->authorizeSameBusiness($product);

        $business = app('current.business');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('business_id', $business->id),
            ],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'track_inventory' => ['nullable', 'boolean'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! empty($validated['sku'])) {
            $exists = Product::withoutGlobalScopes()
                ->where('business_id', $product->business_id)
                ->where('sku', $validated['sku'])
                ->where('id', '!=', $product->id)
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'SKU already used in this business.', 'errors' => ['sku' => ['SKU already used in this business.']]], 422);
            }
        }

        $product->update($validated);

        return response()->json(['data' => $product->fresh()->load('category')]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $business = app('current.business');
        $this->authorizeSameBusiness($product);

        $product->delete();
        $this->gate->incrementUsage($business, 'max_products', -1);

        return response()->json(['message' => 'Product deleted.']);
    }

    protected function authorizeSameBusiness(Product $product): void
    {
        $business = app('current.business');
        if ($product->business_id !== $business->id) {
            abort(404, 'Product not found.');
        }
    }
}
