<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function model(): User
    {
        return new User();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findBy('email', $email);
    }
}
