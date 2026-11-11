<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')
            ->where('status', true)
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return $products;
    }

    public function featured()
    {
        return Product::with('category')
            ->where('status', true)
            ->where('featured', true)
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();
    }

    public function show($slug)
    {
        $product = Product::with('category')
            ->where('slug', $slug)
            ->where('status', true)
            ->firstOrFail();
        return $product;
    }

    public function byCategory($categorySlug)
    {
        $products = Product::with('category')
            ->whereHas('category', fn($q) => $q->where('slug', $categorySlug))
            ->where('status', true)
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return $products;
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        $products = Product::with('category')
            ->where('status', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return $products;
    }
}
