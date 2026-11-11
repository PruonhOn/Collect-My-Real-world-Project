<?php

namespace App\Services;

use App\Models\Product;

class BarcodeService
{
    public function generateUniqueBarcode(): string
    {
        $prefix = 'MM';
        $lastProduct = Product::where('barcode', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastProduct) {
            $lastNumber = (int) substr($lastProduct->barcode, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $barcode = $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);

        while (Product::where('barcode', $barcode)->exists()) {
            $newNumber++;
            $barcode = $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
        }

        return $barcode;
    }

    public function findProductByBarcode(string $barcode): ?Product
    {
        return Product::with('category')->where('barcode', $barcode)->first();
    }
}
