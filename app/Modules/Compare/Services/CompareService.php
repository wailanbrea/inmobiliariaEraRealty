<?php

namespace App\Modules\Compare\Services;

use App\Modules\Properties\Models\Amenity;
use App\Modules\Properties\Models\Property;
use Illuminate\Support\Collection;

/**
 * Comparador de propiedades.
 *
 * La lista vive en SESION, no solo en localStorage: asi funciona sin
 * JavaScript y sobrevive a un cambio de idioma o a compartir el enlace con
 * ?ids=. El prompt maestro (§19) permite ambas; la sesion es la que no deja
 * fuera a nadie.
 */
class CompareService
{
    /** El diseno muestra 3 columnas + hueco; el prompt admite 3 o 4. */
    public const MAX = 4;

    private const KEY = 'compare';

    /** @return list<int> */
    public function ids(): array
    {
        return array_values(array_unique(session(self::KEY, [])));
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function has(int $propertyId): bool
    {
        return in_array($propertyId, $this->ids(), true);
    }

    public function isFull(): bool
    {
        return $this->count() >= self::MAX;
    }

    /**
     * Anade una propiedad. Devuelve false si ya no caben mas, para que la
     * vista pueda avisar en vez de descartar en silencio.
     */
    public function add(int $propertyId): bool
    {
        if ($this->has($propertyId)) {
            return true;
        }

        if ($this->isFull()) {
            return false;
        }

        session()->push(self::KEY, $propertyId);

        return true;
    }

    public function remove(int $propertyId): void
    {
        session()->put(
            self::KEY,
            array_values(array_filter($this->ids(), fn (int $id) => $id !== $propertyId)),
        );
    }

    public function toggle(int $propertyId): bool
    {
        if ($this->has($propertyId)) {
            $this->remove($propertyId);

            return true;
        }

        return $this->add($propertyId);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    /**
     * Sustituye la lista por la recibida, respetando el limite.
     * Lo usa el enlace compartible ?ids=12,45,78
     *
     * @param  list<int>  $ids
     */
    public function replace(array $ids): void
    {
        $validos = Property::query()
            ->published()
            ->whereIn('id', array_slice($ids, 0, self::MAX))
            ->pluck('id')
            ->all();

        session()->put(self::KEY, $validos);
    }

    /**
     * Propiedades a comparar, en el orden en que se anadieron.
     *
     * @return Collection<int, Property>
     */
    public function properties(): Collection
    {
        $ids = $this->ids();

        if ($ids === []) {
            return collect();
        }

        $propiedades = Property::query()
            ->published()
            ->forListing()
            ->with(['amenities', 'agent'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        // Se limpian de la sesion las que ya no estan publicadas, para que el
        // contador no mienta.
        $encontradas = array_values(array_intersect($ids, $propiedades->keys()->all()));

        if (count($encontradas) !== count($ids)) {
            session()->put(self::KEY, $encontradas);
        }

        return collect($encontradas)->map(fn (int $id) => $propiedades[$id]);
    }

    /**
     * Union de amenidades de las propiedades comparadas, para que la tabla
     * tenga una fila por cada una y se vea quien la tiene y quien no.
     *
     * @param  Collection<int, Property>  $properties
     * @return Collection<int, Amenity>
     */
    public function amenityUnion(Collection $properties): Collection
    {
        return $properties
            ->flatMap(fn (Property $p) => $p->amenities)
            ->unique('id')
            ->sortBy('sort_order')
            ->values();
    }

    /**
     * Marca que filas tienen valores distintos, para el modo
     * "resaltar diferencias".
     *
     * @param  Collection<int, Property>  $properties
     * @return array<string, bool>
     */
    public function differences(Collection $properties): array
    {
        if ($properties->count() < 2) {
            return [];
        }

        $campos = [
            'price' => fn (Property $p) => $p->price,
            'operation' => fn (Property $p) => $p->operation_type->value,
            'type' => fn (Property $p) => $p->property_type_id,
            'bedrooms' => fn (Property $p) => $p->bedrooms,
            'bathrooms' => fn (Property $p) => $p->bathrooms,
            'parking' => fn (Property $p) => $p->parking_spaces,
            'area' => fn (Property $p) => $p->construction_area,
            'land' => fn (Property $p) => $p->land_area,
            'location' => fn (Property $p) => $p->city_id,
            'status' => fn (Property $p) => $p->status->value,
            'year' => fn (Property $p) => $p->year_built,
        ];

        $resultado = [];

        foreach ($campos as $clave => $extraer) {
            $resultado[$clave] = $properties->map($extraer)->unique()->count() > 1;
        }

        return $resultado;
    }
}
