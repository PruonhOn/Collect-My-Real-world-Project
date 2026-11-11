<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryTransaction;
use App\Models\ProductVariant;
use App\Models\User;

class InventoryService
{
    public function stockIn(ProductVariant $variant, int $quantity, ?string $notes, User $user): InventoryTransaction
    {
        return $this->record($variant, InventoryTransactionType::Purchase, $quantity, 'Stock received (purchase)', null, null, $user, $notes);
    }

    public function recordSale(ProductVariant $variant, int $quantity, int $orderId, User $user): InventoryTransaction
    {
        return $this->record($variant, InventoryTransactionType::Sale, $quantity, 'Sold to customer', 'order', $orderId, $user);
    }

    public function recordReturn(ProductVariant $variant, int $quantity, int $orderId, User $user): InventoryTransaction
    {
        return $this->record($variant, InventoryTransactionType::Return, $quantity, 'Returned to stock (refund)', 'order', $orderId, $user);
    }

    public function adjust(ProductVariant $variant, InventoryTransactionType $type, int $quantity, string $reason, ?string $notes, User $user): InventoryTransaction
    {
        if ($type === InventoryTransactionType::Damage) {
            return $this->record($variant, InventoryTransactionType::Damage, $quantity, $reason, null, $notes, $user);
        }

        return $this->record($variant, InventoryTransactionType::Adjustment, $quantity, $reason, null, $notes, $user);
    }

    private function record(
        ProductVariant $variant,
        InventoryTransactionType $type,
        int $quantity,
        ?string $reason,
        ?string $referenceType,
        mixed $referenceId,
        User $user,
        ?string $notes = null
    ): InventoryTransaction {
        $before = $variant->stock_quantity;

        $magnitude = abs($quantity);
        $direction = match ($type) {
            InventoryTransactionType::Adjustment => $quantity >= 0 ? 'in' : 'out',
            default => $type->direction(),
        };

        $variant->increment(
            'stock_quantity',
            $type === InventoryTransactionType::Adjustment ? $quantity : ($direction === 'in' ? $magnitude : -$magnitude)
        );

        $after = $variant->fresh()->stock_quantity;

        return InventoryTransaction::create([
            'variant_id' => $variant->id,
            'user_id' => $user->id,
            'type' => $type->value,
            'direction' => $direction,
            'quantity' => $magnitude,
            'stock_before' => $before,
            'stock_after' => $after,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
        ]);
    }
}
