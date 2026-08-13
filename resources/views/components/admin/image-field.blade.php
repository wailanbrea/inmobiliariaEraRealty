@props([
    'name',
    'label',
    'current' => null,
    'help' => null,
    'accept' => 'image/png,image/jpeg,image/webp,image/svg+xml',
])

@php
    $id = $name;
    $error = $errors->first($name);
@endphp

<div x-data="{ preview: null }">
    <span class="mb-base block text-caption font-medium text-on-surface-variant">{{ $label }}</span>

    <div class="flex items-start gap-sm">

        {{-- Vista previa: la imagen guardada, o la recien elegida --}}
        <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden
                    rounded-lg border border-outline-variant bg-surface-container-low">
            <template x-if="preview">
                <img :src="preview" alt="" class="size-full object-contain">
            </template>

            <template x-if="!preview">
                <div class="flex size-full items-center justify-center">
                    @if ($current)
                        <img src="{{ Storage::url($current) }}"
                             alt="{{ __('admin/settings.current_image') }}"
                             class="size-full object-contain">
                    @else
                        <span class="material-symbols-outlined text-[24px] text-outline">image</span>
                    @endif
                </div>
            </template>
        </div>

        <div class="min-w-0 flex-1">
            <input id="{{ $id }}" name="{{ $name }}" type="file" accept="{{ $accept }}"
                   @change="preview = $event.target.files[0]
                        ? URL.createObjectURL($event.target.files[0]) : null"
                   class="block w-full text-caption text-on-surface-variant
                          file:mr-sm file:rounded-lg file:border-0 file:bg-surface-container
                          file:px-sm file:py-xs file:text-label-md file:text-on-surface
                          hover:file:bg-surface-container-high">

            @if ($help)
                <p class="mt-1 text-caption text-on-surface-variant">{{ $help }}</p>
            @endif

            @if ($error)
                <p role="alert" class="mt-1 flex items-center gap-1 text-caption text-error">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                    {{ $error }}
                </p>
            @endif

            @if ($current)
                {{-- Formulario aparte: borrar una imagen no debe requerir
                     guardar el resto del formulario. --}}
                <button type="submit" form="remove_{{ $name }}"
                        class="mt-xs inline-flex items-center gap-1 text-caption text-error hover:underline">
                    <span class="material-symbols-outlined text-[16px]">delete</span>
                    {{ __('admin/settings.remove_image') }}
                </button>
            @endif
        </div>
    </div>
</div>
