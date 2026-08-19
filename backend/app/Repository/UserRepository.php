<?php

namespace App\Repository;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function findById(int $userId): ?User
    {
        return User::query()->with('role')->find($userId);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()
            ->with('role')
            ->where('email', $email)
            ->first();
    }

    public function findActiveCustomerByTelegramChatId(string $chatId): ?User
    {
        return User::query()
            ->with('role')
            ->where('telegram_profile', trim($chatId))
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('slug', Role::CUSTOMER))
            ->first();
    }

    public function touchLastLogin(User $user): void
    {
        $user->forceFill([
            'last_login_at' => now(),
        ])->save();
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = User::query()->with('role', 'primaryDepartment');

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', $search)
                ->orWhere('email', 'like', $search)
                ->orWhere('company', 'like', $search));
        }

        if (! empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        if (($filters['is_active'] ?? '') !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->latest()->paginate(min(max((int) ($filters['per_page'] ?? 20), 1), 100));
    }

    public function create(array $data): User
    {
        return User::query()->create($data)->load('role', 'primaryDepartment', 'departments');
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh(['role', 'primaryDepartment', 'departments']);
    }

    public function syncDepartments(User $user, array $departmentIds): User
    {
        $user->departments()->sync($departmentIds);

        return $user->fresh(['role', 'primaryDepartment', 'departments']);
    }

    public function departmentIds(User $user): array
    {
        return $user->departments()->pluck('departments.id')->all();
    }

    public function activeAdministratorCount(?int $excludeUserId = null): int
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('slug', 'administrator'))
            ->when($excludeUserId, fn ($query) => $query->whereKeyNot($excludeUserId))
            ->count();
    }
}
