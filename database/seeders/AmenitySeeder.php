<?php

namespace Database\Seeders;

use App\Modules\Properties\Models\Amenity;
use Illuminate\Database\Seeder;

/**
 * Amenidades habituales del mercado dominicano.
 * Las que aparecen en el diseno del detalle vienen primero.
 */
class AmenitySeeder extends Seeder
{
    /** @return list<array{slug:string, es:string, en:string, icon:string, category:string}> */
    public static function definitions(): array
    {
        return [
            // --- Edificio ---
            ['slug' => 'piscina',          'es' => 'Piscina',            'en' => 'Swimming pool',   'icon' => 'pool',           'category' => 'building'],
            ['slug' => 'piscina-infinity', 'es' => 'Piscina infinity',   'en' => 'Infinity pool',   'icon' => 'pool',           'category' => 'building'],
            ['slug' => 'gimnasio',         'es' => 'Gimnasio equipado',  'en' => 'Fitness centre',  'icon' => 'fitness_center', 'category' => 'building'],
            ['slug' => 'ascensor',         'es' => 'Ascensor',           'en' => 'Elevator',        'icon' => 'elevator',       'category' => 'building'],
            ['slug' => 'terraza-comun',    'es' => 'Terraza común',      'en' => 'Rooftop terrace', 'icon' => 'deck',           'category' => 'building'],
            ['slug' => 'area-social',      'es' => 'Área social',        'en' => 'Social area',     'icon' => 'celebration',    'category' => 'building'],
            ['slug' => 'jacuzzi',          'es' => 'Jacuzzi',            'en' => 'Jacuzzi',         'icon' => 'hot_tub',        'category' => 'building'],
            ['slug' => 'cancha',           'es' => 'Cancha deportiva',   'en' => 'Sports court',    'icon' => 'sports_tennis',  'category' => 'building'],
            ['slug' => 'gazebo',           'es' => 'Gazebo',             'en' => 'Gazebo',          'icon' => 'cabin',          'category' => 'building'],

            // --- Seguridad y servicios ---
            ['slug' => 'seguridad-24-7',   'es' => 'Seguridad 24/7',     'en' => '24/7 security',   'icon' => 'security',       'category' => 'services'],
            ['slug' => 'planta-electrica', 'es' => 'Planta eléctrica',   'en' => 'Power generator', 'icon' => 'bolt',           'category' => 'services'],
            ['slug' => 'cisterna',         'es' => 'Cisterna',           'en' => 'Water tank',      'icon' => 'water_drop',     'category' => 'services'],
            ['slug' => 'intercom',         'es' => 'Intercom',           'en' => 'Intercom',        'icon' => 'ring_volume',    'category' => 'services'],
            ['slug' => 'pet-friendly',     'es' => 'Pet friendly',       'en' => 'Pet friendly',    'icon' => 'pets',           'category' => 'services'],

            // --- Interior ---
            ['slug' => 'aire-acondicionado', 'es' => 'Aire acondicionado', 'en' => 'Air conditioning', 'icon' => 'ac_unit',    'category' => 'interior'],
            ['slug' => 'walk-in-closet',   'es' => 'Walk-in closet',     'en' => 'Walk-in closet',  'icon' => 'checkroom',      'category' => 'interior'],
            ['slug' => 'cocina-equipada',  'es' => 'Cocina equipada',    'en' => 'Fitted kitchen',  'icon' => 'kitchen',        'category' => 'interior'],
            ['slug' => 'balcon',           'es' => 'Balcón',             'en' => 'Balcony',         'icon' => 'balcony',        'category' => 'interior'],

            // --- Entorno ---
            ['slug' => 'vista-al-mar',     'es' => 'Vista al mar',       'en' => 'Ocean view',      'icon' => 'waves',          'category' => 'location'],
            ['slug' => 'playa-privada',    'es' => 'Playa privada',      'en' => 'Private beach',   'icon' => 'beach_access',   'category' => 'location'],
            ['slug' => 'campo-de-golf',    'es' => 'Campo de golf',      'en' => 'Golf course',     'icon' => 'golf_course',    'category' => 'location'],
        ];
    }

    public function run(): void
    {
        foreach (self::definitions() as $i => $a) {
            Amenity::updateOrCreate(
                ['slug' => $a['slug']],
                [
                    'name' => ['es' => $a['es'], 'en' => $a['en']],
                    'icon' => $a['icon'],
                    'category' => $a['category'],
                    'is_active' => true,
                    'sort_order' => $i,
                ],
            );
        }

        $this->command->info('Amenidades: '.count(self::definitions()).'.');
    }
}
