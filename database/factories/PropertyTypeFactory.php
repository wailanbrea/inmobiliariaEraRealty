<?php

namespace Database\Factories;

use App\Modules\PropertyTypes\Models\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyType>
 */
class PropertyTypeFactory extends Factory
{
    protected $model = PropertyType::class;

    public function definition(): array
    {
        $nombre = fake()->unique()->word();

        return [
            'name' => ['es' => ucfirst($nombre), 'en' => ucfirst($nombre)],
            'icon' => 'home',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
