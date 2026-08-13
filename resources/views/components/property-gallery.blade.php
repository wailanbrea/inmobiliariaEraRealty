@props(['images', 'title'])

{{--
    Galería del detalle: imagen principal grande + tira de 4 miniaturas con
    contador "+N", tal como en detalle_de_propiedad_era_realty_rd.
    El lightbox con transición compartida llega en la Fase 8; de momento la
    navegación funciona sin JavaScript avanzado.
--}}
@php
    $principal = $images->firstWhere('is_main', true) ?? $images->first();
    $miniaturas = $images->take(4);
    $restantes = max(0, $images->count() - 4);
@endphp

<div x-data="{ activa: {{ $principal->id }} }" class="space-y-sm">

    {{-- Imagen grande --}}
    <div class="relative h-[400px] overflow-hidden rounded-xl ambient-shadow md:h-[500px]">
        @foreach ($images as $imagen)
            <picture x-show="activa === {{ $imagen->id }}" x-cloak>
                @if ($imagen->webpUrl())
                    <source srcset="{{ $imagen->webpUrl() }}" type="image/webp">
                @endif
                <img src="{{ $imagen->url() }}"
                     alt="{{ $imagen->altText() }}"
                     width="{{ $imagen->width }}" height="{{ $imagen->height }}"
                     @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
                     decoding="async"
                     class="size-full object-cover">
            </picture>
        @endforeach

        <div class="pointer-events-none absolute bottom-4 right-4 flex items-center gap-2
                    rounded bg-surface-container-lowest/90 px-sm py-xs text-label-md
                    text-on-surface backdrop-blur-sm">
            <span class="material-symbols-outlined">photo_library</span>
            {{ __('property.labels.photos', ['count' => $images->count()]) }}
        </div>
    </div>

    {{-- Miniaturas --}}
    @if ($images->count() > 1)
        <div class="grid grid-cols-4 gap-sm">
            @foreach ($miniaturas as $imagen)
                <button type="button" @click="activa = {{ $imagen->id }}"
                        :aria-current="activa === {{ $imagen->id }}"
                        aria-label="{{ __('properties.show.view_photo', ['n' => $loop->iteration]) }}"
                        class="relative h-24 overflow-hidden rounded-lg transition-opacity md:h-32"
                        :class="activa === {{ $imagen->id }} ? 'opacity-100 ring-2 ring-secondary' : 'opacity-70 hover:opacity-100'">

                    <img src="{{ $imagen->thumbnailUrl() }}"
                         alt="{{ $imagen->altText() }}"
                         loading="lazy" decoding="async"
                         class="size-full object-cover">

                    @if ($loop->last && $restantes > 0)
                        <span class="absolute inset-0 flex items-center justify-center
                                     bg-primary/40 text-label-md text-on-primary">
                            +{{ $restantes }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>
    @endif
</div>
