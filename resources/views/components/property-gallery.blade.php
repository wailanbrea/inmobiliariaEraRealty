@props(['images', 'title'])

{{--
    Galería del detalle: imagen principal grande + tira de 4 miniaturas con
    contador "+N", tal como en detalle_de_propiedad_era_realty_rd.

    El lightbox (resources/js/gallery.js) crece desde la posición real de la
    miniatura pulsada, no aparece centrado de la nada. Sin JavaScript la
    galería sigue mostrando la imagen principal y todas las miniaturas: lo
    único que se pierde es la ampliación.
--}}
@php
    $principal = $images->firstWhere('is_main', true) ?? $images->first();
    $indicePrincipal = $images->search(fn ($i) => $i->id === $principal->id) ?: 0;
    $miniaturas = $images->take(4);
    $restantes = max(0, $images->count() - 4);

    // La colección viaja a Alpine ya resuelta: así el lightbox no tiene que
    // volver a consultar el DOM para saber qué imagen viene después.
    $paraJs = $images->map(fn ($i) => [
        'url' => $i->url(),
        'webp' => $i->webpUrl(),
        'alt' => $i->altText(),
        'width' => $i->width,
        'height' => $i->height,
    ])->values();
@endphp

<div x-data="gallery({ images: {{ Js::from($paraJs) }}, active: {{ $indicePrincipal }} })"
     @keydown.window="onKeydown($event)"
     class="space-y-sm">

    {{-- Imagen grande --}}
    <div class="relative h-[400px] overflow-hidden rounded-xl ambient-shadow md:h-[500px]">
        @foreach ($images as $imagen)
            <picture x-show="active === {{ $loop->index }}" x-cloak>
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

        <button type="button" x-on:click="openLightbox(active, $event)"
                aria-label="{{ __('properties.show.open_gallery') }}"
                class="absolute inset-0 size-full cursor-zoom-in"></button>

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
                <button type="button"
                        x-on:click="select({{ $loop->index }})"
                        x-on:dblclick="openLightbox({{ $loop->index }}, $event)"
                        :aria-current="active === {{ $loop->index }}"
                        aria-label="{{ __('properties.show.view_photo', ['n' => $loop->iteration]) }}"
                        class="relative h-24 overflow-hidden rounded-lg transition-opacity md:h-32"
                        :class="active === {{ $loop->index }} ? 'opacity-100 ring-2 ring-secondary' : 'opacity-70 hover:opacity-100'">

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

    {{-- ============================ LIGHTBOX ============================ --}}
    <div x-show="open" x-ref="panel" x-cloak
         class="lightbox"
         role="dialog" aria-modal="true"
         aria-label="{{ __('properties.show.open_gallery') }}"
         x-on:touchstart="onTouchStart($event)"
         x-on:touchend="onTouchEnd($event)">

        {{-- El velo cierra al tocarlo; va detrás para no tapar los controles. --}}
        <button type="button" x-on:click="closeLightbox()" tabindex="-1" aria-hidden="true"
                class="absolute inset-0 size-full cursor-zoom-out"></button>

        <img x-ref="image" :src="current?.url" :alt="current?.alt"
             class="relative rounded-lg">

        <button type="button" x-ref="close" x-on:click="closeLightbox()"
                aria-label="{{ __('common.actions.close') }}"
                class="absolute right-4 top-4 flex size-11 items-center justify-center
                       rounded-full bg-surface-container-lowest/90 text-on-surface
                       transition-transform hover:scale-110">
            <span class="material-symbols-outlined">close</span>
        </button>

        <template x-if="images.length > 1">
            <div>
                <button type="button" x-on:click="previous()"
                        aria-label="{{ __('properties.show.previous_photo') }}"
                        class="absolute left-2 top-1/2 flex size-11 -translate-y-1/2 items-center
                               justify-center rounded-full bg-surface-container-lowest/90
                               text-on-surface transition-transform hover:scale-110 md:left-6">
                    <span class="material-symbols-outlined">chevron_left</span>
                </button>

                <button type="button" x-on:click="next()"
                        aria-label="{{ __('properties.show.next_photo') }}"
                        class="absolute right-2 top-1/2 flex size-11 -translate-y-1/2 items-center
                               justify-center rounded-full bg-surface-container-lowest/90
                               text-on-surface transition-transform hover:scale-110 md:right-6">
                    <span class="material-symbols-outlined">chevron_right</span>
                </button>

                <p class="absolute bottom-6 left-1/2 -translate-x-1/2 rounded-full
                          bg-surface-container-lowest/90 px-sm py-1 text-label-md text-on-surface">
                    <span x-text="active + 1"></span> / <span x-text="images.length"></span>
                </p>
            </div>
        </template>
    </div>
</div>
