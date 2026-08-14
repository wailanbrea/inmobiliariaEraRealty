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

    <p class="mb-md text-body-md text-on-surface-variant">{{ __('admin/media.intro') }}</p>

    {{-- Subida --}}
    <div class="mb-md rounded-xl border-2 border-dashed border-outline-variant
                bg-surface-container-low p-md text-center">
        <span class="material-symbols-outlined text-[36px] text-outline">cloud_upload</span>

        <label class="mt-xs inline-flex cursor-pointer items-center gap-xs rounded-lg
                      border border-outline-variant bg-surface-container-lowest px-sm py-xs
                      text-label-md text-on-surface transition-colors hover:bg-surface-container">
            <span class="material-symbols-outlined text-[18px]">add_photo_alternate</span>
            {{ __('admin/media.upload') }}
            <input type="file" class="sr-only" multiple wire:model="uploads"
                   accept="image/jpeg,image/png,image/webp">
        </label>

        <div wire:loading wire:target="uploads" class="mt-xs text-caption text-secondary">
            Subiendo…
        </div>

        @error('uploads.*')
            <p role="alert" class="mt-xs text-caption text-error">{{ $message }}</p>
        @enderror
    </div>

    {{-- Filtros --}}
    <div class="mb-md flex flex-col gap-sm sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="media-search" class="mb-base block text-caption font-medium text-on-surface-variant">
                {{ __('admin/media.search') }}
            </label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-xs top-1/2 -translate-y-1/2
                             text-[20px] text-on-surface-variant">search</span>
                <input id="media-search" type="search" wire:model.live.debounce.400ms="search"
                       class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                              py-xs pl-lg pr-sm text-body-md text-on-surface
                              focus:border-secondary focus:ring-1 focus:ring-secondary">
            </div>
        </div>

        <div class="sm:w-48">
            <label for="media-context" class="mb-base block text-caption font-medium text-on-surface-variant">
                {{ __('admin/media.context') }}
            </label>
            <select id="media-context" wire:model.live="context"
                    class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                           px-sm py-xs text-body-md text-on-surface
                           focus:border-secondary focus:ring-1 focus:ring-secondary">
                <option value="">{{ __('admin/media.all_contexts') }}</option>
                @foreach ($contexts as $c)
                    <option value="{{ $c }}">{{ __("admin/media.contexts.{$c}") }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center rounded-lg border border-outline-variant" role="group">
            <button type="button" wire:click="$set('view', 'grid')"
                    :aria-pressed="'{{ $view }}' === 'grid'"
                    aria-label="{{ __('admin/media.view_grid') }}"
                    @class([
                        'rounded-lg p-xs transition-colors',
                        'bg-primary-container text-on-primary' => $view === 'grid',
                        'text-on-surface-variant hover:text-secondary' => $view !== 'grid',
                    ])>
                <span class="material-symbols-outlined text-[20px]">grid_view</span>
            </button>
            <button type="button" wire:click="$set('view', 'list')"
                    aria-label="{{ __('admin/media.view_list') }}"
                    @class([
                        'rounded-lg p-xs transition-colors',
                        'bg-primary-container text-on-primary' => $view === 'list',
                        'text-on-surface-variant hover:text-secondary' => $view !== 'list',
                    ])>
                <span class="material-symbols-outlined text-[20px]">view_list</span>
            </button>
        </div>
    </div>

    {{-- Contenido --}}
    @if ($files->isEmpty())
        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-xl text-center ambient-shadow">
            <span class="material-symbols-outlined text-[48px] text-outline-variant">photo_library</span>
            <p class="mt-sm text-body-md text-on-surface-variant">
                {{ $search || $context ? __('admin/media.empty_filtered') : __('admin/media.empty') }}
            </p>
        </div>

    @elseif ($view === 'grid')
        <div class="grid grid-cols-2 gap-sm sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @foreach ($files as $file)
                <figure wire:key="media-{{ $file->id }}"
                        class="overflow-hidden rounded-lg border border-outline-variant/40
                               bg-surface-container-lowest ambient-shadow">
                    <div class="aspect-[4/3] overflow-hidden bg-surface-container">
                        <img src="{{ $file->thumbnailUrl() }}"
                             alt="{{ $file->alt_text ?: $file->original_name }}"
                             class="size-full object-cover" loading="lazy">
                    </div>

                    <figcaption class="p-1">
                        <p class="truncate text-caption text-on-surface" title="{{ $file->original_name }}">
                            {{ $file->original_name }}
                        </p>
                        <p class="text-caption text-on-surface-variant">{{ $file->humanSize() }}</p>

                        @unless ($file->alt_text)
                            <p class="text-caption text-error">
                                <span class="material-symbols-outlined align-middle text-[14px]">warning</span>
                                {{ __('admin/media.missing_alt') }}
                            </p>
                        @endunless

                        <div class="mt-1 flex items-center justify-end gap-0.5">
                            <button type="button"
                                    x-data="{ copiado: false }"
                                    @click="navigator.clipboard.writeText('{{ $file->url() }}');
                                            copiado = true; setTimeout(() => copiado = false, 1500)"
                                    :title="copiado ? '{{ __('admin/media.copied') }}' : '{{ __('admin/media.copy_url') }}'"
                                    aria-label="{{ __('admin/media.copy_url') }}"
                                    class="rounded-lg p-1 text-on-surface-variant transition-colors hover:text-secondary">
                                <span class="material-symbols-outlined text-[18px]"
                                      x-text="copiado ? 'check' : 'link'">link</span>
                            </button>

                            <button type="button" wire:click="edit({{ $file->id }})"
                                    aria-label="{{ __('admin/media.edit') }}"
                                    class="rounded-lg p-1 text-on-surface-variant transition-colors hover:text-secondary">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </button>

                            <button type="button" wire:click="confirmDelete({{ $file->id }})"
                                    aria-label="{{ __('admin/media.delete') }}"
                                    class="rounded-lg p-1 text-on-surface-variant transition-colors hover:text-error">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </div>
                    </figcaption>
                </figure>
            @endforeach
        </div>

    @else
        <div class="table-scroll rounded-xl border border-outline-variant/40
                    bg-surface-container-lowest ambient-shadow">
            <table class="w-full min-w-[720px] text-left">
                <thead class="border-b border-outline-variant/40 bg-surface-container-low">
                    <tr class="text-caption uppercase tracking-wider text-on-surface-variant">
                        <th class="px-sm py-xs">{{ __('admin/media.table.file') }}</th>
                        <th class="px-sm py-xs">{{ __('admin/media.table.context') }}</th>
                        <th class="px-sm py-xs">{{ __('admin/media.table.dimensions') }}</th>
                        <th class="px-sm py-xs">{{ __('admin/media.table.size') }}</th>
                        <th class="px-sm py-xs">{{ __('admin/media.table.date') }}</th>
                        <th class="px-sm py-xs text-right">{{ __('admin/media.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/30">
                    @foreach ($files as $file)
                        <tr wire:key="media-row-{{ $file->id }}" class="hover:bg-surface-container-low">
                            <td class="px-sm py-xs">
                                <div class="flex items-center gap-xs">
                                    <img src="{{ $file->thumbnailUrl() }}" alt=""
                                         class="size-10 rounded object-cover" loading="lazy">
                                    <div class="min-w-0">
                                        <p class="truncate text-label-md text-on-surface">{{ $file->original_name }}</p>
                                        <p class="truncate text-caption text-on-surface-variant">
                                            {{ $file->alt_text ?: __('admin/media.missing_alt') }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-sm py-xs text-caption text-on-surface-variant">
                                {{ $file->context ? __("admin/media.contexts.{$file->context}") : '—' }}
                            </td>
                            <td class="whitespace-nowrap px-sm py-xs text-caption text-on-surface-variant">
                                {{ $file->width }}×{{ $file->height }}
                            </td>
                            <td class="whitespace-nowrap px-sm py-xs text-caption text-on-surface-variant">
                                {{ $file->humanSize() }}
                            </td>
                            <td class="whitespace-nowrap px-sm py-xs text-caption text-on-surface-variant">
                                {{ $file->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-sm py-xs">
                                <div class="flex items-center justify-end gap-0.5">
                                    <a href="{{ $file->url() }}" target="_blank" rel="noopener"
                                       aria-label="{{ __('admin/media.open') }}"
                                       class="rounded-lg p-1 text-on-surface-variant hover:text-secondary">
                                        <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                                    </a>
                                    <button type="button" wire:click="edit({{ $file->id }})"
                                            aria-label="{{ __('admin/media.edit') }}"
                                            class="rounded-lg p-1 text-on-surface-variant hover:text-secondary">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $file->id }})"
                                            aria-label="{{ __('admin/media.delete') }}"
                                            class="rounded-lg p-1 text-on-surface-variant hover:text-error">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-sm">{{ $files->links() }}</div>

    {{-- Modal de edición --}}
    @if ($editingId)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-primary/50 p-margin-mobile"
             role="dialog" aria-modal="true" wire:keydown.escape="cancelEdit">
            <div class="w-full max-w-md rounded-xl bg-surface-container-lowest p-md shadow-ambient-lg">
                <h2 class="mb-sm font-heading text-title-lg text-on-surface">{{ __('admin/media.edit') }}</h2>

                <div class="space-y-sm">
                    <div>
                        <label for="media-alt" class="mb-base block text-caption font-medium text-on-surface-variant">
                            {{ __('admin/media.alt_text') }}
                        </label>
                        <input id="media-alt" type="text" wire:model="editAlt"
                               class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                      px-sm py-xs text-body-md focus:border-secondary focus:ring-1 focus:ring-secondary">
                        <p class="mt-1 text-caption text-on-surface-variant">{{ __('admin/media.alt_help') }}</p>
                    </div>

                    <div>
                        <label for="media-title" class="mb-base block text-caption font-medium text-on-surface-variant">
                            {{ __('admin/media.title_field') }}
                        </label>
                        <input id="media-title" type="text" wire:model="editTitle"
                               class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                      px-sm py-xs text-body-md focus:border-secondary focus:ring-1 focus:ring-secondary">
                    </div>

                    <div class="flex justify-end gap-xs pt-xs">
                        <button type="button" wire:click="cancelEdit"
                                class="rounded-lg border border-outline-variant px-sm py-xs text-label-md text-on-surface">
                            {{ __('admin/media.cancel') }}
                        </button>
                        <button type="button" wire:click="saveEdit"
                                class="rounded-lg bg-primary-container px-md py-xs text-label-md font-semibold text-on-primary">
                            {{ __('admin/media.save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Confirmación de borrado, con los usos encontrados --}}
    @if ($confirmingId)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-primary/50 p-margin-mobile"
             role="dialog" aria-modal="true" wire:keydown.escape="cancelDelete">
            <div class="w-full max-w-md rounded-xl bg-surface-container-lowest p-md shadow-ambient-lg">
                <h2 class="mb-sm font-heading text-title-lg text-on-surface">
                    {{ __('admin/media.confirm_delete') }}
                </h2>

                @if ($confirmingUsages)
                    <div class="mb-sm rounded-lg bg-error-container px-sm py-xs">
                        <p class="flex items-center gap-xs text-label-md font-semibold text-on-error-container">
                            <span class="material-symbols-outlined text-[20px]">warning</span>
                            {{ __('admin/media.in_use_warning') }}
                        </p>
                        <ul class="mt-1 list-inside list-disc text-caption text-on-error-container">
                            @foreach ($confirmingUsages as $uso)
                                <li>{{ $uso }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-1 text-caption text-on-error-container">{{ __('admin/media.in_use_body') }}</p>
                    </div>
                @else
                    <p class="mb-sm text-body-md text-on-surface-variant">{{ __('admin/media.not_in_use') }}</p>
                @endif

                <p class="text-caption text-on-surface-variant">{{ __('admin/media.confirm_delete_body') }}</p>

                <div class="mt-md flex justify-end gap-xs">
                    <button type="button" wire:click="cancelDelete"
                            class="rounded-lg border border-outline-variant px-sm py-xs text-label-md text-on-surface">
                        {{ __('admin/media.cancel') }}
                    </button>
                    <button type="button" wire:click="delete"
                            class="rounded-lg bg-error px-md py-xs text-label-md font-semibold text-on-error">
                        {{ __('admin/media.delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
