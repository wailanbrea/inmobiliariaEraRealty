<?php

namespace Database\Factories;

use App\Modules\PropertyImages\Models\PropertyImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PropertyImage>
 */
class PropertyImageFactory extends Factory
{
    protected $model = PropertyImage::class;

    public function definition(): array
    {
        $nombre = Str::random(16);

        return [
            'path' => "properties/1/original/{$nombre}.jpg",
            'webp_path' => "properties/1/webp/{$nombre}.webp",
            'thumbnail_path' => "properties/1/thumb/{$nombre}.jpg",
            'original_name' => fake()->word().'.jpg',
            'sort_order' => 0,
            'is_main' => false,
            'width' => 1200,
            'height' => 900,
            'size' => fake()->numberBetween(100_000, 500_000),
            'mime_type' => 'image/jpeg',
        ];
    }

    public function main(): static
    {
        return $this->state(fn () => ['is_main' => true]);
    }
}
