<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\BarcodeService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ApiResponse;

    protected BarcodeService $barcodeService;

    public function __construct(BarcodeService $barcodeService)
    {
        $this->barcodeService = $barcodeService;
    }

    public function index(Request $request)
    {
        $query = Product::with(['category:id,name', 'brand:id,name', 'unit:id,short_name', 'stock']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('low_stock')) {
            $query->whereColumn('current_stock', '<=', 'min_stock');
        }

        $products = $query->latest()->paginate($request->per_page ?? 15);
        return $this->paginatedSuccess($products);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'required|exists:units,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'barcode' => 'nullable|string|max:100|unique:products,barcode',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'min_stock' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive,draft',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->all();
        $data['slug'] = Str::slug($request->name) . '-' . Str::random(4);
        $data['created_by'] = auth()->id();

        if (empty($data['barcode'])) {
            $productId = Product::max('id') + 1;
            $data['barcode'] = $this->barcodeService->generateProductBarcode($productId);
        }

        $product = Product::create($data);

        return $this->success($product->load(['category:id,name', 'brand:id,name', 'unit:id,short_name']), 'Product created successfully', 201);
    }

    public function show(int $id)
    {
        $product = Product::with(['category', 'brand', 'unit', 'stock', 'creator:id,name'])->find($id);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        return $this->success($product);
    }

    public function update(Request $request, int $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'sometimes|exists:units,id',
            'name' => 'sometimes|string|max:255',
            'sku' => 'sometimes|string|max:100|unique:products,sku,' . $id,
            'barcode' => 'nullable|string|max:100|unique:products,barcode,' . $id,
            'cost_price' => 'sometimes|numeric|min:0',
            'selling_price' => 'sometimes|numeric|min:0',
            'min_stock' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:active,inactive,draft',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $product->update($request->all());

        return $this->success($product->fresh()->load(['category:id,name', 'brand:id,name', 'unit:id,short_name']), 'Product updated successfully');
    }

    public function destroy(int $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        $product->delete();

        return $this->success(null, 'Product deleted successfully');
    }

    public function generateBarcode(int $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        $barcode = $this->barcodeService->generateProductBarcode($product->id);
        $product->update(['barcode' => $barcode]);

        return $this->success([
            'product' => $product,
            'barcode_svg' => $this->barcodeService->generateSvg($barcode),
            'barcode_html' => $this->barcodeService->generateHtml($barcode),
        ], 'Barcode generated successfully');
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $products = Product::with(['category:id,name', 'unit:id,short_name'])
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'sku', 'barcode', 'selling_price', 'current_stock', 'image', 'unit_id', 'category_id']);

        return $this->success($products);
    }
}
