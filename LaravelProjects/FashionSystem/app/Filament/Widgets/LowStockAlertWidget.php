<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockAlertWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected bool $canToggle = true;

    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return Product::query()
            ->whereColumn('stock_quantity', '<=', 'min_stock')
            ->orderBy('stock_quantity');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Product Name')
                ->searchable(),

            TextColumn::make('sku')
                ->label('SKU'),

            TextColumn::make('stock_quantity')
                ->label('Current Stock'),

            TextColumn::make('min_stock')
                ->label('Min Stock'),

            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->getStateUsing(fn (Product $record): string => $record->stock_quantity === 0 ? 'Out of Stock' : 'Low Stock')
                ->color(fn (Product $record): string => $record->stock_quantity === 0 ? 'danger' : 'warning'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('edit')
                ->label('Edit')
                ->url(fn (Product $record): string => route('filament.admin.resources.products.edit', $record))
                ->icon('heroicon-o-pencil'),
        ];
    }
}
