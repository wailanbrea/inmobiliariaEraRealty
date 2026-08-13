<?php

namespace App\Console\Commands;

use App\Modules\Media\Models\MediaFile;
use App\Modules\Media\Services\MediaLibraryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Limpieza de la biblioteca de medios.
 *
 * Por defecto SOLO LISTA. Borrar exige --force y una confirmacion, porque un
 * huerfano puede ser un archivo legitimo subido por otra via.
 * Ver docs/05_MEDIA_UPLOADS.md seccion 8.
 */
class PruneMedia extends Command
{
    protected $signature = 'media:prune
                            {--force : Borra de verdad, en vez de solo listar}';

    protected $description = 'Lista (o borra con --force) ficheros huérfanos y registros sin fichero';

    public function handle(MediaLibraryService $service): int
    {
        $huerfanos = $service->findOrphans();
        $sinFichero = $service->findMissingFiles();

        $this->newLine();
        $this->info('Revisión de la biblioteca de medios');
        $this->line(str_repeat('─', 50));

        // --- Ficheros en disco sin registro ---
        if ($huerfanos === []) {
            $this->line('  Ficheros huérfanos en disco: ninguno.');
        } else {
            $this->warn('  Ficheros en disco sin registro en base de datos: '.count($huerfanos));

            foreach (array_slice($huerfanos, 0, 20) as $ruta) {
                $this->line("    · {$ruta}");
            }

            if (count($huerfanos) > 20) {
                $this->line('    … y '.(count($huerfanos) - 20).' más.');
            }
        }

        // --- Registros sin fichero ---
        if ($sinFichero === []) {
            $this->line('  Registros sin fichero: ninguno.');
        } else {
            $this->warn('  Registros cuyo fichero ya no existe: '.count($sinFichero));
        }

        if ($huerfanos === [] && $sinFichero === []) {
            $this->newLine();
            $this->info('Nada que limpiar.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->newLine();
            $this->comment('Simulación: no se ha borrado nada.');
            $this->comment('Ejecuta con --force para borrar.');

            return self::SUCCESS;
        }

        if (! $this->confirm('¿Borrar definitivamente lo listado?', false)) {
            $this->comment('Cancelado. No se ha borrado nada.');

            return self::SUCCESS;
        }

        Storage::disk('public')->delete($huerfanos);
        MediaFile::whereIn('id', $sinFichero)->delete();

        $this->newLine();
        $this->info('Borrados '.count($huerfanos).' ficheros y '.count($sinFichero).' registros.');

        return self::SUCCESS;
    }
}
