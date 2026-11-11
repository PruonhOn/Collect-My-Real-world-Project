<?php

namespace App\Filament\Widgets;

use App\Models\SaleItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopProductsWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Top 5 Products';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $topProducts = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Quantity Sold',
                    'data' => $topProducts->pluck('total_qty')->toArray(),
                    'backgroundColor' => ['#4f46e5', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6'],
                ],
            ],
            'labels' => $topProducts->pluck('product.name')->toArray(),
        ];
    }
}
