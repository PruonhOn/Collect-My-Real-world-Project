<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->with(['category', 'variants', 'images'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim($request->input('q'));
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when($request->filled('stock'), function ($query) use ($request) {
                $filter = $request->input('stock');
                $query->when($filter === 'low', fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 5)))
                    ->when($filter === 'out', fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('stock_quantity', 0)))
                    ->when($filter === 'in', fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('stock_quantity', '>', 5)));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = \App\Models\Category::query()->with('parent')->orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        $categories = \App\Models\Category::query()->with('parent')->orderBy('name')->get();

        return view('products.create', compact('categories'));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $product = $this->products->createWithVariants(
            $request->validated(),
            $request->file('images', [])
        );

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Product "'.$product->name.'" created successfully.');
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        $product->load(['category', 'variants', 'images', 'orderItems']);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load('variants', 'images', 'category');
        $categories = \App\Models\Category::query()->with('parent')->orderBy('name')->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $this->products->updateWithVariants(
            $product,
            $request->validated(),
            $request->file('images', []),
            $request->input('existing_images', [])
        );

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Product "'.$product->name.'" updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Product "'.$product->name.'" deleted.');
    }
}
