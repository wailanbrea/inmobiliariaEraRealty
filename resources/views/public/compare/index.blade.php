@extends('layouts.public')

@section('title', __('compare.title') . ' · ' . setting('site_name'))
@section('description', __('compare.description', ['max' => $max]))

@push('head')
    {{-- Una comparación concreta no aporta nada a Google y genera infinitas
         combinaciones de URL. --}}
    <meta name="robots" content="noindex, follow">
@endpush

@section('content')

<div class="mx-auto max-w-container-max px-margin-mobile py-lg md:px-gutter">

    <nav aria-label="breadcrumb" class="mb-xs text-caption text-on-surface-variant">
        <a href="{{ lroute('home') }}" class="transition-colors hover:text-secondary">
            {{ __('common.nav.home') }}
        </a>
        <span class="mx-1">/</span>
        <span class="text-on-surface">{{ __('compare.title') }}</span>
    </nav>

    <div class="mb-lg flex flex-wrap items-end justify-between gap-sm">
        <h1 class="font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
            {{ __('compare.title') }}
        </h1>

        @if ($properties->isNotEmpty())
            <div class="flex flex-wrap items-center gap-xs">
                {{-- Enlace compartible con los ids --}}
                <button type="button"
                        x-data="{ copiado: false }"
                        @click="navigator.clipboard.writeText(
                                    '{{ lroute('compare.index') }}?ids={{ $properties->pluck('id')->implode(',') }}'
                                );
                                copiado = true; setTimeout(() => copiado = false, 1500)"
                        class="flex items-center gap-xs rounded-lg border border-outline-variant px-sm py-xs
                               text-label-md text-on-surface transition-colors hover:bg-surface-container-low">
                    <span class="material-symbols-outlined text-[18px]" x-text="copiado ? 'check' : 'link'">link</span>
                    <span x-text="copiado ? '{{ __('compare.copied') }}' : '{{ __('compare.share') }}'">
                        {{ __('compare.share') }}
                    </span>
                </button>

                <form method="POST" action="{{ lroute('compare.clear') }}">
                    @csrf
                    <button type="submit"
                            class="flex items-center gap-xs rounded-lg border border-outline-variant px-sm py-xs
                                   text-label-md text-on-surface transition-colors hover:bg-surface-container-low">
                        <span class="material-symbols-outlined text-[18px]">delete_sweep</span>
                        {{ __('compare.clear') }}
                    </button>
                </form>
            </div>
        @endif
    </div>

    @if (session('compare_error'))
        <div role="alert" class="mb-md rounded-lg bg-error-container px-sm py-xs text-on-error-container">
            <p class="text-body-md">{{ session('compare_error') }}</p>
        </div>
    @endif

    @if ($properties->isEmpty())
        {{-- Estado vacío, contemplado en el diseño --}}
        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest
                    p-xl text-center ambient-shadow">
            <span class="material-symbols-outlined text-[48px] text-outline-variant">compare_arrows</span>
            <h2 class="mt-sm font-heading text-title-lg text-on-surface">
                {{ __('compare.empty_title') }}
            </h2>
            <p class="mx-auto mt-base max-w-md text-body-md text-on-surface-variant">
                {{ __('compare.empty_body') }}
            </p>
            <a href="{{ lroute('properties.index') }}"
               class="mt-md inline-flex items-center gap-xs rounded-lg bg-primary-container px-md py-xs
                      text-label-md font-semibold text-on-primary transition-all hover-lift">
                {{ __('compare.browse') }}
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
        </div>
    @else
        @php
            // Filas de datos. Cada una sabe extraer su valor y si difiere.
            $filas = [
                'price' => fn ($p) => $p->formattedPrice(),
                'operation' => fn ($p) => $p->operation_type->label(),
                'type' => fn ($p) => $p->type?->name,
                'location' => fn ($p) => $p->locationLabel(),
                'bedrooms' => fn ($p) => $p->bedrooms,
                'bathrooms' => fn ($p) => $p->bathrooms
                    ? rtrim(rtrim(number_format($p->bathrooms, 1), '0'), '.') : null,
                'parking' => fn ($p) => $p->parking_spaces,
                'area' => fn ($p) => $p->construction_area
                    ? number_format($p->construction_area, 0).' m²' : null,
                'land' => fn ($p) => $p->land_area
                    ? number_format($p->land_area, 0).' m²' : null,
                'year' => fn ($p) => $p->year_built,
                'reference' => fn ($p) => $p->reference_code,
            ];
        @endphp

        <div x-data="{ soloDiferencias: false }">

            <label class="mb-sm inline-flex cursor-pointer items-center gap-xs">
                <input type="checkbox" x-model="soloDiferencias"
                       class="rounded-sm border-outline-variant text-secondary focus:ring-secondary">
                <span class="text-body-md text-on-surface">{{ __('compare.only_differences') }}</span>
            </label>

            {{-- La tabla scrollea DENTRO de su contenedor: con 4 columnas no
                 cabe en móvil de ninguna forma razonable. --}}
            <div class="overflow-x-auto rounded-xl border border-outline-variant/40
                        bg-surface-container-lowest ambient-shadow">
                <table class="w-full min-w-[720px] text-left">
                    <caption class="sr-only">{{ __('compare.title') }}</caption>

                    <thead>
                        <tr>
                            {{-- Columna de etiquetas, pegajosa al hacer scroll lateral --}}
                            <th scope="col"
                                class="sticky left-0 z-10 w-40 bg-surface-container-low p-sm
                                       text-caption uppercase tracking-wider text-on-surface-variant">
                                {{ __('compare.title') }}
                            </th>

                            @foreach ($properties as $property)
                                <th scope="col" class="min-w-[220px] border-l border-outline-variant/30 p-sm align-top">
                                    <div class="relative">
                                        @php $portada = $property->mainImage; @endphp

                                        <a href="{{ lroute('properties.show', ['slug' => $property->slug]) }}"
                                           class="block overflow-hidden rounded-lg">
                                            @if ($portada)
                                                <img src="{{ $portada->thumbnailUrl() }}"
                                                     alt="{{ $portada->altText() }}"
                                                     width="400" height="300" loading="lazy"
                                                     class="h-32 w-full object-cover">
                                            @else
                                                <div class="flex h-32 items-center justify-center bg-surface-container">
                                                    <span class="material-symbols-outlined text-[32px] text-outline-variant">
                                                        home
                                                    </span>
                                                </div>
                                            @endif
                                        </a>

                                        <form method="POST"
                                              action="{{ lroute('compare.remove', ['property' => $property->id]) }}"
                                              class="absolute right-1 top-1">
                                            @csrf
                                            <button type="submit" aria-label="{{ __('compare.remove') }}"
                                                    class="flex size-8 items-center justify-center rounded-full
                                                           bg-surface-container-lowest/90 text-on-surface-variant
                                                           backdrop-blur transition-colors hover:text-error">
                                                <span class="material-symbols-outlined text-[18px]">close</span>
                                            </button>
                                        </form>
                                    </div>

                                    <a href="{{ lroute('properties.show', ['slug' => $property->slug]) }}"
                                       class="mt-xs block text-label-md font-semibold text-on-surface hover:text-secondary">
                                        {{ $property->title }}
                                    </a>

                                    <div class="mt-1">
                                        <x-status-chip :status="$property->status" />
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-outline-variant/30">
                        @foreach ($filas as $clave => $extraer)
                            @php $difiere = $differences[$clave] ?? false; @endphp

                            <tr @if (! $difiere) x-show="!soloDiferencias" x-cloak @endif
                                class="transition-colors hover:bg-surface-container-low">

                                <th scope="row"
                                    class="sticky left-0 z-10 bg-surface-container-low p-sm text-left
                                           text-label-md font-medium text-on-surface-variant">
                                    <span class="flex items-center gap-1">
                                        {{ __("compare.rows.{$clave}") }}
                                        @if ($difiere)
                                            <span class="size-1.5 rounded-full bg-secondary"
                                                  title="{{ __('compare.differs') }}"></span>
                                        @endif
                                    </span>
                                </th>

                                @foreach ($properties as $property)
                                    <td class="border-l border-outline-variant/30 p-sm text-body-md text-on-surface">
                                        {{ $extraer($property) ?: __('compare.empty_value') }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        {{-- Amenidades: una fila por cada una que tenga alguna --}}
                        @foreach ($amenities as $amenidad)
                            @php
                                $quienLaTiene = $properties->map(
                                    fn ($p) => $p->amenities->contains('id', $amenidad->id)
                                );
                                $difiere = $quienLaTiene->unique()->count() > 1;
                            @endphp

                            <tr @if (! $difiere) x-show="!soloDiferencias" x-cloak @endif
                                class="transition-colors hover:bg-surface-container-low">

                                <th scope="row"
                                    class="sticky left-0 z-10 bg-surface-container-low p-sm text-left
                                           text-label-md font-medium text-on-surface-variant">
                                    <span class="flex items-center gap-1">
                                        {{ $amenidad->name }}
                                        @if ($difiere)
                                            <span class="size-1.5 rounded-full bg-secondary"
                                                  title="{{ __('compare.differs') }}"></span>
                                        @endif
                                    </span>
                                </th>

                                @foreach ($properties as $index => $property)
                                    <td class="border-l border-outline-variant/30 p-sm">
                                        @if ($quienLaTiene[$index])
                                            <span class="material-symbols-outlined text-[20px] text-on-tertiary-container"
                                                  role="img" aria-label="{{ __('compare.yes') }}">check_circle</span>
                                        @else
                                            <span class="material-symbols-outlined text-[20px] text-outline-variant"
                                                  role="img" aria-label="{{ __('compare.no') }}">remove</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        {{-- Contacto --}}
                        <tr>
                            <th scope="row"
                                class="sticky left-0 z-10 bg-surface-container-low p-sm text-left
                                       text-label-md font-medium text-on-surface-variant">
                                {{ __('compare.rows.actions') }}
                            </th>

                            @foreach ($properties as $property)
                                <td class="border-l border-outline-variant/30 p-sm">
                                    <div class="flex flex-col gap-xs">
                                        <a href="{{ lroute('properties.show', ['slug' => $property->slug]) }}"
                                           class="flex items-center justify-center gap-xs rounded-lg
                                                  bg-primary-container px-sm py-xs text-caption
                                                  font-semibold text-on-primary">
                                            {{ __('compare.see_property') }}
                                        </a>

                                        @if ($property->status->acceptsLeads())
                                            @php
                                                $enlaceWa = whatsapp()->link(
                                                    $property->agent?->whatsapp ?: null,
                                                    whatsapp()->propertyMessage([
                                                        'reference_code' => $property->reference_code,
                                                        'title' => $property->title,
                                                        'price' => $property->formattedPrice(),
                                                        'location' => $property->locationLabel(),
                                                        'url' => lroute('properties.show', ['slug' => $property->slug]),
                                                    ]),
                                                );
                                            @endphp

                                            @if ($enlaceWa)
                                                <a href="{{ $enlaceWa }}" target="_blank" rel="noopener noreferrer"
                                                   class="flex items-center justify-center gap-xs rounded-lg
                                                          bg-whatsapp px-sm py-xs text-caption font-semibold text-white">
                                                    <span class="material-symbols-outlined text-[16px]">chat</span>
                                                    WhatsApp
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@endsection
