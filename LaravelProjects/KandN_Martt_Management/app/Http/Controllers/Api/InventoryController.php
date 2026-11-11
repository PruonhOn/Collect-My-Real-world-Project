<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\InventoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    use ApiResponse;

    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $query = Stock::with(['product:id,name,sku,cost_price,selling_price,min_stock', 'creator:id,name']);

        if ($request->has('low_stock')) {
            $query->whereColumn('quantity', '<=', 'min_quantity');
        }

        $stocks = $query->latest()->paginate($request->per_page ?? 15);
        return $this->paginatedSuccess($stocks);
    }

    public function stockIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $product = Product::findOrFail($request->product_id);

        try {
            $stock = $this->inventoryService->addStock(
                $product,
                $request->quantity,
                'manual_in',
                null,
                $request->unit_price,
                $request->notes
            );

            return $this->success($stock->load('product:id,name,sku'), 'Stock added successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function stockOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $product = Product::findOrFail($request->product_id);

        try {
            $stock = $this->inventoryService->removeStock(
                $product,
                $request->quantity,
                'manual_out',
                null,
                null,
                $request->notes
            );

            return $this->success($stock->load('product:id,name,sku'), 'Stock removed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function adjustStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:addition,subtraction,damage,expired',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:200',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $product = Product::findOrFail($request->product_id);

        try {
            $adjustment = $this->inventoryService->adjustStock(
                $product,
                $request->quantity,
                $request->type,
                $request->reason,
                $request->notes
            );

            return $this->success($adjustment->load('product:id,name,sku'), 'Stock adjusted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function movements(Request $request)
    {
        $productId = $request->product_id;
        $movements = $this->inventoryService->getStockMovements($productId);
        return $this->paginatedSuccess($movements);
    }

    public function lowStock()
    {
        $products = $this->inventoryService->getLowStockProducts();
        return $this->success($products);
    }

    public function show(int $id)
    {
        $stock = Stock::with(['product:id,name,sku,cost_price,selling_price,image', 'product.category:id,name', 'creator:id,name'])->find($id);

        if (!$stock) {
            return $this->error('Stock not found', 404);
        }

        return $this->success($stock);
    }
}
