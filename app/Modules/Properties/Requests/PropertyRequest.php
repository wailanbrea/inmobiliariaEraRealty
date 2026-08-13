<?php

namespace App\Modules\Properties\Requests;

use App\Enums\Currency;
use App\Enums\OperationType;
use App\Enums\PricePeriod;
use App\Enums\PropertyStatus;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Sector;
use App\Modules\Properties\Models\Property;
use App\Support\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $property = $this->route('property');

        return $property
            ? $this->user()->can('update', $property)
            : $this->user()->can('create', Property::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'operation_type' => ['required', Rule::enum(OperationType::class)],
            'property_type_id' => ['required', 'exists:property_types,id'],
            'status' => ['required', Rule::enum(PropertyStatus::class)],

            // --- Precio ---
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'price_period' => ['nullable', Rule::enum(PricePeriod::class)],
            'maintenance_fee' => ['nullable', 'numeric', 'min:0', 'max:9999999'],

            // --- Ubicacion ---
            'province_id' => ['nullable', 'exists:provinces,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'sector_id' => ['nullable', 'exists:sectors,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'show_exact_location' => ['nullable', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            // --- Caracteristicas ---
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'parking_spaces' => ['nullable', 'integer', 'min:0', 'max:50'],
            'construction_area' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'land_area' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'floor_level' => ['nullable', 'string', 'max:20'],
            'year_built' => ['nullable', 'integer', 'min:1800', 'max:'.(date('Y') + 10)],
            'is_furnished' => ['nullable', 'boolean'],

            // --- Clasificacion ---
            'is_featured' => ['nullable', 'boolean'],
            'is_investment' => ['nullable', 'boolean'],
            'is_project' => ['nullable', 'boolean'],

            'video_url' => ['nullable', 'url', 'max:255'],
            'virtual_tour_url' => ['nullable', 'url', 'max:255'],

            'agent_id' => ['nullable', 'exists:agents,id'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer', 'exists:amenities,id'],

            // --- Privado ---
            'owner_name' => ['nullable', 'string', 'max:150'],
            'owner_phone' => ['nullable', 'string', 'max:30'],
            'owner_email' => ['nullable', 'email', 'max:190'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],

            'published_at' => ['nullable', 'date'],
        ];

        // --- Traducciones ---
        // El idioma por defecto es obligatorio: sin el no hay ficha.
        // El otro es opcional y cae al espanol si falta.
        foreach (Locale::codes() as $code) {
            $obligatorio = $code === Locale::default();

            $rules["translations.{$code}.title"] = [
                $obligatorio ? 'required' : 'nullable', 'string', 'max:200',
            ];
            $rules["translations.{$code}.slug"] = ['nullable', 'string', 'max:220', 'regex:/^[a-z0-9\-]+$/'];
            $rules["translations.{$code}.short_description"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$code}.description"] = ['nullable', 'string', 'max:20000'];
            $rules["translations.{$code}.meta_title"] = ['nullable', 'string', 'max:200'];
            $rules["translations.{$code}.meta_description"] = ['nullable', 'string', 'max:300'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {

            // La jerarquia de ubicacion tiene que ser coherente: no vale una
            // ciudad de otra provincia.
            if ($this->filled(['province_id', 'city_id'])) {
                $ciudadValida = City::where('id', $this->input('city_id'))
                    ->where('province_id', $this->input('province_id'))
                    ->exists();

                if (! $ciudadValida) {
                    $validator->errors()->add('city_id', 'La ciudad no pertenece a la provincia elegida.');
                }
            }

            if ($this->filled(['city_id', 'sector_id'])) {
                $sectorValido = Sector::where('id', $this->input('sector_id'))
                    ->where('city_id', $this->input('city_id'))
                    ->exists();

                if (! $sectorValido) {
                    $validator->errors()->add('sector_id', 'El sector no pertenece a la ciudad elegida.');
                }
            }

            // Mostrar la ubicacion exacta sin coordenadas deja el mapa vacio.
            if ($this->boolean('show_exact_location')
                && ! $this->filled(['latitude', 'longitude'])) {
                $validator->errors()->add(
                    'latitude',
                    'Para mostrar la ubicación exacta hacen falta las coordenadas.'
                );
            }

            // Publicar sin precio ni "a consultar" explicito suele ser un
            // descuido; se avisa solo si ademas esta disponible.
            $status = $this->input('status');

            if ($status === PropertyStatus::Available->value && $this->filled('price')
                && (float) $this->input('price') <= 0) {
                $validator->errors()->add('price', 'Deja el precio vacío para mostrar "a consultar".');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        // Un alquiler sin periodo se queda sin el "/mes" del diseno.
        $operation = OperationType::tryFrom((string) $this->input('operation_type'));

        if ($operation?->hasPeriod() && ! $this->filled('price_period')) {
            $this->merge(['price_period' => $operation->defaultPeriod()?->value]);
        }

        if ($operation && ! $operation->hasPeriod()) {
            $this->merge(['price_period' => null]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'translations.es.title.required' => 'El título en español es obligatorio.',
            'translations.*.slug.regex' => 'El slug solo admite minúsculas, números y guiones.',
            'property_type_id.required' => 'Elige el tipo de propiedad.',
            'operation_type.required' => 'Elige la operación.',
        ];
    }

    /**
     * Datos de la propiedad, sin traducciones ni amenidades.
     *
     * @return array<string, mixed>
     */
    public function propertyData(): array
    {
        $data = $this->safe()->except(['translations', 'amenities']);

        foreach (['show_exact_location', 'is_furnished', 'is_featured', 'is_investment', 'is_project'] as $flag) {
            $data[$flag] = $this->boolean($flag);
        }

        return $data;
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    public function translationsData(): array
    {
        return $this->input('translations', []);
    }

    /**
     * @return list<int>
     */
    public function amenityIds(): array
    {
        return array_map('intval', $this->input('amenities', []));
    }
}
