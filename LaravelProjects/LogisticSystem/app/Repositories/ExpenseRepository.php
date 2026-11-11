<?php

namespace App\Repositories;

use App\Models\Expense;
use Illuminate\Support\Collection;

class ExpenseRepository extends BaseRepository
{
    public function __construct(Expense $expense)
    {
        parent::__construct($expense);
    }

    public function model(): Expense
    {
        return new Expense();
    }

    public function findByExpenseNumber(string $expenseNumber): ?Expense
    {
        return $this->findBy('expense_number', $expenseNumber);
    }

    public function findByCategory(string $category): Collection
    {
        return $this->findAllBy('category', $category);
    }

    public function findByBranch(int $branchId): Collection
    {
        return $this->findAllBy('branch_id', $branchId);
    }
}
