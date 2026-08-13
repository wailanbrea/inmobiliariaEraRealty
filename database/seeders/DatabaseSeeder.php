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
            SettingsSeeder::class,
        ]);

        // Los catalogos (tipos de propiedad, ubicaciones, amenidades) se
        // anaden en la Fase 2.
    }
}
