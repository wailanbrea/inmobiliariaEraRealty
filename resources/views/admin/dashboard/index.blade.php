@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $tarjetas = [
        ['leads',      'contact_page', 'var(--color-chart-leads)'],
        ['whatsapp',   'chat',         'var(--color-chart-whatsapp)'],
        ['visitas',    'visibility',   'var(--color-chart-views)'],
        ['publicadas', 'home_work',    'var(--color-primary)'],
    ];

    // Lo que reclama atención hoy. Se muestra solo si hay algo: una tarjeta
    // que dice «0 pendientes» ocupa sitio y no aporta nada.
    $avisos = collect([
        ['leads_nuevos', 'contact_page', 'admin.leads.index', 'Leads sin atender'],
        ['borradores',   'edit_note',    'admin.properties.index', 'Propiedades en borrador'],
        ['sin_fotos',    'no_photography', 'admin.properties.index', 'Propiedades sin fotos'],
    ])->filter(fn ($a) => ($inventario[$a[0]] ?? 0) > 0);
@endphp

{{-- ========================== AVISOS ========================== --}}
@if ($avisos->isNotEmpty())
    <div class="mb-md grid grid-cols-1 gap-sm sm:grid-cols-3">
        @foreach ($avisos as [$clave, $icono, $ruta, $etiqueta])
            <a href="{{ route($ruta) }}" data-touch-target
               class="flex items-center gap-sm rounded-xl border border-tertiary-fixed/50
                      bg-tertiary-fixed/15 p-sm transition-colors hover:bg-tertiary-fixed/25">
                <span class="material-symbols-outlined text-[28px] text-on-tertiary-fixed">{{ $icono }}</span>
                <div>
                    <p class="font-heading text-title-lg text-on-surface">{{ $inventario[$clave] }}</p>
                    <p class="text-caption text-on-surface-variant">{{ $etiqueta }}</p>
                </div>
            </a>
        @endforeach
    </div>
@endif

{{-- ========================= MÉTRICAS ========================= --}}
@if ($resumen)
    <div class="mb-xs flex flex-wrap items-baseline justify-between gap-xs">
        <h2 class="font-heading text-title-lg text-on-surface">Últimos 30 días</h2>
        <a href="{{ route('admin.reports.index') }}"
           class="flex items-center gap-base text-label-md text-secondary hover:underline">
            Ver reportes completos
            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-sm sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($tarjetas as [$clave, $icono, $color])
            @php $m = $resumen[$clave]; @endphp

            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm ambient-shadow">
                <div class="flex items-start justify-between gap-xs">
                    <p class="text-caption uppercase tracking-wider text-on-surface-variant">
                        {{ __('admin/reports.metrics.'.$clave) }}
                    </p>
                    <span class="material-symbols-outlined text-[20px] text-outline">{{ $icono }}</span>
                </div>

                <p class="mt-xs font-heading text-headline-md-mobile text-on-surface">
                    {{ number_format($m['valor'], 0, ',', '.') }}
                </p>

                @if ($m['variacion'] === null)
                    <p class="text-caption text-on-surface-variant">{{ __('admin/reports.metrics.no_previous') }}</p>
                @else
                    @php $sube = $m['variacion'] >= 0; @endphp
                    <p @class([
                        'flex items-center gap-1 text-caption',
                        'text-status-available' => $sube,
                        'text-error' => ! $sube,
                    ])>
                        <span class="material-symbols-outlined text-[16px]">
                            {{ $sube ? 'trending_up' : 'trending_down' }}
                        </span>
                        {{ $sube ? '+' : '' }}{{ number_format($m['variacion'], 1, ',', '.') }} %
                    </p>
                @endif

                <div class="mt-xs">
                    <x-admin.sparkline :puntos="$serie->pluck($clave)"
                                       :color="$color" :alto="36"
                                       aria-label="{{ __('admin/reports.metrics.'.$clave) }}" />
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="mt-md grid grid-cols-1 gap-sm lg:grid-cols-2">

    {{-- ======================= ÚLTIMOS LEADS ======================= --}}
    <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm ambient-shadow">
        <div class="mb-xs flex items-baseline justify-between gap-xs">
            <h2 class="font-heading text-title-lg text-on-surface">Últimos leads</h2>
            <a href="{{ route('admin.leads.index') }}" class="text-label-md text-secondary hover:underline">
                Ver todos
            </a>
        </div>

        @if ($ultimosLeads->isEmpty())
            <p class="py-md text-center text-body-md text-on-surface-variant">
                Todavía no hay leads.
            </p>
        @else
            <ul class="divide-y divide-outline-variant/30">
                @foreach ($ultimosLeads as $lead)
                    <li>
                        <a href="{{ route('admin.leads.show', $lead) }}"
                           class="flex items-center justify-between gap-sm py-xs transition-colors hover:text-secondary">
                            <span class="min-w-0">
                                <span class="block truncate text-body-md text-on-surface">{{ $lead->name }}</span>
                                <span class="block truncate text-caption text-on-surface-variant">
                                    {{ __('lead.source.'.$lead->source->value) }}
                                    @if ($lead->property) · {{ $lead->property->title }} @endif
                                </span>
                            </span>
                            <span class="shrink-0 text-caption text-on-surface-variant">
                                {{ $lead->created_at->diffForHumans(short: true) }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- ======================== MÁS VISTAS ======================== --}}
    @if ($masVistas !== null)
        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm ambient-shadow">
            <div class="mb-xs flex items-baseline justify-between gap-xs">
                <h2 class="font-heading text-title-lg text-on-surface">
                    {{ __('admin/reports.most_viewed.title') }}
                </h2>
                <a href="{{ route('admin.reports.index') }}" class="text-label-md text-secondary hover:underline">
                    Ver todas
                </a>
            </div>

            @if ($masVistas->isEmpty())
                <p class="py-md text-center text-body-md text-on-surface-variant">
                    {{ __('admin/reports.empty') }}
                </p>
            @else
                <ul class="divide-y divide-outline-variant/30">
                    @foreach ($masVistas as $fila)
                        <li class="flex items-center justify-between gap-sm py-xs">
                            <a href="{{ route('admin.properties.edit', $fila['property']) }}"
                               class="min-w-0 truncate text-body-md text-on-surface transition-colors hover:text-secondary">
                                {{ $fila['property']->title ?: 'Propiedad #'.$fila['property']->id }}
                            </a>
                            <span class="flex shrink-0 items-center gap-xs text-caption text-on-surface-variant">
                                <span class="material-symbols-outlined text-[16px]">visibility</span>
                                {{ number_format($fila['visitas'], 0, ',', '.') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</div>

{{-- ========================= INVENTARIO ========================= --}}
<div class="mt-md rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm ambient-shadow">
    <h2 class="mb-xs font-heading text-title-lg text-on-surface">{{ __('admin/reports.inventory.title') }}</h2>

    <dl class="grid grid-cols-2 gap-sm sm:grid-cols-4 xl:grid-cols-8">
        @foreach ($inventario as $clave => $valor)
            <div class="rounded-lg bg-surface-container-low p-xs text-center">
                <dd class="font-heading text-title-lg text-on-surface">{{ number_format($valor, 0, ',', '.') }}</dd>
                <dt class="text-caption text-on-surface-variant">{{ __('admin/reports.inventory.'.$clave) }}</dt>
            </div>
        @endforeach
    </dl>
</div>
@endsection
