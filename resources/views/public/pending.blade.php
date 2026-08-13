@extends('layouts.public')

@section('title', __($titleKey) . ' · ' . config('app.name'))

@section('content')

<section class="mx-auto max-w-container-max px-margin-mobile py-xl md:px-gutter">

    <nav aria-label="breadcrumb" class="mb-md text-caption text-on-surface-variant">
        <a href="{{ lroute('home') }}" class="transition-colors hover:text-secondary">
            {{ __('common.nav.home') }}
        </a>
        <span class="mx-1">/</span>
        <span class="text-on-surface">{{ __($titleKey) }}</span>
    </nav>

    <h1 class="mb-md font-heading text-headline-md-mobile text-on-surface md:text-headline-md">
        {{ __($titleKey) }}
    </h1>

    <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <div class="flex items-start gap-sm">
            <span class="material-symbols-outlined text-[32px] text-secondary">construction</span>
            <div>
                <h2 class="font-heading text-title-lg text-on-surface">{{ __('common.pending.title') }}</h2>
                <p class="mt-base text-body-md text-on-surface-variant">
                    {{ __('common.pending.body', ['phase' => $phase]) }}
                </p>
                @if ($slug)
                    <p class="mt-xs text-caption text-on-surface-variant">
                        {{ __('common.pending.reference', ['slug' => $slug]) }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    <a href="{{ lroute('home') }}"
       class="mt-md inline-flex items-center gap-xs rounded-lg border border-outline-variant
              px-sm py-xs text-label-md text-on-surface transition-colors hover:bg-surface-container-low">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        {{ __('common.actions.back_home') }}
    </a>

</section>

@endsection
