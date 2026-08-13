<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\OperationType;
use App\Enums\PropertyStatus;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Province;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyTypes\Models\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        static $secuencia = 0;
        $secuencia++;

        return [
            'reference_code' => 'ERA-'.(9000 + $secuencia),
            'operation_type' => OperationType::Sale,
            'property_type_id' => PropertyType::factory(),
            'status' => PropertyStatus::Draft,
            'price' => fake()->numberBetween(80, 1500) * 1000,
            'currency' => Currency::USD,
            'bedrooms' => fake()->numberBetween(1, 5),
            'bathrooms' => fake()->randomElement([1, 1.5, 2, 2.5, 3, 3.5]),
            'parking_spaces' => fake()->numberBetween(0, 3),
            'construction_area' => fake()->numberBetween(60, 600),
            'show_exact_location' => false,
        ];
    }

    /** Con traducciones en ambos idiomas. */
    public function translated(?string $titleEs = null, ?string $titleEn = null): static
    {
        return $this->afterCreating(function (Property $property) use ($titleEs, $titleEn) {
            $es = $titleEs ?? fake()->unique()->sentence(4);
            $en = $titleEn ?? fake()->unique()->sentence(4);

            app(PropertyService::class)
                ->syncTranslations($property, [
                    'es' => ['title' => $es, 'description' => fake()->paragraph()],
                    'en' => ['title' => $en, 'description' => fake()->paragraph()],
                ]);
        });
    }

    /** Solo en espanol: sirve para probar el respaldo de traduccion. */
    public function spanishOnly(?string $title = null): static
    {
        return $this->afterCreating(function (Property $property) use ($title) {
            app(PropertyService::class)
                ->syncTranslations($property, [
                    'es' => ['title' => $title ?? fake()->unique()->sentence(4)],
                ]);
        });
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PropertyStatus::Available,
            'published_at' => now()->subDay(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => PropertyStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function investment(): static
    {
        return $this->state(fn () => ['is_investment' => true]);
    }

    public function inSantoDomingo(): static
    {
        return $this->state(function () {
            $provincia = Province::firstOrCreate(
                ['slug' => 'distrito-nacional'],
                ['name' => 'Distrito Nacional'],
            );

            $ciudad = City::firstOrCreate(
                ['province_id' => $provincia->id, 'slug' => 'santo-domingo'],
                ['name' => 'Santo Domingo'],
            );

            return [
                'province_id' => $provincia->id,
                'city_id' => $ciudad->id,
            ];
        });
    }
}
