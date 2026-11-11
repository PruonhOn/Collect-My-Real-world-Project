<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Supplier;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();
        $totalSales = Sale::count();
        $totalRevenue = Sale::sum('grand_total');
        $totalCustomers = Customer::count();
        $totalSuppliers = Supplier::count();

        $lowStockProducts = Product::with('category', 'variants')
            ->whereIn('id', function ($q) {
                $q->select('product_id')
                  ->from('product_variants')
                  ->groupBy('product_id')
                  ->havingRaw('SUM(quantity) < 10');
            })
            ->get()
            ->map(function ($product) {
                $product->stock = $product->variants->sum('quantity');
                $product->price = $product->selling_price;
                return $product;
            });

        $recentSales = Sale::with('customer', 'user')
            ->latest()
            ->take(5)
            ->get();

        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('D');
            $chartData[] = Sale::whereDate('created_at', $date)->sum('grand_total');
        }

        return view('dashboard', compact(
            'totalProducts',
            'totalSales',
            'totalRevenue',
            'totalCustomers',
            'totalSuppliers',
            'lowStockProducts',
            'recentSales',
            'chartLabels',
            'chartData'
        ));
    }
}
