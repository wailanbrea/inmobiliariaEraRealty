<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Crea el super_admin inicial.
 *
 * La contrasena NO se escribe en codigo ni en el repositorio: se genera
 * aleatoria y se muestra UNA sola vez en consola. El usuario queda marcado
 * con must_change_password para forzar el cambio en el primer acceso.
 *
 * El email se puede fijar con ADMIN_EMAIL en .env.
 * Ver docs/10_TODO_MASTER.md pregunta 6.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@erarealtyrd.com');

        if (User::where('email', $email)->exists()) {
            $this->command->warn("El usuario {$email} ya existe. No se modifica.");

            return;
        }

        $password = Str::password(16, symbols: false);

        $user = User::create([
            'name' => 'Administrador',
            'email' => $email,
            'password' => $password,
            'is_active' => true,
            'must_change_password' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole('super_admin');

        $this->command->newLine();
        $this->command->warn('════════════════════════════════════════════════════');
        $this->command->warn('  USUARIO ADMINISTRADOR CREADO');
        $this->command->warn('  Esta contrasena se muestra UNA sola vez.');
        $this->command->warn('════════════════════════════════════════════════════');
        $this->command->line("  Email:      {$email}");
        $this->command->line("  Contrasena: {$password}");
        $this->command->warn('════════════════════════════════════════════════════');
        $this->command->line('  Se pedira cambiarla en el primer inicio de sesion.');
        $this->command->newLine();
    }
}
