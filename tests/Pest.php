<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Crea un usuario con el rol indicado, sembrando antes roles y permisos.
 */
function userWithRole(string $role, array $attributes = []): User
{
    if (! Role::where('name', $role)->exists()) {
        (new RolePermissionSeeder)->setCommand(
            new class extends Command
            {
                public function info($string, $verbosity = null): void {}
            }
        )->run();
    }

    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}
