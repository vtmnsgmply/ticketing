<?php

namespace Database\Seeders;

use App\Repository\RoleRepository;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(RoleRepository::class)->ensureDefaultRoles();
    }
}
