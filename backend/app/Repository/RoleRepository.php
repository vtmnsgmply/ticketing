<?php

namespace App\Repository;

use App\Models\Role;

class RoleRepository
{
    /**
     * @return array<string, string>
     */
    public function defaultRoles(): array
    {
        return [
            Role::CUSTOMER => 'Customer',
            Role::AGENT => 'Staff / Agent',
            Role::MANAGER => 'Manager',
            Role::ADMINISTRATOR => 'Administrator',
        ];
    }

    public function findBySlug(string $slug): ?Role
    {
        return Role::query()->where('slug', $slug)->first();
    }

    public function ensureDefaultRoles(): void
    {
        foreach ($this->defaultRoles() as $slug => $name) {
            Role::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name],
            );
        }
    }
}
