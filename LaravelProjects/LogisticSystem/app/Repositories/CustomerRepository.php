<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $customer)
    {
        parent::__construct($customer);
    }

    public function model(): Customer
    {
        return new Customer();
    }

    public function findByCode(string $code): ?Customer
    {
        return $this->findBy('code', $code);
    }

    public function findByPhone(string $phone): ?Customer
    {
        return $this->findBy('phone', $phone);
    }

    public function findByEmail(string $email): ?Customer
    {
        return $this->findBy('email', $email);
    }
}
