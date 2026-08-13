<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
        ]);

        // Los seeders de catalogos (tipos, ubicaciones, amenidades, settings)
        // se anaden en la Fase 1 y 2.
    }
}
