<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $query = Stock::with(['product:id,name,sku,cost_price,selling_price,min_stock,image', 'product.category:id,name']);

        if ($request->filled('low_stock')) {
            $query->whereColumn('quantity', '<=', 'min_quantity');
        }

        $stocks = $query->latest()->paginate(20);
        $lowStockProducts = $this->inventoryService->getLowStockProducts();
        $movements = StockMovement::with(['product:id,name,sku', 'creator:id,name'])
            ->latest()
            ->take(50)
            ->get();

        return view('inventory.index', compact('stocks', 'lowStockProducts', 'movements'));
    }

    public function data(Request $request)
    {
        $query = Stock::with(['product:id,name,sku,min_stock,max_stock']);

        if ($request->filled('warehouse')) {
            $query->where('warehouse_location', $request->warehouse);
        }
        if ($request->filled('category')) {
            $query->whereHas('product', fn($q) => $q->where('category_id', $request->category));
        }
        if ($request->filled('status')) {
            $s = $request->status;
            if ($s === 'low') $query->whereColumn('quantity', '>', 0)->whereColumn('quantity', '<=', 'min_quantity');
            elseif ($s === 'out') $query->where('quantity', '<=', 0);
            elseif ($s === 'over') $query->whereColumn('quantity', '>=', 'max_quantity');
            elseif ($s === 'normal') $query->whereColumn('quantity', '>', 'min_quantity')->whereColumn('quantity', '<', 'max_quantity');
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('product', fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
        }

        $recordsTotal = Stock::count();
        $recordsFiltered = $query->count();
        $stocks = $query->orderBy('updated_at', 'desc')
            ->skip($request->start)
            ->take($request->length)
            ->get();

        return response()->json([
            'draw' => (int) $request->draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $stocks->map(function ($s) {
                return [
                    'id' => $s->id,
                    'product' => $s->product ? ['name' => $s->product->name, 'sku' => $s->product->sku] : null,
                    'warehouse' => ['name' => $s->warehouse_location ?? 'Main'],
                    'quantity' => (float) $s->quantity,
                    'min_stock' => (float) ($s->min_quantity ?? $s->product->min_stock ?? 5),
                    'max_stock' => (float) ($s->max_quantity ?? $s->product->max_stock ?? 100),
                    'updated_at' => $s->updated_at ? $s->updated_at->toDateTimeString() : null,
                ];
            }),
        ]);
    }

    public function movementsIndex()
    {
        $movements = StockMovement::with(['product:id,name,sku', 'creator:id,name'])->latest()->paginate(50);
        return view('inventory.movements', compact('movements'));
    }

    public function movementsData(Request $request)
    {
        $query = StockMovement::with(['product:id,name,sku', 'creator:id,name']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $recordsTotal = StockMovement::count();
        $recordsFiltered = $query->count();
        $movements = $query->orderBy('created_at', 'desc')
            ->skip($request->start)
            ->take($request->length)
            ->get();

        return response()->json([
            'draw' => (int) $request->draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $movements->map(function ($m) {
                return [
                    'id' => $m->id,
                    'created_at' => $m->created_at ? $m->created_at->toDateTimeString() : null,
                    'product' => $m->product ? ['name' => $m->product->name, 'sku' => $m->product->sku] : ['name' => 'Deleted', 'sku' => '—'],
                    'type' => $m->type,
                    'quantity' => (float) $m->quantity,
                    'reference' => $m->reference_type ? ($m->reference_type . ' #' . $m->reference_id) : '-',
                    'notes' => $m->notes ?? '-',
                    'user' => $m->creator ? ['name' => $m->creator->name] : ['name' => 'System'],
                ];
            }),
        ]);
    }

    public function alerts(Request $request)
    {
        $query = Stock::with('product:id,name,sku,min_stock')
            ->where(function ($q) {
                $q->whereColumn('quantity', '<=', 'min_quantity')
                    ->orWhere('quantity', '<=', 0);
            });

        $recordsTotal = Stock::where(function ($q) {
            $q->whereColumn('quantity', '<=', 'min_quantity')->orWhere('quantity', '<=', 0);
        })->count();
        $recordsFiltered = $query->count();
        $stocks = $query->orderBy('quantity', 'asc')
            ->skip($request->start)
            ->take($request->length)
            ->get();

        return response()->json([
            'draw' => (int) $request->draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $stocks->map(function ($s) {
                return [
                    'id' => $s->id,
                    'product' => $s->product ? ['name' => $s->product->name, 'sku' => $s->product->sku] : null,
                    'quantity' => (float) $s->quantity,
                    'min_stock' => (float) ($s->min_quantity ?? $s->product->min_stock ?? 5),
                ];
            }),
        ]);
    }

    public function alertsCount()
    {
        $count = Stock::where(function ($q) {
            $q->whereColumn('quantity', '<=', 'min_quantity')->orWhere('quantity', '<=', 0);
        })->count();

        return response()->json(['count' => $count]);
    }

    public function stockIn(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($request->product_id);
        $this->inventoryService->addStock(
            $product,
            $request->quantity,
            'stock-in',
            null,
            $request->unit_cost,
            $request->notes
        );

        return response()->json(['message' => 'Stock added successfully']);
    }

    public function stockOut(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($request->product_id);
        $this->inventoryService->removeStock(
            $product,
            $request->quantity,
            'stock-out',
            null,
            null,
            $request->notes ?: $request->reason
        );

        return response()->json(['message' => 'Stock removed successfully']);
    }

    public function adjust(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'adjustment_type' => 'required|in:add,subtract,set',
            'quantity' => 'required|numeric|min:0',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($request->adjustment_type === 'set') {
            $stock = Stock::firstOrCreate(
                ['product_id' => $product->id],
                ['quantity' => 0, 'created_by' => auth()->id()]
            );
            $diff = $request->quantity - $stock->quantity;
            if ($diff > 0) {
                $this->inventoryService->addStock($product, $diff, 'adjustment', null, null, $request->reason);
            } elseif ($diff < 0) {
                $this->inventoryService->removeStock($product, abs($diff), 'adjustment', null, null, $request->reason);
            }
        } else {
            $type = $request->adjustment_type === 'add' ? 'addition' : 'subtraction';
            $this->inventoryService->adjustStock($product, $request->quantity, $type, $request->reason, $request->notes);
        }

        return response()->json(['message' => 'Stock adjusted successfully']);
    }

    public function warehouses()
    {
        $locations = Stock::whereNotNull('warehouse_location')
            ->distinct()
            ->pluck('warehouse_location')
            ->map(fn($loc) => ['id' => $loc, 'name' => $loc]);

        if ($locations->isEmpty()) {
            $locations = collect([
                ['id' => 'Main', 'name' => 'Main Warehouse'],
                ['id' => 'Warehouse A', 'name' => 'Warehouse A'],
            ]);
        }

        return $locations;
    }
}
