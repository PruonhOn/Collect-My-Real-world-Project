<?php

namespace App\Services;

use App\Models\Expense;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class ExpenseService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createExpense(array $data): Expense
    {
        if (!isset($data['expense_number'])) {
            $data['expense_number'] = $this->generateExpenseNumber();
        }

        $data['status'] = $data['status'] ?? 'pending';

        /** @var Expense */
        return $this->repository->create($data);
    }

    public function generateExpenseNumber(): string
    {
        $prefix = 'EXP';
        $year = now()->format('Y');
        $month = now()->format('m');
        $lastExpense = Expense::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->orderByDesc('id')
            ->first();

        $sequence = $lastExpense ? ((int) substr($lastExpense->expense_number, -4)) + 1 : 1;

        return "{$prefix}-{$year}{$month}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function getCategories(): Collection
    {
        return Expense::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->orderBy('category')
            ->pluck('category');
    }

    public function findByExpenseNumber(string $expenseNumber): ?Expense
    {
        /** @var Expense|null */
        return $this->repository->findBy('expense_number', $expenseNumber);
    }

    public function getExpensesByBranch(int $branchId): Collection
    {
        return $this->repository->findAllBy('branch_id', $branchId);
    }

    public function getExpensesByCategory(string $category): Collection
    {
        return $this->repository->findAllBy('category', $category);
    }

    public function approveExpense(int $id, int $approvedBy): ?Expense
    {
        /** @var Expense|null $expense */
        $expense = $this->repository->find($id);

        if (!$expense) {
            return null;
        }

        $expense->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
        ]);

        return $expense->fresh();
    }

    public function getExpensesByVehicle(int $vehicleId): Collection
    {
        return $this->repository->findAllBy('vehicle_id', $vehicleId);
    }

    public function getExpensesByDateRange(string $startDate, string $endDate): Collection
    {
        return Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->with(['branch', 'vehicle', 'createdBy'])
            ->orderByDesc('expense_date')
            ->get();
    }

    public function getExpenseSummary(?int $branchId = null, ?string $category = null): array
    {
        $query = Expense::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($category) {
            $query->where('category', $category);
        }

        return [
            'total_amount' => $query->sum('amount'),
            'total_count' => $query->count(),
            'average_amount' => $query->avg('amount'),
            'by_category' => (clone $query)
                ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('category')
                ->get(),
        ];
    }
}
