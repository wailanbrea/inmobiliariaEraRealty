<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Users\Services\UserGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Restablece la contrasena de un usuario del panel desde la consola.
 *
 * Es la ULTIMA red de seguridad: la que funciona cuando no hay correo
 * configurado, no queda ningun super_admin que pueda entrar, o el panel
 * simplemente no abre. Solo la puede ejecutar quien tenga acceso al servidor,
 * que es exactamente la garantia que se quiere.
 *
 *   php artisan admin:password ana@erarealtyrd.com
 *   php artisan admin:password ana@erarealtyrd.com --promote
 */
class ResetAdminPassword extends Command
{
    protected $signature = 'admin:password
                            {email? : Correo del usuario}
                            {--promote : Le da ademas el rol super_admin}
                            {--list : Solo muestra los usuarios y sus roles}';

    protected $description = 'Restablece la contraseña de un usuario del panel (última red de seguridad, sin correo)';

    public function handle(UserGuard $guard): int
    {
        if ($this->option('list')) {
            return $this->listar();
        }

        $email = $this->argument('email') ?: $this->ask('Correo del usuario');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No hay ningún usuario con el correo {$email}.");
            $this->newLine();
            $this->line('  Para ver los que existen:  php artisan admin:password --list');

            return self::FAILURE;
        }

        $clave = $this->generarClave();

        $user->forceFill([
            'password' => $clave,
            'must_change_password' => true,
            // Se reactiva: si estaba desactivado, restablecer la clave sin
            // poder entrar no arregla nada.
            'is_active' => true,
            'remember_token' => Str::random(60),
        ])->save();

        if ($this->option('promote')) {
            $user->syncRoles(['super_admin']);
        }

        $this->newLine();
        $this->info('Contraseña restablecida');
        $this->line(str_repeat('─', 52));
        $this->line('  Usuario:     '.$user->name);
        $this->line('  Correo:      '.$user->email);
        $this->line('  Rol:         '.($user->getRoleNames()->first() ?? '(sin rol)'));
        $this->newLine();
        $this->line('  Contraseña:  '.$clave);
        $this->newLine();
        $this->comment('  Se pedirá cambiarla en el primer acceso.');
        $this->comment('  Entra en:    '.route('admin.login'));
        $this->newLine();

        return self::SUCCESS;
    }

    private function listar(): int
    {
        $usuarios = User::with('roles')->orderBy('name')->get();

        if ($usuarios->isEmpty()) {
            $this->warn('No hay ningún usuario. Ejecuta: php artisan db:seed');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['Nombre', 'Correo', 'Rol', 'Activo'],
            $usuarios->map(fn (User $u) => [
                $u->name,
                $u->email,
                $u->getRoleNames()->first() ?? '—',
                $u->is_active ? 'sí' : 'no',
            ])->all()
        );

        return self::SUCCESS;
    }

    /**
     * Misma forma que la del panel: cuatro grupos de cuatro, sin caracteres
     * que se confundan al dictarla por telefono (0/O, 1/l).
     */
    private function generarClave(): string
    {
        $alfabeto = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        return collect(range(1, 4))
            ->map(fn () => collect(range(1, 4))
                ->map(fn () => $alfabeto[random_int(0, strlen($alfabeto) - 1)])
                ->implode(''))
            ->implode('-');
    }
}
