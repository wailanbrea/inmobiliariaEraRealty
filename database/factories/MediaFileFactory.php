<?php

namespace Database\Factories;

use App\Modules\Media\Models\MediaFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MediaFile>
 */
class MediaFileFactory extends Factory
{
    protected $model = MediaFile::class;

    public function definition(): array
    {
        $nombre = Str::random(16);
        $carpeta = 'media/2026/08';

        return [
            'disk' => 'public',
            'path' => "{$carpeta}/{$nombre}.jpg",
            'webp_path' => "{$carpeta}/{$nombre}.webp",
            'thumbnail_path' => "{$carpeta}/{$nombre}_thumb.jpg",
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(50_000, 800_000),
            'width' => 1200,
            'height' => 800,
            'context' => 'general',
            'folder' => $carpeta,
        ];
    }
}
