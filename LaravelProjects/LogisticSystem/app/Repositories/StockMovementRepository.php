<?php

namespace App\Repositories;

use App\Models\StockMovement;
use Illuminate\Support\Collection;

class StockMovementRepository extends BaseRepository
{
    public function __construct(StockMovement $stockMovement)
    {
        parent::__construct($stockMovement);
    }

    public function model(): StockMovement
    {
        return new StockMovement();
    }

    public function findByType(string $type): Collection
    {
        return $this->findAllBy('type', $type);
    }

    public function findByItem(int $itemId): Collection
    {
        return $this->findAllBy('inventory_item_id', $itemId);
    }

    public function findByReference(string $reference): Collection
    {
        return $this->findAllBy('reference', $reference);
    }
}
