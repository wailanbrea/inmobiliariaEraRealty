@props(['types' => null, 'provinces' => null, 'sectors' => null, 'glass' => true])

@php
    $types ??= \App\Modules\PropertyTypes\Models\PropertyType::active()->get();
    $provinces ??= \App\Modules\Locations\Models\Province::active()
        ->whereHas('properties', fn ($q) => $q->published())
        ->get();
    $sectors ??= \App\Modules\Locations\Models\Sector::active()->with('city.province')->get();
    $cities ??= \App\Modules\Locations\Models\City::active()->with('province')->get();

    $trigger = 'flex h-12 w-full items-center justify-between rounded-lg border border-outline-variant
                bg-surface-container-lowest py-xs pl-sm pr-sm text-left text-body-md text-on-surface
                outline-none transition focus:border-secondary focus:ring-2 focus:ring-secondary';

    $operationOptions = collect([['value' => '', 'label' => __('home.search.any')]])
        ->merge(collect(\App\Enums\OperationType::cases())->map(fn ($operation) => [
            'value' => $operation->value,
            'label' => $operation->label(),
        ]))
        ->values();

    $typeOptions = collect([['value' => '', 'label' => __('home.search.any')]])
        ->merge($types->map(fn ($type) => [
            'value' => $type->slug,
            'label' => $type->name,
        ]))
        ->values();

    $provinceOptions = collect([['value' => '', 'label' => __('home.search.anywhere')]])
        ->merge($provinces->map(fn ($province) => [
            'value' => $province->slug,
            'label' => $province->name,
        ]))
        ->values();

    $sectorOptions = collect([['value' => '', 'label' => __('home.search.anywhere')]])
        ->merge($sectors->map(fn ($sector) => [
            'value' => (string) $sector->id,
            'label' => $sector->name,
            'city' => $sector->city?->slug,
            'province' => $sector->city?->province?->slug,
        ]))
        ->values();

    $cityOptions = collect([['value' => '', 'label' => __('home.search.anywhere')]])
        ->merge($cities->map(fn ($city) => [
            'value' => $city->slug,
            'label' => $city->name.($city->province ? ', '.$city->province->name : ''),
            'province' => $city->province?->slug,
        ]))
        ->values();

    $filters = [
        [
            'id' => 's-operacion',
            'name' => 'operacion',
            'label' => __('home.search.operation'),
            'value' => request('operacion', ''),
            'options' => $operationOptions,
        ],
        [
            'id' => 's-tipo',
            'name' => 'tipo',
            'label' => __('home.search.type'),
            'value' => request('tipo', ''),
            'options' => $typeOptions,
        ],
        [
            'id' => 's-provincia',
            'name' => 'provincia',
            'label' => __('properties.filters.province'),
            'value' => request('provincia', ''),
            'options' => $provinceOptions,
        ],
        [
            'id' => 's-zona',
            'name' => 'ciudad',
            'label' => __('properties.filters.zona'),
            'value' => request('ciudad', ''),
            'options' => $cityOptions,
        ],
        [
            'id' => 's-sector',
            'name' => 'sector',
            'label' => __('properties.filters.sector'),
            'value' => request('sector', ''),
            'options' => $sectorOptions,
        ],
    ];
@endphp

<form method="GET" action="{{ lroute('properties.index') }}"
      {{ $attributes->merge(['class' => ($glass ? 'glass-panel ' : 'bg-surface-container-lowest ')
          . 'w-full rounded-xl p-sm ambient-shadow md:p-md']) }}>

    <div class="grid gap-sm md:grid-cols-3">
        @foreach ($filters as $filter)
            <div class="min-w-0 {{ $filter['wrapperClass'] ?? '' }}"
                 x-data="{
                     open: false,
                     name: @js($filter['name']),
                     value: @js($filter['value']),
                     allOptions: @js($filter['options']),
                     get options() {
                         const province = document.querySelector('[data-hero-filter=provincia]')?.value ?? '';
                         const city = document.querySelector('[data-hero-filter=ciudad]')?.value ?? '';

                         if (this.name === 'ciudad') {
                             return this.allOptions.filter((option) => !province || option.province === province);
                         }

                         if (this.name === 'sector') {
                             return this.allOptions.filter((option) => city
                                 ? option.city === city
                                 : (!province || option.province === province));
                         }

                         return this.allOptions;
                     },
                     get selectedLabel() {
                         return this.options.find((option) => option.value === this.value)?.label ?? this.options[0].label;
                     },
                     choose(option) {
                         this.value = option.value;
                         this.open = false;
                         if (this.name === 'provincia' || this.name === 'ciudad') {
                             window.dispatchEvent(new CustomEvent('hero-location-changed', {
                                 detail: { name: this.name },
                             }));
                         }
                     },
                     resetFromParent(event) {
                         if ((event.detail.name === 'provincia' && ['ciudad', 'sector'].includes(this.name))
                             || (event.detail.name === 'ciudad' && this.name === 'sector')) {
                             this.value = '';
                             this.open = false;
                         }
                     },
                 }"
                 @hero-location-changed.window="resetFromParent($event)"
                 @keydown.escape.window="open = false">
                <label for="{{ $filter['id'] }}-button"
                       class="mb-base block text-caption uppercase tracking-wider text-on-surface-variant">
                    {{ $filter['label'] }}
                </label>
                <div class="relative">
                    <input id="{{ $filter['id'] }}" type="hidden" name="{{ $filter['name'] }}"
                           data-hero-filter="{{ $filter['name'] }}" :value="value">

                    <button id="{{ $filter['id'] }}-button"
                            type="button"
                            class="{{ $trigger }}"
                            data-filter-trigger="{{ $filter['name'] }}"
                            aria-haspopup="listbox"
                            :aria-expanded="open.toString()"
                            @click="open = !open">
                        <span class="truncate" x-text="selectedLabel"></span>
                        <span class="material-symbols-outlined shrink-0 text-on-surface-variant transition-transform"
                              :class="{ 'rotate-180': open }">expand_more</span>
                    </button>

                    <div x-cloak
                         x-show="open"
                         @click.outside="open = false"
                         data-filter-menu="{{ $filter['name'] }}"
                         class="absolute left-0 right-0 top-full z-40 mt-xs max-h-72 overflow-auto rounded-lg border
                                border-outline-variant bg-surface-container-lowest py-xs shadow-ambient-lg"
                         role="listbox"
                         aria-labelledby="{{ $filter['id'] }}-button">
                        <template x-for="option in options" :key="option.value">
                            <button type="button"
                                    class="flex min-h-11 w-full items-center px-sm py-xs text-left text-body-md text-on-surface
                                           transition-colors hover:bg-surface-container focus:bg-surface-container"
                                    :class="{ 'bg-secondary-fixed text-on-secondary-fixed': option.value === value }"
                                    :aria-selected="(option.value === value).toString()"
                                    x-bind:data-filter-option="option.value"
                                    role="option"
                                    @click="choose(option)">
                                <span class="truncate" x-text="option.label"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="mt-sm flex min-w-0 items-end md:mt-0">
            <button type="submit"
                    title="{{ __('properties.filters.search_help') }}"
                    class="flex h-12 w-full items-center justify-center gap-xs rounded-lg
                           bg-primary-container px-sm text-label-md text-on-primary shadow-sm
                           transition-colors hover:bg-primary-container/90">
                <span class="material-symbols-outlined">search</span>
                {{ __('common.actions.search') }}
            </button>
        </div>
    </div>
</form>
