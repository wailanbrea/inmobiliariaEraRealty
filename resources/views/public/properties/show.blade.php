@extends('layouts.public')

@php
    $traduccion = $property->translated();
    $metaTitulo = $traduccion?->meta_title
        ?: trim("{$property->title} · {$property->locationLabel()}");
    // OJO: nunca dejar esto en null.
    // @section('nombre', null) NO asigna un valor: Blade lo interpreta como
    // una sección que se ABRE, llama a ob_start() y espera un @endsection que
    // nunca llega. El buffer queda colgado y la página se corrompe.
    // Por eso el respaldo termina siempre en una cadena.
    $metaDesc = $traduccion?->meta_description
        ?: $property->short_description
        ?: setting('seo_default_description', '');

    $portada = $property->images->firstWhere('is_main', true) ?? $property->images->first();
@endphp

@section('title', $metaTitulo . ' · ' . setting('site_name'))
@section('description', $metaDesc)

@push('head')
    @unless ($property->status->isIndexable() && ! $isPreview)
        <meta name="robots" content="noindex, nofollow">
    @endunless

    @if ($portada)
        <meta property="og:image" content="{{ $portada->url() }}">
    @endif

    {{-- Datos estructurados. La geolocalización solo se publica si el
         administrador autorizó mostrar la ubicación exacta. --}}
    @php
        $coordenadas = $property->publicCoordinates();

        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateListing',
            'name' => $property->title,
            'url' => url()->current(),
            'image' => $property->images->take(5)->map->url()->values()->all(),
            'description' => $property->short_description,
            'offers' => array_filter([
                '@type' => 'Offer',
                'price' => $property->price ? (float) $property->price : null,
                'priceCurrency' => $property->currency->value,
                'availability' => $property->status->acceptsLeads()
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/SoldOut',
            ], fn ($v) => $v !== null),
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'addressLocality' => $property->sector?->name ?? $property->city?->name,
                'addressRegion' => $property->province?->name,
                'addressCountry' => 'DO',
            ], fn ($v) => $v !== null),
            'numberOfRooms' => $property->bedrooms,
            'numberOfBathroomsTotal' => $property->bathrooms ? (float) $property->bathrooms : null,
            'floorSize' => $property->construction_area ? [
                '@type' => 'QuantitativeValue',
                'value' => (float) $property->construction_area,
                'unitCode' => 'MTK',
            ] : null,
            'geo' => $coordenadas ? [
                '@type' => 'GeoCoordinates',
                'latitude' => $coordenadas['lat'],
                'longitude' => $coordenadas['lng'],
            ] : null,
        ], fn ($v) => $v !== null && $v !== []);
    @endphp

    <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')

@if ($isPreview)
    <div role="status" class="bg-secondary px-margin-mobile py-xs text-center md:px-gutter">
        <p class="text-label-md text-on-secondary">
            <span class="material-symbols-outlined align-middle text-[18px]">visibility</span>
            {{ __('properties.show.preview_notice') }}
        </p>
    </div>
@endif

<div class="mx-auto max-w-container-max px-margin-mobile py-md md:px-gutter">

    {{-- Migas --}}
    <nav aria-label="breadcrumb" class="mb-sm text-caption text-on-surface-variant">
        <a href="{{ lroute('home') }}" class="transition-colors hover:text-secondary">
            {{ __('common.nav.home') }}
        </a>
        <span class="mx-1">/</span>
        <a href="{{ lroute('properties.index') }}" class="transition-colors hover:text-secondary">
            {{ __('common.nav.properties') }}
        </a>
        <span class="mx-1">/</span>
        <span class="text-on-surface">{{ $property->title }}</span>
    </nav>

    {{-- Cabecera --}}
    <header class="mb-md flex flex-col gap-sm md:flex-row md:items-start md:justify-between">
        <div>
            <div class="mb-xs flex flex-wrap items-center gap-xs">
                <x-status-chip :status="$property->status" size="lg" />
                <span class="text-caption text-on-surface-variant">{{ $property->reference_code }}</span>
                @if ($property->is_project)
                    <span class="rounded-full bg-secondary-fixed px-xs py-0.5 text-caption
                                 font-bold uppercase tracking-wider text-on-secondary-fixed">
                        {{ __('property.labels.project') }}
                    </span>
                @endif
            </div>

            <h1 class="font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
                {{ $property->title }}
            </h1>

            <p class="mt-xs flex items-center gap-xs text-body-lg text-on-surface-variant">
                <span class="material-symbols-outlined text-[20px]">location_on</span>
                {{ $property->locationLabel() }}
            </p>
        </div>

        <div class="shrink-0 md:text-right">
            <p class="font-heading text-headline-md-mobile text-secondary md:text-headline-md">
                {{ $property->formattedPrice() }}
            </p>
            @if ($otraMoneda = $property->priceInOtherCurrency())
                <p class="text-body-md text-on-surface-variant">≈ {{ $otraMoneda }}</p>
            @endif
        </div>
    </header>

    {{-- Rejilla de 12 columnas: contenido en 8, barra lateral en 4 --}}
    <div class="grid grid-cols-1 gap-gutter lg:grid-cols-12">

        {{-- ==================== COLUMNA IZQUIERDA ==================== --}}
        <div class="space-y-lg lg:col-span-8">

            {{-- Galería --}}
            @if ($property->images->isNotEmpty())
                <x-property-gallery :images="$property->images" :title="$property->title" />
            @else
                <div class="flex h-64 items-center justify-center rounded-xl bg-surface-container">
                    <span class="material-symbols-outlined text-[48px] text-outline-variant">image</span>
                </div>
            @endif

            {{-- Meta-grid de 4 --}}
            <dl class="grid grid-cols-2 gap-sm rounded-xl bg-surface-container-lowest p-md
                       ambient-shadow md:grid-cols-4">
                @foreach ([
                    ['bed', $property->bedrooms, 'property.specs.bedrooms'],
                    ['bathtub', $property->bathrooms ? rtrim(rtrim(number_format($property->bathrooms, 1), '0'), '.') : null, 'property.specs.bathrooms'],
                    ['directions_car', $property->parking_spaces, $property->parking_spaces == 1
                        ? 'property.specs.parking_one' : 'property.specs.parking_many'],
                    ['square_foot', $property->construction_area ? number_format($property->construction_area, 0) : null, 'property.specs.area'],
                ] as $i => [$icono, $valor, $etiqueta])
                    <div @class([
                        'flex flex-col items-center justify-center p-sm text-center',
                        'border-l border-outline-variant/30' => $i > 0,
                    ])>
                        <span class="material-symbols-outlined mb-2 text-[32px] text-secondary">{{ $icono }}</span>
                        <dd class="text-title-lg text-on-surface">{{ $valor ?? '—' }}</dd>
                        <dt class="text-caption text-on-surface-variant">{{ __($etiqueta) }}</dt>
                    </div>
                @endforeach
            </dl>

            {{-- Descripción --}}
            @if ($property->description)
                <section class="rounded-xl bg-surface-container-lowest p-md ambient-shadow md:p-lg">
                    <h2 class="mb-md font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
                        {{ __('property.sections.description') }}
                    </h2>
                    <div class="space-y-4 text-body-md text-on-surface-variant">
                        @foreach (preg_split('/\n\s*\n/', str_replace(["\r\n", "\r"], "\n", trim($property->description))) as $parrafo)
                            <p class="whitespace-pre-line">{{ $parrafo }}</p>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Amenidades en bento --}}
            @if ($property->amenities->isNotEmpty())
                <section class="rounded-xl bg-surface-container-lowest p-md ambient-shadow md:p-lg">
                    <h2 class="mb-md font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
                        {{ __('property.sections.amenities') }}
                    </h2>
                    <ul class="grid grid-cols-1 gap-sm sm:grid-cols-2 md:grid-cols-3">
                        @foreach ($property->amenities as $amenidad)
                            <li class="flex items-center gap-sm rounded-lg bg-surface-container-low p-sm">
                                <span class="material-symbols-outlined text-secondary">
                                    {{ $amenidad->icon ?: 'check' }}
                                </span>
                                <span class="text-label-md text-on-surface">{{ $amenidad->name }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Ficha técnica --}}
            <section class="rounded-xl bg-surface-container-lowest p-md ambient-shadow md:p-lg">
                <h2 class="mb-md font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
                    {{ __('property.sections.features') }}
                </h2>

                <dl class="grid grid-cols-1 gap-x-gutter gap-y-xs sm:grid-cols-2">
                    @foreach ([
                        'property.specs.reference' => $property->reference_code,
                        'property.operation.'.$property->operation_type->value => null,
                        'property.specs.land_area' => $property->land_area ? number_format($property->land_area, 0).' m²' : null,
                        'property.specs.floor' => $property->floor_level,
                        'property.specs.year_built' => $property->year_built,
                        'property.specs.maintenance' => $property->maintenance_fee
                            ? $property->currency->format($property->maintenance_fee) : null,
                        'property.specs.furnished' => $property->is_furnished ? __('properties.show.yes') : null,
                    ] as $etiqueta => $valor)
                        @continue(blank($valor))
                        <div class="flex justify-between border-b border-outline-variant/20 py-xs">
                            <dt class="text-body-md text-on-surface-variant">{{ __($etiqueta) }}</dt>
                            <dd class="text-body-md font-medium text-on-surface">{{ $valor }}</dd>
                        </div>
                    @endforeach

                    <div class="flex justify-between border-b border-outline-variant/20 py-xs">
                        <dt class="text-body-md text-on-surface-variant">{{ __('property.sections.location') }}</dt>
                        <dd class="text-body-md font-medium text-on-surface">{{ $property->locationLabel() }}</dd>
                    </div>
                </dl>
            </section>

            {{-- Video --}}
            @if ($property->video_url)
                <section class="rounded-xl bg-surface-container-lowest p-md ambient-shadow md:p-lg">
                    <h2 class="mb-md font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
                        {{ __('properties.show.video') }}
                    </h2>
                    <a href="{{ $property->video_url }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-xs text-label-md text-secondary hover:underline">
                        <span class="material-symbols-outlined">play_circle</span>
                        {{ __('properties.show.watch_video') }}
                    </a>
                </section>
            @endif
        </div>

        {{-- ==================== BARRA LATERAL ==================== --}}
        <aside class="lg:col-span-4">
            <div class="space-y-md lg:sticky lg:top-24">

                {{-- Contacto --}}
                <div class="rounded-xl border border-surface-variant bg-surface-container-lowest p-md ambient-shadow">
                    <h2 class="mb-xs text-title-lg text-on-surface">
                        {{ __('properties.show.interested') }}
                    </h2>
                    <p class="mb-md text-caption text-on-surface-variant">
                        {{ __('properties.show.interested_help') }}
                    </p>

                    @if ($property->status->acceptsLeads())
                        {{-- El formulario se conecta en la Fase 5 --}}
                        <div class="space-y-sm">
                            @if (session('lead_success'))
                                <div role="status" class="rounded-lg bg-tertiary-fixed p-sm text-body-md text-on-tertiary-fixed">{{ session('lead_success') }}</div>
                            @endif
                            @if ($errors->any())
                                <div role="alert" class="rounded-lg bg-error-container p-sm text-body-md text-on-error-container">{{ $errors->first() }}</div>
                            @endif
                            <form method="POST" action="{{ lroute('properties.inquiry', ['slug' => $property->slug]) }}" class="space-y-xs">
                                @csrf
                                <input type="hidden" name="form_token" value="{{ app(\App\Modules\Leads\Services\LeadService::class)->formToken() }}">
                                <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
                                <label class="grid gap-1 text-label-md"><span>{{ __('leads.fields.name') }} *</span><input name="name" required value="{{ old('name') }}" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"></label>
                                <label class="grid gap-1 text-label-md"><span>{{ __('leads.fields.phone') }} *</span><input type="tel" name="phone" required value="{{ old('phone') }}" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"></label>
                                <label class="grid gap-1 text-label-md"><span>{{ __('leads.fields.email') }}</span><input type="email" name="email" value="{{ old('email') }}" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"></label>
                                <label class="grid gap-1 text-label-md"><span>{{ __('contact.message') }} *</span><textarea name="message" required rows="4" class="rounded-lg border border-outline-variant bg-surface px-sm py-xs">{{ old('message', current_locale() === 'es' ? "Quiero informaci\u{00F3}n sobre ".$property->reference_code : 'I would like information about '.$property->reference_code) }}</textarea></label>
                                <button type="submit" class="flex w-full items-center justify-center gap-xs rounded-lg bg-primary-container px-md py-sm text-label-md font-semibold text-on-primary">
                                    <span class="material-symbols-outlined text-[20px]">send</span>{{ __('contact.send') }}
                                </button>
                            </form>

                            @php
                                $mensajeWa = whatsapp()->propertyMessage([
                                    'reference_code' => $property->reference_code,
                                    'title' => $property->title,
                                    'price' => $property->formattedPrice(),
                                    'location' => $property->locationLabel(),
                                    'url' => url()->current(),
                                ]);
                                $enlaceWa = whatsapp()->link(
                                    $property->agent?->whatsapp ?: null,
                                    $mensajeWa
                                );
                            @endphp

                            @if ($enlaceWa)
                                <a href="{{ $enlaceWa }}" target="_blank" rel="noopener noreferrer"
                                   data-whatsapp-source="property_detail"
                                   data-whatsapp-property="{{ $property->id }}"
                                   data-touch-target
                                   class="flex w-full items-center justify-center gap-xs rounded-lg
                                          bg-whatsapp px-md py-sm text-label-md font-semibold text-white
                                          transition-transform hover:scale-[1.02]">
                                    <span class="material-symbols-outlined text-[20px]">chat</span>
                                    {{ __('properties.show.whatsapp_cta') }}
                                </a>
                            @endif

                            @if (setting('contact_phone'))
                                <a href="tel:{{ preg_replace('/\D+/', '', setting('contact_phone')) }}"
                                   data-touch-target
                                   class="flex w-full items-center justify-center gap-xs rounded-lg
                                          border border-outline-variant px-md py-sm text-label-md
                                          text-on-surface transition-colors hover:bg-surface-container-low">
                                    <span class="material-symbols-outlined text-[20px]">call</span>
                                    {{ setting('contact_phone') }}
                                </a>
                            @endif
                        </div>
                    @else
                        <p class="rounded-lg bg-surface-container-low p-sm text-body-md text-on-surface-variant">
                            {{ __('properties.show.not_available_notice') }}
                        </p>
                        <a href="{{ lroute('properties.index') }}"
                           class="mt-sm flex w-full items-center justify-center gap-xs rounded-lg
                                  bg-primary-container px-md py-sm text-label-md font-semibold text-on-primary">
                            {{ __('properties.show.see_similar') }}
                        </a>
                    @endif
                </div>

                {{-- Agente --}}
                @if ($property->agent)
                    <div class="flex items-center gap-md rounded-xl border border-surface-variant
                                bg-surface-container-lowest p-md ambient-shadow">
                        @if ($property->agent->photoUrl())
                            <img src="{{ $property->agent->photoUrl() }}"
                                 alt="{{ $property->agent->name }}"
                                 width="64" height="64" loading="lazy"
                                 class="size-16 rounded-full object-cover">
                        @else
                            <span class="flex size-16 items-center justify-center rounded-full
                                         bg-primary-container text-title-lg text-on-primary">
                                {{ Str::upper(Str::substr($property->agent->name, 0, 1)) }}
                            </span>
                        @endif

                        <div>
                            <p class="mb-1 text-caption uppercase tracking-wider text-on-surface-variant">
                                {{ __('properties.show.your_agent') }}
                            </p>
                            <p class="text-title-lg text-on-surface">{{ $property->agent->name }}</p>
                            @if ($property->agent->position)
                                <p class="text-label-md text-secondary">{{ $property->agent->position }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </aside>
    </div>

    {{-- Similares --}}
    @if ($similar->isNotEmpty())
        <section class="mt-xl">
            <h2 class="mb-lg font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
                {{ __('property.sections.similar') }}
            </h2>
            <div class="grid grid-cols-1 gap-gutter md:grid-cols-2 lg:grid-cols-3">
                @foreach ($similar as $otra)
                    <x-property-card :property="$otra" />
                @endforeach
            </div>
        </section>
    @endif
</div>

{{-- Barra fija de contacto en móvil, como en el diseño --}}
@if ($property->status->acceptsLeads())
    <div class="fixed inset-x-0 bottom-0 z-40 flex gap-xs border-t border-outline-variant/40
                bg-surface-container-lowest/95 p-xs backdrop-blur lg:hidden">
        @if (setting('contact_phone'))
            <a href="tel:{{ preg_replace('/\D+/', '', setting('contact_phone')) }}"
               data-touch-target
               class="flex flex-1 items-center justify-center gap-xs rounded-lg border
                      border-outline-variant py-xs text-label-md text-on-surface">
                <span class="material-symbols-outlined text-[20px]">call</span>
                {{ __('common.actions.call') }}
            </a>
        @endif

        @if ($enlaceWa ?? null)
            <a href="{{ $enlaceWa }}" target="_blank" rel="noopener noreferrer"
               data-touch-target
               data-whatsapp-source="property_mobile_bar"
               data-whatsapp-property="{{ $property->id }}"
               class="flex flex-1 items-center justify-center gap-xs rounded-lg bg-whatsapp
                      py-xs text-label-md font-semibold text-white">
                <span class="material-symbols-outlined text-[20px]">chat</span>
                {{ __('common.actions.whatsapp') }}
            </a>
        @endif
    </div>

    {{-- Espacio para que la barra fija no tape el pie --}}
    <div class="h-16 lg:hidden" aria-hidden="true"></div>
@endif

@endsection
