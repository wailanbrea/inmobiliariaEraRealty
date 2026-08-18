<div>

    {{-- Cabecera --}}
    <div class="mb-md flex flex-col gap-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-xs">
            @if ($trashed)
                <button wire:click="$set('trashed', false)"
                        class="flex items-center gap-xs rounded-lg border border-outline-variant px-sm py-xs
                               text-label-md text-on-surface transition-colors hover:bg-surface-container-low">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    {{ __('admin/properties.filters.active') }}
                </button>
            @elseif ($totalTrashed > 0)
                <button wire:click="$set('trashed', true)"
                        class="flex items-center gap-xs rounded-lg border border-outline-variant px-sm py-xs
                               text-label-md text-on-surface-variant transition-colors hover:bg-surface-container-low">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                    {{ __('admin/properties.filters.trashed') }} ({{ $totalTrashed }})
                </button>
            @endif
        </div>

        <a href="{{ route('admin.properties.create') }}"
           class="flex items-center justify-center gap-xs rounded-lg bg-primary-container px-md py-xs
                  text-label-md font-semibold text-on-primary shadow-sm
                  transition-all hover:shadow-ambient-hover">
            <span class="material-symbols-outlined text-[20px]">add</span>
            {{ __('admin/properties.new') }}
        </a>
    </div>

    @if (session('status'))
        <div role="status"
             class="mb-md flex items-start gap-xs rounded-lg bg-tertiary-fixed px-sm py-xs text-on-tertiary-fixed">
            <span class="material-symbols-outlined text-[20px]">check_circle</span>
            <p class="text-body-md">{{ session('status') }}</p>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="mb-md rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm ambient-shadow">
        <div class="grid gap-sm md:grid-cols-2 lg:grid-cols-4">

            <div class="lg:col-span-2">
                <label for="f-search" class="mb-base block text-caption font-medium text-on-surface-variant">
                    {{ __('admin/properties.filters.search') }}
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-xs top-1/2 -translate-y-1/2
                                 text-[20px] text-on-surface-variant">search</span>
                    <input id="f-search" type="search" wire:model.live.debounce.400ms="search"
                           class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                  py-xs pl-lg pr-sm text-body-md text-on-surface
                                  focus:border-secondary focus:ring-1 focus:ring-secondary">
                </div>
            </div>

            <div>
                <label for="f-status" class="mb-base block text-caption font-medium text-on-surface-variant">
                    {{ __('admin/properties.filters.status') }}
                </label>
                <select id="f-status" wire:model.live="status"
                        class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                               px-sm py-xs text-body-md text-on-surface
                               focus:border-secondary focus:ring-1 focus:ring-secondary">
                    <option value="">{{ __('admin/properties.filters.all') }}</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="f-operation" class="mb-base block text-caption font-medium text-on-surface-variant">
                    {{ __('admin/properties.filters.operation') }}
                </label>
                <select id="f-operation" wire:model.live="operation"
                        class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                               px-sm py-xs text-body-md text-on-surface
                               focus:border-secondary focus:ring-1 focus:ring-secondary">
                    <option value="">{{ __('admin/properties.filters.all') }}</option>
                    @foreach ($operations as $o)
                        <option value="{{ $o->value }}">{{ $o->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="f-type" class="mb-base block text-caption font-medium text-on-surface-variant">
                    {{ __('admin/properties.filters.type') }}
                </label>
                <select id="f-type" wire:model.live="type"
                        class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                               px-sm py-xs text-body-md text-on-surface
                               focus:border-secondary focus:ring-1 focus:ring-secondary">
                    <option value="">{{ __('admin/properties.filters.all') }}</option>
                    @foreach ($types as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="f-province" class="mb-base block text-caption font-medium text-on-surface-variant">
                    {{ __('admin/properties.filters.province') }}
                </label>
                <select id="f-province" wire:model.live="province"
                        class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                               px-sm py-xs text-body-md text-on-surface
                               focus:border-secondary focus:ring-1 focus:ring-secondary">
                    <option value="">{{ __('admin/properties.filters.all') }}</option>
                    @foreach ($provinces as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- El filtro por asesor ya existia en el componente y en la
                 consulta, y la vista recibia $agents, pero nunca se pinto el
                 select: media implementacion que no servia de nada. --}}
            @if ($agents->isNotEmpty())
                <div>
                    <label for="f-agent" class="mb-base block text-caption font-medium text-on-surface-variant">
                        {{ __('admin/properties.filters.agent') }}
                    </label>
                    <select id="f-agent" wire:model.live="agent"
                            class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                   px-sm py-xs text-body-md text-on-surface
                                   focus:border-secondary focus:ring-1 focus:ring-secondary">
                        <option value="">{{ __('admin/properties.filters.all') }}</option>
                        @foreach ($agents as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="f-flag" class="mb-base block text-caption font-medium text-on-surface-variant">
                    {{ __('admin/properties.filters.flag') }}
                </label>
                <select id="f-flag" wire:model.live="flag"
                        class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                               px-sm py-xs text-body-md text-on-surface
                               focus:border-secondary focus:ring-1 focus:ring-secondary">
                    <option value="">{{ __('admin/properties.filters.all') }}</option>
                    <option value="featured">{{ __('admin/properties.filters.featured') }}</option>
                    <option value="investment">{{ __('admin/properties.filters.investment') }}</option>
                    <option value="project">{{ __('admin/properties.filters.project') }}</option>
                </select>
            </div>

            <div>
                <label for="f-sort" class="mb-base block text-caption font-medium text-on-surface-variant">
                    {{ __('admin/properties.sort.label') }}
                </label>
                <select id="f-sort" wire:model.live="sort"
                        class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                               px-sm py-xs text-body-md text-on-surface
                               focus:border-secondary focus:ring-1 focus:ring-secondary">
                    @foreach (['recent', 'oldest', 'price_asc', 'price_desc', 'views'] as $opcion)
                        <option value="{{ $opcion }}">{{ __("admin/properties.sort.{$opcion}") }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-sm flex items-center justify-between">
            <p class="text-caption text-on-surface-variant" wire:loading.remove>
                {{ trans_choice(':count propiedad|:count propiedades', $properties->total(), ['count' => $properties->total()]) }}
            </p>
            <p class="text-caption text-secondary" wire:loading>
                Cargando…
            </p>

            <button wire:click="clearFilters"
                    class="text-caption text-secondary hover:underline">
                {{ __('admin/properties.filters.clear') }}
            </button>
        </div>
    </div>

    {{-- Acciones en lote --}}
    @if (count($selected) > 0)
        <div class="mb-sm flex flex-wrap items-center gap-xs rounded-lg bg-secondary-fixed px-sm py-xs">
            <span class="text-label-md font-semibold text-on-secondary-fixed">
                {{ __('admin/properties.bulk.selected', ['count' => count($selected)]) }}
            </span>

            <div class="flex flex-wrap gap-xs">
                @if ($trashed)
                    @if (auth()->user()->hasRole('super_admin'))
                        <button wire:click="bulkForceDelete"
                                wire:confirm="{{ __('admin/properties.bulk.confirm_force_delete') }}"
                                class="rounded-lg bg-error-container px-sm py-1 text-caption text-on-error-container hover:shadow-sm">
                            {{ __('admin/properties.bulk.force_delete') }}
                        </button>
                    @endif
                @else
                    <button wire:click="bulkPublish"
                            class="rounded-lg bg-surface-container-lowest px-sm py-1 text-caption text-on-surface hover:shadow-sm">
                        {{ __('admin/properties.bulk.publish') }}
                    </button>
                    <button wire:click="bulkPause"
                            class="rounded-lg bg-surface-container-lowest px-sm py-1 text-caption text-on-surface hover:shadow-sm">
                        {{ __('admin/properties.bulk.pause') }}
                    </button>
                    <button wire:click="bulkFeature(true)"
                            class="rounded-lg bg-surface-container-lowest px-sm py-1 text-caption text-on-surface hover:shadow-sm">
                        {{ __('admin/properties.bulk.feature') }}
                    </button>
                    <button wire:click="bulkDelete"
                            wire:confirm="{{ __('admin/properties.bulk.confirm_delete') }}"
                            class="rounded-lg bg-error-container px-sm py-1 text-caption text-on-error-container hover:shadow-sm">
                        {{ __('admin/properties.bulk.delete') }}
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- Tabla --}}
    @if ($properties->isEmpty())
        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-xl text-center ambient-shadow">
            <span class="material-symbols-outlined text-[48px] text-outline-variant">
                {{ $trashed ? 'delete' : 'home_work' }}
            </span>
            <h2 class="mt-sm font-heading text-title-lg text-on-surface">
                @if ($trashed)
                    {{ __('admin/properties.empty.trash_title') }}
                @elseif ($search || $status || $operation || $type || $province || $flag)
                    {{ __('admin/properties.empty.filtered_title') }}
                @else
                    {{ __('admin/properties.empty.title') }}
                @endif
            </h2>
            <p class="mt-base text-body-md text-on-surface-variant">
                @if ($search || $status || $operation || $type || $province || $flag)
                    {{ __('admin/properties.empty.filtered_body') }}
                @elseif (! $trashed)
                    {{ __('admin/properties.empty.body') }}
                @endif
            </p>
        </div>
    @else
        {{-- La tabla desborda por su propio contenedor, nunca por la pagina.
             Regla dura de docs/14_RESPONSIVE.md. --}}
        <div class="table-scroll rounded-xl border border-outline-variant/40 bg-surface-container-lowest ambient-shadow">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-outline-variant/40 bg-surface-container-low">
                    <tr class="text-caption uppercase tracking-wider text-on-surface-variant">
                        <th class="w-10 px-sm py-xs">
                            <input type="checkbox" wire:model.live="selectAll" aria-label="Seleccionar todo"
                                   class="rounded-sm border-outline-variant text-secondary focus:ring-secondary">
                        </th>
                        <th class="px-sm py-xs">{{ __('admin/properties.table.property') }}</th>
                        <th class="px-sm py-xs">{{ __('admin/properties.table.type') }}</th>
                        <th class="px-sm py-xs">{{ __('admin/properties.table.price') }}</th>
                        <th class="px-sm py-xs">{{ __('admin/properties.table.status') }}</th>
                        <th class="px-sm py-xs">{{ __('admin/properties.table.location') }}</th>
                        <th class="px-sm py-xs">{{ __('admin/properties.table.agent') }}</th>
                        <th class="px-sm py-xs text-right">{{ __('admin/properties.table.actions') }}</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-outline-variant/30">
                    @foreach ($properties as $property)
                        @php
                            $traduccion = $property->translated();
                            $faltan = collect(\App\Support\Locale::codes())
                                ->reject(fn ($c) => $property->translations->contains('locale', $c));
                        @endphp

                        <tr class="transition-colors hover:bg-surface-container-low" wire:key="prop-{{ $property->id }}">
                            <td class="px-sm py-xs">
                                <input type="checkbox" wire:model.live="selected" value="{{ $property->id }}"
                                       aria-label="Seleccionar {{ $property->reference_code }}"
                                       class="rounded-sm border-outline-variant text-secondary focus:ring-secondary">
                            </td>

                            <td class="px-sm py-xs">
                                <div class="flex items-start gap-sm">
                                    {{-- Miniatura de la imagen principal.
                                         Se usa thumbnailUrl(), que cae a la foto
                                         completa si aun no hay recorte generado.
                                         El hueco mide lo mismo con foto y sin
                                         ella, para que las filas no bailen. --}}
                                    @php $portada = $property->mainImage; @endphp

                                    @if ($portada)
                                        <img src="{{ $portada->thumbnailUrl() }}"
                                             alt="{{ $portada->altText() }}"
                                             width="64" height="48" loading="lazy" decoding="async"
                                             class="h-12 w-16 shrink-0 rounded-lg border border-outline-variant/40
                                                    bg-surface-container object-cover">
                                    @else
                                        <span class="flex h-12 w-16 shrink-0 flex-col items-center justify-center gap-0.5
                                                     rounded-lg border border-dashed border-outline-variant
                                                     bg-surface-container text-on-surface-variant"
                                              title="{{ __('admin/properties.table.no_photo') }}">
                                            <span class="material-symbols-outlined text-[18px]">no_photography</span>
                                        </span>
                                    @endif

                                <div class="flex min-w-0 flex-col">
                                    <span class="text-label-md font-semibold text-on-surface">
                                        {{ $traduccion?->title ?? __('admin/properties.table.no_title') }}
                                    </span>
                                    <span class="text-caption text-on-surface-variant">
                                        {{ $property->reference_code }}
                                    </span>

                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @if ($property->is_featured)
                                            <span class="rounded-full bg-secondary-fixed px-xs text-caption text-on-secondary-fixed">
                                                {{ __('property.labels.featured') }}
                                            </span>
                                        @endif
                                        @if ($property->is_investment)
                                            <span class="rounded-full bg-tertiary-fixed px-xs text-caption text-on-tertiary-fixed">
                                                {{ __('property.labels.investment') }}
                                            </span>
                                        @endif
                                        @foreach ($faltan as $codigo)
                                            <span class="rounded-full bg-error-container px-xs text-caption text-on-error-container"
                                                  title="{{ __('admin/properties.table.untranslated', ['locale' => strtoupper($codigo)]) }}">
                                                {{ __('admin/properties.table.untranslated', ['locale' => strtoupper($codigo)]) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                                </div>
                            </td>

                            <td class="px-sm py-xs text-body-md text-on-surface-variant">
                                {{ $property->type?->name }}
                                <div class="text-caption">{{ $property->operation_type->label() }}</div>
                            </td>

                            <td class="whitespace-nowrap px-sm py-xs text-body-md text-on-surface">
                                {{ $property->formattedPrice() }}
                            </td>

                            <td class="px-sm py-xs">
                                <span class="rounded-full px-xs py-0.5 text-caption font-semibold text-white"
                                      style="background-color: var(--color-{{ $property->status->color() }})">
                                    {{ $property->status->label() }}
                                </span>
                            </td>

                            <td class="px-sm py-xs text-body-md text-on-surface-variant">
                                {{ $property->locationLabel() ?: '—' }}
                            </td>

                            <td class="px-sm py-xs text-body-md text-on-surface-variant">
                                {{ $property->agent?->name ?? __('admin/properties.table.no_agent') }}
                            </td>

                            <td class="px-sm py-xs">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($trashed)
                                        <button wire:click="restore({{ $property->id }})"
                                                class="rounded-lg p-1 text-on-surface-variant transition-colors hover:text-secondary"
                                                title="{{ __('admin/properties.actions.restore') }}">
                                            <span class="material-symbols-outlined text-[20px]">restore_from_trash</span>
                                        </button>
                                    @else
                                        <a href="{{ route('admin.properties.edit', $property) }}"
                                           class="rounded-lg p-1 text-on-surface-variant transition-colors hover:text-secondary"
                                           title="{{ __('admin/properties.edit') }}">
                                            <span class="material-symbols-outlined text-[20px]">edit</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-sm">
            {{ $properties->links() }}
        </div>
    @endif
</div>
