<?php

namespace App\Http\Controllers;

use App\Enums\InventoryTransactionType;
use App\Http\Requests\StockAdjustmentRequest;
use App\Http\Requests\StockInRequest;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', InventoryTransaction::class);

        $transactions = InventoryTransaction::query()
            ->with('variant.product', 'user')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $search = trim($request->input('q'));
                $q->whereHas('variant.product', function ($product) use ($search) {
                    $product->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('date_to')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stock = ProductVariant::query()
            ->with(['product', 'inventoryTransactions' => fn ($q) => $q->latest()->limit(1)])
            ->when($request->filled('stock_q'), function ($q) use ($request) {
                $search = trim($request->input('stock_q'));
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            })
            ->whereHas('product', fn ($p) => $p->whereNull('deleted_at'))
            ->orderBy('stock_quantity')
            ->paginate(15, ['*'], 'stock_page');

        $types = InventoryTransactionType::cases();
        $variants = ProductVariant::query()->with('product')->whereHas('product', fn ($p) => $p->whereNull('deleted_at'))->orderBy('id')->get();

        return view('inventory.index', compact('transactions', 'stock', 'types', 'variants'));
    }

    public function stockIn(StockInRequest $request): RedirectResponse
    {
        $this->authorize('stockIn', InventoryTransaction::class);

        $variant = ProductVariant::findOrFail($request->integer('variant_id'));

        $this->inventory->stockIn($variant, $request->integer('quantity'), $request->input('notes'), $request->user());

        return back()->with('success', "Added {$request->integer('quantity')} unit(s) to stock for \"{$variant->product->name} {$variant->label}\".");
    }

    public function adjust(StockAdjustmentRequest $request): RedirectResponse
    {
        $this->authorize('adjust', InventoryTransaction::class);

        $variant = ProductVariant::findOrFail($request->integer('variant_id'));
        $type = InventoryTransactionType::from($request->input('type'));

        if ($type === InventoryTransactionType::Adjustment) {
            return $this->handleAdjustment($request, $variant);
        }

        $quantity = $request->integer('quantity');

        if ($type->direction() === 'out' && $variant->stock_quantity < $quantity) {
            return back()->withErrors([
                'quantity' => 'Cannot remove '.$quantity.' unit(s) — only '.$variant->stock_quantity.' in stock.',
            ])->withInput();
        }

        $this->inventory->adjust($variant, $type, $quantity, $request->input('reason'), $request->input('notes'), $request->user());

        return back()->with('success', 'Stock adjusted successfully.');
    }

    private function handleAdjustment(Request $request, ProductVariant $variant): RedirectResponse
    {
        $delta = $request->integer('quantity');
        $resulting = $variant->stock_quantity + $delta;

        if ($resulting < 0) {
            return back()->withErrors([
                'quantity' => 'Adjustment would result in negative stock (current: '.$variant->stock_quantity.').',
            ])->withInput();
        }

        $this->inventory->adjust($variant, InventoryTransactionType::Adjustment, $delta, $request->input('reason'), $request->input('notes'), $request->user());

        return back()->with('success', "Stock adjusted by {$delta} (new level: {$resulting}).");
    }
}
