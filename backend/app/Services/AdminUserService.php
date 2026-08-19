<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Role;
use App\Models\User;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Support\AuditAction;
use Illuminate\Support\Facades\Hash;

class AdminUserService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly RoleRepository $roles,
        private readonly AuditLogService $audit,
    ) {
    }

    public function paginate(array $filters)
    {
        return $this->users->paginate($filters);
    }

    public function find(int $id): ?User
    {
        return $this->users->findById($id);
    }

    public function create(User $actor, array $data, mixed $request): User
    {
        $departmentIds = $data['department_ids'] ?? [];
        unset($data['department_ids']);
        $data = $this->normalizeCustomerProfile($data);
        $data['password'] = Hash::make($data['password']);
        unset($data['password_confirmation']);
        $user = $this->users->create($data);
        if ($departmentIds !== []) {
            $user = $this->users->syncDepartments($user, $departmentIds);
        }
        $this->audit->record($actor, AuditAction::USER_CREATED, 'user', $user->id, null, $data, $request);

        return $user;
    }

    public function update(User $actor, User $user, array $data, mixed $request): User
    {
        $this->guardFinalAdmin($user, $data);
        $old = $user->only(['name', 'email', 'role_id', 'is_active', 'primary_department_id', 'customer_label', 'telegram_profile']);
        $departmentIds = $data['department_ids'] ?? null;
        unset($data['department_ids']);
        $data = $this->normalizeCustomerProfile($data, $user);
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        unset($data['password_confirmation']);
        $updated = $this->users->update($user, $data);
        if ($departmentIds !== null) {
            $updated = $this->users->syncDepartments($updated, $departmentIds);
        }
        $action = match (true) {
            array_key_exists('role_id', $data) && (int) $data['role_id'] !== (int) $old['role_id'] => AuditAction::USER_ROLE_CHANGED,
            array_key_exists('is_active', $data) && (bool) $data['is_active'] !== (bool) $old['is_active'] => ((bool) $data['is_active'] ? AuditAction::USER_REACTIVATED : AuditAction::USER_DISABLED),
            default => AuditAction::USER_UPDATED,
        };
        $this->audit->record($actor, $action, 'user', $user->id, $old, $data, $request);

        return $updated;
    }

    public function departments(User $actor, User $user, array $data, mixed $request): User
    {
        $old = [
            'primary_department_id' => $user->primary_department_id,
            'department_ids' => $this->users->departmentIds($user),
        ];
        $updated = $this->users->update($user, ['primary_department_id' => $data['primary_department_id'] ?? null]);
        $updated = $this->users->syncDepartments($updated, $data['department_ids'] ?? []);
        $this->audit->record($actor, AuditAction::USER_DEPARTMENTS_CHANGED, 'user', $user->id, $old, $data, $request);

        return $updated;
    }

    public function status(User $actor, User $user, bool $active, mixed $request): User
    {
        return $this->update($actor, $user, ['is_active' => $active] + $user->only(['name', 'email', 'phone', 'company', 'customer_label', 'telegram_profile', 'role_id', 'primary_department_id']), $request);
    }

    public function role(User $actor, User $user, int $roleId, mixed $request): User
    {
        return $this->update($actor, $user, ['role_id' => $roleId] + $user->only(['name', 'email', 'phone', 'company', 'customer_label', 'telegram_profile', 'is_active', 'primary_department_id']), $request);
    }

    private function normalizeCustomerProfile(array $data, ?User $existingUser = null): array
    {
        $customerRole = $this->roles->findBySlug(Role::CUSTOMER);
        $roleId = (int) ($data['role_id'] ?? $existingUser?->role_id ?? 0);
        $isCustomer = $customerRole !== null && $roleId === (int) $customerRole->id;

        if (! $isCustomer) {
            $data['customer_label'] = null;
            $data['telegram_profile'] = null;
        } elseif (array_key_exists('customer_label', $data) && $data['customer_label'] === '') {
            $data['customer_label'] = null;
        }

        if ($isCustomer && array_key_exists('telegram_profile', $data) && $data['telegram_profile'] === '') {
            $data['telegram_profile'] = null;
        }

        return $data;
    }

    private function guardFinalAdmin(User $user, array $newData): void
    {
        $user->loadMissing('role');
        if (! $user->hasRole(Role::ADMINISTRATOR) || $this->users->activeAdministratorCount((int) $user->id) > 0) {
            return;
        }

        $newRole = $this->roles->findBySlug(Role::ADMINISTRATOR);
        $keepsAdminRole = (int) ($newData['role_id'] ?? $user->role_id) === (int) $newRole?->id;
        $keepsActive = (bool) ($newData['is_active'] ?? $user->is_active);

        if (! $keepsAdminRole || ! $keepsActive) {
            throw new BusinessRuleException('At least one active Administrator account must remain.');
        }
    }
}
