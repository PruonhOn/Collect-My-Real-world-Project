<?php

namespace App\Services;

use App\Models\Transaction;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class TransactionService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createTransaction(array $data): Transaction
    {
        if (!isset($data['transaction_number'])) {
            $data['transaction_number'] = $this->generateTransactionNumber();
        }

        $data['status'] = $data['status'] ?? 'completed';

        /** @var Transaction */
        return $this->repository->create($data);
    }

    public function generateTransactionNumber(): string
    {
        $prefix = 'TXN';
        $year = now()->format('Y');
        $month = now()->format('m');
        $lastTxn = Transaction::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->orderByDesc('id')
            ->first();

        $sequence = $lastTxn ? ((int) substr($lastTxn->transaction_number, -4)) + 1 : 1;

        return "{$prefix}-{$year}{$month}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function findByTransactionNumber(string $transactionNumber): ?Transaction
    {
        /** @var Transaction|null */
        return $this->repository->findBy('transaction_number', $transactionNumber);
    }

    public function getTransactionsByBranch(int $branchId): Collection
    {
        return $this->repository->findAllBy('branch_id', $branchId);
    }

    public function getTransactionsByType(string $type): Collection
    {
        return $this->repository->findAllBy('type', $type);
    }

    public function getTransactionsByDateRange(string $startDate, string $endDate): Collection
    {
        return Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->with(['branch', 'createdBy'])
            ->orderByDesc('transaction_date')
            ->get();
    }

    public function getTransactionSummary(?int $branchId = null): array
    {
        $query = Transaction::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $income = (clone $query)->where('type', 'income')->sum('amount');
        $expense = (clone $query)->where('type', 'expense')->sum('amount');

        return [
            'total_income' => $income,
            'total_expense' => $expense,
            'net' => $income - $expense,
            'count' => $query->count(),
        ];
    }
}
