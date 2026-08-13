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
<section class="border-b border-outline-variant/40 bg-surface-container-low py-lg">
    <div class="mx-auto max-w-container-max px-margin-mobile md:px-gutter">
        <nav aria-label="breadcrumb" class="mb-xs text-caption text-on-surface-variant">
            <a href="{{ lroute('home') }}" class="transition-colors hover:text-secondary">
                {{ __('common.nav.home') }}
            </a>
            <span class="mx-1">/</span>
            <span class="text-on-surface">{{ __('common.nav.properties') }}</span>
        </nav>

        <h1 class="font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
            {{ __('properties.index.title') }}
        </h1>
    </div>
</section>

<div class="mx-auto max-w-container-max px-margin-mobile py-lg md:px-gutter">
    <div class="flex flex-col gap-gutter lg:flex-row">

        {{-- ===================== FILTROS ===================== --}}
        {{-- En escritorio es una columna pegajosa; en móvil, un panel que se
             despliega, porque ocuparía toda la pantalla antes de los
             resultados. --}}
        <aside class="lg:w-72 lg:shrink-0" x-data="{ abierto: false }">

            <button type="button" @click="abierto = !abierto"
                    :aria-expanded="abierto"
                    class="flex w-full items-center justify-between rounded-lg border
                           border-outline-variant bg-surface-container-lowest px-sm py-xs
                           text-label-md text-on-surface lg:hidden">
                <span class="flex items-center gap-xs">
                    <span class="material-symbols-outlined text-[20px]">tune</span>
                    {{ __('properties.filters.title') }}
                    @if ($hasFilters)
                        <span class="rounded-full bg-secondary px-xs text-caption text-on-secondary">
                            {{ count($filters) }}
                        </span>
                    @endif
                </span>
                <span class="material-symbols-outlined transition-transform"
                      :class="abierto && 'rotate-180'">expand_more</span>
            </button>

            <form method="GET" action="{{ lroute('properties.index') }}"
                  x-show="abierto || window.innerWidth >= 1024"
                  x-cloak
                  class="mt-sm space-y-md rounded-xl border border-outline-variant/40
                         bg-surface-container-lowest p-sm ambient-shadow lg:sticky lg:top-24 lg:mt-0">

                {{-- Búsqueda --}}
                <div>
                    <label for="f-q" class="mb-base block text-caption font-medium text-on-surface-variant">
                        {{ __('properties.filters.search') }}
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-xs top-1/2 -translate-y-1/2
                                     text-[20px] text-on-surface-variant">search</span>
                        <input id="f-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                               placeholder="{{ __('properties.filters.search_placeholder') }}"
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

                {{-- Operación y tipo --}}
                <div class="grid grid-cols-2 gap-sm lg:grid-cols-1">
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
                </div>

                {{-- Ubicación --}}
                <div>
                    <label for="f-provincia" class="mb-base block text-caption font-medium text-on-surface-variant">
                        {{ __('home.search.location') }}
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

                {{-- Se conserva el orden al aplicar filtros --}}
                @if (isset($filters['orden']))
                    <input type="hidden" name="orden" value="{{ $filters['orden'] }}">
                @endif

                <div class="flex gap-xs pt-xs">
                    <button type="submit"
                            class="flex-1 rounded-lg bg-primary-container px-sm py-xs text-label-md
                                   font-semibold text-on-primary transition-all hover:shadow-ambient-hover">
                        {{ __('properties.filters.apply') }}
                    </button>

                    @if ($hasFilters)
                        <a href="{{ lroute('properties.index') }}"
                           class="rounded-lg border border-outline-variant px-sm py-xs text-label-md
                                  text-on-surface transition-colors hover:bg-surface-container-low">
                            {{ __('properties.filters.clear') }}
                        </a>
                    @endif
                </div>
            </form>
        </aside>

        {{-- ===================== RESULTADOS ===================== --}}
        <div class="min-w-0 flex-1">

            <div class="mb-md flex flex-col gap-sm sm:flex-row sm:items-center sm:justify-between">
                <p class="text-body-md text-on-surface-variant">
                    {{ trans_choice('properties.index.count', $properties->total(), ['count' => $properties->total()]) }}
                </p>

                <form method="GET" action="{{ lroute('properties.index') }}" class="flex items-center gap-xs">
                    @foreach ($filters as $clave => $valor)
                        @continue($clave === 'orden')
                        @if (is_array($valor))
                            @foreach ($valor as $v)
                                <input type="hidden" name="{{ $clave }}[]" value="{{ $v }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                        @endif
                    @endforeach

                    <label for="orden" class="text-caption text-on-surface-variant">
                        {{ __('properties.sort.label') }}
                    </label>
                    <select id="orden" name="orden" onchange="this.form.submit()"
                            class="rounded-lg border border-outline-variant bg-surface-container-lowest
                                   px-sm py-xs text-body-md text-on-surface
                                   focus:border-secondary focus:ring-1 focus:ring-secondary">
                        @foreach ($sorts as $opcion)
                            <option value="{{ $opcion }}" @selected(($filters['orden'] ?? 'recent') === $opcion)>
                                {{ __("properties.sort.{$opcion}") }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if ($properties->isEmpty())
                <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest
                            p-xl text-center ambient-shadow">
                    <span class="material-symbols-outlined text-[48px] text-outline-variant">search_off</span>
                    <h2 class="mt-sm font-heading text-title-lg text-on-surface">
                        {{ __('properties.index.empty_title') }}
                    </h2>
                    <p class="mt-base text-body-md text-on-surface-variant">
                        {{ $hasFilters
                            ? __('properties.index.empty_filtered')
                            : __('properties.index.empty_body') }}
                    </p>

                    @if ($hasFilters)
                        <a href="{{ lroute('properties.index') }}"
                           class="mt-md inline-flex items-center gap-xs rounded-lg border border-outline-variant
                                  px-sm py-xs text-label-md text-on-surface transition-colors
                                  hover:bg-surface-container-low">
                            {{ __('properties.filters.clear') }}
                        </a>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 gap-gutter md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($properties as $index => $property)
                        <x-property-card :property="$property" :eager="$index < 3" />
                    @endforeach
                </div>

                <div class="mt-lg">
                    {{ $properties->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
