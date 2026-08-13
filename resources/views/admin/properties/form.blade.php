@extends('admin.layouts.app')

@section('title', $property->exists ? __('admin/properties.edit') : __('admin/properties.new'))

@section('content')

@php
    $esNueva = ! $property->exists;
    $accion  = $esNueva
        ? route('admin.properties.store')
        : route('admin.properties.update', $property);

    // Ciudades y sectores ya cargados, para que al reabrir el formulario los
    // selects encadenados muestren la seleccion sin esperar a JavaScript.
    $ciudades = $property->province_id
        ? \App\Modules\Locations\Models\City::where('province_id', $property->province_id)->active()->get()
        : collect();

    $sectores = $property->city_id
        ? \App\Modules\Locations\Models\Sector::where('city_id', $property->city_id)->active()->get()
        : collect();

    $traducciones = $property->exists
        ? $property->translations->keyBy('locale')
        : collect();
@endphp

<div class="mb-md flex items-center justify-between gap-sm">
    <a href="{{ route('admin.properties.index') }}"
       class="flex items-center gap-xs text-label-md text-on-surface-variant transition-colors hover:text-secondary">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        {{ __('admin/properties.actions.back') }}
    </a>

    @if ($property->exists)
        <div class="flex items-center gap-xs">
            <span class="rounded-full px-xs py-0.5 text-caption font-semibold text-white"
                  style="background-color: var(--color-{{ $property->status->color() }})">
                {{ $property->status->label() }}
            </span>
            <span class="text-caption text-on-surface-variant">{{ $property->reference_code }}</span>
        </div>
    @endif
</div>

@if (session('status'))
    <div role="status"
         class="mb-md flex items-start gap-xs rounded-lg bg-tertiary-fixed px-sm py-xs text-on-tertiary-fixed">
        <span class="material-symbols-outlined text-[20px]">check_circle</span>
        <p class="text-body-md">{{ session('status') }}</p>
    </div>
@endif

@if ($errors->any())
    <div role="alert" class="mb-md rounded-lg bg-error-container px-sm py-xs text-on-error-container">
        <div class="flex items-start gap-xs">
            <span class="material-symbols-outlined text-[20px]">error</span>
            <div>
                <p class="text-body-md font-semibold">{{ $errors->first() }}</p>
                @if ($errors->count() > 1)
                    <p class="mt-1 text-caption">{{ $errors->count() }} campos necesitan revisión.</p>
                @endif
            </div>
        </div>
    </div>
@endif

<form method="POST" action="{{ $accion }}" x-data="{ tab: 'general' }">
    @csrf
    @if (! $esNueva) @method('PUT') @endif

    {{-- Pestañas --}}
    <nav class="mb-md flex gap-1 overflow-x-auto border-b border-outline-variant/40"
         aria-label="{{ __('admin/properties.title') }}">
        @foreach ([
            'general'   => 'tune',
            'price'     => 'payments',
            'location'  => 'location_on',
            'features'  => 'straighten',
            'amenities' => 'checklist',
            'content'   => 'article',
            'media'     => 'movie',
            'contact'   => 'badge',
            'seo'       => 'travel_explore',
        ] as $key => $icono)
            <button type="button" @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}'
                        ? 'border-b-2 border-secondary font-semibold text-secondary'
                        : 'text-on-surface-variant hover:text-secondary'"
                    class="flex shrink-0 items-center gap-xs whitespace-nowrap px-sm py-xs text-label-md transition-colors">
                <span class="material-symbols-outlined text-[18px]">{{ $icono }}</span>
                {{ __("admin/properties.tabs.{$key}") }}
            </button>
        @endforeach
    </nav>

    {{-- ---------------- General ---------------- --}}
    <section x-show="tab === 'general'" x-cloak
             class="space-y-md rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">

        <div class="grid gap-sm md:grid-cols-3">
            <x-admin.field name="operation_type" :label="__('admin/properties.fields.operation_type')"
                type="select" required :value="$property->operation_type?->value"
                :options="collect($operations)->mapWithKeys(fn($o) => [$o->value => $o->label()])->all()" />

            <x-admin.field name="property_type_id" :label="__('admin/properties.fields.property_type')"
                type="select" required :value="$property->property_type_id"
                :options="$types->pluck('name', 'id')->all()" />

            <x-admin.field name="status" :label="__('admin/properties.fields.status')"
                type="select" required :value="$property->status?->value"
                :options="collect($statuses)->mapWithKeys(fn($s) => [$s->value => $s->label()])->all()" />
        </div>

        <div class="space-y-xs border-t border-outline-variant/30 pt-sm">
            <x-admin.field name="is_featured" type="checkbox"
                :label="__('admin/properties.fields.is_featured')" :value="$property->is_featured"
                :help="__('admin/properties.fields.is_featured')" />

            <x-admin.field name="is_investment" type="checkbox"
                :label="__('admin/properties.fields.is_investment')" :value="$property->is_investment"
                :help="__('admin/properties.fields.is_investment')" />

            <x-admin.field name="is_project" type="checkbox"
                :label="__('admin/properties.fields.is_project')" :value="$property->is_project"
                :help="__('admin/properties.fields.is_project')" />
        </div>
    </section>

    {{-- ---------------- Precio ---------------- --}}
    <section x-show="tab === 'price'" x-cloak
             class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <div class="grid gap-sm md:grid-cols-2 lg:grid-cols-4">
            <x-admin.field name="price" :label="__('admin/properties.fields.price')"
                type="number" step="0.01" :value="$property->price"
                :help="__('admin/properties.fields.price_help')" />

            <x-admin.field name="currency" :label="__('admin/properties.fields.currency')"
                type="select" required :value="$property->currency?->value"
                :options="collect($currencies)->mapWithKeys(fn($c) => [$c->value => $c->value.' — '.$c->label()])->all()" />

            <x-admin.field name="price_period" :label="__('admin/properties.fields.price_period')"
                type="select" :value="$property->price_period?->value"
                :options="['' => __('admin/properties.select.none')] +
                          collect(\App\Enums\PricePeriod::cases())->mapWithKeys(fn($p) => [$p->value => $p->label()])->all()" />

            <x-admin.field name="maintenance_fee" :label="__('admin/properties.fields.maintenance_fee')"
                type="number" step="0.01" :value="$property->maintenance_fee" />
        </div>
    </section>

    {{-- ---------------- Ubicación ---------------- --}}
    <section x-show="tab === 'location'" x-cloak
             class="space-y-md rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow"
             x-data="selectsEncadenados(
                 {{ (int) $property->province_id }},
                 {{ (int) $property->city_id }},
                 {{ (int) $property->sector_id }}
             )">

        <div class="grid gap-sm md:grid-cols-3">
            <div>
                <label for="province_id" class="mb-base block text-caption font-medium text-on-surface-variant">
                    {{ __('admin/properties.fields.province') }}
                </label>
                <select id="province_id" name="province_id" x-model="provincia" @change="cargarCiudades()"
                        class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                               px-sm py-xs text-body-md text-on-surface
                               focus:border-secondary focus:ring-1 focus:ring-secondary">
                    <option value="">{{ __('admin/properties.select.choose') }}</option>
                    @foreach ($provinces as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="city_id" class="mb-base block text-caption font-medium text-on-surface-variant">
                    {{ __('admin/properties.fields.city') }}
                </label>
                <select id="city_id" name="city_id" x-model="ciudad" @change="cargarSectores()"
                        :disabled="!provincia"
                        class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                               px-sm py-xs text-body-md text-on-surface disabled:opacity-50
                               focus:border-secondary focus:ring-1 focus:ring-secondary">
                    <option value="">
                        <span x-text="provincia ? '{{ __('admin/properties.select.choose') }}'
                                              : '{{ __('admin/properties.select.province_first') }}'"></span>
                    </option>
                    <template x-for="c in ciudades" :key="c.id">
                        <option :value="c.id" x-text="c.name"></option>
                    </template>
                </select>
            </div>

            <div>
                <label for="sector_id" class="mb-base block text-caption font-medium text-on-surface-variant">
                    {{ __('admin/properties.fields.sector') }}
                </label>
                <select id="sector_id" name="sector_id" x-model="sector" :disabled="!ciudad"
                        class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                               px-sm py-xs text-body-md text-on-surface disabled:opacity-50
                               focus:border-secondary focus:ring-1 focus:ring-secondary">
                    <option value="">{{ __('admin/properties.select.choose') }}</option>
                    <template x-for="s in sectores" :key="s.id">
                        <option :value="s.id" x-text="s.name"></option>
                    </template>
                </select>
            </div>
        </div>

        <x-admin.field name="address" :label="__('admin/properties.fields.address')"
            :value="$property->address" />

        <div class="rounded-lg bg-surface-container-low p-sm">
            <x-admin.field name="show_exact_location" type="checkbox"
                :label="__('admin/properties.fields.show_exact_location')"
                :value="$property->show_exact_location"
                :help="__('admin/properties.fields.show_exact_location')" />
            <p class="mt-1 text-caption text-on-surface-variant">
                {{ __('admin/properties.fields.location_privacy') }}
            </p>
        </div>

        <div class="grid gap-sm md:grid-cols-2">
            <x-admin.field name="latitude" :label="__('admin/properties.fields.latitude')"
                type="number" step="0.00000001" :value="$property->latitude" placeholder="18.4861" />
            <x-admin.field name="longitude" :label="__('admin/properties.fields.longitude')"
                type="number" step="0.00000001" :value="$property->longitude" placeholder="-69.9312" />
        </div>
    </section>

    {{-- ---------------- Características ---------------- --}}
    <section x-show="tab === 'features'" x-cloak
             class="space-y-md rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <div class="grid gap-sm md:grid-cols-3">
            <x-admin.field name="bedrooms" :label="__('admin/properties.fields.bedrooms')"
                type="number" :value="$property->bedrooms" />

            <x-admin.field name="bathrooms" :label="__('admin/properties.fields.bathrooms')"
                type="number" step="0.5" :value="$property->bathrooms"
                :help="__('admin/properties.fields.bathrooms_help')" />

            <x-admin.field name="parking_spaces" :label="__('admin/properties.fields.parking_spaces')"
                type="number" :value="$property->parking_spaces" />

            <x-admin.field name="construction_area" :label="__('admin/properties.fields.construction_area')"
                type="number" step="0.01" :value="$property->construction_area" />

            <x-admin.field name="land_area" :label="__('admin/properties.fields.land_area')"
                type="number" step="0.01" :value="$property->land_area" />

            <x-admin.field name="floor_level" :label="__('admin/properties.fields.floor_level')"
                :value="$property->floor_level" />

            <x-admin.field name="year_built" :label="__('admin/properties.fields.year_built')"
                type="number" :value="$property->year_built" />
        </div>

        <x-admin.field name="is_furnished" type="checkbox"
            :label="__('admin/properties.fields.is_furnished')" :value="$property->is_furnished"
            :help="__('admin/properties.fields.is_furnished')" />
    </section>

    {{-- ---------------- Amenidades ---------------- --}}
    <section x-show="tab === 'amenities'" x-cloak
             class="space-y-md rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        @foreach ($amenities as $categoria => $lista)
            <div>
                <h3 class="mb-xs text-label-md font-semibold uppercase tracking-wider text-on-surface-variant">
                    {{ $categoria ?: 'Otras' }}
                </h3>
                <div class="grid gap-xs sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($lista as $amenidad)
                        <label class="flex cursor-pointer items-center gap-xs rounded-lg
                                      bg-surface-container-low px-sm py-xs transition-colors
                                      hover:bg-surface-container">
                            <input type="checkbox" name="amenities[]" value="{{ $amenidad->id }}"
                                   @checked(in_array($amenidad->id, old('amenities', $selectedAmenities)))
                                   class="rounded-sm border-outline-variant text-secondary focus:ring-secondary">
                            <span class="material-symbols-outlined text-[20px] text-secondary">
                                {{ $amenidad->icon }}
                            </span>
                            <span class="text-body-md text-on-surface">{{ $amenidad->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>

    {{-- ---------------- Descripción (ES / EN) ---------------- --}}
    <section x-show="tab === 'content'" x-cloak
             class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow"
             x-data="{ locale: '{{ array_key_first($locales) }}' }">

        <div class="mb-sm flex items-center justify-between">
            <h2 class="font-heading text-title-lg text-on-surface">
                {{ __('admin/properties.tabs.content') }}
            </h2>

            <div class="flex items-center rounded-lg border border-outline-variant" role="tablist">
                @foreach ($locales as $codigo => $meta)
                    <button type="button" @click="locale = '{{ $codigo }}'"
                            :class="locale === '{{ $codigo }}'
                                ? 'bg-primary-container text-on-primary font-semibold'
                                : 'text-on-surface-variant hover:text-secondary'"
                            class="rounded-lg px-sm py-1 text-caption transition-colors">
                        {{ $meta['short'] }}
                        @if (! $traducciones->has($codigo) && $property->exists)
                            <span class="ml-1 text-error">•</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        @foreach ($locales as $codigo => $meta)
            @php $t = $traducciones->get($codigo); @endphp

            <div x-show="locale === '{{ $codigo }}'" x-cloak class="space-y-sm">
                <x-admin.field :name="'translations['.$codigo.'][title]'"
                    :label="__('admin/properties.fields.title').' ('.$meta['short'].')'"
                    :value="$t?->title" :required="$codigo === \App\Support\Locale::default()"
                    :maxlength="200" />

                <x-admin.field :name="'translations['.$codigo.'][slug]'"
                    :label="__('admin/properties.fields.slug')"
                    :value="$t?->slug" :help="__('admin/properties.fields.slug_help')" />

                <x-admin.field :name="'translations['.$codigo.'][short_description]'"
                    :label="__('admin/properties.fields.short_description')"
                    type="textarea" :rows="2" :maxlength="500" :value="$t?->short_description"
                    :help="__('admin/properties.fields.short_description_help')" />

                <x-admin.field :name="'translations['.$codigo.'][description]'"
                    :label="__('admin/properties.fields.description')"
                    type="textarea" :rows="10" :value="$t?->description" />
            </div>
        @endforeach
    </section>

    {{-- ---------------- Multimedia ---------------- --}}
    <section x-show="tab === 'media'" x-cloak
             class="space-y-md rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <div class="grid gap-sm md:grid-cols-2">
            <x-admin.field name="video_url" :label="__('admin/properties.fields.video_url')"
                type="url" :value="$property->video_url" placeholder="https://youtube.com/..." />

            <x-admin.field name="virtual_tour_url" :label="__('admin/properties.fields.virtual_tour_url')"
                type="url" :value="$property->virtual_tour_url" placeholder="https://" />
        </div>

        <div class="rounded-lg border border-dashed border-outline-variant p-md text-center">
            <span class="material-symbols-outlined text-[32px] text-outline-variant">photo_library</span>
            <p class="mt-xs text-body-md text-on-surface-variant">
                La subida de imágenes llega en la Fase 3.
            </p>
        </div>
    </section>

    {{-- ---------------- Agente y propietario ---------------- --}}
    <section x-show="tab === 'contact'" x-cloak
             class="space-y-md rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">

        <x-admin.field name="agent_id" :label="__('admin/properties.fields.agent')"
            type="select" :value="$property->agent_id"
            :options="['' => __('admin/properties.select.none')] + $agents->pluck('name', 'id')->all()" />

        <div class="rounded-lg border border-error/30 bg-error-container/30 p-sm">
            <p class="mb-sm flex items-center gap-xs text-caption font-semibold text-on-error-container">
                <span class="material-symbols-outlined text-[18px]">lock</span>
                {{ __('admin/properties.fields.private_warning') }}
            </p>

            <div class="grid gap-sm md:grid-cols-3">
                <x-admin.field name="owner_name" :label="__('admin/properties.fields.owner_name')"
                    :value="$property->owner_name" />
                <x-admin.field name="owner_phone" :label="__('admin/properties.fields.owner_phone')"
                    :value="$property->owner_phone" />
                <x-admin.field name="owner_email" :label="__('admin/properties.fields.owner_email')"
                    type="email" :value="$property->owner_email" />
            </div>

            <div class="mt-sm">
                <x-admin.field name="internal_notes" :label="__('admin/properties.fields.internal_notes')"
                    type="textarea" :rows="3" :value="$property->internal_notes" />
            </div>
        </div>
    </section>

    {{-- ---------------- SEO ---------------- --}}
    <section x-show="tab === 'seo'" x-cloak
             class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow"
             x-data="{ locale: '{{ array_key_first($locales) }}' }">

        <div class="mb-sm flex items-center justify-between">
            <h2 class="font-heading text-title-lg text-on-surface">SEO</h2>
            <div class="flex items-center rounded-lg border border-outline-variant" role="tablist">
                @foreach ($locales as $codigo => $meta)
                    <button type="button" @click="locale = '{{ $codigo }}'"
                            :class="locale === '{{ $codigo }}'
                                ? 'bg-primary-container text-on-primary font-semibold'
                                : 'text-on-surface-variant hover:text-secondary'"
                            class="rounded-lg px-sm py-1 text-caption transition-colors">
                        {{ $meta['short'] }}
                    </button>
                @endforeach
            </div>
        </div>

        @foreach ($locales as $codigo => $meta)
            @php $t = $traducciones->get($codigo); @endphp

            <div x-show="locale === '{{ $codigo }}'" x-cloak class="space-y-sm">
                <x-admin.field :name="'translations['.$codigo.'][meta_title]'"
                    :label="__('admin/properties.fields.meta_title')"
                    :value="$t?->meta_title" :maxlength="200"
                    :help="__('admin/settings.seo.title_limit')" />

                <x-admin.field :name="'translations['.$codigo.'][meta_description]'"
                    :label="__('admin/properties.fields.meta_description')"
                    type="textarea" :rows="3" :maxlength="300" :value="$t?->meta_description"
                    :help="__('admin/settings.seo.description_limit')" />
            </div>
        @endforeach
    </section>

    {{-- Barra de acciones --}}
    <div class="sticky bottom-0 mt-md flex flex-wrap items-center justify-end gap-xs
                border-t border-outline-variant/40 bg-surface/95 py-sm backdrop-blur">

        @if ($property->exists)
            <a href="{{ route('admin.properties.preview', $property) }}" target="_blank" rel="noopener"
               class="flex items-center gap-xs rounded-lg border border-outline-variant px-sm py-xs
                      text-label-md text-on-surface transition-colors hover:bg-surface-container-low">
                <span class="material-symbols-outlined text-[18px]">visibility</span>
                {{ __('admin/properties.actions.preview') }}
            </a>
        @endif

        <button type="submit"
                class="flex items-center gap-xs rounded-lg bg-primary-container px-md py-xs
                       text-label-md font-semibold text-on-primary shadow-sm
                       transition-all hover:shadow-ambient-hover">
            <span class="material-symbols-outlined text-[20px]">save</span>
            {{ $esNueva ? __('admin/properties.actions.save_draft') : __('admin/properties.actions.save') }}
        </button>
    </div>
</form>

{{-- Acciones que no forman parte del formulario principal --}}
@if ($property->exists)
    <div class="mt-md flex flex-wrap gap-xs">
        @can('publish', $property)
            @if ($property->status !== \App\Enums\PropertyStatus::Available)
                <form method="POST" action="{{ route('admin.properties.publish', $property) }}">
                    @csrf
                    <button type="submit"
                            class="flex items-center gap-xs rounded-lg bg-on-tertiary-container px-sm py-xs
                                   text-label-md font-semibold text-on-tertiary transition-opacity hover:opacity-90">
                        <span class="material-symbols-outlined text-[18px]">publish</span>
                        {{ __('admin/properties.actions.publish') }}
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.properties.pause', $property) }}">
                    @csrf
                    <button type="submit"
                            class="flex items-center gap-xs rounded-lg border border-outline-variant px-sm py-xs
                                   text-label-md text-on-surface transition-colors hover:bg-surface-container-low">
                        <span class="material-symbols-outlined text-[18px]">pause</span>
                        {{ __('admin/properties.actions.pause') }}
                    </button>
                </form>
            @endif
        @endcan

        @can('delete', $property)
            <form method="POST" action="{{ route('admin.properties.destroy', $property) }}"
                  onsubmit="return confirm('{{ __('admin/properties.actions.confirm_delete') }}')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="flex items-center gap-xs rounded-lg border border-error px-sm py-xs
                               text-label-md text-error transition-colors hover:bg-error-container">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                    {{ __('admin/properties.actions.delete') }}
                </button>
            </form>
        @endcan
    </div>
@endif

@push('scripts')
<script>
    // Selects encadenados provincia -> ciudad -> sector.
    // Se cargan por fetch en vez de volcar todas las ubicaciones del pais en
    // el HTML: son 32 provincias con sus ciudades y sectores.
    function selectsEncadenados(provinciaInicial, ciudadInicial, sectorInicial) {
        return {
            provincia: provinciaInicial || '',
            ciudad: ciudadInicial || '',
            sector: sectorInicial || '',
            ciudades: @json($ciudades->map->only(['id', 'name'])->values()),
            sectores: @json($sectores->map->only(['id', 'name'])->values()),

            async cargarCiudades() {
                this.ciudad = '';
                this.sector = '';
                this.sectores = [];
                this.ciudades = this.provincia
                    ? await (await fetch(`/admin/ubicaciones/ciudades/${this.provincia}`)).json()
                    : [];
            },

            async cargarSectores() {
                this.sector = '';
                this.sectores = this.ciudad
                    ? await (await fetch(`/admin/ubicaciones/sectores/${this.ciudad}`)).json()
                    : [];
            },
        }
    }
</script>
@endpush

@endsection
