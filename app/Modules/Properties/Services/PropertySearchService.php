<?php

namespace App\Modules\Properties\Services;

use App\Enums\Currency;
use App\Modules\Properties\Models\Property;
use App\Support\Locale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Buscador publico de propiedades.
 *
 * Los filtros llegan por query string para que un listado filtrado se pueda
 * compartir e indexar. Ver docs/04_PUBLIC_PAGES.md seccion 2.
 */
class PropertySearchService
{
    public const PER_PAGE = 12;

    /** Ordenaciones admitidas. Cualquier otra cae en 'recent'. */
    public const SORTS = ['recent', 'price_asc', 'price_desc', 'area_desc', 'views'];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return $this->query($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters): Builder
    {
        $get = fn (string $key, mixed $default = null) => $filters[$key] ?? $default;

        return Property::query()
            ->published()
            ->forListing()

            // --- Texto libre ---
            ->when($get('q'), function (Builder $q, string $termino) {
                $termino = trim($termino);

                $q->where(function (Builder $sub) use ($termino) {
                    $sub->where('reference_code', 'like', "%{$termino}%")
                        ->orWhereHas('translations', function (Builder $t) use ($termino) {
                            $t->where('locale', Locale::current())
                                ->where(function (Builder $x) use ($termino) {
                                    $x->where('title', 'like', "%{$termino}%")
                                        ->orWhere('short_description', 'like', "%{$termino}%");
                                });
                        });
                });
            })

            // --- Clasificacion ---
            ->when($get('operacion'), fn (Builder $q, $v) => $q->where('operation_type', $v))
            ->when($get('tipo'), fn (Builder $q, $v) => $q->whereHas('type', fn ($t) => $t->where('slug', $v)))

            // --- Ubicacion ---
            ->when($get('provincia'), fn (Builder $q, $v) => $q->whereHas('province', fn ($p) => $p->where('slug', $v)))
            ->when($get('ciudad'), fn (Builder $q, $v) => $q->whereHas('city', fn ($c) => $c->where('slug', $v)))
            ->when($get('sector'), fn (Builder $q, $v) => $q->whereHas('sector', fn ($s) => $s->where('slug', $v)))

            // --- Precio ---
            // Se compara en la moneda pedida, convirtiendo el limite en vez de
            // los precios: asi el indice de (currency, price) sigue sirviendo.
            ->when($get('precio_min'), fn (Builder $q, $v) => $this->filterPrice($q, (float) $v, '>=', $get('moneda')))
            ->when($get('precio_max'), fn (Builder $q, $v) => $this->filterPrice($q, (float) $v, '<=', $get('moneda')))

            // --- Caracteristicas ---
            ->when($get('habitaciones'), fn (Builder $q, $v) => $q->where('bedrooms', '>=', (int) $v))
            ->when($get('banos'), fn (Builder $q, $v) => $q->where('bathrooms', '>=', (float) $v))
            ->when($get('parqueos'), fn (Builder $q, $v) => $q->where('parking_spaces', '>=', (int) $v))
            ->when($get('area_min'), fn (Builder $q, $v) => $q->where('construction_area', '>=', (float) $v))
            ->when($get('area_max'), fn (Builder $q, $v) => $q->where('construction_area', '<=', (float) $v))

            // --- Amenidades: deben cumplirse TODAS las marcadas ---
            ->when($get('amenidades'), function (Builder $q, $slugs) {
                foreach ((array) $slugs as $slug) {
                    $q->whereHas('amenities', fn ($a) => $a->where('slug', $slug));
                }
            })

            // --- Marcas ---
            ->when($get('destacadas'), fn (Builder $q) => $q->where('is_featured', true))
            ->when($get('inversion'), fn (Builder $q) => $q->where('is_investment', true))
            ->when($get('proyecto'), fn (Builder $q) => $q->where('is_project', true))
            ->when($get('amueblada'), fn (Builder $q) => $q->where('is_furnished', true))

            ->when($get('estado'), fn (Builder $q, $v) => $q->where('status', $v))

            ->tap(fn (Builder $q) => $this->applySort($q, (string) $get('orden', 'recent')));
    }

    /**
     * Convierte el limite a la moneda de cada propiedad en vez de convertir
     * los precios guardados.
     */
    private function filterPrice(Builder $query, float $amount, string $operator, ?string $currency): Builder
    {
        $moneda = Currency::tryFrom((string) $currency)
            ?? Currency::tryFrom((string) setting('currency_default', 'USD'))
            ?? Currency::USD;

        $otra = $moneda === Currency::USD ? Currency::DOP : Currency::USD;
        $convertido = $moneda->convertTo($otra, $amount);

        return $query->where(function (Builder $q) use ($moneda, $otra, $amount, $convertido, $operator) {
            $q->where(fn (Builder $x) => $x->where('currency', $moneda->value)->where('price', $operator, $amount));

            // Si no hay tasa configurada no se inventa una conversion: las
            // propiedades en la otra moneda simplemente no entran en el rango.
            if ($convertido !== null) {
                $q->orWhere(fn (Builder $x) => $x->where('currency', $otra->value)->where('price', $operator, $convertido));
            }
        });
    }

    private function applySort(Builder $query, string $sort): Builder
    {
        return match (in_array($sort, self::SORTS, true) ? $sort : 'recent') {
            'price_asc' => $query->orderByRaw('price IS NULL, price ASC'),
            'price_desc' => $query->orderByRaw('price IS NULL, price DESC'),
            'area_desc' => $query->orderByRaw('construction_area IS NULL, construction_area DESC'),
            'views' => $query->orderByDesc('views_count'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }

    /**
     * Extrae de la peticion solo los filtros conocidos, ya limpios.
     *
     * @return array<string, mixed>
     */
    public function filtersFromRequest(Request $request): array
    {
        $claves = [
            'q', 'operacion', 'tipo', 'provincia', 'ciudad', 'sector',
            'precio_min', 'precio_max', 'moneda',
            'habitaciones', 'banos', 'parqueos', 'area_min', 'area_max',
            'amenidades', 'destacadas', 'inversion', 'proyecto', 'amueblada',
            'estado', 'orden',
        ];

        return collect($request->only($claves))
            ->reject(fn ($valor) => $valor === null || $valor === '' || $valor === [])
            ->all();
    }

    /**
     * ¿Hay algun filtro activo? Lo usa la vista para decidir si el listado es
     * indexable o debe apuntar su canonical al listado limpio.
     *
     * @param  array<string, mixed>  $filters
     */
    public function hasActiveFilters(array $filters): bool
    {
        return collect($filters)->except('orden')->isNotEmpty();
    }

    /**
     * Propiedades similares: mismo tipo y ciudad, precio parecido.
     *
     * @return Collection<int, Property>
     */
    public function similar(Property $property, int $limit = 3): Collection
    {
        return Property::query()
            ->published()
            ->forListing()
            ->whereKeyNot($property->id)
            ->where('property_type_id', $property->property_type_id)
            ->when($property->city_id, fn (Builder $q) => $q->where('city_id', $property->city_id))
            ->when($property->price, function (Builder $q) use ($property) {
                // Rango del 40 % arriba y abajo: parecido, no identico.
                $q->whereBetween('price', [$property->price * 0.6, $property->price * 1.4]);
            })
            ->limit($limit)
            ->get();
    }
}
