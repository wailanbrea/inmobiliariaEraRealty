<?php

namespace App\Console\Commands;

use App\Modules\PropertyImages\Models\PropertyImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Borra las fotos de propiedad que ya no referencia ninguna fila.
 *
 * Por defecto SOLO SIMULA, igual que media:prune y audit:prune. Borrar exige
 * --force.
 *
 * Los huerfanos aparecen sobre todo al reimportar: `era:import --force`
 * genera nombres aleatorios nuevos y, hasta que se corrigio, dejaba los
 * anteriores en disco. Tras tres pasadas habia 1.477 ficheros para 488
 * referenciados.
 */
class PrunePropertyImages extends Command
{
    protected $signature = 'properties:prune-images {--force : Borra de verdad, en vez de simular}';

    protected $description = 'Simula (o borra con --force) las fotos de propiedad sin fila que las referencie';

    public function handle(): int
    {
        // PropertyImage no usa borrado suave: cuando se borra una foto,
        // desaparece. Asi que basta con las filas vivas.
        $referenciados = PropertyImage::query()
            ->get(['path', 'thumbnail_path', 'webp_path'])
            ->flatMap(fn (PropertyImage $i) => $i->allPaths())
            ->unique()
            ->flip();

        $disco = Storage::disk('public');
        $huerfanos = [];
        $bytes = 0;

        foreach ($disco->allFiles('properties') as $ruta) {
            if ($referenciados->has($ruta)) {
                continue;
            }

            // .gitkeep sostiene la carpeta en el repositorio; borrarlo la
            // haria desaparecer del clon y storage:link fallaria.
            if (str_ends_with($ruta, '.gitkeep')) {
                continue;
            }

            $huerfanos[] = $ruta;
            $bytes += $disco->size($ruta);
        }

        $this->newLine();
        $this->info('Fotos de propiedad huérfanas');
        $this->line(str_repeat('─', 54));
        $this->line('  En disco:      '.count($disco->allFiles('properties')));
        $this->line('  Referenciadas: '.$referenciados->count());
        $this->line('  Huérfanas:     '.count($huerfanos).'  ('.round($bytes / 1048576).' MB)');

        if ($huerfanos === []) {
            $this->newLine();
            $this->info('  Nada que limpiar.');

            return self::SUCCESS;
        }

        foreach (array_slice($huerfanos, 0, 5) as $h) {
            $this->line('    · '.$h);
        }
        if (count($huerfanos) > 5) {
            $this->line('    … y '.(count($huerfanos) - 5).' más.');
        }

        if (! $this->option('force')) {
            $this->newLine();
            $this->comment('  Simulación. Nada se ha borrado.');
            $this->comment('  Para ejecutarla: php artisan properties:prune-images --force');

            return self::SUCCESS;
        }

        // Ruta por ruta y explicita: nada de comodines sobre el directorio.
        foreach ($huerfanos as $ruta) {
            $disco->delete($ruta);
        }

        $this->newLine();
        $this->info('  Borradas '.count($huerfanos).' fotos ('.round($bytes / 1048576).' MB liberados).');
        $this->comment('  Se regeneran con: php artisan era:import --force');

        return self::SUCCESS;
    }
}
