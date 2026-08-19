<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Repository\RoleRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialAdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');

        if (! is_string($password) || trim($password) === '') {
            $this->command?->warn('ADMIN_PASSWORD is empty; initial administrator was not created.');

            return;
        }

        app(RoleRepository::class)->ensureDefaultRoles();

        $role = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();

        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'role_id' => $role->id,
                'name' => env('ADMIN_NAME', 'System Administrator'),
                'password' => Hash::make($password),
                'is_active' => true,
            ],
        );
    }
}
