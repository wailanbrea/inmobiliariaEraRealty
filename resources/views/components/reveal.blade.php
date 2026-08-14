@props(['as' => 'div', 'delay' => null])

{{--
    Revelado al entrar en pantalla.

    El elemento se renderiza VISIBLE. Es motion.js quien le anade .is-primed
    para ocultarlo justo antes de animarlo, de modo que si el JavaScript no
    llega a ejecutarse el contenido se ve igual. Ver docs/13 seccion 5.

    'delay' escalona a mano; para una rejilla entera es mejor poner
    data-reveal-group en el contenedor y dejar que motion.js reparta.
--}}
<{{ $as }}
    data-reveal
    @if ($delay !== null) data-reveal-delay="{{ $delay }}" @endif
    {{ $attributes }}>
    {{ $slot }}
</{{ $as }}>
