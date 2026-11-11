<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;
use App\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected BarcodeService $barcodeService;

    public function __construct(BarcodeService $barcodeService)
    {
        $this->barcodeService = $barcodeService;
    }

    public function index(Request $request)
    {
        $query = Product::with(['category:id,name', 'brand:id,name', 'unit:id,short_name']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->latest()->paginate(20);
        $categories = Category::where('status', 'active')->pluck('name', 'id');
        $brands = Brand::where('status', 'active')->pluck('name', 'id');
        $units = Unit::where('status', 'active')->pluck('name', 'id');

        return view('products.index', compact('products', 'categories', 'brands', 'units'));
    }

    public function create()
    {
        $categories = Category::where('status', 'active')->pluck('name', 'id');
        $brands = Brand::where('status', 'active')->pluck('name', 'id');
        $units = Unit::where('status', 'active')->pluck('name', 'id');
        return view('products.form', compact('categories', 'brands', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
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

        $validated['slug'] = Str::slug($request->name) . '-' . Str::random(4);
        $validated['created_by'] = auth()->id();

        if (empty($validated['barcode'])) {
            $validated['barcode'] = $this->barcodeService->generateProductBarcode(Product::max('id') + 1);
        }

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Product created successfully');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'brand', 'unit', 'stock', 'creator:id,name']);
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::where('status', 'active')->pluck('name', 'id');
        $brands = Brand::where('status', 'active')->pluck('name', 'id');
        $units = Unit::where('status', 'active')->pluck('name', 'id');
        return view('products.form', compact('product', 'categories', 'brands', 'units'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'required|exists:units,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|max:100|unique:products,barcode,' . $product->id,
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'min_stock' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive,draft',
        ]);

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully');
    }

    public function data(Request $request)
    {
        $query = Product::with('category:id,name');

        if ($request->filled('search') && $request->search['value']) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('stock_status')) {
            $status = $request->stock_status;
            if ($status === 'low') $query->whereColumn('current_stock', '<=', 'min_stock')->where('current_stock', '>', 0);
            elseif ($status === 'out') $query->where('current_stock', '<=', 0);
            elseif ($status === 'in') $query->where('current_stock', '>', 0);
        }

        $recordsTotal = Product::count();
        $recordsFiltered = $query->count();
        $products = $query->orderBy($request->columns[$request->order[0]['column']]['data'] ?? 'name', $request->order[0]['dir'] ?? 'asc')
            ->skip($request->start)
            ->take($request->length)
            ->get();

        return response()->json([
            'draw' => (int) $request->draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $products->map(function ($p) {
                return [
                    'id' => $p->id,
                    'image' => $p->image ? asset('storage/' . $p->image) : null,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'category' => $p->category ? ['name' => $p->category->name] : null,
                    'selling_price' => $p->selling_price,
                    'stock_quantity' => $p->current_stock,
                    'min_stock' => $p->min_stock,
                    'status' => $p->status,
                ];
            }),
        ]);
    }

    public function barcode(Product $product)
    {
        $barcodeSvg = $this->barcodeService->generateSvg($product->barcode ?? $product->sku);
        return view('products.barcode', compact('product', 'barcodeSvg'));
    }

    public function list()
    {
        return Product::where('status', 'active')
            ->get(['id', 'name', 'sku'])
            ->map(fn($p) => ['id' => $p->id, 'name' => $p->name . ' (' . $p->sku . ')']);
    }
}
