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

    <p class="mb-md text-body-md text-on-surface-variant">{{ __('admin/agents.intro') }}</p>

    {{-- Formulario --}}
    @if ($showForm)
        <div class="mb-md rounded-xl border border-secondary/30 bg-surface-container-lowest p-md ambient-shadow"
             x-data="{ locale: '{{ array_key_first($locales) }}' }">

            <h2 class="mb-sm font-heading text-title-lg text-on-surface">
                {{ $editingId ? __('admin/agents.edit') : __('admin/agents.new') }}
            </h2>

            <div class="grid gap-sm md:grid-cols-2">
                <div>
                    <label for="ag-name" class="mb-base block text-caption font-medium text-on-surface-variant">
                        {{ __('admin/agents.fields.name') }} <span class="text-error">*</span>
                    </label>
                    <input id="ag-name" type="text" wire:model="name"
                           class="w-full rounded-lg border bg-surface-container-low px-sm py-xs text-body-md
                                  text-on-surface focus:ring-1
                                  @error('name') border-error focus:border-error focus:ring-error
                                  @else border-outline-variant focus:border-secondary focus:ring-secondary @enderror">
                    @error('name') <p role="alert" class="mt-1 text-caption text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="ag-email" class="mb-base block text-caption font-medium text-on-surface-variant">
                        {{ __('admin/agents.fields.email') }}
                    </label>
                    <input id="ag-email" type="email" wire:model="email"
                           class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                  px-sm py-xs text-body-md text-on-surface
                                  focus:border-secondary focus:ring-1 focus:ring-secondary">
                    @error('email') <p role="alert" class="mt-1 text-caption text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="ag-phone" class="mb-base block text-caption font-medium text-on-surface-variant">
                        {{ __('admin/agents.fields.phone') }}
                    </label>
                    <input id="ag-phone" type="tel" wire:model="phone" placeholder="(809) 000-0000"
                           class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                  px-sm py-xs text-body-md text-on-surface
                                  focus:border-secondary focus:ring-1 focus:ring-secondary">
                </div>

                <div>
                    <label for="ag-whatsapp" class="mb-base block text-caption font-medium text-on-surface-variant">
                        {{ __('admin/agents.fields.whatsapp') }}
                    </label>
                    <input id="ag-whatsapp" type="tel" wire:model="whatsapp" placeholder="(829) 000-0000"
                           class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                  px-sm py-xs text-body-md text-on-surface
                                  focus:border-secondary focus:ring-1 focus:ring-secondary">
                    <p class="mt-1 text-caption text-on-surface-variant">
                        {{ __('admin/agents.whatsapp_help') }}
                    </p>
                </div>

                <div>
                    <label for="ag-ig" class="mb-base block text-caption font-medium text-on-surface-variant">
                        {{ __('admin/agents.fields.instagram') }}
                    </label>
                    <input id="ag-ig" type="url" wire:model="social_instagram" placeholder="https://"
                           class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                  px-sm py-xs text-body-md text-on-surface
                                  focus:border-secondary focus:ring-1 focus:ring-secondary">
                </div>

                <div>
                    <label for="ag-li" class="mb-base block text-caption font-medium text-on-surface-variant">
                        {{ __('admin/agents.fields.linkedin') }}
                    </label>
                    <input id="ag-li" type="url" wire:model="social_linkedin" placeholder="https://"
                           class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                  px-sm py-xs text-body-md text-on-surface
                                  focus:border-secondary focus:ring-1 focus:ring-secondary">
                </div>
            </div>

            {{-- Cargo y biografía, por idioma --}}
            <div class="mt-md">
                <div class="mb-base flex items-center justify-between">
                    <span class="text-caption font-medium text-on-surface-variant">
                        {{ __('admin/agents.fields.position') }} / {{ __('admin/agents.fields.bio') }}
                    </span>

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
                    <div x-show="locale === '{{ $codigo }}'" x-cloak class="space-y-sm">
                        <input type="text" wire:model="position.{{ $codigo }}" lang="{{ $codigo }}"
                               placeholder="{{ __('admin/agents.fields.position') }} ({{ $meta['short'] }})"
                               aria-label="{{ __('admin/agents.fields.position') }} {{ $meta['short'] }}"
                               class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                      px-sm py-xs text-body-md text-on-surface
                                      focus:border-secondary focus:ring-1 focus:ring-secondary">

                        <textarea wire:model="bio.{{ $codigo }}" rows="3" lang="{{ $codigo }}"
                                  placeholder="{{ __('admin/agents.fields.bio') }} ({{ $meta['short'] }})"
                                  aria-label="{{ __('admin/agents.fields.bio') }} {{ $meta['short'] }}"
                                  class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                         px-sm py-xs text-body-md text-on-surface
                                         focus:border-secondary focus:ring-1 focus:ring-secondary"></textarea>
                    </div>
                @endforeach

                <p class="mt-1 text-caption text-on-surface-variant">{{ __('admin/agents.bio_help') }}</p>
            </div>

            {{-- Foto --}}
            <div class="mt-md">
                <label for="ag-photo" class="mb-base block text-caption font-medium text-on-surface-variant">
                    {{ __('admin/agents.fields.photo') }}
                </label>

                <div class="flex items-start gap-sm">
                    @if ($photo && $photo->isPreviewable())
                        <img src="{{ $photo->temporaryUrl() }}" alt=""
                             class="size-20 rounded-full object-cover">
                    @endif

                    <div class="flex-1">
                        <input id="ag-photo" type="file" wire:model="photo"
                               accept="image/jpeg,image/png,image/webp"
                               class="block w-full text-caption text-on-surface-variant
                                      file:mr-sm file:rounded-lg file:border-0 file:bg-surface-container
                                      file:px-sm file:py-xs file:text-label-md file:text-on-surface
                                      hover:file:bg-surface-container-high">

                        <p class="mt-1 text-caption text-on-surface-variant">
                            {{ __('admin/agents.photo_help') }}
                        </p>

                        <div wire:loading wire:target="photo" class="mt-1 text-caption text-secondary">Subiendo…</div>

                        @error('photo') <p role="alert" class="mt-1 text-caption text-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="mt-sm">
                <label class="flex cursor-pointer items-center gap-xs">
                    <input type="checkbox" wire:model="is_active"
                           class="rounded-sm border-outline-variant text-secondary focus:ring-secondary">
                    <span class="text-body-md text-on-surface">{{ __('admin/agents.fields.is_active') }}</span>
                </label>
            </div>

            <div class="mt-md flex justify-end gap-xs">
                <button type="button" wire:click="cancel"
                        class="rounded-lg border border-outline-variant px-sm py-xs text-label-md text-on-surface">
                    {{ __('admin/agents.cancel') }}
                </button>
                <button type="button" wire:click="save"
                        class="flex items-center gap-xs rounded-lg bg-primary-container px-md py-xs
                               text-label-md font-semibold text-on-primary shadow-sm
                               transition-all hover:shadow-ambient-hover">
                    <span class="material-symbols-outlined text-[20px]">save</span>
                    {{ __('admin/agents.save') }}
                </button>
            </div>
        </div>
    @else
        <div class="mb-md flex justify-end">
            <button type="button" wire:click="create"
                    class="flex items-center gap-xs rounded-lg bg-primary-container px-md py-xs
                           text-label-md font-semibold text-on-primary shadow-sm
                           transition-all hover:shadow-ambient-hover">
                <span class="material-symbols-outlined text-[20px]">person_add</span>
                {{ __('admin/agents.new') }}
            </button>
        </div>
    @endif

    {{-- Listado --}}
    @if ($agents->isEmpty())
        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest
                    p-xl text-center ambient-shadow">
            <span class="material-symbols-outlined text-[48px] text-outline-variant">badge</span>
            <p class="mt-sm text-body-md text-on-surface-variant">{{ __('admin/agents.empty') }}</p>
        </div>
    @else
        <ul class="grid grid-cols-1 gap-sm md:grid-cols-2 xl:grid-cols-3">
            @foreach ($agents as $agente)
                <li wire:key="agent-{{ $agente->id }}"
                    @class([
                        'rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm ambient-shadow',
                        'opacity-60' => ! $agente->is_active,
                    ])>

                    <div class="flex items-start gap-sm">
                        @if ($agente->photoUrl())
                            <img src="{{ $agente->photoUrl() }}" alt="{{ $agente->name }}"
                                 loading="lazy" class="size-16 shrink-0 rounded-full object-cover">
                        @else
                            <span class="flex size-16 shrink-0 items-center justify-center rounded-full
                                         bg-primary-container text-title-lg text-on-primary">
                                {{ Str::upper(Str::substr($agente->name, 0, 1)) }}
                            </span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-title-lg text-on-surface">{{ $agente->name }}</h3>

                            @if ($agente->position)
                                <p class="truncate text-label-md text-secondary">{{ $agente->position }}</p>
                            @endif

                            <p class="mt-1 text-caption text-on-surface-variant">
                                {{ $agente->properties_count }} {{ __('admin/agents.fields.properties') }}
                            </p>

                            <div class="mt-1 flex flex-wrap gap-1 text-caption text-on-surface-variant">
                                @if ($agente->whatsapp)
                                    <span class="inline-flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">chat</span>
                                        {{ $agente->whatsapp }}
                                    </span>
                                @endif
                                @if ($agente->email)
                                    <span class="inline-flex items-center gap-1 truncate">
                                        <span class="material-symbols-outlined text-[14px]">mail</span>
                                        {{ $agente->email }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-sm flex flex-wrap items-center justify-between gap-xs
                                border-t border-outline-variant/30 pt-xs">

                        <button type="button" wire:click="toggleActive({{ $agente->id }})"
                                class="rounded-full px-xs py-0.5 text-caption font-semibold transition-colors
                                       {{ $agente->is_active
                                          ? 'bg-tertiary-fixed text-on-tertiary-fixed'
                                          : 'bg-surface-container text-on-surface-variant' }}">
                            {{ $agente->is_active
                                ? __('admin/agents.status.visible')
                                : __('admin/agents.status.hidden') }}
                        </button>

                        <div class="flex items-center gap-1">
                            <button type="button" wire:click="move({{ $agente->id }}, 'up')" aria-label="Subir"
                                    class="rounded-lg p-1 text-on-surface-variant hover:text-secondary">
                                <span class="material-symbols-outlined text-[18px]">arrow_upward</span>
                            </button>
                            <button type="button" wire:click="move({{ $agente->id }}, 'down')" aria-label="Bajar"
                                    class="rounded-lg p-1 text-on-surface-variant hover:text-secondary">
                                <span class="material-symbols-outlined text-[18px]">arrow_downward</span>
                            </button>

                            @if ($agente->photo)
                                <button type="button" wire:click="removePhoto({{ $agente->id }})"
                                        aria-label="{{ __('admin/agents.remove_photo') }}"
                                        title="{{ __('admin/agents.remove_photo') }}"
                                        class="rounded-lg p-1 text-on-surface-variant hover:text-secondary">
                                    <span class="material-symbols-outlined text-[18px]">hide_image</span>
                                </button>
                            @endif

                            <button type="button" wire:click="edit({{ $agente->id }})"
                                    aria-label="{{ __('admin/agents.edit') }}"
                                    class="rounded-lg p-1 text-on-surface-variant hover:text-secondary">
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </button>

                            <button type="button" wire:click="confirmDelete({{ $agente->id }})"
                                    aria-label="{{ __('admin/agents.delete') }}"
                                    class="rounded-lg p-1 text-on-surface-variant hover:text-error">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <p class="mt-sm text-caption text-on-surface-variant">{{ __('admin/agents.inactive_help') }}</p>
    @endif

    {{-- Confirmación de borrado --}}
    @if ($confirmingId)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-primary/50 p-margin-mobile"
             role="dialog" aria-modal="true" wire:keydown.escape="cancelDelete">
            <div class="w-full max-w-md rounded-xl bg-surface-container-lowest p-md shadow-ambient-lg">
                <h2 class="mb-sm font-heading text-title-lg text-on-surface">
                    {{ __('admin/agents.confirm_delete') }}
                </h2>

                @if ($confirmingProperties > 0)
                    <div class="mb-sm rounded-lg bg-error-container px-sm py-xs">
                        <p class="flex items-start gap-xs text-body-md text-on-error-container">
                            <span class="material-symbols-outlined text-[20px]">warning</span>
                            {{ __('admin/agents.has_properties', ['count' => $confirmingProperties]) }}
                        </p>
                    </div>
                @else
                    <p class="mb-sm text-body-md text-on-surface-variant">
                        {{ __('admin/agents.no_properties') }}
                    </p>
                @endif

                <p class="text-caption text-on-surface-variant">
                    {{ __('admin/agents.confirm_delete_body') }}
                </p>

                <div class="mt-md flex justify-end gap-xs">
                    <button type="button" wire:click="cancelDelete"
                            class="rounded-lg border border-outline-variant px-sm py-xs text-label-md text-on-surface">
                        {{ __('admin/agents.cancel') }}
                    </button>
                    <button type="button" wire:click="delete"
                            class="rounded-lg bg-error px-md py-xs text-label-md font-semibold text-on-error">
                        {{ __('admin/agents.delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
