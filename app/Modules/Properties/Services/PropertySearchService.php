<?php

namespace App\Modules\Properties\Services;

use App\Enums\Currency;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Province;
use App\Modules\Locations\Models\Sector;
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
    public const PER_PAGE = 9;

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
                        ->orWhere('address', 'like', "%{$termino}%")
                        ->orWhereHas('translations', function (Builder $t) use ($termino) {
                            $t->where('locale', Locale::current())
                                ->where(function (Builder $x) use ($termino) {
                                    $x->where('title', 'like', "%{$termino}%")
                                        ->orWhere('short_description', 'like', "%{$termino}%");
                                });
                        })
                        ->orWhereHas('province', fn (Builder $province) => $province
                            ->where('name', 'like', "%{$termino}%"))
                        ->orWhereHas('city', fn (Builder $city) => $city
                            ->where('name', 'like', "%{$termino}%"))
                        ->orWhereHas('sector', fn (Builder $sector) => $sector
                            ->where('name', 'like', "%{$termino}%"));
                });
            })

            // --- Clasificacion ---
            ->when($get('operacion'), fn (Builder $q, $v) => $q->where('operation_type', $v))
            ->when($get('tipo'), fn (Builder $q, $v) => $q->whereHas('type', fn ($t) => $t->where('slug', $v)))

            // --- Ubicacion ---
             ->when($get('provincia'), function (Builder $q, $v) {
                 $province = Province::where('slug', $v)->first();

                 $q->where(function (Builder $location) use ($province) {
                     if (! $province) {
                         return $location->whereRaw('1 = 0');
                     }

                      $location->where('province_id', $province->id)
                          ->orWhereHas('city', fn (Builder $city) => $city->where('province_id', $province->id))
                          ->orWhereHas('sector', fn (Builder $sector) => $sector
                              ->whereHas('city', fn (Builder $city) => $city->where('province_id', $province->id)))
                          ->orWhere('address', 'like', "%{$province->name}%")
                          ->orWhereHas('translations', fn (Builder $t) => $t
                              ->where('title', 'like', "%{$province->name}%"));

                      $lugares = City::where('province_id', $province->id)->pluck('name')
                          ->merge(Sector::whereHas('city', fn (Builder $city) => $city
                              ->where('province_id', $province->id))->pluck('name'))
                          ->filter()
                          ->unique();

                      foreach ($lugares as $lugar) {
                          $location->orWhere('address', 'like', "%{$lugar}%")
                              ->orWhereHas('translations', fn (Builder $t) => $t
                                  ->where('title', 'like', "%{$lugar}%"));
                      }
                  });
              })
             ->when($get('ciudad'), function (Builder $q, $v) {
                 $city = City::where('slug', $v)->first();

                 $q->where(function (Builder $location) use ($city, $v) {
                     if (! $city) {
                         return $location->whereHas('city', fn (Builder $related) => $related->where('slug', $v));
                     }

                     $location->where('city_id', $city->id)
                         ->orWhereHas('city', fn (Builder $related) => $related->where('slug', $v))
                         ->orWhereHas('sector', fn (Builder $sector) => $sector->where('city_id', $city->id))
                         ->orWhere('address', 'like', "%{$city->name}%")
                         ->orWhereHas('translations', fn (Builder $t) => $t
                             ->where('title', 'like', "%{$city->name}%"));
                 });
             })
             ->when($get('sector'), function (Builder $q, $v) {
                 $sector = filter_var($v, FILTER_VALIDATE_INT) !== false
                     ? Sector::with('city')->find($v)
                     : Sector::with('city')->where('slug', $v)->first();

                 $q->where(function (Builder $location) use ($sector, $v) {
                     if (! $sector) {
                         return $location->whereHas('sector', fn (Builder $related) => $related->where('slug', $v));
                     }

                      $location->where('sector_id', $sector->id)
                          ->orWhere(function (Builder $legacy) use ($sector) {
                              // Los registros legacy pueden no tener sector_id,
                              // pero solo se incluyen si el nombre del sector
                              // aparece realmente en su ubicación o título.
                              $legacy->whereNull('sector_id')
                                  ->where(function (Builder $text) use ($sector) {
                                      $text->where('address', 'like', "%{$sector->name}%")
                                          ->orWhereHas('translations', fn (Builder $t) => $t
                                              ->where('title', 'like', "%{$sector->name}%"));
                                  });
                          });
                 });
             })

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
            'estado', 'orden', 'sector',
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
     * Cuantos filtros "reales" estan activos. Excluye el orden y la moneda,
     * que se envian siempre con el formulario y no son una eleccion del usuario.
     *
     * @param  array<string, mixed>  $filters
     */
    public function activeFilterCount(array $filters): int
    {
        return collect($filters)->except(['orden', 'moneda'])->count();
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
