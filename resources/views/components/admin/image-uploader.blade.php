@props(['property'])

@php
    $imagenes = $property->images->map(fn ($i) => [
        'id' => $i->id,
        'thumb' => $i->thumbnailUrl(),
        'url' => $i->url(),
        'is_main' => $i->is_main,
        'alt_text' => $i->alt_text,
        'title' => $i->title,
        'original_name' => $i->original_name,
        'size' => $i->size,
        'width' => $i->width,
        'height' => $i->height,
    ])->values();

    $config = [
        'images' => $imagenes,
        'maxImages' => \App\Modules\PropertyImages\Services\PropertyImageService::MAX_IMAGES,
        'maxSizeMb' => 5,
        'csrf' => csrf_token(),
        'endpoints' => [
            'store' => route('admin.properties.images.store', $property),
            'reorder' => route('admin.properties.images.reorder', $property),
            'main' => route('admin.properties.images.main', [$property, ':id']),
            'update' => route('admin.properties.images.update', [$property, ':id']),
            'destroy' => route('admin.properties.images.destroy', [$property, ':id']),
        ],
    ];
@endphp

<div x-data="uploader(@js($config))" x-cloak>

    {{-- Zona de arrastre --}}
    <div @dragover.prevent="dragging = true"
         @dragleave.prevent="dragging = false"
         @drop.prevent="onDrop($event)"
         :class="dragging
            ? 'border-secondary bg-secondary-fixed/40'
            : 'border-outline-variant bg-surface-container-low'"
         class="rounded-xl border-2 border-dashed p-md text-center transition-colors">

        <span class="material-symbols-outlined text-[40px] text-outline"
              :class="dragging && 'text-secondary'">upload</span>

        <p class="mt-xs text-body-md text-on-surface">
            {{ __('admin/images.drop_here') }}
        </p>

        <label class="mt-xs inline-flex cursor-pointer items-center gap-xs rounded-lg
                      border border-outline-variant bg-surface-container-lowest px-sm py-xs
                      text-label-md text-on-surface transition-colors hover:bg-surface-container"
               :class="isFull && 'pointer-events-none opacity-50'">
            <span class="material-symbols-outlined text-[18px]">add_photo_alternate</span>
            {{ __('admin/images.select') }}
            <input type="file" class="sr-only" multiple
                   accept="image/jpeg,image/png,image/webp"
                   :disabled="isFull"
                   @change="onSelect($event)">
        </label>

        <p class="mt-xs text-caption text-on-surface-variant">
            {{ __('admin/images.constraints', [
                'max' => \App\Modules\PropertyImages\Services\PropertyImageService::MAX_IMAGES,
                'size' => 5,
            ]) }}
            &middot;
            <span x-text="`${images.length} / ${maxImages}`"></span>
        </p>
    </div>

    {{-- Errores --}}
    <template x-if="errors.length">
        <div role="alert" class="mt-sm space-y-1 rounded-lg bg-error-container px-sm py-xs">
            <template x-for="(error, i) in errors" :key="i">
                <p class="flex items-start gap-xs text-caption text-on-error-container">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                    <span x-text="error"></span>
                </p>
            </template>
            <button type="button" @click="errors = []"
                    class="text-caption text-on-error-container underline">
                {{ __('admin/images.dismiss') }}
            </button>
        </div>
    </template>

    {{-- Subidas en curso --}}
    <template x-if="queue.length">
        <div class="mt-sm space-y-xs">
            <template x-for="item in queue" :key="item.id">
                <div class="rounded-lg bg-surface-container-low px-sm py-xs">
                    <div class="flex items-center justify-between text-caption text-on-surface-variant">
                        <span x-text="item.name" class="truncate"></span>
                        <span x-text="`${item.progress}%`"></span>
                    </div>
                    <div class="mt-1 h-1 overflow-hidden rounded-full bg-surface-container-high">
                        <div class="h-full rounded-full bg-secondary transition-all duration-200"
                             :style="`width: ${item.progress}%`"></div>
                    </div>
                </div>
            </template>
        </div>
    </template>

    {{-- Rejilla de imágenes --}}
    <div x-ref="grid" class="mt-md grid grid-cols-2 gap-sm sm:grid-cols-3 lg:grid-cols-4">
        <template x-for="image in images" :key="image.id">
            <figure :data-image-id="image.id"
                    class="group relative overflow-hidden rounded-lg border border-outline-variant/40
                           bg-surface-container-lowest ambient-shadow">

                <div class="relative aspect-[4/3] overflow-hidden bg-surface-container">
                    <img :src="image.thumb" :alt="image.alt_text || image.original_name"
                         class="size-full object-cover" loading="lazy">

                    {{-- Distintivo de principal --}}
                    <template x-if="image.is_main">
                        <span class="absolute left-1 top-1 flex items-center gap-1 rounded-full
                                     bg-on-tertiary-container px-xs py-0.5 text-caption font-semibold text-white">
                            <span class="material-symbols-outlined text-[14px]">star</span>
                            {{ __('admin/images.main') }}
                        </span>
                    </template>

                    {{-- Asa de arrastre --}}
                    <button type="button" data-drag-handle
                            :aria-label="`{{ __('admin/images.reorder') }}: ${image.original_name}`"
                            class="absolute right-1 top-1 cursor-grab rounded-lg bg-surface-container-lowest/90
                                   p-1 text-on-surface-variant backdrop-blur active:cursor-grabbing">
                        <span class="material-symbols-outlined text-[18px]">drag_indicator</span>
                    </button>
                </div>

                {{-- Acciones --}}
                <figcaption class="flex items-center justify-between gap-1 p-1">
                    <span class="truncate text-caption text-on-surface-variant"
                          x-text="formatSize(image.size)"></span>

                    <div class="flex items-center gap-0.5">
                        <button type="button" @click="setMain(image)"
                                :disabled="image.is_main"
                                :aria-label="`{{ __('admin/images.set_main') }}`"
                                :title="`{{ __('admin/images.set_main') }}`"
                                class="rounded-lg p-1 transition-colors disabled:opacity-30"
                                :class="image.is_main ? 'text-on-tertiary-container' : 'text-on-surface-variant hover:text-secondary'">
                            <span class="material-symbols-outlined text-[18px]"
                                  x-text="image.is_main ? 'star' : 'star_outline'"></span>
                        </button>

                        <button type="button" @click="startEdit(image)"
                                :aria-label="`{{ __('admin/images.edit_alt') }}`"
                                :title="`{{ __('admin/images.edit_alt') }}`"
                                class="rounded-lg p-1 text-on-surface-variant transition-colors hover:text-secondary">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                        </button>

                        <button type="button" @click="remove(image)"
                                :aria-label="`{{ __('admin/images.delete') }}`"
                                :title="`{{ __('admin/images.delete') }}`"
                                class="rounded-lg p-1 text-on-surface-variant transition-colors hover:text-error">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </div>
                </figcaption>

                {{-- Aviso de alt vacío: una imagen sin alt penaliza SEO
                     y deja fuera a quien usa lector de pantalla. --}}
                <template x-if="!image.alt_text">
                    <p class="border-t border-outline-variant/30 px-1 pb-1 text-caption text-on-surface-variant">
                        <span class="material-symbols-outlined align-middle text-[14px] text-error">warning</span>
                        {{ __('admin/images.missing_alt') }}
                    </p>
                </template>
            </figure>
        </template>
    </div>

    {{-- Modal de alt/título --}}
    <template x-if="editing">
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-primary/50 p-margin-mobile"
             @keydown.escape.window="editing = null" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-xl bg-surface-container-lowest p-md shadow-ambient-lg"
                 @click.outside="editing = null">

                <h3 class="mb-sm font-heading text-title-lg text-on-surface">
                    {{ __('admin/images.edit_alt') }}
                </h3>

                <template x-for="image in images.filter(i => i.id === editing)" :key="image.id">
                    <div class="space-y-sm">
                        <div>
                            <label :for="`alt-${image.id}`"
                                   class="mb-base block text-caption font-medium text-on-surface-variant">
                                {{ __('admin/images.alt_text') }}
                            </label>
                            <input :id="`alt-${image.id}`" type="text" x-model="editAlt"
                                   class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                          px-sm py-xs text-body-md text-on-surface
                                          focus:border-secondary focus:ring-1 focus:ring-secondary">
                            <p class="mt-1 text-caption text-on-surface-variant">
                                {{ __('admin/images.alt_help') }}
                            </p>
                        </div>

                        <div>
                            <label :for="`title-${image.id}`"
                                   class="mb-base block text-caption font-medium text-on-surface-variant">
                                {{ __('admin/images.title') }}
                            </label>
                            <input :id="`title-${image.id}`" type="text" x-model="editTitle"
                                   class="w-full rounded-lg border border-outline-variant bg-surface-container-low
                                          px-sm py-xs text-body-md text-on-surface
                                          focus:border-secondary focus:ring-1 focus:ring-secondary">
                        </div>

                        <div class="flex justify-end gap-xs pt-xs">
                            <button type="button" @click="editing = null"
                                    class="rounded-lg border border-outline-variant px-sm py-xs
                                           text-label-md text-on-surface">
                                {{ __('admin/images.cancel') }}
                            </button>
                            <button type="button" @click="saveEdit(image)"
                                    class="rounded-lg bg-primary-container px-md py-xs
                                           text-label-md font-semibold text-on-primary">
                                {{ __('admin/images.save') }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
