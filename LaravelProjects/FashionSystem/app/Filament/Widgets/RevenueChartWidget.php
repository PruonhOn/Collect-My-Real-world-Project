<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenueChartWidget extends ChartWidget
{
    protected int | string | array $columnSpan = 2;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Monthly Revenue';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $data = collect();
        $now = Carbon::now();

        for ($i = 5; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $month = $date->format('M Y');
            $revenue = Sale::whereMonth('sale_date', $date->month)
                ->whereYear('sale_date', $date->year)
                ->sum('total');

            $data->push([
                'month' => $month,
                'revenue' => (float) $revenue,
            ]);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data->pluck('revenue')->toArray(),
                    'borderColor' => '#4f46e5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }
}
