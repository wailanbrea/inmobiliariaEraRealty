@props(['datos', 'color' => 'var(--color-chart-leads)', 'formato' => null])

{{--
    Barras horizontales con tooltip.

    HTML y CSS, sin SVG ni librería. Con diez filas como mucho, una lista de
    divs con `width: N%` se lee igual, es accesible sin trabajo extra —cada
    fila es un <dt>/<dd> de verdad— y no obliga a calcular escalas.

    'datos' es una lista de ['nombre' => ..., 'total' => ..., 'color' => ...].
    El color por fila es opcional: si no viene, todas usan el del componente.

    El número va SIEMPRE al lado de la barra. La longitud comunica la
    proporción de un vistazo, pero quien necesita el dato exacto no debería
    tener que pasar el ratón por encima —ni podría, en una tableta—.
--}}
@php
    $lista = collect($datos);
    $mayor = max(1, $lista->max('total') ?? 1);
    $suma = max(1, $lista->sum('total'));
@endphp

@if ($lista->isEmpty())
    <p class="py-md text-center text-body-md text-on-surface-variant">
        {{ __('admin/reports.empty') }}
    </p>
@else
    <dl class="space-y-xs">
        @foreach ($lista as $fila)
            @php
                $porcentaje = round(($fila['total'] / $suma) * 100, 1);
                $ancho = round(($fila['total'] / $mayor) * 100, 1);
            @endphp

            <div class="group" title="{{ $fila['nombre'] }}: {{ number_format($fila['total'], 0, ',', '.') }} ({{ $porcentaje }} %)">
                <div class="flex items-baseline justify-between gap-xs text-body-md">
                    <dt class="min-w-0 truncate text-on-surface-variant transition-colors group-hover:text-on-surface">
                        {{ $fila['nombre'] }}
                    </dt>
                    <dd class="shrink-0 tabular-nums font-semibold text-on-surface">
                        {{ number_format($fila['total'], 0, ',', '.') }}
                        <span class="text-caption font-normal text-on-surface-variant">{{ $porcentaje }} %</span>
                    </dd>
                </div>

                <div class="mt-1 h-2 overflow-hidden rounded-full bg-surface-container">
                    {{-- La barra crece al entrar en pantalla y se ensancha al
                         pasar por encima: el movimiento dirige la mirada sin
                         que haga falta leer. --}}
                    <div class="h-full rounded-full transition-[width,filter] duration-700 ease-out
                                group-hover:brightness-110"
                         style="width: {{ $ancho }}%; background-color: {{ $fila['color'] ?? $color }}"></div>
                </div>
            </div>
        @endforeach
    </dl>
@endif
