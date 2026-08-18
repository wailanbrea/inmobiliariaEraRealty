{{-- ===================== RESULTADOS ===================== --}}
<div id="property-results" class="min-w-0">

    <div class="mb-md flex flex-col gap-sm sm:flex-row sm:items-center sm:justify-between">
        <p class="text-body-md text-on-surface-variant">
            {{ trans_choice('properties.index.count', $properties->total(), ['count' => $properties->total()]) }}
        </p>

        <form method="GET" action="{{ lroute('properties.index') }}" class="flex items-center gap-xs">
            @foreach ($filters as $clave => $valor)
                @continue($clave === 'orden')
                @if (is_array($valor))
                    @foreach ($valor as $v)
                        <input type="hidden" name="{{ $clave }}[]" value="{{ $v }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                @endif
            @endforeach

            <label for="orden" class="text-caption text-on-surface-variant">
                {{ __('properties.sort.label') }}
            </label>
            <select id="orden" name="orden" onchange="this.form.submit()"
                    class="rounded-lg border border-outline-variant bg-surface-container-lowest
                           px-sm py-xs text-body-md text-on-surface
                           focus:border-secondary focus:ring-1 focus:ring-secondary">
                @foreach ($sorts as $opcion)
                    <option value="{{ $opcion }}" @selected(($filters['orden'] ?? 'recent') === $opcion)>
                        {{ __("properties.sort.{$opcion}") }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if ($properties->isEmpty())
        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest
                    p-xl text-center ambient-shadow">
            <span class="material-symbols-outlined text-[48px] text-outline-variant">search_off</span>
            <h2 class="mt-sm font-heading text-title-lg text-on-surface">
                {{ __('properties.index.empty_title') }}
            </h2>
            <p class="mt-base text-body-md text-on-surface-variant">
                {{ $hasFilters
                    ? __('properties.index.empty_filtered')
                    : __('properties.index.empty_body') }}
            </p>

            @if ($hasFilters)
                <a href="{{ lroute('properties.index') }}"
                   data-property-filter-clear
                   class="mt-md inline-flex items-center gap-xs rounded-lg border border-outline-variant
                          px-sm py-xs text-label-md text-on-surface transition-colors
                          hover:bg-surface-container-low">
                    {{ __('properties.filters.clear') }}
                </a>
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 gap-gutter md:grid-cols-2 xl:grid-cols-3">
            @foreach ($properties as $index => $property)
                <x-property-card :property="$property" :eager="$index < 3" />
            @endforeach
        </div>

        <div class="mt-lg">
            {{ $properties->links() }}
        </div>
    @endif
</div>