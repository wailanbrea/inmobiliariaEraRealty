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
            ['slug' => 'linea-blanca',     'es' => 'Línea blanca',       'en' => 'Home appliances', 'icon' => 'home_repair_service', 'category' => 'interior'],
            ['slug' => 'balcon',           'es' => 'Balcón',             'en' => 'Balcony',         'icon' => 'balcony',        'category' => 'interior'],

            // --- Entorno ---
            ['slug' => 'vista-al-mar',     'es' => 'Vista al mar',       'en' => 'Ocean view',      'icon' => 'waves',          'category' => 'location'],
            ['slug' => 'playa-privada',    'es' => 'Playa privada',      'en' => 'Private beach',   'icon' => 'beach_access',   'category' => 'location'],
            ['slug' => 'campo-de-golf',    'es' => 'Campo de golf',      'en' => 'Golf course',     'icon' => 'golf_course',    'category' => 'location'],
            // --- Acabados y equipamiento ---
            ['slug' => 'galeria',                  'es' => 'Galería',                 'en' => 'Gallery/veranda',        'icon' => 'deck',              'category' => 'interior'],
            ['slug' => 'patio',                    'es' => 'Patio',                   'en' => 'Patio',                  'icon' => 'yard',              'category' => 'interior'],
            ['slug' => 'pisos-porcelanato',        'es' => 'Pisos en porcelanato',     'en' => 'Porcelain flooring',      'icon' => 'grid_view',         'category' => 'interior'],
            ['slug' => 'pisos-ceramica',           'es' => 'Pisos en cerámica',        'en' => 'Ceramic flooring',        'icon' => 'grid_on',           'category' => 'interior'],
            ['slug' => 'pisos-marmol',             'es' => 'Pisos en mármol',          'en' => 'Marble flooring',         'icon' => 'texture',            'category' => 'interior'],
            ['slug' => 'pisos-granito',            'es' => 'Pisos en granito',         'en' => 'Granite flooring',        'icon' => 'view_quilt',        'category' => 'interior'],
            ['slug' => 'cocina-modular',           'es' => 'Cocina modular',           'en' => 'Modular kitchen',         'icon' => 'kitchen',            'category' => 'interior'],
            ['slug' => 'cocina-fria',              'es' => 'Cocina fría',              'en' => 'Cold kitchen',            'icon' => 'kitchen',            'category' => 'interior'],
            ['slug' => 'tope-granito-natural',     'es' => 'Tope de granito natural',  'en' => 'Natural granite counter', 'icon' => 'countertops',       'category' => 'interior'],
            ['slug' => 'tope-marmol',              'es' => 'Tope de mármol',           'en' => 'Marble counter',          'icon' => 'countertops',       'category' => 'interior'],
            ['slug' => 'tope-marmolite',           'es' => 'Tope de marmolite',        'en' => 'Marmolite counter',       'icon' => 'countertops',       'category' => 'interior'],
            ['slug' => 'madera-preciosa',          'es' => 'Madera preciosa',          'en' => 'Fine wood',               'icon' => 'forest',             'category' => 'interior'],
            ['slug' => 'bano-visitas',             'es' => 'Baño de visitas',          'en' => 'Guest bathroom',          'icon' => 'bathroom',           'category' => 'interior'],
            ['slug' => 'calentador',               'es' => 'Calentador',               'en' => 'Water heater',             'icon' => 'water_heater',       'category' => 'services'],
            ['slug' => 'camara-seguridad',         'es' => 'Cámara de seguridad',      'en' => 'Security camera',          'icon' => 'videocam',           'category' => 'services'],
            ['slug' => 'sistema-contra-incendios', 'es' => 'Sistema contra incendios', 'en' => 'Fire protection system',  'icon' => 'fire_extinguisher', 'category' => 'services'],
            ['slug' => 'area-juegos-ninos',         'es' => 'Área de juegos para niños', 'en' => 'Children\'s play area',      'icon' => 'toys',              'category' => 'building'],
            ['slug' => 'cuarto-servicio',            'es' => 'Cuarto de servicio',        'en' => 'Service room',              'icon' => 'room_service',       'category' => 'interior'],
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
