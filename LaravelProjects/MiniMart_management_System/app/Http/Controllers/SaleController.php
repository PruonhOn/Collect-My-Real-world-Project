<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    protected $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    public function pos()
    {
        return view('sales.pos');
    }

    public function index(Request $request)
    {
        $query = Sale::with('user', 'items.product');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', '%' . $request->search . '%');
        }

        $sales = $query->latest()->paginate(10);

        return view('sales.index', compact('sales'));
    }

    public function show($id)
    {
        $sale = Sale::with('items.product', 'user')->findOrFail($id);
        return view('sales.show', compact('sale'));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'cart' => 'required|json',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,other',
        ]);

        $cart = json_decode($request->cart, true);

        if (empty($cart)) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty.',
            ], 400);
        }

        try {
            $sale = $this->saleService->processSale($cart, $request->paid_amount, $request->payment_method);
            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully.',
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function printReceipt($id)
    {
        $sale = Sale::with('items.product', 'user')->findOrFail($id);
        return view('receipts.print', compact('sale'));
    }
}
