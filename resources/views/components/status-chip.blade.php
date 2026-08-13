@props(['status', 'size' => 'sm'])

{{--
    Chip de estado. Forma de píldora completa para distinguirlo de los botones,
    como pide DESIGN.md. Los colores salen de los tokens status-*.
--}}
@php
    $clases = $size === 'lg'
        ? 'px-sm py-1 text-label-md'
        : 'px-xs py-0.5 text-caption';
@endphp

<span {{ $attributes->merge([
        'class' => "inline-flex items-center rounded-full font-bold uppercase tracking-wider
                    text-white shadow-sm {$clases}",
    ]) }}
      style="background-color: var(--color-{{ $status->color() }})">
    {{ $status->label() }}
</span>
