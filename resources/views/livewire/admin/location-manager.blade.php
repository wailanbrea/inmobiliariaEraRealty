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

    <p class="mb-md text-body-md text-on-surface-variant">
        {{ __('admin/catalog.locations_intro') }}
    </p>

    <div class="mb-md flex flex-col gap-sm sm:flex-row sm:items-end sm:justify-between">
        <div class="flex-1">
            <label for="loc-search" class="mb-base block text-caption font-medium text-on-surface-variant">
                {{ __('admin/catalog.province') }}
            </label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-xs top-1/2 -translate-y-1/2
                             text-[20px] text-on-surface-variant">search</span>
                <input id="loc-search" type="search" wire:model.live.debounce.300ms="search"
                       class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                              py-xs pl-lg pr-sm text-body-md text-on-surface
                              focus:border-secondary focus:ring-1 focus:ring-secondary">
            </div>
        </div>

        <button type="button" wire:click="startAdd('province')"
                class="flex items-center justify-center gap-xs rounded-lg bg-primary-container px-md py-xs
                       text-label-md font-semibold text-on-primary shadow-sm
                       transition-all hover:shadow-ambient-hover">
            <span class="material-symbols-outlined text-[20px]">add</span>
            {{ __('admin/catalog.add_province') }}
        </button>
    </div>

    @if ($addingType === 'province')
        <div class="mb-sm flex items-end gap-xs rounded-lg border border-secondary/30 bg-surface-container-lowest p-sm">
            <div class="flex-1">
                <label for="new-prov" class="mb-base block text-caption text-on-surface-variant">
                    {{ __('admin/catalog.add_province') }}
                </label>
                <input id="new-prov" type="text" wire:model="newName" wire:keydown.enter="add"
                       placeholder="{{ __('admin/catalog.name_placeholder') }}"
                       class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                              px-sm py-xs text-body-md focus:border-secondary focus:ring-1 focus:ring-secondary">
                @error('newName') <p role="alert" class="mt-1 text-caption text-error">{{ $message }}</p> @enderror
            </div>
            <button wire:click="add"
                    class="rounded-lg bg-primary-container px-sm py-xs text-label-md text-on-primary">
                {{ __('admin/catalog.save') }}
            </button>
            <button wire:click="cancelForms"
                    class="rounded-lg border border-outline-variant px-sm py-xs text-label-md text-on-surface">
                {{ __('admin/catalog.cancel') }}
            </button>
        </div>
    @endif

    {{-- Árbol --}}
    <div class="divide-y divide-outline-variant/30 overflow-hidden rounded-xl border
                border-outline-variant/40 bg-surface-container-lowest ambient-shadow">

        @forelse ($provincias as $provincia)
            <div wire:key="prov-{{ $provincia->id }}">

                {{-- Provincia --}}
                <div class="flex items-center gap-xs px-sm py-xs transition-colors hover:bg-surface-container-low
                            {{ $provincia->is_active ? '' : 'opacity-60' }}">

                    <button type="button" wire:click="toggleProvince({{ $provincia->id }})"
                            class="flex flex-1 items-center gap-xs text-left">
                        <span class="material-symbols-outlined text-[20px] text-on-surface-variant transition-transform
                                     {{ $openProvince === $provincia->id ? 'rotate-90' : '' }}">
                            chevron_right
                        </span>

                        @if ($editingType === 'province' && $editingId === $provincia->id)
                            <input type="text" wire:model="editName" wire:keydown.enter="saveEdit"
                                   @click.stop
                                   class="rounded-lg border border-secondary bg-surface-container-low px-xs py-1
                                          text-body-md focus:ring-1 focus:ring-secondary">
                        @else
                            <span class="text-label-md font-semibold text-on-surface">{{ $provincia->name }}</span>
                            <span class="text-caption text-on-surface-variant">
                                {{ __('admin/catalog.cities_count', ['count' => $provincia->cities_count]) }}
                            </span>
                        @endif
                    </button>

                    <div class="flex items-center gap-1">
                        @if ($editingType === 'province' && $editingId === $provincia->id)
                            <button wire:click="saveEdit" aria-label="{{ __('admin/catalog.save') }}"
                                    class="rounded-lg p-1 text-secondary">
                                <span class="material-symbols-outlined text-[20px]">check</span>
                            </button>
                            <button wire:click="cancelForms" aria-label="{{ __('admin/catalog.cancel') }}"
                                    class="rounded-lg p-1 text-on-surface-variant">
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        @else
                            <button wire:click="toggleActive('province', {{ $provincia->id }})"
                                    class="rounded-full px-xs py-0.5 text-caption font-semibold
                                           {{ $provincia->is_active
                                              ? 'bg-tertiary-fixed text-on-tertiary-fixed'
                                              : 'bg-surface-container text-on-surface-variant' }}">
                                {{ $provincia->is_active
                                    ? __('admin/catalog.status.active')
                                    : __('admin/catalog.status.inactive') }}
                            </button>
                            <button wire:click="startEdit('province', {{ $provincia->id }}, '{{ addslashes($provincia->name) }}')"
                                    aria-label="{{ __('admin/catalog.edit') }}"
                                    class="rounded-lg p-1 text-on-surface-variant hover:text-secondary">
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </button>
                            <button wire:click="delete('province', {{ $provincia->id }})"
                                    wire:confirm="{{ __('admin/catalog.confirm_delete') }}"
                                    aria-label="{{ __('admin/catalog.delete') }}"
                                    class="rounded-lg p-1 text-on-surface-variant hover:text-error">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Ciudades --}}
                @if ($openProvince === $provincia->id)
                    <div class="border-t border-outline-variant/20 bg-surface-container-low/50 pl-md">

                        @foreach ($ciudades as $ciudad)
                            <div wire:key="city-{{ $ciudad->id }}">
                                <div class="flex items-center gap-xs px-sm py-xs
                                            {{ $ciudad->is_active ? '' : 'opacity-60' }}">

                                    <button type="button" wire:click="toggleCity({{ $ciudad->id }})"
                                            class="flex flex-1 items-center gap-xs text-left">
                                        <span class="material-symbols-outlined text-[18px] text-on-surface-variant
                                                     transition-transform {{ $openCity === $ciudad->id ? 'rotate-90' : '' }}">
                                            chevron_right
                                        </span>

                                        @if ($editingType === 'city' && $editingId === $ciudad->id)
                                            <input type="text" wire:model="editName" wire:keydown.enter="saveEdit" @click.stop
                                                   class="rounded-lg border border-secondary bg-surface-container-lowest
                                                          px-xs py-1 text-body-md focus:ring-1 focus:ring-secondary">
                                        @else
                                            <span class="text-body-md text-on-surface">{{ $ciudad->name }}</span>
                                            <span class="text-caption text-on-surface-variant">
                                                {{ __('admin/catalog.sectors_count', ['count' => $ciudad->sectors_count]) }}
                                            </span>
                                        @endif
                                    </button>

                                    <div class="flex items-center gap-1">
                                        @if ($editingType === 'city' && $editingId === $ciudad->id)
                                            <button wire:click="saveEdit" class="rounded-lg p-1 text-secondary"
                                                    aria-label="{{ __('admin/catalog.save') }}">
                                                <span class="material-symbols-outlined text-[20px]">check</span>
                                            </button>
                                            <button wire:click="cancelForms" class="rounded-lg p-1 text-on-surface-variant"
                                                    aria-label="{{ __('admin/catalog.cancel') }}">
                                                <span class="material-symbols-outlined text-[20px]">close</span>
                                            </button>
                                        @else
                                            <button wire:click="toggleActive('city', {{ $ciudad->id }})"
                                                    class="rounded-full px-xs py-0.5 text-caption
                                                           {{ $ciudad->is_active
                                                              ? 'bg-tertiary-fixed text-on-tertiary-fixed'
                                                              : 'bg-surface-container text-on-surface-variant' }}">
                                                {{ $ciudad->is_active
                                                    ? __('admin/catalog.status.active')
                                                    : __('admin/catalog.status.inactive') }}
                                            </button>
                                            <button wire:click="startEdit('city', {{ $ciudad->id }}, '{{ addslashes($ciudad->name) }}')"
                                                    aria-label="{{ __('admin/catalog.edit') }}"
                                                    class="rounded-lg p-1 text-on-surface-variant hover:text-secondary">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </button>
                                            <button wire:click="delete('city', {{ $ciudad->id }})"
                                                    wire:confirm="{{ __('admin/catalog.confirm_delete') }}"
                                                    aria-label="{{ __('admin/catalog.delete') }}"
                                                    class="rounded-lg p-1 text-on-surface-variant hover:text-error">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- Sectores --}}
                                @if ($openCity === $ciudad->id)
                                    <div class="border-l-2 border-outline-variant/30 pb-xs pl-md">
                                        @foreach ($sectores as $sector)
                                            <div wire:key="sect-{{ $sector->id }}"
                                                 class="flex items-center gap-xs px-sm py-1
                                                        {{ $sector->is_active ? '' : 'opacity-60' }}">

                                                @if ($editingType === 'sector' && $editingId === $sector->id)
                                                    <input type="text" wire:model="editName" wire:keydown.enter="saveEdit"
                                                           class="flex-1 rounded-lg border border-secondary
                                                                  bg-surface-container-lowest px-xs py-1 text-body-md">
                                                    <button wire:click="saveEdit" class="rounded-lg p-1 text-secondary"
                                                            aria-label="{{ __('admin/catalog.save') }}">
                                                        <span class="material-symbols-outlined text-[18px]">check</span>
                                                    </button>
                                                    <button wire:click="cancelForms" class="rounded-lg p-1"
                                                            aria-label="{{ __('admin/catalog.cancel') }}">
                                                        <span class="material-symbols-outlined text-[18px]">close</span>
                                                    </button>
                                                @else
                                                    <span class="flex-1 text-body-md text-on-surface-variant">
                                                        {{ $sector->name }}
                                                    </span>
                                                    <button wire:click="toggleActive('sector', {{ $sector->id }})"
                                                            class="rounded-full px-xs text-caption
                                                                   {{ $sector->is_active
                                                                      ? 'text-on-tertiary-container'
                                                                      : 'text-on-surface-variant' }}">
                                                        {{ $sector->is_active
                                                            ? __('admin/catalog.status.active')
                                                            : __('admin/catalog.status.inactive') }}
                                                    </button>
                                                    <button wire:click="startEdit('sector', {{ $sector->id }}, '{{ addslashes($sector->name) }}')"
                                                            aria-label="{{ __('admin/catalog.edit') }}"
                                                            class="rounded-lg p-1 text-on-surface-variant hover:text-secondary">
                                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                                    </button>
                                                    <button wire:click="delete('sector', {{ $sector->id }})"
                                                            wire:confirm="{{ __('admin/catalog.confirm_delete') }}"
                                                            aria-label="{{ __('admin/catalog.delete') }}"
                                                            class="rounded-lg p-1 text-on-surface-variant hover:text-error">
                                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach

                                        @if ($addingType === 'sector' && $addingParent === $ciudad->id)
                                            <div class="flex items-center gap-xs px-sm py-1">
                                                <input type="text" wire:model="newName" wire:keydown.enter="add"
                                                       placeholder="{{ __('admin/catalog.name_placeholder') }}"
                                                       class="flex-1 rounded-lg border border-secondary
                                                              bg-surface-container-lowest px-xs py-1 text-body-md">
                                                <button wire:click="add" class="rounded-lg p-1 text-secondary"
                                                        aria-label="{{ __('admin/catalog.save') }}">
                                                    <span class="material-symbols-outlined text-[18px]">check</span>
                                                </button>
                                                <button wire:click="cancelForms" class="rounded-lg p-1"
                                                        aria-label="{{ __('admin/catalog.cancel') }}">
                                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                                </button>
                                            </div>
                                            @error('newName')
                                                <p role="alert" class="px-sm text-caption text-error">{{ $message }}</p>
                                            @enderror
                                        @else
                                            <button wire:click="startAdd('sector', {{ $ciudad->id }})"
                                                    class="flex items-center gap-xs px-sm py-1 text-caption
                                                           text-secondary hover:underline">
                                                <span class="material-symbols-outlined text-[16px]">add</span>
                                                {{ __('admin/catalog.add_sector') }}
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        @if ($addingType === 'city' && $addingParent === $provincia->id)
                            <div class="flex items-center gap-xs px-sm py-xs">
                                <input type="text" wire:model="newName" wire:keydown.enter="add"
                                       placeholder="{{ __('admin/catalog.name_placeholder') }}"
                                       class="flex-1 rounded-lg border border-secondary bg-surface-container-lowest
                                              px-xs py-1 text-body-md">
                                <button wire:click="add" class="rounded-lg p-1 text-secondary"
                                        aria-label="{{ __('admin/catalog.save') }}">
                                    <span class="material-symbols-outlined text-[20px]">check</span>
                                </button>
                                <button wire:click="cancelForms" class="rounded-lg p-1"
                                        aria-label="{{ __('admin/catalog.cancel') }}">
                                    <span class="material-symbols-outlined text-[20px]">close</span>
                                </button>
                            </div>
                            @error('newName')
                                <p role="alert" class="px-sm text-caption text-error">{{ $message }}</p>
                            @enderror
                        @else
                            <button wire:click="startAdd('city', {{ $provincia->id }})"
                                    class="flex items-center gap-xs px-sm py-xs text-caption
                                           text-secondary hover:underline">
                                <span class="material-symbols-outlined text-[18px]">add</span>
                                {{ __('admin/catalog.add_city') }}
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <p class="p-xl text-center text-body-md text-on-surface-variant">
                {{ __('admin/catalog.empty') }}
            </p>
        @endforelse
    </div>
</div>
