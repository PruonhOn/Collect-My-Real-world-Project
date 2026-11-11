<?php

namespace App\Exceptions;

use App\Models\ProductVariant;
use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(public readonly ProductVariant $variant, public readonly int $requested = 0)
    {
        $label = $variant->product->name.($variant->label ? ' ('.$variant->label.')' : '');
        $message = "Insufficient stock for {$label}. Available: {$variant->stock_quantity}, requested: {$requested}.";

        parent::__construct($message);
    }
}
