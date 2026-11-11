<?php

namespace App\Repositories;

use App\Models\Sale;

class SaleRepository extends BaseRepository
{
    public function __construct(Sale $model)
    {
        parent::__construct($model);
    }

    public function findWithDetails(int $id): ?Sale
    {
        return $this->model->with(['details.product', 'customer', 'payments', 'creator'])->find($id);
    }

    public function getByDateRange(string $startDate, string $endDate)
    {
        return $this->model->with(['customer'])
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->orderByDesc('sale_date')
            ->paginate(20);
    }

    public function getTodaySales()
    {
        return $this->model->with(['customer'])
            ->whereDate('sale_date', today())
            ->where('status', 'completed')
            ->get();
    }
}
