@extends('layouts.public')

@section('title', __('home.hero.title') . ' · ' . config('app.name'))
@section('description', __('home.hero.subtitle'))

@section('content')

{{-- Hero provisional. En la Fase 4 lleva la imagen de fondo con parallax,
     Ken Burns, entrada del titulo por lineas y el buscador de cristal
     conectado a datos reales. Ver docs/13_MOTION_AND_EFFECTS.md 3.2. --}}
<section class="relative flex min-h-[60vh] items-center justify-center bg-primary-container
                px-margin-mobile py-xl md:px-gutter">
    <div class="mx-auto w-full max-w-container-max text-center">
        <h1 class="mx-auto mb-sm max-w-4xl font-display text-display-lg-mobile
                   text-on-primary md:text-display-lg">
            {{ __('home.hero.title') }}
        </h1>
        <p class="mx-auto mb-lg max-w-2xl text-body-lg text-on-primary/90">
            {{ __('home.hero.subtitle') }}
        </p>

        <div class="flex flex-col items-center justify-center gap-sm sm:flex-row">
            <a href="{{ lroute('properties.index') }}"
               class="flex w-full items-center justify-center gap-xs rounded-lg bg-surface-container-lowest
                      px-md py-xs text-label-md font-semibold text-on-surface
                      transition-all hover-lift sm:w-auto">
                <span class="material-symbols-outlined text-[20px]">search</span>
                {{ __('common.actions.search') }}
            </a>
            <a href="{{ lroute('invest.index') }}"
               class="flex w-full items-center justify-center gap-xs rounded-lg border border-on-primary/40
                      px-md py-xs text-label-md text-on-primary transition-colors
                      hover:bg-on-primary/10 sm:w-auto">
                <span class="material-symbols-outlined text-[20px]">trending_up</span>
                {{ __('common.nav.invest') }}
            </a>
        </div>
    </div>
</section>

<section class="mx-auto max-w-container-max px-margin-mobile py-xl md:px-gutter">
    <h2 class="mb-xs font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
        {{ __('home.featured.title') }}
    </h2>
    <p class="mb-lg text-body-md text-on-surface-variant">
        {{ __('home.featured.subtitle') }}
    </p>

    <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <div class="flex items-start gap-sm">
            <span class="material-symbols-outlined text-[32px] text-secondary">construction</span>
            <p class="text-body-md text-on-surface-variant">
                {{ __('common.pending.body', ['phase' => 4]) }}
            </p>
        </div>
    </div>
</section>

@endsection
