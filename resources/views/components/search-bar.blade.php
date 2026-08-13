@props(['types' => null, 'provinces' => null, 'glass' => true])

{{--
    Buscador del hero. El .glass-panel viene del diseño: fondo blanco al 85 %
    con desenfoque de 12px por detrás.
--}}
@php
    $types ??= \App\Modules\PropertyTypes\Models\PropertyType::active()->get();
    $provinces ??= \App\Modules\Locations\Models\Province::active()
        ->whereHas('properties', fn ($q) => $q->published())
        ->get();

    $campo = 'h-12 w-full appearance-none rounded-lg border border-outline-variant
              bg-surface-container-lowest py-xs pl-sm pr-lg text-body-md text-on-surface
              outline-none transition-shadow focus:border-secondary focus:ring-2 focus:ring-secondary';
@endphp

<form method="GET" action="{{ lroute('properties.index') }}"
      {{ $attributes->merge(['class' => ($glass ? 'glass-panel ' : 'bg-surface-container-lowest ')
          . 'w-full rounded-xl p-sm ambient-shadow md:p-md']) }}>

    <div class="flex flex-col gap-sm md:flex-row md:gap-gutter">

        {{-- Operación --}}
        <div class="flex-1">
            <label for="s-operacion"
                   class="mb-base block text-caption uppercase tracking-wider text-on-surface-variant">
                {{ __('home.search.operation') }}
            </label>
            <div class="relative">
                <select id="s-operacion" name="operacion" class="{{ $campo }}">
                    <option value="">{{ __('home.search.any') }}</option>
                    @foreach (\App\Enums\OperationType::cases() as $operacion)
                        <option value="{{ $operacion->value }}" @selected(request('operacion') === $operacion->value)>
                            {{ $operacion->label() }}
                        </option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined pointer-events-none absolute right-sm top-1/2
                             -translate-y-1/2 text-on-surface-variant">expand_more</span>
            </div>
        </div>

        {{-- Tipo --}}
        <div class="flex-1">
            <label for="s-tipo"
                   class="mb-base block text-caption uppercase tracking-wider text-on-surface-variant">
                {{ __('home.search.type') }}
            </label>
            <div class="relative">
                <select id="s-tipo" name="tipo" class="{{ $campo }}">
                    <option value="">{{ __('home.search.any') }}</option>
                    @foreach ($types as $tipo)
                        <option value="{{ $tipo->slug }}" @selected(request('tipo') === $tipo->slug)>
                            {{ $tipo->name }}
                        </option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined pointer-events-none absolute right-sm top-1/2
                             -translate-y-1/2 text-on-surface-variant">expand_more</span>
            </div>
        </div>

        {{-- Ubicación --}}
        <div class="flex-1">
            <label for="s-provincia"
                   class="mb-base block text-caption uppercase tracking-wider text-on-surface-variant">
                {{ __('home.search.location') }}
            </label>
            <div class="relative">
                <select id="s-provincia" name="provincia" class="{{ $campo }}">
                    <option value="">{{ __('home.search.anywhere') }}</option>
                    @foreach ($provinces as $provincia)
                        <option value="{{ $provincia->slug }}" @selected(request('provincia') === $provincia->slug)>
                            {{ $provincia->name }}
                        </option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined pointer-events-none absolute right-sm top-1/2
                             -translate-y-1/2 text-on-surface-variant">expand_more</span>
            </div>
        </div>

        <div class="mt-sm flex shrink-0 items-end md:mt-0">
            <button type="submit"
                    class="flex h-12 w-full items-center justify-center gap-xs rounded-lg
                           bg-primary-container px-gutter text-label-md text-on-primary shadow-sm
                           transition-colors hover:bg-primary-container/90 md:w-auto">
                <span class="material-symbols-outlined">search</span>
                {{ __('common.actions.search') }}
            </button>
        </div>
    </div>
</form>
