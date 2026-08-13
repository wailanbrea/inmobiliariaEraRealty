@props([
    'name',
    'label',
    'translations' => [],
    'locales' => [],
    'type' => 'text',
    'help' => null,
    'rows' => 3,
    'maxlength' => null,
])

{{--
    Campo con una pestana por idioma. Solo se duplica el texto: el resto de
    datos de la entidad es comun a ambos idiomas.
    Ver docs/15_I18N.md seccion 7.
--}}
<div x-data="{ locale: '{{ array_key_first($locales) }}' }">

    <div class="mb-base flex items-center justify-between">
        <span class="text-caption font-medium text-on-surface-variant">{{ $label }}</span>

        <div class="flex items-center rounded-lg border border-outline-variant"
             role="tablist" aria-label="{{ $label }}">
            @foreach ($locales as $code => $meta)
                <button type="button" role="tab"
                        @click="locale = '{{ $code }}'"
                        :aria-selected="locale === '{{ $code }}'"
                        :class="locale === '{{ $code }}'
                            ? 'bg-primary-container text-on-primary font-semibold'
                            : 'text-on-surface-variant hover:text-secondary'"
                        class="rounded-lg px-xs py-0.5 text-caption transition-colors">
                    {{ $meta['short'] }}
                </button>
            @endforeach
        </div>
    </div>

    @foreach ($locales as $code => $meta)
        @php
            $field = "{$name}[{$code}]";
            $id = "{$name}_{$code}";
            $error = $errors->first($field);
            $current = old("{$name}.{$code}", $translations[$code] ?? null);

            $base = 'w-full rounded-lg border bg-surface-container-low px-sm py-xs text-body-md
                     text-on-surface transition-colors focus:ring-1'
                . ($error
                    ? ' border-error focus:border-error focus:ring-error'
                    : ' border-outline-variant focus:border-secondary focus:ring-secondary');
        @endphp

        <div x-show="locale === '{{ $code }}'" x-cloak>
            @if ($type === 'textarea')
                <textarea id="{{ $id }}" name="{{ $field }}" rows="{{ $rows }}"
                          @if ($maxlength) maxlength="{{ $maxlength }}" @endif
                          lang="{{ $code }}"
                          class="{{ $base }}">{{ $current }}</textarea>
            @else
                <input id="{{ $id }}" name="{{ $field }}" type="text"
                       value="{{ $current }}" lang="{{ $code }}"
                       @if ($maxlength) maxlength="{{ $maxlength }}" @endif
                       class="{{ $base }}">
            @endif

            @if ($error)
                <p role="alert" class="mt-1 flex items-center gap-1 text-caption text-error">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                    {{ $error }}
                </p>
            @endif
        </div>
    @endforeach

    @if ($help)
        <p class="mt-1 text-caption text-on-surface-variant">{{ $help }}</p>
    @endif
</div>
