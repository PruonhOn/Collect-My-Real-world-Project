<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class CustomerService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createCustomer(array $data): Customer
    {
        $data['outstanding_balance'] = $data['outstanding_balance'] ?? 0;

        /** @var Customer */
        return $this->repository->create($data);
    }

    public function updateCustomer(int $id, array $data): ?Customer
    {
        /** @var Customer|null */
        return $this->repository->update($id, $data);
    }

    public function findByCode(string $code): ?Customer
    {
        /** @var Customer|null */
        return $this->repository->findBy('code', $code);
    }

    public function getActiveCustomers(): Collection
    {
        return $this->repository->findAllBy('is_active', true);
    }

    public function searchCustomers(?string $keyword = null): Collection
    {
        if (!$keyword) {
            return $this->repository->all();
        }

        return Customer::where('name', 'like', "%{$keyword}%")
            ->orWhere('company', 'like', "%{$keyword}%")
            ->orWhere('phone', 'like', "%{$keyword}%")
            ->orWhere('email', 'like', "%{$keyword}%")
            ->orWhere('code', 'like', "%{$keyword}%")
            ->get();
    }

    public function getShipmentHistory(int $id): Collection
    {
        $customer = $this->repository->find($id);
        if (!$customer) {
            return collect();
        }
        return $customer->shipments()->with(['originBranch', 'destinationBranch'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getCustomerWithShipments(int $id): ?Customer
    {
        /** @var Customer|null */
        return $this->repository->find($id)?->load('shipments');
    }

    public function updateOutstandingBalance(int $customerId): ?Customer
    {
        /** @var Customer|null $customer */
        $customer = $this->repository->find($customerId);

        if (!$customer) {
            return null;
        }

        $totalInvoiced = $customer->invoices()->sum('total');
        $totalPaid = $customer->payments()->sum('amount');
        $balance = $totalInvoiced - $totalPaid;

        $customer->update(['outstanding_balance' => max(0, $balance)]);

        return $customer->fresh();
    }

    public function toggleActive(int $id): ?Customer
    {
        /** @var Customer|null $customer */
        $customer = $this->repository->find($id);

        if (!$customer) {
            return null;
        }

        $customer->update(['is_active' => !$customer->is_active]);

        return $customer->fresh();
    }
}
