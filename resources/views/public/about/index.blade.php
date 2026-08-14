@extends('layouts.public')

@section('title', ($hero?->title ?: __('about.title')) . ' · ' . setting('site_name'))
@section('description', $hero?->subtitle ?: __('about.description'))

@section('content')

{{-- ============================ HERO ============================ --}}
<section class="relative bg-primary-container py-xl">
    @if ($hero?->imageUrl())
        <div class="absolute inset-0 bg-cover bg-center opacity-40"
             style="background-image: url('{{ $hero->imageUrl() }}')"
             role="img" aria-label="{{ $hero->title }}"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-primary-container to-transparent"></div>
    @endif

    <div class="relative mx-auto max-w-container-max px-margin-mobile md:px-gutter">
        <nav aria-label="breadcrumb" class="mb-sm text-caption text-on-primary-container">
            <a href="{{ lroute('home') }}" class="transition-opacity hover:opacity-80">
                {{ __('common.nav.home') }}
            </a>
            <span class="mx-1">/</span>
            <span class="text-on-secondary-container">{{ __('common.nav.about') }}</span>
        </nav>

        <h1 class="max-w-3xl font-display text-display-lg-mobile text-on-primary md:text-display-lg">
            {{ $hero?->title ?: __('about.title') }}
        </h1>

        @if ($hero?->subtitle)
            <p class="mt-sm max-w-2xl text-body-lg text-on-primary/90">{{ $hero->subtitle }}</p>
        @endif
    </div>
</section>

{{-- ========================== HISTORIA ========================== --}}
@if ($story?->content)
    <section class="mx-auto max-w-container-max px-margin-mobile py-xl md:px-gutter">
        <div class="grid grid-cols-1 gap-gutter lg:grid-cols-12">
            <div class="lg:col-span-7">
                <h2 class="mb-md font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
                    {{ $story->title }}
                </h2>

                <div class="space-y-4 text-body-lg text-on-surface-variant">
                    @foreach (preg_split('/\n\s*\n/', trim($story->content)) as $parrafo)
                        <p>{{ $parrafo }}</p>
                    @endforeach
                </div>
            </div>

            {{-- Cifras contadas de la base de datos, no inventadas --}}
            <div class="lg:col-span-5">
                <dl class="grid grid-cols-2 gap-sm">
                    @foreach ([
                        ['published', $stats['published'], 'home_work'],
                        ['closed', $stats['sold'], 'handshake'],
                    ] as [$clave, $valor, $icono])
                        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest
                                    p-md text-center ambient-shadow">
                            <span class="material-symbols-outlined mb-xs text-[28px] text-secondary">
                                {{ $icono }}
                            </span>
                            <dd class="font-heading text-headline-md-mobile text-on-surface">
                                {{ number_format($valor, 0) }}
                            </dd>
                            <dt class="mt-base text-caption uppercase tracking-wider text-on-surface-variant">
                                {{ __("about.stats.{$clave}") }}
                            </dt>
                        </div>
                    @endforeach
                </dl>

                @if ($story?->imageUrl())
                    <img src="{{ $story->imageUrl() }}" alt="{{ $story->title }}"
                         loading="lazy" decoding="async"
                         class="mt-sm w-full rounded-xl object-cover ambient-shadow">
                @endif
            </div>
        </div>
    </section>
@endif

{{-- =========================== VALORES =========================== --}}
@if ($values && $values->extra_json)
    <section class="bg-surface-container-low py-xl">
        <div class="mx-auto max-w-container-max px-margin-mobile md:px-gutter">
            <div class="mb-lg">
                <h2 class="mb-xs font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
                    {{ $values->title }}
                </h2>
                @if ($values->subtitle)
                    <p class="text-body-md text-on-surface-variant">{{ $values->subtitle }}</p>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-gutter md:grid-cols-3">
                @foreach ($values->extra_json as $valor)
                    <article class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest
                                    p-md ambient-shadow hover-lift">
                        <span class="material-symbols-outlined mb-xs text-[32px] text-secondary">
                            {{ $valor['icon'] ?? 'check_circle' }}
                        </span>
                        <h3 class="mb-xs text-title-lg text-on-surface">
                            {{ $valor['title_'.current_locale()] ?? $valor['title_es'] ?? '' }}
                        </h3>
                        <p class="text-body-md text-on-surface-variant">
                            {{ $valor['text_'.current_locale()] ?? $valor['text_es'] ?? '' }}
                        </p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ============================ EQUIPO ============================ --}}
{{-- Solo si hay agentes activos: una sección "El equipo" vacía queda peor
     que no tenerla. Los agentes se gestionan en la Fase 7. --}}
@if ($team->isNotEmpty())
    <section class="mx-auto max-w-container-max px-margin-mobile py-xl md:px-gutter">
        <div class="mb-lg">
            <h2 class="mb-xs font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
                {{ $teamSection?->title ?: __('about.title') }}
            </h2>
            @if ($teamSection?->subtitle)
                <p class="text-body-md text-on-surface-variant">{{ $teamSection->subtitle }}</p>
            @endif
        </div>

        <ul class="grid grid-cols-1 gap-gutter sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($team as $agente)
                <li class="overflow-hidden rounded-xl border border-outline-variant/40
                           bg-surface-container-lowest ambient-shadow hover-lift">

                    <div class="aspect-square overflow-hidden bg-surface-container">
                        @if ($agente->photoUrl())
                            <img src="{{ $agente->photoUrl() }}" alt="{{ $agente->name }}"
                                 loading="lazy" decoding="async"
                                 class="size-full object-cover">
                        @else
                            <div class="flex size-full items-center justify-center bg-primary-container">
                                <span class="font-heading text-headline-md text-on-primary">
                                    {{ Str::upper(Str::substr($agente->name, 0, 1)) }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="p-sm">
                        <h3 class="text-title-lg text-on-surface">{{ $agente->name }}</h3>

                        @if ($agente->position)
                            <p class="text-label-md text-secondary">{{ $agente->position }}</p>
                        @endif

                        @if ($agente->bio)
                            <p class="mt-xs text-body-md text-on-surface-variant">{{ $agente->bio }}</p>
                        @endif

                        <div class="mt-sm flex flex-wrap gap-xs">
                            @if ($enlaceWa = $agente->whatsappLink(whatsapp()->generalMessage()))
                                <a href="{{ $enlaceWa }}" target="_blank" rel="noopener noreferrer"
                                   data-touch-target
                                   aria-label="WhatsApp {{ $agente->name }}"
                                   class="flex items-center gap-xs rounded-lg bg-whatsapp px-sm py-xs
                                          text-caption font-semibold text-white">
                                    <span class="material-symbols-outlined text-[16px]">chat</span>
                                    WhatsApp
                                </a>
                            @endif

                            @if ($agente->email)
                                <a href="mailto:{{ $agente->email }}"
                                   data-touch-target
                                   aria-label="{{ __('about.contact_agent') }} {{ $agente->name }}"
                                   class="flex items-center gap-xs rounded-lg border border-outline-variant
                                          px-sm py-xs text-caption text-on-surface">
                                    <span class="material-symbols-outlined text-[16px]">mail</span>
                                    {{ __('about.contact_agent') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endif

{{-- ============================= CTA ============================= --}}
@if ($cta)
    <section class="bg-primary-container py-xl">
        <div class="mx-auto max-w-container-max px-margin-mobile text-center md:px-gutter">
            <h2 class="mb-xs font-heading text-headline-md-mobile text-on-secondary-container md:text-headline-md">
                {{ $cta->title }}
            </h2>

            @if ($cta->subtitle)
                <p class="mx-auto mb-md max-w-2xl text-body-lg text-on-primary-container">
                    {{ $cta->subtitle }}
                </p>
            @endif

            <div class="flex flex-col items-center justify-center gap-sm sm:flex-row">
                <a href="{{ $cta->button_url ?: lroute('contact.index') }}"
                   data-touch-target
                   class="flex w-full items-center justify-center gap-xs rounded-lg
                          bg-surface-container-lowest px-md py-xs text-label-md font-semibold
                          text-on-surface transition-all hover-lift sm:w-auto">
                    <span class="material-symbols-outlined text-[20px]">mail</span>
                    {{ $cta->button_text ?: __('common.actions.contact_us') }}
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
