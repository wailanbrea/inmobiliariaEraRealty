@props(['property', 'variant' => 'card'])

@php
    $compare = app(\App\Modules\Compare\Services\CompareService::class);
    $marcada = $compare->has($property->id);
    $lleno = $compare->isFull() && ! $marcada;
@endphp

{{--
    Formulario, no enlace: cambiar el comparador modifica estado y no debe
    ocurrir con un GET (un prefetch del navegador lo dispararia solo).
    Funciona sin JavaScript.
--}}
<form method="POST" action="{{ lroute('compare.toggle', ['property' => $property->id]) }}"
      {{ $attributes->merge(['class' => 'inline-flex']) }}>
    @csrf

    {{-- aria-pressed dice si la propiedad ya esta en el comparador. Lo usa el
         lector de pantalla y tambien compare.js, para no hacer volar la
         tarjeta cuando lo que se pulsa es "quitar". --}}
    <button type="submit"
            @disabled($lleno)
            aria-pressed="{{ $marcada ? 'true' : 'false' }}"
            title="{{ $lleno ? __('compare.full', ['max' => $compare::MAX]) : ($marcada ? __('compare.remove') : __('compare.add')) }}"
            @class([
                'inline-flex items-center gap-xs rounded-lg text-label-md transition-colors',
                'px-xs py-1 text-caption' => $variant === 'card',
                'px-sm py-xs' => $variant !== 'card',
                'bg-secondary text-on-secondary' => $marcada,
                'border border-outline-variant text-on-surface-variant hover:text-secondary' => ! $marcada && ! $lleno,
                'border border-outline-variant text-outline cursor-not-allowed opacity-60' => $lleno,
            ])>
        <span class="material-symbols-outlined text-[16px]">
            {{ $marcada ? 'check' : 'compare_arrows' }}
        </span>
        {{ $marcada ? __('compare.added') : __('compare.add') }}
    </button>
</form>
