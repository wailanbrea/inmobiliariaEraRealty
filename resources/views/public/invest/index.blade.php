@extends('layouts.public')

@section('title', ($hero?->title ?: __('invest.title')) . ' · ' . setting('site_name'))
@section('description', $hero?->subtitle ?: __('invest.description'))

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
            <span class="text-on-secondary-container">{{ __('common.nav.invest') }}</span>
        </nav>

        <h1 class="max-w-3xl font-display text-display-lg-mobile text-on-primary md:text-display-lg">
            {{ $hero?->title ?: __('invest.title') }}
        </h1>

        @if ($hero?->subtitle)
            <p class="mt-sm max-w-2xl text-body-lg text-on-primary/90">
                {{ $hero->subtitle }}
            </p>
        @endif
    </div>
</section>

{{-- ======================= POR QUÉ INVERTIR ======================= --}}
@if ($whyInvest && $whyInvest->extra_json)
    <section class="mx-auto max-w-container-max px-margin-mobile py-xl md:px-gutter">
        <div class="mb-lg">
            <h2 class="mb-xs font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
                {{ $whyInvest->title }}
            </h2>
            @if ($whyInvest->subtitle)
                <p class="text-body-md text-on-surface-variant">{{ $whyInvest->subtitle }}</p>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-gutter md:grid-cols-2">
            @foreach ($whyInvest->extra_json as $bloque)
                <article class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest
                                p-md ambient-shadow hover-lift">
                    <span class="material-symbols-outlined mb-xs text-[32px] text-secondary">
                        {{ $bloque['icon'] ?? 'check_circle' }}
                    </span>

                    <h3 class="mb-xs text-title-lg text-on-surface">
                        {{ $bloque['title_'.current_locale()] ?? $bloque['title_es'] ?? '' }}
                    </h3>

                    <p class="text-body-md text-on-surface-variant">
                        {{ $bloque['text_'.current_locale()] ?? $bloque['text_es'] ?? '' }}
                    </p>
                </article>
            @endforeach
        </div>
    </section>
@endif

{{-- ======================= OPORTUNIDADES ======================= --}}
<section class="bg-surface-container-low py-xl">
    <div class="mx-auto max-w-container-max px-margin-mobile md:px-gutter">
        <div class="mb-lg flex flex-wrap items-end justify-between gap-sm">
            <div>
                <h2 class="mb-xs font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
                    {{ __('invest.opportunities') }}
                </h2>
                <p class="text-body-md text-on-surface-variant">
                    {{ __('invest.opportunities_subtitle') }}
                </p>
            </div>

            <a href="{{ lroute('properties.index', ['inversion' => 1]) }}"
               class="hidden items-center gap-base text-label-md text-secondary hover:underline md:flex">
                {{ __('common.actions.view_all') }}
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
        </div>

        @if ($properties->isEmpty())
            {{-- Estado vacío honesto: no se finge un catálogo que no existe. --}}
            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest
                        p-xl text-center ambient-shadow">
                <span class="material-symbols-outlined text-[48px] text-outline-variant">trending_up</span>
                <p class="mx-auto mt-sm max-w-lg text-body-md text-on-surface-variant">
                    {{ __('invest.no_opportunities') }}
                </p>
                <a href="{{ lroute('contact.index') }}"
                   class="mt-md inline-flex items-center gap-xs rounded-lg bg-primary-container px-md py-xs
                          text-label-md font-semibold text-on-primary transition-all hover-lift">
                    {{ __('common.actions.contact_us') }}
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 gap-gutter md:grid-cols-2 lg:grid-cols-3">
                @foreach ($properties as $property)
                    <x-property-card :property="$property" />
                @endforeach
            </div>

            <div class="mt-md text-center md:hidden">
                <a href="{{ lroute('properties.index', ['inversion' => 1]) }}"
                   class="block w-full rounded-lg border border-outline py-sm text-label-md text-on-surface">
                    {{ __('invest.view_all') }}
                </a>
            </div>
        @endif
    </div>
</section>

{{-- ========================== PROCESO ========================== --}}
@if ($process && $process->extra_json)
    <section class="mx-auto max-w-container-max px-margin-mobile py-xl md:px-gutter">
        <div class="mb-lg">
            <h2 class="mb-xs font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
                {{ $process->title }}
            </h2>
            @if ($process->subtitle)
                <p class="text-body-md text-on-surface-variant">{{ $process->subtitle }}</p>
            @endif
        </div>

        {{-- Línea temporal. La línea se dibuja progresivamente en la Fase 8. --}}
        <ol class="relative space-y-md border-l-2 border-outline-variant/40 pl-md md:pl-lg">
            @foreach ($process->extra_json as $indice => $paso)
                <li class="relative">
                    <span class="absolute -left-[calc(theme(spacing.md)+9px)] flex size-5 items-center
                                 justify-center rounded-full bg-secondary text-caption font-bold
                                 text-on-secondary md:-left-[calc(theme(spacing.lg)+9px)]">
                        {{ $indice + 1 }}
                    </span>

                    <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest
                                p-md ambient-shadow">
                        <p class="mb-1 text-caption uppercase tracking-wider text-secondary">
                            {{ __('invest.step', ['n' => $indice + 1]) }}
                        </p>
                        <h3 class="mb-xs text-title-lg text-on-surface">
                            {{ $paso['title_'.current_locale()] ?? $paso['title_es'] ?? '' }}
                        </h3>
                        <p class="text-body-md text-on-surface-variant">
                            {{ $paso['text_'.current_locale()] ?? $paso['text_es'] ?? '' }}
                        </p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>
@endif

{{-- ========================= AVISO LEGAL ========================= --}}
@if ($disclaimer?->content)
    <section class="mx-auto max-w-container-max px-margin-mobile pb-xl md:px-gutter">
        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-low p-md">
            <p class="mb-xs flex items-center gap-xs text-label-md font-semibold text-on-surface">
                <span class="material-symbols-outlined text-[20px] text-on-surface-variant">info</span>
                {{ __('invest.legal_notice') }}
            </p>
            <p class="text-body-md text-on-surface-variant">{{ $disclaimer->content }}</p>
        </div>
    </section>
@endif

<section class="mx-auto max-w-3xl px-margin-mobile pb-xl md:px-gutter">
    <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow md:p-lg">
        <h2 class="mb-xs font-heading text-headline-md-mobile md:text-headline-md">{{ current_locale() === 'es' ? "Hablemos de tu inversi\u{00F3}n" : 'Tell us about your investment' }}</h2>
        <p class="mb-md text-body-md text-on-surface-variant">{{ current_locale() === 'es' ? "Comparte tu objetivo y presupuesto para preparar una selecci\u{00F3}n realista." : 'Share your goal and budget so we can prepare a realistic shortlist.' }}</p>
        @if (session('lead_success'))<div role="status" class="mb-sm rounded-lg bg-tertiary-fixed p-sm">{{ session('lead_success') }}</div>@endif
        @if ($errors->any())<div role="alert" class="mb-sm rounded-lg bg-error-container p-sm text-on-error-container">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ lroute('invest.store') }}" class="grid gap-sm md:grid-cols-2">
            @csrf
            <input type="hidden" name="form_token" value="{{ app(\App\Modules\Leads\Services\LeadService::class)->formToken() }}">
            <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
            <label class="grid gap-1 text-label-md"><span>{{ __('leads.fields.name') }} *</span><input name="name" required value="{{ old('name') }}" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"></label>
            <label class="grid gap-1 text-label-md"><span>{{ __('leads.fields.phone') }} *</span><input type="tel" name="phone" required value="{{ old('phone') }}" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"></label>
            <label class="grid gap-1 text-label-md"><span>{{ __('leads.fields.email') }}</span><input type="email" name="email" value="{{ old('email') }}" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"></label>
            <label class="grid gap-1 text-label-md"><span>{{ current_locale() === 'es' ? 'Presupuesto aproximado' : 'Approximate budget' }}</span><input name="budget_range" value="{{ old('budget_range') }}" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"></label>
            <label class="grid gap-1 text-label-md"><span>{{ __('contact.preferred') }} *</span><select name="preferred_contact" required class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm">@foreach(__('contact.channels') as $value => $label)<option value="{{ $value }}" @selected(old('preferred_contact') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-label-md md:col-span-2"><span>{{ __('contact.message') }} *</span><textarea name="message" required rows="5" class="rounded-lg border border-outline-variant bg-surface px-sm py-xs">{{ old('message') }}</textarea></label>
            <button class="rounded-lg bg-primary-container px-md py-sm text-label-md font-semibold text-on-primary md:col-span-2">{{ __('contact.send') }}</button>
        </form>
    </div>
</section>

{{-- ============================ CTA ============================ --}}
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

                @php
                    // Mensaje específico de inversión, no el general.
                    $enlaceWa = whatsapp()->link(null, whatsapp()->investmentMessage());
                @endphp

                @if ($enlaceWa)
                    <a href="{{ $enlaceWa }}" target="_blank" rel="noopener noreferrer"
                       data-touch-target
                       class="flex w-full items-center justify-center gap-xs rounded-lg bg-whatsapp
                              px-md py-xs text-label-md font-semibold text-white
                              transition-transform hover:scale-105 sm:w-auto">
                        <span class="material-symbols-outlined text-[20px]">chat</span>
                        {{ __('invest.whatsapp_cta') }}
                    </a>
                @endif
            </div>
        </div>
    </section>
@endif

@endsection
