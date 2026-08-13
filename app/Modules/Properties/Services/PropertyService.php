<?php

namespace App\Modules\Properties\Services;

use App\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\PropertyTranslation;
use App\Support\Locale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PropertyService
{
    /**
     * Crea una propiedad con sus traducciones.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, string|null>>  $translations  [locale => campos]
     * @param  list<int>  $amenityIds
     */
    public function create(array $data, array $translations, array $amenityIds = []): Property
    {
        return DB::transaction(function () use ($data, $translations, $amenityIds) {
            $data['reference_code'] ??= $this->nextReferenceCode();
            $data['created_by_user_id'] ??= auth()->id();

            $property = Property::create($data);

            $this->syncTranslations($property, $translations);
            $property->amenities()->sync($amenityIds);

            return $property->fresh(['translations', 'amenities']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, string|null>>  $translations
     * @param  list<int>|null  $amenityIds  null = no tocar las amenidades
     */
    public function update(
        Property $property,
        array $data,
        array $translations = [],
        ?array $amenityIds = null,
    ): Property {
        return DB::transaction(function () use ($property, $data, $translations, $amenityIds) {
            $data['updated_by_user_id'] = auth()->id();

            $property->update($data);

            if ($translations !== []) {
                $this->syncTranslations($property, $translations);
            }

            if ($amenityIds !== null) {
                $property->amenities()->sync($amenityIds);
            }

            return $property->fresh(['translations', 'amenities']);
        });
    }

    /**
     * Genera el siguiente codigo de referencia: ERA-1001, ERA-1002...
     *
     * Se busca el maximo existente en vez de contar filas: si se borra una
     * propiedad, contar generaria un codigo repetido.
     */
    public function nextReferenceCode(string $prefix = 'ERA'): string
    {
        $ultimo = Property::withTrashed()
            ->where('reference_code', 'like', "{$prefix}-%")
            ->selectRaw('MAX(CAST(SUBSTRING(reference_code, ?) AS UNSIGNED)) as max_num', [strlen($prefix) + 2])
            ->value('max_num');

        $siguiente = max((int) $ultimo, 1000) + 1;

        return "{$prefix}-{$siguiente}";
    }

    /**
     * Crea o actualiza las traducciones, generando el slug de cada idioma
     * desde su propio titulo.
     *
     * @param  array<string, array<string, string|null>>  $translations
     */
    public function syncTranslations(Property $property, array $translations): void
    {
        foreach ($translations as $locale => $fields) {
            if (! Locale::isSupported($locale)) {
                continue;
            }

            // Sin titulo no hay traduccion: se ignora ese idioma en lugar de
            // crear una fila vacia que luego saldria como ficha en blanco.
            if (blank($fields['title'] ?? null)) {
                continue;
            }

            $existente = $property->translations()->where('locale', $locale)->first();

            $slug = filled($fields['slug'] ?? null)
                ? $this->uniqueSlug($fields['slug'], $locale, $existente?->id)
                : ($existente?->slug ?? $this->uniqueSlug($fields['title'], $locale));

            $property->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => $fields['title'],
                    'slug' => $slug,
                    'short_description' => $fields['short_description'] ?? null,
                    'description' => $fields['description'] ?? null,
                    'meta_title' => $fields['meta_title'] ?? null,
                    'meta_description' => $fields['meta_description'] ?? null,
                ],
            );
        }
    }

    /**
     * Slug unico DENTRO de su idioma: /propiedades/villa-cap-cana y
     * /en/properties/villa-cap-cana pueden coexistir sin chocar.
     */
    public function uniqueSlug(string $value, string $locale, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $i = 2;

        while (
            PropertyTranslation::query()
                ->where('locale', $locale)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    // ------------------------------------------------------------------
    // Transiciones de estado
    // ------------------------------------------------------------------

    /**
     * Publicar: fija published_at si aun no lo tenia.
     * No se pisa una fecha existente, para no falsear la antiguedad de una
     * ficha que solo se estaba pausando.
     */
    public function publish(Property $property): Property
    {
        $property->update([
            'status' => PropertyStatus::Available,
            'published_at' => $property->published_at ?? now(),
            'updated_by_user_id' => auth()->id(),
        ]);

        return $property;
    }

    public function pause(Property $property): Property
    {
        $property->update([
            'status' => PropertyStatus::Paused,
            'updated_by_user_id' => auth()->id(),
        ]);

        return $property;
    }

    public function changeStatus(Property $property, PropertyStatus $status): Property
    {
        $data = [
            'status' => $status,
            'updated_by_user_id' => auth()->id(),
        ];

        // Al publicar por primera vez desde borrador hay que sellar la fecha,
        // o la propiedad nunca apareceria en el listado publico.
        if ($status->isPublic() && $property->published_at === null) {
            $data['published_at'] = now();
        }

        $property->update($data);

        return $property;
    }

    /**
     * ¿Le falta alguna traduccion? Lo usa el listado del panel para avisar.
     *
     * @return list<string>
     */
    public function missingTranslations(Property $property): array
    {
        $existentes = $property->translations->pluck('locale')->all();

        return array_values(array_diff(Locale::codes(), $existentes));
    }
}
