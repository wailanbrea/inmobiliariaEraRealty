@props(['property', 'eager' => false, 'compare' => true])

{{--
    Tarjeta de propiedad — la pieza central del diseño.
    Traducida de inicio_era_realty_rd/code.html: radio 12px, sombra ambiental,
    imagen con zoom al hover, precio superpuesto abajo a la derecha y chip de
    estado arriba a la izquierda.
--}}
{{--
    Una propiedad sin NINGUNA traducción no tiene slug, y construir su enlace
    lanza UrlGenerationException: eso tumbaba la portada entera con un 500 por
    una sola fila incompleta.

    translated() ya cae al idioma por defecto, así que esto solo ocurre si la
    propiedad se creó fuera del formulario del panel. Aun así se omite la
    tarjeta en lugar de dejar caer la página: el resto del listado vale más
    que esa ficha.
--}}
@if ($property->slug !== null)
@php
    $imagen = $property->mainImage;
    $enlace = lroute('properties.show', ['slug' => $property->slug]);
@endphp

{{-- data-compare-card marca desde donde despega la miniatura que vuela hasta
     la barra del comparador (resources/js/compare.js). --}}
<article data-reveal data-compare-card
         {{ $attributes->merge(['class' => 'group overflow-hidden rounded-xl border border-surface-variant
                bg-surface-container-lowest ambient-shadow hover-lift']) }}>

    <a href="{{ $enlace }}" class="block">
        <div class="relative h-64 overflow-hidden bg-surface-container">
            @if ($imagen)
                {{--
                    La tarjeta usa la MINIATURA, no la foto completa.

                    Se medía en el navegador: la tarjeta muestra la imagen a
                    284 px y estaba descargando la de 1024 —3,6 veces más de
                    lo que se ve—, con archivos de hasta 163 KB. Doce tarjetas
                    sumaban 1 MB de imágenes para pintar una rejilla.

                    La miniatura ya existía y solo la usaba la galería del
                    detalle. Es el mismo recorte 600×400 que necesita la
                    tarjeta.
                --}}
                <img src="{{ $imagen->thumbnailUrl() }}"
                     alt="{{ $imagen->altText() }}"
                     width="600" height="400"
                     @if ($eager) fetchpriority="high" @else loading="lazy" @endif
                     decoding="async"
                     class="size-full object-cover transition-transform duration-500 group-hover:scale-105">
            @else
                <div class="flex size-full items-center justify-center">
                    <span class="material-symbols-outlined text-[48px] text-outline-variant">
                        {{ $property->type?->icon ?: 'home' }}
                    </span>
                </div>
            @endif

            {{--
                Estado y marca de inversión en UNA fila flex, no en dos
                elementos absolutos independientes.

                Antes cada uno se anclaba a su esquina —left-sm y right-sm— y
                se solapaban en cuanto sus textos sumaban más ancho que la
                tarjeta. Ocurría en inglés: «AVAILABLE» quedaba tapado por
                «INVESTMENT OPPORTUNITY». No era un problema de traducción sino
                de maquetación: dos cajas ancladas a la misma altura acaban
                chocando con cualquier idioma lo bastante largo.

                Con la fila, el estado nunca se comprime —es el dato que no se
                puede perder— y la marca de inversión cede espacio truncándose.
            --}}
            <div class="pointer-events-none absolute inset-x-sm top-sm flex items-start justify-between gap-xs">
                <x-status-chip :status="$property->status" class="shrink-0" />

                @if ($property->is_investment)
                    <span class="min-w-0 truncate rounded-full bg-tertiary-fixed px-xs py-0.5
                                 text-caption font-bold uppercase tracking-wider
                                 text-on-tertiary-fixed shadow-sm"
                          title="{{ __('property.labels.investment') }}">
                        {{ __('property.labels.investment_short') }}
                    </span>
                @endif
            </div>

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

            {{--
                Meta-grid: SOLO las cifras que existen.

                Antes se pintaban siempre las tres con un guion cuando faltaba
                el dato, y eso daba dos resultados malos a la vez: un solar
                anunciando «— Habs — Baños», que no tiene sentido ni aunque el
                dato estuviera, y una rejilla de tarjetas llena de guiones que
                hace parecer el catálogo a medio cargar.

                Se muestra lo que se sabe. Si no se sabe nada, la fila
                desaparece y la tarjeta se cierra limpia después de la
                ubicación.
            --}}
            @php
                $specs = collect([
                    $property->bedrooms ? [
                        'bed',
                        __('property.specs.bedrooms'),
                        $property->bedrooms.' '.__('property.specs_short.bedrooms'),
                    ] : null,
                    $property->bathrooms ? [
                        'shower',
                        __('property.specs.bathrooms'),
                        rtrim(rtrim(number_format($property->bathrooms, 1), '0'), '.')
                            .' '.__('property.specs_short.bathrooms'),
                    ] : null,
                    // En un terreno o un solar la cifra que importa es la
                    // superficie del terreno, no la construida.
                    ($area = $property->construction_area ?: $property->land_area) ? [
                        'square_foot',
                        __('property.specs.area'),
                        number_format($area, 0, ',', '.').' m²',
                    ] : null,
                    $property->parking_spaces ? [
                        'directions_car',
                        __('property.specs.parking'),
                        $property->parking_spaces.' '.__('property.specs_short.parking'),
                    ] : null,
                ])->filter()->take(3)->values();
            @endphp

            @if ($specs->isNotEmpty())
                <dl @class([
                    'grid gap-xs border-t border-surface-variant pt-sm',
                    'grid-cols-1' => $specs->count() === 1,
                    'grid-cols-2' => $specs->count() === 2,
                    'grid-cols-3' => $specs->count() === 3,
                ])>
                    @foreach ($specs as $spec)
                        <div @class([
                            'flex flex-col items-center',
                            'border-x border-surface-variant' => $specs->count() === 3 && $loop->index === 1,
                        ])>
                            <span class="material-symbols-outlined mb-1 text-[20px] text-outline">{{ $spec[0] }}</span>
                            <dt class="sr-only">{{ $spec[1] }}</dt>
                            <dd class="text-label-md text-on-surface">{{ $spec[2] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif

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
@endif
