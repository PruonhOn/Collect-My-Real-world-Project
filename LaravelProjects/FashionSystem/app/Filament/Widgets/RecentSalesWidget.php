<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentSalesWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return Sale::query()
            ->with('customer')
            ->orderBy('sale_date', 'desc')
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('invoice_no')
                ->label('Invoice No')
                ->searchable(),

            TextColumn::make('customer.name')
                ->label('Customer Name'),

            TextColumn::make('total')
                ->label('Total')
                ->money('USD'),

            TextColumn::make('payment_status')
                ->label('Payment Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'paid' => 'success',
                    'partial' => 'warning',
                    'unpaid' => 'danger',
                    default => 'gray',
                }),

            TextColumn::make('sale_date')
                ->label('Sale Date')
                ->dateTime('M d, Y')
                ->sortable(),
        ];
    }
}
