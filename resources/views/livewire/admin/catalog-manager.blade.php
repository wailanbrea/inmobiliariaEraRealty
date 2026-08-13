<div>

    @if ($successMessage)
        <div role="status"
             class="mb-md flex items-start gap-xs rounded-lg bg-tertiary-fixed px-sm py-xs text-on-tertiary-fixed">
            <span class="material-symbols-outlined text-[20px]">check_circle</span>
            <p class="text-body-md">{{ $successMessage }}</p>
        </div>
    @endif

    @if ($errorMessage)
        <div role="alert"
             class="mb-md flex items-start gap-xs rounded-lg bg-error-container px-sm py-xs text-on-error-container">
            <span class="material-symbols-outlined text-[20px]">error</span>
            <p class="text-body-md">{{ $errorMessage }}</p>
        </div>
    @endif

    {{-- Formulario --}}
    @if ($showForm)
        <div class="mb-md rounded-xl border border-secondary/30 bg-surface-container-lowest p-md ambient-shadow">
            <h2 class="mb-sm font-heading text-title-lg text-on-surface">
                {{ $editingId ? __('admin/catalog.edit') : __('admin/catalog.new') }}
            </h2>

            <div class="grid gap-sm md:grid-cols-2">
                @foreach ($locales as $codigo => $meta)
                    <div>
                        <label for="name_{{ $codigo }}"
                               class="mb-base block text-caption font-medium text-on-surface-variant">
                            {{ __('admin/catalog.fields.name') }} ({{ $meta['short'] }})
                            @if ($codigo === \App\Support\Locale::default())
                                <span class="text-error">*</span>
                            @endif
                        </label>
                        <input id="name_{{ $codigo }}" type="text" wire:model="name.{{ $codigo }}"
                               lang="{{ $codigo }}"
                               class="w-full rounded-lg border bg-surface-container-low px-sm py-xs text-body-md
                                      text-on-surface focus:ring-1
                                      @error('name.'.$codigo) border-error focus:border-error focus:ring-error
                                      @else border-outline-variant focus:border-secondary focus:ring-secondary @enderror">
                        @error('name.'.$codigo)
                            <p role="alert" class="mt-1 text-caption text-error">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <div>
                    <label for="cat_slug" class="mb-base block text-caption font-medium text-on-surface-variant">
                        {{ __('admin/catalog.fields.slug') }}
                    </label>
                    <input id="cat_slug" type="text" wire:model="slug"
                           class="w-full rounded-lg border bg-surface-container-low px-sm py-xs text-body-md
                                  text-on-surface focus:ring-1
                                  @error('slug') border-error focus:border-error focus:ring-error
                                  @else border-outline-variant focus:border-secondary focus:ring-secondary @enderror">
                    <p class="mt-1 text-caption text-on-surface-variant">
                        {{ __('admin/catalog.fields.slug_help') }}
                    </p>
                    @error('slug')
                        <p role="alert" class="mt-1 text-caption text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="cat_icon" class="mb-base block text-caption font-medium text-on-surface-variant">
                        {{ __('admin/catalog.fields.icon') }}
                    </label>
                    <div class="flex items-center gap-xs">
                        <input id="cat_icon" type="text" wire:model.live.debounce.300ms="icon"
                               placeholder="pool"
                               class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                      px-sm py-xs text-body-md text-on-surface
                                      focus:border-secondary focus:ring-1 focus:ring-secondary">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-lg
                                     bg-surface-container text-secondary">
                            <span class="material-symbols-outlined">{{ $icon ?: 'help' }}</span>
                        </span>
                    </div>
                    <p class="mt-1 text-caption text-on-surface-variant">
                        {{ __('admin/catalog.fields.icon_help') }}
                    </p>
                </div>

                @if ($this->isAmenities())
                    <div>
                        <label for="cat_category" class="mb-base block text-caption font-medium text-on-surface-variant">
                            {{ __('admin/catalog.fields.category') }}
                        </label>
                        <input id="cat_category" type="text" wire:model="category" list="categorias"
                               class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                      px-sm py-xs text-body-md text-on-surface
                                      focus:border-secondary focus:ring-1 focus:ring-secondary">
                        <datalist id="categorias">
                            <option value="building"></option>
                            <option value="services"></option>
                            <option value="interior"></option>
                            <option value="location"></option>
                        </datalist>
                        <p class="mt-1 text-caption text-on-surface-variant">
                            {{ __('admin/catalog.fields.category_help') }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="mt-sm">
                <label class="flex cursor-pointer items-center gap-xs">
                    <input type="checkbox" wire:model="is_active"
                           class="rounded-sm border-outline-variant text-secondary focus:ring-secondary">
                    <span class="text-body-md text-on-surface">{{ __('admin/catalog.fields.is_active') }}</span>
                </label>
            </div>

            <div class="mt-md flex justify-end gap-xs">
                <button type="button" wire:click="cancel"
                        class="rounded-lg border border-outline-variant px-sm py-xs text-label-md
                               text-on-surface transition-colors hover:bg-surface-container-low">
                    {{ __('admin/catalog.cancel') }}
                </button>
                <button type="button" wire:click="save"
                        class="flex items-center gap-xs rounded-lg bg-primary-container px-md py-xs
                               text-label-md font-semibold text-on-primary shadow-sm
                               transition-all hover:shadow-ambient-hover">
                    <span class="material-symbols-outlined text-[20px]">save</span>
                    {{ __('admin/catalog.save') }}
                </button>
            </div>
        </div>
    @else
        <div class="mb-md flex justify-end">
            <button type="button" wire:click="create"
                    class="flex items-center gap-xs rounded-lg bg-primary-container px-md py-xs
                           text-label-md font-semibold text-on-primary shadow-sm
                           transition-all hover:shadow-ambient-hover">
                <span class="material-symbols-outlined text-[20px]">add</span>
                {{ __('admin/catalog.new') }}
            </button>
        </div>
    @endif

    {{-- Listado --}}
    @if ($items->isEmpty())
        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-xl text-center ambient-shadow">
            <p class="text-body-md text-on-surface-variant">{{ __('admin/catalog.empty') }}</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-outline-variant/40 bg-surface-container-lowest ambient-shadow">
            <table class="w-full min-w-[640px] text-left">
                <thead class="border-b border-outline-variant/40 bg-surface-container-low">
                    <tr class="text-caption uppercase tracking-wider text-on-surface-variant">
                        <th class="px-sm py-xs">{{ __('admin/catalog.fields.name') }}</th>
                        <th class="px-sm py-xs">{{ __('admin/catalog.fields.slug') }}</th>
                        @if ($this->isAmenities())
                            <th class="px-sm py-xs">{{ __('admin/catalog.fields.category') }}</th>
                        @endif
                        <th class="px-sm py-xs text-center">{{ __('admin/catalog.fields.usage') }}</th>
                        <th class="px-sm py-xs">{{ __('admin/catalog.fields.is_active') }}</th>
                        <th class="px-sm py-xs text-right">{{ __('admin/properties.table.actions') }}</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-outline-variant/30">
                    @foreach ($items as $item)
                        <tr wire:key="cat-{{ $item->id }}"
                            class="transition-colors hover:bg-surface-container-low
                                   {{ $item->is_active ? '' : 'opacity-60' }}">

                            <td class="px-sm py-xs">
                                <div class="flex items-center gap-xs">
                                    <span class="material-symbols-outlined text-[20px] text-secondary">
                                        {{ $item->icon ?: 'label' }}
                                    </span>
                                    <div>
                                        <div class="text-label-md font-semibold text-on-surface">{{ $item->name }}</div>
                                        @php $raw = json_decode($item->getAttributes()['name'] ?? '{}', true) ?: []; @endphp
                                        <div class="text-caption text-on-surface-variant">
                                            @foreach ($locales as $codigo => $meta)
                                                <span class="mr-xs">
                                                    {{ $meta['short'] }}:
                                                    {{ $raw[$codigo] ?? '—' }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-sm py-xs font-mono text-caption text-on-surface-variant">
                                {{ $item->slug }}
                            </td>

                            @if ($this->isAmenities())
                                <td class="px-sm py-xs text-caption text-on-surface-variant">
                                    {{ $item->category ?: '—' }}
                                </td>
                            @endif

                            <td class="px-sm py-xs text-center text-body-md text-on-surface-variant">
                                {{ $item->properties_count }}
                            </td>

                            <td class="px-sm py-xs">
                                <button type="button" wire:click="toggleActive({{ $item->id }})"
                                        class="rounded-full px-xs py-0.5 text-caption font-semibold transition-colors
                                               {{ $item->is_active
                                                  ? 'bg-tertiary-fixed text-on-tertiary-fixed'
                                                  : 'bg-surface-container text-on-surface-variant' }}">
                                    {{ $item->is_active
                                        ? __('admin/catalog.status.active')
                                        : __('admin/catalog.status.inactive') }}
                                </button>
                            </td>

                            <td class="px-sm py-xs">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" wire:click="move({{ $item->id }}, 'up')"
                                            aria-label="Subir"
                                            class="rounded-lg p-1 text-on-surface-variant hover:text-secondary">
                                        <span class="material-symbols-outlined text-[18px]">arrow_upward</span>
                                    </button>
                                    <button type="button" wire:click="move({{ $item->id }}, 'down')"
                                            aria-label="Bajar"
                                            class="rounded-lg p-1 text-on-surface-variant hover:text-secondary">
                                        <span class="material-symbols-outlined text-[18px]">arrow_downward</span>
                                    </button>
                                    <button type="button" wire:click="edit({{ $item->id }})"
                                            aria-label="{{ __('admin/catalog.edit') }}"
                                            class="rounded-lg p-1 text-on-surface-variant hover:text-secondary">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </button>
                                    <button type="button" wire:click="delete({{ $item->id }})"
                                            wire:confirm="{{ __('admin/catalog.confirm_delete') }}"
                                            aria-label="{{ __('admin/catalog.delete') }}"
                                            class="rounded-lg p-1 text-on-surface-variant hover:text-error">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-sm text-caption text-on-surface-variant">
            {{ __('admin/catalog.inactive_help') }}
        </p>
    @endif
</div>
