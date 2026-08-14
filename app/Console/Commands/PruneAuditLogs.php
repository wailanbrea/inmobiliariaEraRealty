<?php

namespace App\Console\Commands;

use App\Enums\AuditAction;
use App\Modules\Audit\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Poda del registro de auditoria.
 *
 * Por defecto SOLO SIMULA: dice cuantas filas borraria y desde cuando, sin
 * tocar nada. Borrar de verdad exige --force.
 *
 * Es deliberado. Un registro de auditoria es la unica prueba de lo que paso,
 * y un comando que borra en cuanto se teclea es exactamente lo que no debe
 * existir en la herramienta pensada para investigar incidentes.
 */
class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune
                            {--force : Borra de verdad, en vez de solo simular}';

    protected $description = 'Simula (o ejecuta con --force) la poda de apuntes de auditoría caducados';

    public function handle(): int
    {
        $retencion = (int) config('audit.retention_days');
        $retencionFallidos = (int) config('audit.failed_login_retention_days');

        $corte = Carbon::now()->subDays($retencion);
        $corteFallidos = Carbon::now()->subDays($retencionFallidos);

        // Dos consultas porque los accesos fallidos caducan antes: son los
        // que mas volumen generan y su valor se pierde rapido.
        $generales = AuditLog::query()
            ->where('created_at', '<', $corte)
            ->where('action', '!=', AuditAction::LoginFailed->value);

        $fallidos = AuditLog::query()
            ->where('created_at', '<', $corteFallidos)
            ->where('action', AuditAction::LoginFailed->value);

        $nGenerales = (clone $generales)->count();
        $nFallidos = (clone $fallidos)->count();

        $this->newLine();
        $this->info('Poda del registro de auditoría');
        $this->line(str_repeat('─', 50));
        $this->line("  Apuntes anteriores a {$corte->format('d/m/Y')} ({$retencion} días): {$nGenerales}");
        $this->line("  Accesos fallidos anteriores a {$corteFallidos->format('d/m/Y')} ({$retencionFallidos} días): {$nFallidos}");
        $this->line('  Quedarían en la tabla: '.(AuditLog::count() - $nGenerales - $nFallidos));

        if ($nGenerales + $nFallidos === 0) {
            $this->newLine();
            $this->info('  Nada que podar.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->newLine();
            $this->comment('  Simulación. Nada se ha borrado.');
            $this->comment('  Para ejecutarla de verdad: php artisan audit:prune --force');

            return self::SUCCESS;
        }

        $borrados = $generales->delete() + $fallidos->delete();

        $this->newLine();
        $this->info("  Borrados {$borrados} apuntes.");

        return self::SUCCESS;
    }
}
