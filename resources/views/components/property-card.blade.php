@props(['property', 'eager' => false, 'compare' => true])

{{--
    Tarjeta de propiedad — la pieza central del diseño.
    Traducida de inicio_era_realty_rd/code.html: radio 12px, sombra ambiental,
    imagen con zoom al hover, precio superpuesto abajo a la derecha y chip de
    estado arriba a la izquierda.
--}}
@php
    $imagen = $property->mainImage;
    $enlace = lroute('properties.show', ['slug' => $property->slug]);
@endphp

<article class="group overflow-hidden rounded-xl border border-surface-variant
                bg-surface-container-lowest ambient-shadow hover-lift">

    <a href="{{ $enlace }}" class="block">
        <div class="relative h-64 overflow-hidden bg-surface-container">
            @if ($imagen)
                <picture>
                    @if ($imagen->webpUrl())
                        <source srcset="{{ $imagen->webpUrl() }}" type="image/webp">
                    @endif
                    <img src="{{ $imagen->url() }}"
                         alt="{{ $imagen->altText() }}"
                         width="{{ $imagen->width }}" height="{{ $imagen->height }}"
                         @if ($eager) fetchpriority="high" @else loading="lazy" @endif
                         decoding="async"
                         class="size-full object-cover transition-transform duration-500 group-hover:scale-105">
                </picture>
            @else
                <div class="flex size-full items-center justify-center">
                    <span class="material-symbols-outlined text-[48px] text-outline-variant">
                        {{ $property->type?->icon ?: 'home' }}
                    </span>
                </div>
            @endif

            {{-- Estado --}}
            <x-status-chip :status="$property->status" class="absolute left-sm top-sm" />

            {{-- Marcas --}}
            @if ($property->is_investment)
                <span class="absolute right-sm top-sm rounded-full bg-tertiary-fixed px-xs py-0.5
                             text-caption font-bold uppercase tracking-wider text-on-tertiary-fixed shadow-sm">
                    {{ __('property.labels.investment') }}
                </span>
            @endif

            {{-- Precio --}}
            <div class="absolute bottom-sm right-sm rounded bg-surface-container-lowest/90 px-sm py-xs
                        text-title-lg text-on-surface shadow-sm backdrop-blur">
                {{ $property->formattedPrice() }}
            </div>
        </div>

        <div class="p-sm">
            <h3 class="mb-xs truncate text-title-lg text-on-surface">
                {{ $property->title }}
            </h3>

            <p class="mb-sm flex items-center gap-xs text-body-md text-on-surface-variant">
                <span class="material-symbols-outlined text-[18px]">location_on</span>
                {{ $property->locationLabel() ?: $property->type?->name }}
            </p>

            {{-- Meta-grid de 3 columnas, como en el diseño --}}
            <dl class="grid grid-cols-3 gap-xs border-t border-surface-variant pt-sm">
                <div class="flex flex-col items-center">
                    <span class="material-symbols-outlined mb-1 text-[20px] text-outline">bed</span>
                    <dt class="sr-only">{{ __('property.specs.bedrooms') }}</dt>
                    <dd class="text-label-md text-on-surface">
                        {{ $property->bedrooms ?? '—' }} {{ __('property.specs_short.bedrooms') }}
                    </dd>
                </div>

                <div class="flex flex-col items-center border-x border-surface-variant">
                    <span class="material-symbols-outlined mb-1 text-[20px] text-outline">shower</span>
                    <dt class="sr-only">{{ __('property.specs.bathrooms') }}</dt>
                    <dd class="text-label-md text-on-surface">
                        {{ $property->bathrooms ? rtrim(rtrim(number_format($property->bathrooms, 1), '0'), '.') : '—' }}
                        {{ __('property.specs_short.bathrooms') }}
                    </dd>
                </div>

                <div class="flex flex-col items-center">
                    <span class="material-symbols-outlined mb-1 text-[20px] text-outline">square_foot</span>
                    <dt class="sr-only">{{ __('property.specs.area') }}</dt>
                    <dd class="text-label-md text-on-surface">
                        {{ $property->construction_area ? number_format($property->construction_area, 0) : '—' }} m²
                    </dd>
                </div>
            </dl>
        </div>
    </a>

    {{-- El botón de comparar va FUERA del enlace: un formulario dentro de un
         <a> es HTML inválido y el navegador lo desanida de forma impredecible. --}}
    @if ($compare)
        <div class="border-t border-surface-variant px-sm py-xs">
            <x-compare-toggle :property="$property" />
        </div>
    @endif
</article>
