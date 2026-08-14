@extends('layouts.public')

@section('title', ($hero?->title ?: __('home.hero.title')) . ' · ' . setting('site_name'))
@section('description', $hero?->subtitle ?: __('home.hero.subtitle'))

@section('content')

{{-- ============================ HERO ============================ --}}
{{-- Traducido de inicio_era_realty_rd: 85vh, imagen de fondo al 60 % de
     opacidad sobre primary, degradado desde abajo y buscador de cristal.

     Parallax de TRES capas (el limite que fija docs/13): el fondo baja un
     30 %, el texto sube un 15 % y el buscador sube un 5 %. Esa diferencia de
     velocidades es lo que genera la sensacion de profundidad.

     El fondo se agranda un 15 % y se sube un 7,5 % para que al desplazarse no
     asome su borde inferior. Sin ese margen se veria la franja del `primary`
     por debajo de la foto. --}}
<section data-parallax-scene
         class="relative flex min-h-[600px] w-full flex-col items-center justify-center
                overflow-hidden px-margin-mobile md:px-gutter"
         style="height: 85vh">

    {{-- Las cinco capas del fondo, de atrás hacia delante. El detalle de cada
         una está en resources/css/motion.css, sección «Hero cinematográfico». --}}
    <div class="absolute inset-0 z-0 overflow-hidden bg-primary">

        {{-- 1 · Foto: Ken Burns dentro, parallax fuera, cortina al cargar --}}
        @if ($hero?->imageUrl())
            {{-- 92 % y no 60 %: el contraste del texto lo resuelve el velo
                 central (capa 3.b), que cobra solo donde hay letras. Apagar la
                 foto entera para proteger el centro se paga con el sol y la
                 villa iluminada, que es justo lo que se quiere lucir. --}}
            <div data-parallax="30" data-hero-curtain
                 class="hero-curtain absolute -top-[7.5%] left-0 h-[115%] w-full">
                <div data-ken-burns
                     class="size-full bg-cover bg-center opacity-[0.92]"
                     style="background-image: url('{{ $hero->imageUrl() }}')"
                     role="img" aria-label="{{ $hero->title }}"></div>
            </div>
        @endif

        {{-- 2 · Aurora. Se pinta SIEMPRE, haya foto o no: mientras no se suba
             la portada es lo único que separa el hero de un rectángulo azul
             plano, y con foto le añade profundidad de color. --}}
        <div class="hero-aurora" aria-hidden="true"></div>

        {{-- 3 · Grano de película --}}
        <div class="hero-grain" aria-hidden="true"></div>

        {{-- 3.b · Velo de legibilidad: oscurece el centro, no la foto entera --}}
        <div class="hero-scrim" aria-hidden="true"></div>

        {{-- Degradado del diseño original: asienta el hero sobre lo que sigue --}}
        <div class="absolute inset-0 bg-gradient-to-t from-primary/70 via-transparent to-primary/25"></div>

        {{-- 3.c · Barrido de luz: la animación que mantiene vivo el hero en
             cualquier momento, no solo al cargar --}}
        <div class="hero-sweep" aria-hidden="true"></div>

        {{-- 4 · Viñeta --}}
        <div class="hero-vignette" aria-hidden="true"></div>

        {{-- 5 · Foco del cursor (solo escritorio con puntero fino) --}}
        <div class="hero-spotlight" data-hero-spotlight aria-hidden="true"></div>
    </div>

    <div data-hero-content
         class="relative z-10 mx-auto flex w-full max-w-container-max flex-col items-center text-center">

        {{-- La máscara por línea es lo que hace lucir a Playfair Display: el
             título sube desde debajo de un borde recto en vez de aparecer.
             El brillo lo recorre una sola vez, 900 ms después. --}}
        <h1 class="line-mask mb-sm max-w-4xl" data-parallax="-15">
            <span data-hero-shine
                  class="hero-shine block font-display text-display-lg-mobile
                         text-on-primary drop-shadow-lg md:text-display-lg">
                {{ $hero?->title ?: __('home.hero.title') }}
            </span>
        </h1>

        <p class="mb-lg max-w-2xl font-body text-body-lg text-on-primary/90 drop-shadow-md"
           data-parallax="-15">
            {{ $hero?->subtitle ?: __('home.hero.subtitle') }}
        </p>

        <x-search-bar class="max-w-4xl" data-parallax="-5" />
    </div>
</section>

{{-- ==================== PROPIEDADES DESTACADAS ==================== --}}
@if ($featured->isNotEmpty())
    <section class="mx-auto max-w-container-max px-margin-mobile py-xl md:px-gutter">
        <div class="mb-lg flex items-end justify-between">
            <div>
                <h2 class="mb-xs font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
                    {{ $featuredSection?->title ?: __('home.featured.title') }}
                </h2>
                <p class="font-body text-body-md text-on-surface-variant">
                    {{ $featuredSection?->subtitle ?: __('home.featured.subtitle') }}
                </p>
            </div>

            <a href="{{ lroute('properties.index') }}"
               class="hidden items-center gap-base text-label-md text-secondary hover:underline md:flex">
                {{ __('common.actions.view_all') }}
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
        </div>

        <div data-reveal-group="100"
             class="grid grid-cols-1 gap-gutter md:grid-cols-2 lg:grid-cols-3">
            @foreach ($featured as $index => $property)
                <x-property-card :property="$property" :eager="$index < 3" />
            @endforeach
        </div>

        <div class="mt-sm text-center md:hidden">
            <a href="{{ lroute('properties.index') }}"
               class="block w-full rounded-lg border border-outline py-sm text-label-md
                      text-on-surface transition-colors hover:bg-surface-container-low">
                {{ __('home.featured.view_all_mobile') }}
            </a>
        </div>
    </section>
@endif

{{-- ========================= ESTADÍSTICAS ========================= --}}
@if ($stats && $stats->extra_json)
    <section class="bg-surface-container-low py-lg">
        <div class="mx-auto max-w-container-max px-margin-mobile md:px-gutter">
            <dl data-reveal-group="80" class="grid grid-cols-2 gap-sm text-center md:grid-cols-4">
                @foreach ($stats->extra_json as $dato)
                    <div data-reveal class="rounded-xl bg-surface-container-lowest p-md ambient-shadow">
                        <dd class="font-heading text-headline-md-mobile text-secondary md:text-headline-md"
                            data-counter="{{ $dato['value'] ?? 0 }}">
                            {{ $dato['value'] ?? '' }}{{ $dato['suffix'] ?? '' }}
                        </dd>
                        <dt class="mt-base text-caption uppercase tracking-wider text-on-surface-variant">
                            {{ $dato['label_'.current_locale()] ?? $dato['label_es'] ?? '' }}
                        </dt>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>
@endif

{{-- ======================== INVERSIÓN ======================== --}}
@if ($investment->isNotEmpty())
    <section class="mx-auto max-w-container-max px-margin-mobile py-xl md:px-gutter">
        <div class="mb-lg">
            <h2 class="mb-xs font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
                {{ $investmentSection?->title ?: __('home.investment.title') }}
            </h2>
            <p class="font-body text-body-md text-on-surface-variant">
                {{ $investmentSection?->subtitle ?: __('home.investment.subtitle') }}
            </p>
        </div>

        <div data-reveal-group="100"
             class="grid grid-cols-1 gap-gutter md:grid-cols-2 lg:grid-cols-3">
            @foreach ($investment as $property)
                <x-property-card :property="$property" />
            @endforeach
        </div>

        <div class="mt-md text-center">
            <a href="{{ lroute('invest.index') }}"
               class="inline-flex items-center gap-xs rounded-lg border border-primary px-md py-xs
                      text-label-md text-primary transition-colors hover:bg-surface-container-low">
                <span class="material-symbols-outlined text-[18px]">trending_up</span>
                {{ $investmentSection?->button_text ?: __('common.nav.invest') }}
            </a>
        </div>
    </section>
@endif

{{-- ========================= CTA FINAL ========================= --}}
@if ($finalCta)
    <section class="bg-primary-container py-xl">
        <div class="mx-auto max-w-container-max px-margin-mobile text-center md:px-gutter">
            <h2 class="mb-xs font-heading text-headline-md-mobile text-on-secondary-container md:text-headline-md">
                {{ $finalCta->title }}
            </h2>

            @if ($finalCta->subtitle)
                <p class="mx-auto mb-md max-w-2xl text-body-lg text-on-primary-container">
                    {{ $finalCta->subtitle }}
                </p>
            @endif

            <div class="flex flex-col items-center justify-center gap-sm sm:flex-row">
                <a href="{{ $finalCta->button_url ?: lroute('contact.index') }}"
                   data-touch-target
                   class="flex w-full items-center justify-center gap-xs rounded-lg
                          bg-surface-container-lowest px-md py-xs text-label-md font-semibold
                          text-on-surface transition-all hover-lift sm:w-auto">
                    <span class="material-symbols-outlined text-[20px]">mail</span>
                    {{ $finalCta->button_text ?: __('common.actions.contact_us') }}
                </a>

                @if ($enlaceWhatsapp = whatsapp()->generalLink())
                    <a href="{{ $enlaceWhatsapp }}" target="_blank" rel="noopener noreferrer"
                       data-touch-target
                       class="flex w-full items-center justify-center gap-xs rounded-lg bg-whatsapp
                              px-md py-xs text-label-md font-semibold text-white
                              transition-transform hover:scale-105 sm:w-auto">
                        <span class="material-symbols-outlined text-[20px]">chat</span>
                        {{ __('common.actions.whatsapp') }}
                    </a>
                @endif
            </div>
        </div>
    </section>
@endif

@endsection
