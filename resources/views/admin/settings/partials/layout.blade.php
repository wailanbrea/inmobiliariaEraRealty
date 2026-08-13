@extends('admin.layouts.app')

@section('title', __('admin/settings.title'))

@section('content')

{{-- Pestanas --}}
<nav class="mb-md flex gap-1 overflow-x-auto border-b border-outline-variant/40"
     aria-label="{{ __('admin/settings.title') }}">
    @foreach ([
        'general'  => ['admin/settings.tabs.general',  'tune'],
        'whatsapp' => ['admin/settings.tabs.whatsapp', 'chat'],
        'mail'     => ['admin/settings.tabs.mail',     'mail'],
        'seo'      => ['admin/settings.tabs.seo',      'travel_explore'],
    ] as $key => [$label, $icon])
        @php $activa = $tab === $key; @endphp
        <a href="{{ route("admin.settings.{$key}") }}"
           @class([
               'flex shrink-0 items-center gap-xs whitespace-nowrap px-sm py-xs text-label-md transition-colors',
               'border-b-2 border-secondary font-semibold text-secondary' => $activa,
               'text-on-surface-variant hover:text-secondary' => ! $activa,
           ])
           @if ($activa) aria-current="page" @endif>
            <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
            {{ __($label) }}
        </a>
    @endforeach
</nav>

@if (session('status'))
    <div role="status"
         class="mb-md flex items-start gap-xs rounded-lg bg-tertiary-fixed px-sm py-xs text-on-tertiary-fixed">
        <span class="material-symbols-outlined text-[20px]">check_circle</span>
        <p class="text-body-md">{{ session('status') }}</p>
    </div>
@endif

@if ($errors->any())
    <div role="alert"
         class="mb-md rounded-lg bg-error-container px-sm py-xs text-on-error-container">
        <div class="flex items-start gap-xs">
            <span class="material-symbols-outlined text-[20px]">error</span>
            <div>
                <p class="text-body-md font-semibold">{{ $errors->first() }}</p>
                @if (session('mail_test_error'))
                    <p class="mt-1 font-mono text-caption">{{ session('mail_test_error') }}</p>
                @endif
            </div>
        </div>
    </div>
@endif

@yield('settings')

@endsection
