@extends('admin.layouts.app')

@section('title', __('admin/reports.title'))

@section('content')
@php
    $inventario = app(App\Modules\Reports\Services\ReportService::class)->inventory();

    $tarjetas = [
        ['leads',      'contact_page', 'var(--color-chart-leads)'],
        ['whatsapp',   'chat',         'var(--color-chart-whatsapp)'],
        ['visitas',    'visibility',   'var(--color-chart-views)'],
        ['publicadas', 'home_work',    'var(--color-primary)'],
    ];

    $campo = 'h-11 w-full rounded-lg border border-outline-variant bg-surface-container-lowest
              px-sm text-body-md text-on-surface outline-none transition-shadow
              focus:border-secondary focus:ring-2 focus:ring-secondary';
@endphp

<p class="mb-md text-body-md text-on-surface-variant">{{ __('admin/reports.subtitle') }}</p>

{{-- ============================ RANGO ============================ --}}
<form method="GET" action="{{ route('admin.reports.index') }}"
      class="mb-md rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm ambient-shadow">

    <div class="grid grid-cols-1 gap-sm sm:grid-cols-2 lg:grid-cols-4">
        <label class="block">
            <span class="mb-1 block text-caption text-on-surface-variant">{{ __('admin/reports.range.from') }}</span>
            <input type="date" name="desde" value="{{ $desde->toDateString() }}"
                   max="{{ now()->toDateString() }}" class="{{ $campo }}">
        </label>

        <label class="block">
            <span class="mb-1 block text-caption text-on-surface-variant">{{ __('admin/reports.range.to') }}</span>
            <input type="date" name="hasta" value="{{ $hasta->toDateString() }}"
                   max="{{ now()->toDateString() }}" class="{{ $campo }}">
        </label>

        <div class="flex items-end gap-xs sm:col-span-2">
            <button type="submit"
                    class="h-11 rounded-lg bg-primary-container px-md text-label-md font-semibold
                           text-on-primary transition-all hover-lift">
                {{ __('admin/reports.range.apply') }}
            </button>

            <a href="{{ route('admin.reports.export', request()->only('desde', 'hasta')) }}"
               data-touch-target
               class="flex h-11 items-center gap-xs rounded-lg border border-outline-variant px-sm
                      text-label-md text-on-surface transition-colors hover:bg-surface-container-low">
                <span class="material-symbols-outlined text-[18px]">download</span>
                {{ __('admin/reports.range.export') }}
            </a>
        </div>
    </div>

    <div class="mt-sm flex flex-wrap items-center gap-xs">
        <span class="text-caption text-on-surface-variant">{{ __('admin/reports.range.presets') }}:</span>
        @foreach ([7 => 'last_7', 30 => 'last_30', 90 => 'last_90'] as $dias => $clave)
            <a href="{{ route('admin.reports.index', ['desde' => now()->subDays($dias - 1)->toDateString(), 'hasta' => now()->toDateString()]) }}"
               class="rounded-full border border-outline-variant px-xs py-1 text-caption
                      text-on-surface-variant transition-colors hover:border-secondary hover:text-secondary">
                {{ __('admin/reports.range.'.$clave) }}
            </a>
        @endforeach
    </div>
</form>

{{-- ========================== MÉTRICAS ========================== --}}
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
                    <span class="text-on-surface-variant">{{ __('admin/reports.metrics.vs_previous') }}</span>
                </p>
            @endif

            <div class="mt-xs">
                <x-admin.sparkline :puntos="$serie->pluck($clave)"
                                   :color="$color" :alto="40"
                                   aria-label="{{ __('admin/reports.metrics.'.$clave) }}" />
            </div>
        </div>
    @endforeach
</div>

{{-- ====================== ACTIVIDAD DIARIA ====================== --}}
<div class="mt-md rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm ambient-shadow">
    <div class="mb-xs flex flex-wrap items-baseline justify-between gap-xs">
        <h2 class="font-heading text-title-lg text-on-surface">{{ __('admin/reports.chart.title') }}</h2>
        <p class="text-caption text-on-surface-variant">
            {{ __('admin/reports.range.showing', ['desde' => $desde->format('d/m/Y'), 'hasta' => $hasta->format('d/m/Y')]) }}
        </p>
    </div>

    <div class="grid grid-cols-1 gap-sm md:grid-cols-3">
        @foreach ([['visitas', 'var(--color-chart-views)'], ['whatsapp', 'var(--color-chart-whatsapp)'], ['leads', 'var(--color-chart-leads)']] as [$clave, $color])
            <div>
                <p class="mb-1 flex items-center gap-xs text-caption text-on-surface-variant">
                    <span class="size-2 rounded-full" style="background-color: {{ $color }}"></span>
                    {{ __('admin/reports.chart.'.$clave) }}
                    <strong class="text-on-surface">{{ number_format($serie->sum($clave), 0, ',', '.') }}</strong>
                </p>
                <x-admin.sparkline :puntos="$serie->pluck($clave)" :color="$color" :alto="72"
                                   aria-label="{{ __('admin/reports.chart.'.$clave) }}" />
            </div>
        @endforeach
    </div>
</div>

{{-- ========================= MÁS VISTAS ========================= --}}
<div class="mt-md">
    <div class="mb-xs">
        <h2 class="font-heading text-title-lg text-on-surface">{{ __('admin/reports.most_viewed.title') }}</h2>
        <p class="text-caption text-on-surface-variant">{{ __('admin/reports.most_viewed.help') }}</p>
    </div>

    @if ($masVistas->isEmpty())
        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-lg text-center
                    text-body-md text-on-surface-variant">
            {{ __('admin/reports.empty') }}
        </div>
    @else
        <div class="table-scroll rounded-xl border border-outline-variant/40
                    bg-surface-container-lowest ambient-shadow">
            <table class="w-full min-w-[640px] text-left">
                <thead class="border-b border-outline-variant/40 text-caption uppercase
                              tracking-wider text-on-surface-variant">
                    <tr>
                        <th scope="col" class="px-sm py-xs">{{ __('admin/reports.most_viewed.property') }}</th>
                        <th scope="col" class="px-sm py-xs text-right">{{ __('admin/reports.most_viewed.views') }}</th>
                        <th scope="col" class="px-sm py-xs text-right">{{ __('admin/reports.most_viewed.whatsapp') }}</th>
                        <th scope="col" class="px-sm py-xs text-right">{{ __('admin/reports.most_viewed.leads') }}</th>
                        <th scope="col" class="px-sm py-xs text-right"
                            title="{{ __('admin/reports.most_viewed.conversion_help') }}">
                            {{ __('admin/reports.most_viewed.conversion') }}
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-outline-variant/30">
                    @foreach ($masVistas as $fila)
                        <tr class="transition-colors hover:bg-surface-container-low">
                            <td class="max-w-xs truncate px-sm py-xs text-body-md text-on-surface">
                                <a href="{{ route('admin.properties.edit', $fila['property']) }}"
                                   class="hover:text-secondary hover:underline">
                                    {{ $fila['property']->title ?: 'Propiedad #'.$fila['property']->id }}
                                </a>
                            </td>
                            <td class="px-sm py-xs text-right text-body-md text-on-surface">{{ number_format($fila['visitas'], 0, ',', '.') }}</td>
                            <td class="px-sm py-xs text-right text-body-md text-on-surface-variant">{{ $fila['whatsapp'] }}</td>
                            <td class="px-sm py-xs text-right text-body-md text-on-surface-variant">{{ $fila['leads'] }}</td>
                            <td @class([
                                'px-sm py-xs text-right text-body-md font-semibold',
                                'text-status-available' => $fila['conversion'] >= 2,
                                'text-on-surface-variant' => $fila['conversion'] < 2,
                            ])>
                                {{ number_format($fila['conversion'], 1, ',', '.') }} %
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ================== REPARTOS E INVENTARIO ================== --}}
<div class="mt-md grid grid-cols-1 gap-sm lg:grid-cols-3">

    @foreach ([
        [__('admin/reports.leads_by.source'), $leads['origen'], 'lead.source.'],
        [__('admin/reports.leads_by.status'), $leads['estado'], 'lead.status.'],
        [__('admin/reports.whatsapp_by_source'), $whatsapp, 'whatsapp.sources.'],
    ] as [$titulo, $datos, $prefijo])
        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm ambient-shadow">
            <h2 class="mb-xs font-heading text-title-lg text-on-surface">{{ $titulo }}</h2>

            @if ($datos->isEmpty())
                <p class="text-body-md text-on-surface-variant">{{ __('admin/reports.empty') }}</p>
            @else
                @php $mayor = max(1, $datos->max()); @endphp
                <dl class="space-y-xs">
                    @foreach ($datos as $clave => $total)
                        <div>
                            <div class="flex items-baseline justify-between gap-xs text-body-md">
                                <dt class="truncate text-on-surface-variant">
                                    {{ __($prefijo.$clave) === $prefijo.$clave ? $clave : __($prefijo.$clave) }}
                                </dt>
                                <dd class="font-semibold text-on-surface">{{ $total }}</dd>
                            </div>
                            {{-- Barra proporcional: el ancho ya comunica la
                                 magnitud, pero el número está siempre al lado
                                 porque el color y el tamaño no bastan. --}}
                            <div class="mt-1 h-1.5 rounded-full bg-surface-container">
                                <div class="h-full rounded-full bg-secondary"
                                     style="width: {{ round(($total / $mayor) * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    @endforeach
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
