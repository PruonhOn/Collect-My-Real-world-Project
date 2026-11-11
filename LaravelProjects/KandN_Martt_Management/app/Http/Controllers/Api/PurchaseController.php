<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Services\InventoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    use ApiResponse;

    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $query = Purchase::with(['supplier:id,name', 'creator:id,name']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('reference_no', 'like', "%{$search}%");
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }

        $purchases = $query->latest()->paginate($request->per_page ?? 15);
        return $this->paginatedSuccess($purchases);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'other_cost' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,ordered,received,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        try {
            $purchase = DB::transaction(function () use ($request) {
                $subtotal = 0;
                $totalDiscount = 0;
                $totalTax = 0;

                foreach ($request->items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $itemSubtotal = $item['unit_cost'] * $item['quantity'];
                    $itemDiscount = $item['discount'] ?? 0;
                    $itemTax = $item['tax'] ?? 0;

                    $subtotal += $itemSubtotal;
                    $totalDiscount += $itemDiscount;
                    $totalTax += $itemTax;
                }

                $discountType = $request->discount_type;
                $discountValue = $request->discount_value ?? 0;
                $discountAmount = $request->discount_amount ?? 0;

                if ($discountType === 'percentage' && $discountValue > 0) {
                    $discountAmount = ($subtotal * $discountValue) / 100;
                }

                $grandTotal = $subtotal - $discountAmount + $totalTax + ($request->shipping_cost ?? 0) + ($request->other_cost ?? 0);
                $paidAmount = $request->paid_amount ?? 0;
                $dueAmount = $grandTotal - $paidAmount;

                $purchase = Purchase::create([
                    'supplier_id' => $request->supplier_id,
                    'reference_no' => 'PO-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                    'purchase_date' => $request->purchase_date,
                    'subtotal' => $subtotal,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'discount_amount' => $discountAmount,
                    'tax_amount' => $totalTax,
                    'shipping_cost' => $request->shipping_cost ?? 0,
                    'other_cost' => $request->other_cost ?? 0,
                    'grand_total' => $grandTotal,
                    'paid_amount' => $paidAmount,
                    'due_amount' => $dueAmount,
                    'payment_status' => $paidAmount >= $grandTotal ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid'),
                    'status' => $request->status,
                    'notes' => $request->notes,
                    'created_by' => auth()->id(),
                ]);

                foreach ($request->items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $itemSubtotal = $item['unit_cost'] * $item['quantity'];

                    PurchaseDetail::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                        'subtotal' => $itemSubtotal,
                        'discount' => $item['discount'] ?? 0,
                        'tax' => $item['tax'] ?? 0,
                        'total' => $itemSubtotal - ($item['discount'] ?? 0) + ($item['tax'] ?? 0),
                    ]);

                    if ($request->status === 'received') {
                        $this->inventoryService->addStock(
                            $product,
                            $item['quantity'],
                            'purchase',
                            $purchase->id,
                            $item['unit_cost'],
                            'Purchase #' . $purchase->reference_no
                        );
                    }
                }

                return $purchase->load(['details.product:id,name,sku', 'supplier:id,name', 'creator:id,name']);
            });

            return $this->success($purchase, 'Purchase created successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        $purchase = Purchase::with(['details.product', 'supplier', 'payments', 'creator:id,name'])->find($id);

        if (!$purchase) {
            return $this->error('Purchase not found', 404);
        }

        return $this->success($purchase);
    }

    public function update(Request $request, int $id)
    {
        $purchase = Purchase::find($id);

        if (!$purchase) {
            return $this->error('Purchase not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,ordered,received,cancelled',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_status' => 'sometimes|in:unpaid,partial,paid',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->only(['status', 'paid_amount', 'payment_status', 'notes']);

        if ($request->has('paid_amount')) {
            $data['due_amount'] = $purchase->grand_total - $request->paid_amount;
            $data['paid_amount'] = $request->paid_amount;

            if ($request->paid_amount >= $purchase->grand_total) {
                $data['payment_status'] = 'paid';
            } elseif ($request->paid_amount > 0) {
                $data['payment_status'] = 'partial';
            }
        }

        if ($request->has('status') && $request->status === 'received' && $purchase->status !== 'received') {
            $data['status'] = 'received';

            foreach ($purchase->details as $detail) {
                $this->inventoryService->addStock(
                    $detail->product,
                    $detail->quantity,
                    'purchase',
                    $purchase->id,
                    $detail->unit_cost,
                    'Purchase #' . $purchase->reference_no
                );
            }
        }

        $purchase->update($data);

        return $this->success($purchase->fresh()->load(['details.product:id,name,sku', 'supplier:id,name', 'creator:id,name']), 'Purchase updated successfully');
    }

    public function destroy(int $id)
    {
        $purchase = Purchase::find($id);

        if (!$purchase) {
            return $this->error('Purchase not found', 404);
        }

        $purchase->delete();

        return $this->success(null, 'Purchase deleted successfully');
    }
}
