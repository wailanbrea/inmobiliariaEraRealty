<?php

namespace Database\Seeders;

use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Province;
use App\Modules\Locations\Models\Sector;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Las 32 provincias de Republica Dominicana, con ciudades y sectores para
 * las zonas que aparecen en el diseno de Stitch (Santo Domingo, Santiago,
 * Punta Cana, Las Terrenas).
 *
 * Los toponimos NO se traducen. Ver docs/15_I18N.md seccion 1.
 */
class LocationSeeder extends Seeder
{
    /** @return list<string> */
    public static function provinces(): array
    {
        return [
            'Distrito Nacional', 'Santo Domingo', 'Santiago', 'La Altagracia',
            'La Romana', 'Puerto Plata', 'Samaná', 'San Pedro de Macorís',
            'La Vega', 'Espaillat', 'Duarte', 'Peravia', 'San Cristóbal',
            'Azua', 'Barahona', 'Bahoruco', 'Dajabón', 'El Seibo',
            'Elías Piña', 'Hato Mayor', 'Hermanas Mirabal', 'Independencia',
            'Monseñor Nouel', 'Monte Cristi', 'Monte Plata', 'Pedernales',
            'Sánchez Ramírez', 'San José de Ocoa', 'San Juan',
            'Santiago Rodríguez', 'Valverde', 'María Trinidad Sánchez',
        ];
    }

    /**
     * provincia => [ciudad => [sectores]]
     *
     * @return array<string, array<string, list<string>>>
     */
    public static function cities(): array
    {
        return [
            'Distrito Nacional' => [
                'Santo Domingo' => [
                    'Piantini', 'Naco', 'Bella Vista', 'Evaristo Morales',
                    'Serrallés', 'Gazcue', 'Ensanche Julieta', 'Los Cacicazgos',
                    'Mirador Sur', 'Zona Colonial', 'Arroyo Hondo', 'Paraíso',
                ],
            ],
            'Santo Domingo' => [
                'Santo Domingo Este' => ['Ensanche Ozama', 'Alma Rosa', 'San Isidro'],
                'Santo Domingo Norte' => ['Villa Mella', 'Sabana Perdida'],
                'Santo Domingo Oeste' => ['Herrera', 'Manoguayabo'],
                'Boca Chica' => ['Boca Chica', 'Andrés'],
            ],
            'Santiago' => [
                'Santiago de los Caballeros' => [
                    'Los Cerros', 'Cerros de Gurabo', 'Jardines Metropolitanos',
                    'La Trinitaria', 'Villa Olga', 'El Embrujo',
                ],
                'Jánico' => [],
            ],
            'La Altagracia' => [
                'Punta Cana' => [
                    'Cap Cana', 'Bávaro', 'Cortecito', 'Punta Cana Village',
                    'Uvero Alto', 'Macao',
                ],
                'Higüey' => [],
            ],
            'La Romana' => [
                'La Romana' => ['Casa de Campo', 'Bayahíbe'],
            ],
            'Puerto Plata' => [
                'Puerto Plata' => ['Playa Dorada', 'Costambar', 'Cofresí'],
                'Sosúa' => ['Sosúa', 'Cabarete'],
            ],
            'Samaná' => [
                'Las Terrenas' => ['Playa Bonita', 'Punta Popy', 'El Portillo'],
                'Samaná' => ['Cayo Levantado'],
                'Las Galeras' => [],
            ],
            'San Pedro de Macorís' => [
                'San Pedro de Macorís' => ['Juan Dolio', 'Guayacanes'],
            ],
            'La Vega' => [
                'Jarabacoa' => ['Jarabacoa'],
                'Constanza' => [],
            ],
        ];
    }

    public function run(): void
    {
        $provincias = [];

        foreach (self::provinces() as $i => $nombre) {
            $provincias[$nombre] = Province::firstOrCreate(
                ['slug' => Str::slug($nombre)],
                ['name' => $nombre, 'is_active' => true, 'sort_order' => $i],
            );
        }

        $ciudades = 0;
        $sectores = 0;

        foreach (self::cities() as $provincia => $listaCiudades) {
            $p = $provincias[$provincia] ?? null;

            if (! $p) {
                continue;
            }

            $j = 0;

            foreach ($listaCiudades as $ciudadNombre => $listaSectores) {
                $ciudad = City::firstOrCreate(
                    [
                        'province_id' => $p->id,
                        'slug' => Str::slug($ciudadNombre),
                    ],
                    ['name' => $ciudadNombre, 'is_active' => true, 'sort_order' => $j++],
                );

                $ciudades++;

                foreach ($listaSectores as $k => $sectorNombre) {
                    Sector::firstOrCreate(
                        [
                            'city_id' => $ciudad->id,
                            'slug' => Str::slug($sectorNombre),
                        ],
                        ['name' => $sectorNombre, 'is_active' => true, 'sort_order' => $k],
                    );

                    $sectores++;
                }
            }
        }

        $this->command->info(
            'Ubicaciones: '.count(self::provinces())." provincias, {$ciudades} ciudades, {$sectores} sectores."
        );
    }
}
