<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Transaction;
use App\Services\InventoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    use ApiResponse;

    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $query = Sale::with(['customer:id,name,phone', 'creator:id,name']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        $sales = $query->latest()->paginate($request->per_page ?? 15);
        return $this->paginatedSuccess($sales);
    }

    public function show(int $id)
    {
        $sale = Sale::with(['details.product:id,name,sku,image', 'customer', 'creator:id,name', 'payments'])->find($id);

        if (!$sale) {
            return $this->error('Sale not found', 404);
        }

        return $this->success($sale);
    }

    public function getByDateRange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $sales = Sale::with(['customer:id,name,phone', 'creator:id,name'])
            ->whereBetween('sale_date', [$request->start_date, $request->end_date])
            ->where('status', 'completed')
            ->latest()
            ->paginate($request->per_page ?? 15);

        return $this->paginatedSuccess($sales);
    }

    public function getTodaySales()
    {
        $sales = Sale::with(['customer:id,name,phone', 'creator:id,name'])
            ->whereDate('sale_date', now()->toDateString())
            ->where('status', 'completed')
            ->latest()
            ->get();

        $totals = [
            'total_sales' => $sales->count(),
            'total_revenue' => (float) $sales->sum('grand_total'),
            'total_paid' => (float) $sales->sum('paid_amount'),
            'total_due' => (float) $sales->sum('due_amount'),
            'total_discount' => (float) $sales->sum('discount_amount'),
            'total_tax' => (float) $sales->sum('tax_amount'),
        ];

        return $this->success([
            'sales' => $sales,
            'totals' => $totals,
        ]);
    }

    public function refund(Request $request, int $id)
    {
        $sale = Sale::with('details')->find($id);

        if (!$sale) {
            return $this->error('Sale not found', 404);
        }

        if ($sale->status === 'refunded') {
            return $this->error('Sale already refunded', 409);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        try {
            DB::transaction(function () use ($sale, $request) {
                $sale->update([
                    'status' => 'refunded',
                    'notes' => $request->reason,
                ]);

                foreach ($sale->details as $detail) {
                    $this->inventoryService->addStock(
                        $detail->product,
                        $detail->quantity,
                        'refund',
                        $sale->id,
                        $detail->unit_price,
                        'Refund for sale #' . $sale->invoice_no
                    );
                }

                $refundAmount = $request->refund_amount ?? $sale->paid_amount;

                Transaction::create([
                    'transaction_no' => 'TXN-' . strtoupper(Str::random(10)),
                    'type' => 'expense',
                    'amount' => $refundAmount,
                    'description' => 'Refund for sale - ' . $sale->invoice_no . ' - ' . $request->reason,
                    'transaction_date' => now(),
                    'payment_method' => $sale->payment_method,
                    'reference_type' => 'sale_refund',
                    'reference_id' => $sale->id,
                    'status' => 'completed',
                    'created_by' => auth()->id(),
                ]);
            });

            return $this->success($sale->fresh()->load(['details.product', 'customer', 'payments']), 'Sale refunded successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
