<?php

namespace App\Console\Commands;

use App\Modules\Properties\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Vuelca el catalogo a un JSON versionable para que el repositorio sea
 * autonomo.
 *
 * POR QUE UN JSON Y NO UN VOLCADO SQL: un .sql lleva dentro los ids
 * auto-incrementales, el motor y la version del esquema, asi que se rompe en
 * cuanto cambia una migracion y no se puede leer en una revision de codigo.
 * Un JSON se lee, se compara linea a linea en un diff y lo carga un seeder
 * que respeta el esquema actual.
 *
 * Se acompana de DemoDataSeeder, que es quien lo restaura.
 */
class ExportDemoData extends Command
{
    protected $signature = 'era:export {--path=database/data/properties.json}';

    protected $description = 'Vuelca las propiedades importadas a un JSON versionable';

    public function handle(): int
    {
        $destino = base_path($this->option('path'));
        File::ensureDirectoryExists(dirname($destino));

        $propiedades = Property::query()
            ->with(['translations', 'images', 'type', 'province', 'amenities'])
            ->orderBy('reference_code')
            ->get()
            ->map(fn (Property $p) => [
                'reference_code' => $p->reference_code,
                // El TIPO se guarda por slug y no por id: los ids dependen del
                // orden en que corriera el seeder del catalogo y no significan
                // nada fuera de esta base de datos.
                'type' => $p->type?->slug,
                'province' => $p->province?->name,
                'operation_type' => $p->operation_type?->value,
                'status' => $p->status?->value,
                'price' => $p->price,
                'currency' => $p->currency?->value,
                'address' => $p->address,
                'bedrooms' => $p->bedrooms,
                'bathrooms' => $p->bathrooms,
                'parking_spaces' => $p->parking_spaces,
                'construction_area' => $p->construction_area,
                'land_area' => $p->land_area,
                'is_featured' => (bool) $p->is_featured,
                'is_investment' => (bool) $p->is_investment,
                'published_at' => $p->published_at?->toIso8601String(),
                'translations' => $p->translations->map(fn ($t) => [
                    'locale' => $t->locale,
                    'title' => $t->title,
                    'slug' => $t->slug,
                    'description' => $t->description,
                    'short_description' => $t->short_description,
                ])->values(),
                'images' => $p->images->map(fn ($i) => [
                    'path' => $i->path,
                    'thumbnail_path' => $i->thumbnail_path,
                    'webp_path' => $i->webp_path,
                    'original_name' => $i->original_name,
                    'alt_text' => $i->alt_text,
                    'is_main' => (bool) $i->is_main,
                    'sort_order' => $i->sort_order,
                    'width' => $i->width,
                    'height' => $i->height,
                    'size' => $i->size,
                    'mime_type' => $i->mime_type,
                ])->values(),
            ])
            ->values();

        File::put(
            $destino,
            json_encode($propiedades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $fotos = $propiedades->sum(fn ($p) => count($p['images']));

        $this->newLine();
        $this->info('Catálogo exportado');
        $this->line('  Propiedades: '.$propiedades->count());
        $this->line('  Fotos:       '.$fotos);
        $this->line('  Fichero:     '.$this->option('path').'  ('.round(filesize($destino) / 1024).' KB)');
        $this->newLine();
        $this->comment('  Se restaura con: php artisan db:seed --class=DemoDataSeeder');

        return self::SUCCESS;
    }
}
