<?php

namespace Database\Seeders;

use App\Modules\PropertyTypes\Models\PropertyType;
use Illuminate\Database\Seeder;

/**
 * Tipos de propiedad del prompt maestro (§8), en ambos idiomas.
 * Idempotente: se identifica por slug y no pisa cambios del administrador.
 */
class PropertyTypeSeeder extends Seeder
{
    /** @return list<array{slug:string, es:string, en:string, icon:string}> */
    public static function definitions(): array
    {
        return [
            ['slug' => 'apartamento',    'es' => 'Apartamento',    'en' => 'Apartment',       'icon' => 'apartment'],
            ['slug' => 'casa',           'es' => 'Casa',           'en' => 'House',           'icon' => 'house'],
            ['slug' => 'villa',          'es' => 'Villa',          'en' => 'Villa',           'icon' => 'villa'],
            ['slug' => 'penthouse',      'es' => 'Penthouse',      'en' => 'Penthouse',       'icon' => 'domain'],
            ['slug' => 'solar',          'es' => 'Solar',          'en' => 'Lot',             'icon' => 'crop_landscape'],
            ['slug' => 'terreno',        'es' => 'Terreno',        'en' => 'Land',            'icon' => 'landscape'],
            ['slug' => 'local-comercial', 'es' => 'Local comercial', 'en' => 'Retail space',  'icon' => 'storefront'],
            ['slug' => 'nave',           'es' => 'Nave industrial', 'en' => 'Warehouse',      'icon' => 'warehouse'],
            ['slug' => 'oficina',        'es' => 'Oficina',        'en' => 'Office',          'icon' => 'business_center'],
            ['slug' => 'finca',          'es' => 'Finca',          'en' => 'Farm',            'icon' => 'agriculture'],
            ['slug' => 'proyecto',       'es' => 'Proyecto',       'en' => 'Development',     'icon' => 'foundation'],
        ];
    }

    public function run(): void
    {
        foreach (self::definitions() as $i => $tipo) {
            PropertyType::updateOrCreate(
                ['slug' => $tipo['slug']],
                [
                    'name' => ['es' => $tipo['es'], 'en' => $tipo['en']],
                    'icon' => $tipo['icon'],
                    'is_active' => true,
                    'sort_order' => $i,
                ],
            );
        }

        $this->command->info('Tipos de propiedad: '.count(self::definitions()).'.');
    }
}
