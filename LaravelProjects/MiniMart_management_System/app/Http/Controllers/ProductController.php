<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    protected $productRepository;
    protected $barcodeService;

    public function __construct(ProductRepository $productRepository, BarcodeService $barcodeService)
    {
        $this->productRepository = $productRepository;
        $this->barcodeService = $barcodeService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category_id', 'low_stock']);
        $products = $this->productRepository->getAll($filters);
        $categories = Category::orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $validated['barcode'] = $this->barcodeService->generateUniqueBarcode();

        $product = $this->productRepository->create($validated);

        return redirect()->route('products.index')
            ->with('success', "Product '{$product->name}' created successfully with barcode: {$product->barcode}");
    }

    public function show($id)
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            return redirect()->route('products.index')->with('error', 'Product not found.');
        }
        return view('products.show', compact('product'));
    }

    public function edit($id)
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            return redirect()->route('products.index')->with('error', 'Product not found.');
        }
        $categories = Category::orderBy('name')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            return redirect()->route('products.index')->with('error', 'Product not found.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $this->productRepository->update($product, $validated);

        return redirect()->route('products.index')
            ->with('success', "Product '{$product->name}' updated successfully.");
    }

    public function destroy($id)
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            return redirect()->route('products.index')->with('error', 'Product not found.');
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $this->productRepository->delete($product);

        return redirect()->route('products.index')
            ->with('success', "Product '{$product->name}' deleted successfully.");
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $products = $this->productRepository->search($query);
        return response()->json($products);
    }

    public function searchByBarcode(Request $request)
    {
        $barcode = $request->get('barcode', '');
        $product = $this->barcodeService->findProductByBarcode($barcode);
        return response()->json($product);
    }

    public function lowStock()
    {
        $products = $this->productRepository->getLowStock();
        return view('inventory.index', compact('products'));
    }
}
