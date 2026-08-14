@props(['puntos', 'color' => 'var(--color-secondary)', 'alto' => 56])

{{--
    Gráfico de línea en SVG generado en el servidor.

    Sin librería de gráficos a propósito: Chart.js son ~65 KB comprimidos para
    dibujar una polilínea, y el presupuesto de rendimiento de docs/13 no da
    para eso en un panel que ya carga Livewire y Alpine. El SVG llega en el
    HTML, se ve al instante y no depende de que el JavaScript arranque.

    'puntos' es una lista de enteros. El viewBox es fijo y el SVG escala al
    ancho del contenedor con preserveAspectRatio="none", así que no hay que
    saber cuántos píxeles mide.
--}}
@php
    $valores = collect($puntos)->map(fn ($v) => (int) $v)->values();
    $n = $valores->count();
    $max = max(1, $valores->max());   // evita la división por cero de una serie toda a cero

    $ancho = 100;
    $altoVb = 30;

    // Con un solo punto no hay línea que trazar; se dibuja centrado.
    $x = fn (int $i) => $n > 1 ? round(($i / ($n - 1)) * $ancho, 2) : $ancho / 2;
    $y = fn (int $v) => round($altoVb - ($v / $max) * ($altoVb - 2) - 1, 2);

    $linea = $valores->map(fn ($v, $i) => $x($i).','.$y($v))->implode(' ');
    $area = $n > 1 ? "0,{$altoVb} {$linea} {$ancho},{$altoVb}" : null;

    $id = 'sl-'.Str::random(6);
@endphp

<svg viewBox="0 0 {{ $ancho }} {{ $altoVb }}" preserveAspectRatio="none"
     style="height: {{ $alto }}px" class="w-full" role="img"
     aria-label="{{ $attributes->get('aria-label', __('admin/reports.chart_alt', ['n' => $n])) }}">

    <defs>
        <linearGradient id="{{ $id }}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="{{ $color }}" stop-opacity="0.28"/>
            <stop offset="100%" stop-color="{{ $color }}" stop-opacity="0"/>
        </linearGradient>
    </defs>

    @if ($area)
        <polygon points="{{ $area }}" fill="url(#{{ $id }})"/>
    @endif

    <polyline points="{{ $linea }}" fill="none" stroke="{{ $color }}"
              stroke-width="1.4" stroke-linejoin="round" stroke-linecap="round"
              vector-effect="non-scaling-stroke"/>
</svg>
