@extends('layouts.public')

@section('title', __('properties.index.title') . ' · ' . setting('site_name'))
@section('description', __('properties.index.description'))

@push('head')
    {{-- Un listado filtrado apunta su canonical al listado limpio: si no,
         Google indexaria miles de URL casi duplicadas.
         Ver docs/07_SEO.md sección 4. --}}
    @if ($hasFilters)
        <meta name="robots" content="noindex, follow">
    @endif
@endpush

@section('content')

{{-- Hero interno con migas, como en propiedades_era_realty_rd --}}
<section class="bg-primary-container py-xl">
    <div class="mx-auto max-w-container-max px-margin-mobile md:px-gutter">
        <nav aria-label="breadcrumb" class="mb-sm text-caption text-on-primary-container">
            <a href="{{ lroute('home') }}" class="transition-opacity hover:opacity-80">
                {{ __('common.nav.home') }}
            </a>
            <span class="mx-1">/</span>
            <span class="text-on-secondary-container">{{ __('common.nav.properties') }}</span>
        </nav>

        <h1 class="font-display text-display-lg-mobile text-on-primary md:text-display-lg">
            {{ __('properties.index.title') }}
        </h1>
    </div>
</section>

<div class="mx-auto max-w-container-max px-margin-mobile py-lg md:px-gutter">
    <div class="flex flex-col gap-lg">

        {{-- ===================== FILTROS ===================== --}}
        <section aria-labelledby="property-filters-title">
            <div class="mb-sm flex items-center gap-xs">
                <span class="material-symbols-outlined text-secondary">tune</span>
                <h2 id="property-filters-title" class="font-heading text-title-lg text-on-surface">
                    {{ __('properties.filters.title') }}
                </h2>
            </div>

            <form method="GET" action="{{ lroute('properties.index') }}"
                  data-property-filter-form
                  x-data="{ mostrarFiltros: false }"
                  @submit="mostrarFiltros = false"
                  data-location-cascade
                  class="space-y-sm rounded-xl border border-outline-variant/40
                         bg-surface-container-lowest p-sm ambient-shadow md:p-md">

                <div class="grid w-full items-end gap-sm md:grid-cols-3 lg:grid-cols-6 xl:grid-cols-6">
                    {{-- Búsqueda --}}
                    <div class="md:col-span-2 lg:col-span-1 xl:col-span-1">
                    <label for="f-q" class="mb-base block text-caption font-medium text-on-surface-variant">
                        {{ __('properties.filters.search') }}
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-xs top-1/2 -translate-y-1/2
                                     text-[20px] text-on-surface-variant">search</span>
                        <input id="f-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                               placeholder="{{ __('properties.filters.search_placeholder') }}"
                               title="{{ __('properties.filters.search_help') }}"
                               class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                      py-xs pl-lg pr-sm text-body-md text-on-surface
                                      focus:border-secondary focus:ring-1 focus:ring-secondary">
                 </div>

                 </div>

                @php
                    $selectClase = 'w-full rounded-lg border border-outline-variant bg-surface-container-low
                                    px-sm py-xs text-body-md text-on-surface
                                    focus:border-secondary focus:ring-1 focus:ring-secondary';
                @endphp

{{-- Operación --}}
                    <div>
                        <label for="f-operacion" class="mb-base block text-caption font-medium text-on-surface-variant">
                            {{ __('home.search.operation') }}
                        </label>
                        <select id="f-operacion" name="operacion" class="{{ $selectClase }}">
                            <option value="">{{ __('home.search.any') }}</option>
                            @foreach (\App\Enums\OperationType::cases() as $operacion)
                                <option value="{{ $operacion->value }}"
                                        @selected(($filters['operacion'] ?? null) === $operacion->value)>
                                    {{ $operacion->label() }}
                                </option>
                            @endforeach
                         </select>
                    </div>

                    {{-- Tipo --}}
                    <div>
                        <label for="f-tipo" class="mb-base block text-caption font-medium text-on-surface-variant">
                            {{ __('home.search.type') }}
                        </label>
                        <select id="f-tipo" name="tipo" class="{{ $selectClase }}">
                            <option value="">{{ __('home.search.any') }}</option>
                            @foreach ($types as $tipo)
                                <option value="{{ $tipo->slug }}" @selected(($filters['tipo'] ?? null) === $tipo->slug)>
                                    {{ $tipo->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Ubicación --}}
                    <div>
                    <label for="f-provincia" class="mb-base block text-caption font-medium text-on-surface-variant">
                         {{ __('properties.filters.province') }}
                    </label>
                    <select id="f-provincia" name="provincia" class="{{ $selectClase }}">
                        <option value="">{{ __('home.search.anywhere') }}</option>
                        @foreach ($provinces as $provincia)
                            <option value="{{ $provincia->slug }}"
                                    @selected(($filters['provincia'] ?? null) === $provincia->slug)>
                                {{ $provincia->name }}
                            </option>
                        @endforeach
                     </select>
                    </div>

                    {{-- Zona --}}
                    <div>
                     <label for="f-zona" class="mb-base block text-caption font-medium text-on-surface-variant">
                         {{ __('properties.filters.zona') }}
                     </label>
                     <select id="f-zona" name="ciudad" class="{{ $selectClase }}">
                         <option value="">{{ __('home.search.anywhere') }}</option>
                         @foreach ($cities as $ciudad)
                             <option value="{{ $ciudad->slug }}"
                                     data-province="{{ $ciudad->province?->slug }}"
                                     @selected(($filters['ciudad'] ?? null) === $ciudad->slug)>
                                 {{ $ciudad->name }}{{ $ciudad->province ? ', '.$ciudad->province->name : '' }}
                             </option>
                         @endforeach
                     </select>
                    </div>

                    {{-- Sector --}}
                    <div>
                     <label for="f-sector" class="mb-base block text-caption font-medium text-on-surface-variant">
                         {{ __('properties.filters.sector') }}
                     </label>
                     <select id="f-sector" name="sector" class="{{ $selectClase }}">
                         <option value="">{{ __('home.search.anywhere') }}</option>
                         @foreach ($sectors as $sector)
                             <option value="{{ $sector->id }}"
                                     data-city-id="{{ $sector->city_id }}"
                                     data-province="{{ $sector->city?->province?->slug }}"
                                     @selected((string) ($filters['sector'] ?? '') === (string) $sector->id)>
                                 {{ $sector->name }}
                             </option>
                         @endforeach
                     </select>
                    </div>

                </div>

                <div class="relative flex w-full flex-wrap items-start justify-start gap-xs">
                    <div class="group min-w-0 flex-1">
                    <button type="button" @click="mostrarFiltros = !mostrarFiltros"
                            :aria-expanded="mostrarFiltros.toString()"
                            class="flex min-h-11 items-center gap-xs rounded-lg border
                                   border-outline-variant bg-surface-container-low px-sm py-xs text-label-md
                                   font-medium text-on-surface transition-colors hover:border-secondary">
                        <span class="flex items-center gap-xs">
                            <span class="material-symbols-outlined text-[20px] text-secondary">tune</span>
                            {{ __('properties.filters.more') }}
                            <span data-property-filter-count data-role="button"
                                  class="rounded-full bg-secondary px-xs text-caption text-on-secondary {{ $activeFilterCount === 0 ? 'hidden' : '' }}">
                                {{ $activeFilterCount }}
                            </span>
                        </span>
                        <span class="material-symbols-outlined transition-transform"
                              :class="mostrarFiltros && 'rotate-180'">expand_more</span>
                    </button>

                    <div x-show="mostrarFiltros" x-cloak
                         class="mt-sm w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm
                                ambient-shadow md:p-md">
                        <div class="grid gap-sm md:grid-cols-2 xl:grid-cols-3">
                        {{-- Precio --}}
                        <fieldset>
                    <legend class="mb-base text-caption font-medium text-on-surface-variant">
                        {{ __('properties.filters.price') }}
                    </legend>
                    <div class="grid grid-cols-2 gap-xs">
                        <input type="number" name="precio_min" min="0" step="1000"
                               value="{{ $filters['precio_min'] ?? '' }}"
                               placeholder="{{ __('properties.filters.min') }}"
                               aria-label="{{ __('properties.filters.price_min') }}"
                               class="{{ $selectClase }}">
                        <input type="number" name="precio_max" min="0" step="1000"
                               value="{{ $filters['precio_max'] ?? '' }}"
                               placeholder="{{ __('properties.filters.max') }}"
                               aria-label="{{ __('properties.filters.price_max') }}"
                               class="{{ $selectClase }}">
                    </div>
                    <select name="moneda" class="{{ $selectClase }} mt-xs"
                            aria-label="{{ __('properties.filters.currency') }}">
                        @foreach (\App\Enums\Currency::cases() as $moneda)
                            <option value="{{ $moneda->value }}"
                                    @selected(($filters['moneda'] ?? setting('currency_default')) === $moneda->value)>
                                {{ $moneda->value }}
                            </option>
                        @endforeach
                    </select>
                        </fieldset>

                        {{-- Características --}}
                        <fieldset>
                    <legend class="mb-base text-caption font-medium text-on-surface-variant">
                        {{ __('properties.filters.specs') }}
                    </legend>
                    <div class="grid grid-cols-3 gap-xs">
                        @foreach ([
                            'habitaciones' => 'property.specs.bedrooms',
                            'banos' => 'property.specs.bathrooms',
                            'parqueos' => 'property.specs.parking',
                        ] as $campo => $etiqueta)
                            <div>
                                <label for="f-{{ $campo }}" class="mb-1 block text-caption text-on-surface-variant">
                                    {{ __($etiqueta) }}
                                </label>
                                <select id="f-{{ $campo }}" name="{{ $campo }}" class="{{ $selectClase }}">
                                    <option value="">—</option>
                                    @foreach ([1, 2, 3, 4, 5] as $n)
                                        <option value="{{ $n }}" @selected(($filters[$campo] ?? null) == $n)>
                                            {{ $n }}+
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                        </fieldset>

                        {{-- Amenidades --}}
                        @if ($amenities->isNotEmpty())
                            <fieldset>
                        <legend class="mb-base text-caption font-medium text-on-surface-variant">
                            {{ __('property.sections.amenities') }}
                        </legend>
                        <div class="max-h-48 space-y-1 overflow-y-auto pr-1">
                            @foreach ($amenities->flatten() as $amenidad)
                                <label class="flex cursor-pointer items-center gap-xs">
                                    <input type="checkbox" name="amenidades[]" value="{{ $amenidad->slug }}"
                                           @checked(in_array($amenidad->slug, (array) ($filters['amenidades'] ?? []), true))
                                           class="rounded-sm border-outline-variant text-secondary focus:ring-secondary">
                                    <span class="text-body-md text-on-surface">{{ $amenidad->name }}</span>
                                </label>
                            @endforeach
                        </div>
                            </fieldset>
                        @endif
                        </div>
                    </div>
                    </div>

                    <button type="submit"
                            class="h-11 max-h-11 shrink-0 self-start whitespace-nowrap rounded-lg bg-primary-container px-sm py-xs text-label-md
                                   font-semibold text-on-primary transition-all hover:shadow-ambient-hover">
                        {{ __('properties.filters.apply') }}
                    </button>

                    <a href="{{ lroute('properties.index') }}"
                       data-property-filter-clear
                       class="inline-flex min-h-11 items-center rounded-lg border border-outline-variant
                              px-sm py-xs text-label-md text-on-surface transition-colors
                              hover:bg-surface-container-low">
                        {{ __('properties.filters.clear') }}
                    </a>
                </div>

                {{-- Se conserva el orden al aplicar filtros --}}
                @if (isset($filters['orden']))
                    <input type="hidden" name="orden" value="{{ $filters['orden'] }}">
                @endif

            </form>
        </section>

        @include('public.properties.partials.results')
    </div>
</div>

@endsection
