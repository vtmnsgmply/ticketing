<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Repository\UserRepository;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function update(User $user, array $data): User
    {
        return $this->users->update($user, array_intersect_key($data, array_flip(['name', 'email', 'phone', 'company', 'telegram_profile'])));
    }

    public function changePassword(User $user, array $data): void
    {
        if (! Hash::check($data['current_password'], $user->password)) {
            throw new BusinessRuleException('Current password is incorrect.');
        }

        $this->users->update($user, ['password' => Hash::make($data['password'])]);
    }
}
