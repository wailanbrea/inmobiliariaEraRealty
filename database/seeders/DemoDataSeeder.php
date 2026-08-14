<?php

namespace Database\Seeders;

use App\Enums\Currency;
use App\Enums\OperationType;
use App\Enums\PropertyStatus;
use App\Modules\Locations\Models\Province;
use App\Modules\Properties\Models\Property;
use App\Modules\PropertyImages\Models\PropertyImage;
use App\Modules\PropertyTypes\Models\PropertyType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Restaura el catalogo de demostracion desde database/data/properties.json.
 *
 * Se genera con `php artisan era:export`. Sirve para que quien clone el
 * repositorio tenga contenido real sin depender de que el sitio anterior siga
 * en pie ni de tener conexion.
 *
 * NO va en DatabaseSeeder: los datos de demostracion no deben colarse en una
 * instalacion de produccion por ejecutar `db:seed` sin pensar. Se pide a
 * proposito:
 *
 *     php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $fichero = database_path('data/properties.json');

        if (! File::exists($fichero)) {
            $this->command?->warn('No hay database/data/properties.json. Genera uno con: php artisan era:export');

            return;
        }

        $catalogo = json_decode(File::get($fichero), true);

        if (! is_array($catalogo)) {
            $this->command?->error('El fichero de datos no es un JSON válido.');

            return;
        }

        // Se resuelven una vez y se reutilizan: sin esto serian dos consultas
        // por propiedad solo para traducir un slug a un id.
        $tipos = PropertyType::pluck('id', 'slug');
        $provincias = Province::pluck('id', 'name');

        $creadas = 0;
        $fotos = 0;
        $sinFichero = 0;

        foreach ($catalogo as $fila) {
            $property = Property::updateOrCreate(
                ['reference_code' => $fila['reference_code']],
                [
                    'property_type_id' => $tipos[$fila['type']] ?? $tipos->first(),
                    'province_id' => $provincias[$fila['province']] ?? null,
                    'operation_type' => OperationType::tryFrom((string) $fila['operation_type']) ?? OperationType::Sale,
                    'status' => PropertyStatus::tryFrom((string) $fila['status']) ?? PropertyStatus::Available,
                    'price' => $fila['price'],
                    'currency' => Currency::tryFrom((string) $fila['currency']) ?? Currency::USD,
                    'address' => $fila['address'],
                    'bedrooms' => $fila['bedrooms'],
                    'bathrooms' => $fila['bathrooms'],
                    'parking_spaces' => $fila['parking_spaces'],
                    'construction_area' => $fila['construction_area'],
                    'land_area' => $fila['land_area'],
                    'is_featured' => $fila['is_featured'],
                    'is_investment' => $fila['is_investment'],
                    'published_at' => $fila['published_at'],
                ]
            );

            foreach ($fila['translations'] as $t) {
                $property->translations()->updateOrCreate(
                    ['locale' => $t['locale']],
                    [
                        'title' => $t['title'],
                        'slug' => $t['slug'] ?: Str::slug($t['title']),
                        'description' => $t['description'],
                        'short_description' => $t['short_description'],
                    ]
                );
            }

            $property->images()->delete();

            foreach ($fila['images'] as $i) {
                // Si el fichero no esta en disco se anota, pero la propiedad
                // se crea igual: es mejor un catalogo sin algunas fotos que un
                // seeder que se planta a la mitad.
                if (! File::exists(storage_path('app/public/'.$i['path']))) {
                    $sinFichero++;
                }

                PropertyImage::create(['property_id' => $property->id] + $i);
                $fotos++;
            }

            $creadas++;
        }

        $this->command?->info("Catálogo de demostración: {$creadas} propiedades, {$fotos} fotos.");

        if ($sinFichero > 0) {
            $this->command?->warn(
                "  {$sinFichero} fotos no están en storage/. Ejecuta `php artisan era:import --force` para descargarlas."
            );
        }
    }
}
