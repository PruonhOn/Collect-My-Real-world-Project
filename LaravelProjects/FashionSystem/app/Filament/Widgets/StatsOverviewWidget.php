<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Products', Product::count())
                ->description('All products in inventory')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('success'),

            Stat::make('Total Sales (Today)', number_format(
                Sale::whereDate('sale_date', Carbon::today())->sum('total'), 2
            ))
                ->description('Sales value for today')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('primary'),

            Stat::make('Total Revenue (This Month)', number_format(
                Sale::whereMonth('sale_date', Carbon::now()->month)
                    ->whereYear('sale_date', Carbon::now()->year)
                    ->sum('total'), 2
            ))
                ->description('Revenue for current month')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('info'),

            Stat::make('Low Stock Products', Product::whereColumn('stock_quantity', '<=', 'min_stock')->count())
                ->description('Products below minimum stock')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
