<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\PosService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PosController extends Controller
{
    use ApiResponse;

    protected PosService $posService;

    public function __construct(PosService $posService)
    {
        $this->posService = $posService;
    }

    public function searchProduct(Request $request)
    {
        $query = $request->get('q', '');
        $products = $this->posService->searchProduct($query);
        return $this->success($products);
    }

    public function scanBarcode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barcode' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $product = $this->posService->scanBarcode($request->barcode);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        return $this->success($product);
    }

    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,bank_transfer,mobile_banking',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        try {
            $sale = $this->posService->createSale($request->all());
            return $this->success($sale, 'Sale completed successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function getReceipt(int $id)
    {
        $sale = Sale::with(['details.product', 'customer', 'creator'])->find($id);

        if (!$sale) {
            return $this->error('Sale not found', 404);
        }

        return $this->success($sale);
    }

    public function lowStockAlerts()
    {
        $alerts = $this->posService->getLowStockAlerts();
        return $this->success($alerts);
    }
}
