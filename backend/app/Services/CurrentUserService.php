<?php

namespace App\Services;

use App\Models\User;
use App\Repository\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CurrentUserService
{
    public function __construct(
        private readonly UserRepository $users,
    ) {
    }

    public function getCurrentUser(int $userId): User
    {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw (new ModelNotFoundException())->setModel(User::class, [$userId]);
        }

        return $user;
    }
}
