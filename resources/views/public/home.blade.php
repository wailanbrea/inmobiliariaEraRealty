@extends('layouts.public')

@section('title', ($hero?->title ?: __('home.hero.title')) . ' · ' . setting('site_name'))
@section('description', $hero?->subtitle ?: __('home.hero.subtitle'))

@section('content')

{{-- ============================ HERO ============================ --}}
{{-- Traducido de inicio_era_realty_rd: 85vh, imagen de fondo al 60 % de
     opacidad sobre primary, degradado desde abajo y buscador de cristal.
     El parallax y el Ken Burns llegan en la Fase 8. --}}
<section class="relative flex min-h-[600px] w-full flex-col items-center justify-center
                px-margin-mobile md:px-gutter"
         style="height: 85vh">

    <div class="absolute inset-0 z-0 bg-primary">
        @if ($hero?->imageUrl())
            <div class="size-full bg-cover bg-center opacity-60"
                 style="background-image: url('{{ $hero->imageUrl() }}')"
                 role="img" aria-label="{{ $hero->title }}"></div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-primary/80 via-primary/30 to-transparent"></div>
    </div>

    <div class="relative z-10 mx-auto flex w-full max-w-container-max flex-col items-center text-center">
        <h1 class="mb-sm max-w-4xl font-display text-display-lg-mobile text-on-primary
                   drop-shadow-lg md:text-display-lg">
            {{ $hero?->title ?: __('home.hero.title') }}
        </h1>

        <p class="mb-lg max-w-2xl font-body text-body-lg text-on-primary/90 drop-shadow-md">
            {{ $hero?->subtitle ?: __('home.hero.subtitle') }}
        </p>

        <x-search-bar class="max-w-4xl" />
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

        <div class="grid grid-cols-1 gap-gutter md:grid-cols-2 lg:grid-cols-3">
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
            <dl class="grid grid-cols-2 gap-sm text-center md:grid-cols-4">
                @foreach ($stats->extra_json as $dato)
                    <div class="rounded-xl bg-surface-container-lowest p-md ambient-shadow">
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

        <div class="grid grid-cols-1 gap-gutter md:grid-cols-2 lg:grid-cols-3">
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
