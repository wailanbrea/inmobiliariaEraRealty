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

    <p class="mb-md text-body-md text-on-surface-variant">{{ __('admin/content.intro') }}</p>

    <div class="space-y-sm">
        @foreach ($sections as $seccion)
            @php $editando = $editingId === $seccion->id; @endphp

            <div wire:key="sec-{{ $seccion->id }}"
                 @class([
                     'rounded-xl border bg-surface-container-lowest p-md ambient-shadow',
                     'border-secondary/40' => $editando,
                     'border-outline-variant/40' => ! $editando,
                     'opacity-60' => ! $seccion->is_active,
                 ])>

                {{-- Cabecera --}}
                <div class="flex flex-wrap items-start justify-between gap-sm">
                    <div class="min-w-0 flex-1">
                        <h2 class="font-heading text-title-lg text-on-surface">
                            {{ __("admin/content.sections.{$seccion->section_key}") }}
                        </h2>
                        <p class="mt-base text-caption text-on-surface-variant">
                            {{ __("admin/content.section_help.{$seccion->section_key}") }}
                        </p>

                        @unless ($editando)
                            <p class="mt-xs truncate text-body-md text-on-surface">
                                {{ $seccion->translated()?->title ?: '—' }}
                            </p>
                        @endunless
                    </div>

                    <div class="flex shrink-0 items-center gap-xs">
                        <button type="button" wire:click="toggleActive({{ $seccion->id }})"
                                class="rounded-full px-xs py-0.5 text-caption font-semibold transition-colors
                                       {{ $seccion->is_active
                                          ? 'bg-tertiary-fixed text-on-tertiary-fixed'
                                          : 'bg-surface-container text-on-surface-variant' }}">
                            {{ $seccion->is_active
                                ? __('admin/content.status.visible')
                                : __('admin/content.status.hidden') }}
                        </button>

                        @unless ($editando)
                            <button type="button" wire:click="edit({{ $seccion->id }})"
                                    class="flex items-center gap-xs rounded-lg border border-outline-variant
                                           px-sm py-xs text-label-md text-on-surface transition-colors
                                           hover:bg-surface-container-low">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                {{ __('admin/content.edit') }}
                            </button>
                        @endunless
                    </div>
                </div>

                {{-- Imagen actual --}}
                @if ($seccion->image)
                    <div class="mt-sm flex items-center gap-sm">
                        <img src="{{ $seccion->imageUrl() }}" alt=""
                             class="h-20 w-32 rounded-lg object-cover" loading="lazy">
                        <button type="button" wire:click="removeImage({{ $seccion->id }})"
                                wire:confirm="{{ __('admin/content.remove_image') }}?"
                                class="inline-flex items-center gap-1 text-caption text-error hover:underline">
                            <span class="material-symbols-outlined text-[16px]">delete</span>
                            {{ __('admin/content.remove_image') }}
                        </button>
                    </div>
                @elseif ($seccion->section_key === 'hero')
                    <p class="mt-sm flex items-center gap-xs rounded-lg bg-surface-container-low px-sm py-xs
                              text-caption text-on-surface-variant">
                        <span class="material-symbols-outlined text-[18px]">image</span>
                        {{ __('admin/content.no_image') }}
                    </p>
                @endif

                {{-- Formulario --}}
                @if ($editando)
                    <div class="mt-md space-y-md border-t border-outline-variant/30 pt-md"
                         x-data="{ locale: '{{ array_key_first($locales) }}' }">

                        <div class="flex items-center justify-end">
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
                                @foreach (['title' => 'text', 'subtitle' => 'text', 'content' => 'textarea', 'button_text' => 'text'] as $campo => $tipo)
                                    <div>
                                        <label for="{{ $campo }}_{{ $codigo }}"
                                               class="mb-base block text-caption font-medium text-on-surface-variant">
                                            {{ __("admin/content.fields.{$campo}") }} ({{ $meta['short'] }})
                                        </label>

                                        @if ($tipo === 'textarea')
                                            <textarea id="{{ $campo }}_{{ $codigo }}" rows="4" lang="{{ $codigo }}"
                                                      wire:model="fields.{{ $codigo }}.{{ $campo }}"
                                                      class="w-full rounded-lg border border-outline-variant
                                                             bg-surface-container-low px-sm py-xs text-body-md
                                                             text-on-surface focus:border-secondary focus:ring-1
                                                             focus:ring-secondary"></textarea>
                                        @else
                                            <input id="{{ $campo }}_{{ $codigo }}" type="text" lang="{{ $codigo }}"
                                                   wire:model="fields.{{ $codigo }}.{{ $campo }}"
                                                   class="w-full rounded-lg border border-outline-variant
                                                          bg-surface-container-low px-sm py-xs text-body-md
                                                          text-on-surface focus:border-secondary focus:ring-1
                                                          focus:ring-secondary">
                                        @endif

                                        @error("fields.{$codigo}.{$campo}")
                                            <p role="alert" class="mt-1 text-caption text-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        @endforeach

                        <div>
                            <label for="url_{{ $seccion->id }}"
                                   class="mb-base block text-caption font-medium text-on-surface-variant">
                                {{ __('admin/content.fields.button_url') }}
                            </label>
                            <input id="url_{{ $seccion->id }}" type="text" wire:model="buttonUrl"
                                   placeholder="/contactanos"
                                   class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                          px-sm py-xs text-body-md text-on-surface focus:border-secondary
                                          focus:ring-1 focus:ring-secondary">
                        </div>

                        {{-- Imagen --}}
                        <div>
                            <label for="img_{{ $seccion->id }}"
                                   class="mb-base block text-caption font-medium text-on-surface-variant">
                                {{ __('admin/content.fields.image') }}
                            </label>

                            <input id="img_{{ $seccion->id }}" type="file" wire:model="image"
                                   accept="image/jpeg,image/png,image/webp"
                                   class="block w-full text-caption text-on-surface-variant
                                          file:mr-sm file:rounded-lg file:border-0 file:bg-surface-container
                                          file:px-sm file:py-xs file:text-label-md file:text-on-surface
                                          hover:file:bg-surface-container-high">

                            <p class="mt-1 text-caption text-on-surface-variant">
                                {{ $seccion->section_key === 'hero'
                                    ? __('admin/content.hero_image_help')
                                    : __('admin/content.image_help') }}
                            </p>

                            <div wire:loading wire:target="image" class="mt-1 text-caption text-secondary">
                                Subiendo…
                            </div>

                            @error('image')
                                <p role="alert" class="mt-1 text-caption text-error">{{ $message }}</p>
                            @enderror

                            {{-- isPreviewable() es obligatorio: temporaryUrl()
                                 lanza una excepción con archivos que no son
                                 imagen (por ejemplo un PDF elegido por error),
                                 y tumbaría la pantalla con un 500 en lugar de
                                 mostrar el error de validación. --}}
                            @if ($image && $image->isPreviewable())
                                <img src="{{ $image->temporaryUrl() }}" alt=""
                                     class="mt-xs h-24 w-40 rounded-lg object-cover">
                            @endif
                        </div>

                        <div class="flex justify-end gap-xs">
                            <button type="button" wire:click="cancel"
                                    class="rounded-lg border border-outline-variant px-sm py-xs
                                           text-label-md text-on-surface">
                                {{ __('admin/content.cancel') }}
                            </button>
                            <button type="button" wire:click="save"
                                    class="flex items-center gap-xs rounded-lg bg-primary-container px-md py-xs
                                           text-label-md font-semibold text-on-primary shadow-sm
                                           transition-all hover:shadow-ambient-hover">
                                <span class="material-symbols-outlined text-[20px]">save</span>
                                {{ __('admin/content.save') }}
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
