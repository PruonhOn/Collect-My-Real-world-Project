<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Support\Collection;

class TransactionRepository extends BaseRepository
{
    public function __construct(Transaction $transaction)
    {
        parent::__construct($transaction);
    }

    public function model(): Transaction
    {
        return new Transaction();
    }

    public function findByTransactionNumber(string $transactionNumber): ?Transaction
    {
        return $this->findBy('transaction_number', $transactionNumber);
    }

    public function findByType(string $type): Collection
    {
        return $this->findAllBy('type', $type);
    }

    public function findByBranch(int $branchId): Collection
    {
        return $this->findAllBy('branch_id', $branchId);
    }
}
