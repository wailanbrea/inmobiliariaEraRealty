@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'help' => null,
    'required' => false,
    'placeholder' => null,
    'rows' => 3,
    'options' => null,
    'maxlength' => null,
])

@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], ['_', '', '_'], $name));
    $error = $errors->first($name);
    $current = old($name, $value);

    $base = 'w-full rounded-lg border bg-surface-container-low px-sm py-xs text-body-md
             text-on-surface transition-colors focus:ring-1'
        . ($error
            ? ' border-error focus:border-error focus:ring-error'
            : ' border-outline-variant focus:border-secondary focus:ring-secondary');
@endphp

<div>
    <label for="{{ $id }}" class="mb-base block text-caption font-medium text-on-surface-variant">
        {{ $label }}
        @if ($required)
            <span class="text-error" aria-hidden="true">*</span>
        @endif
    </label>

    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
                  @if ($maxlength) maxlength="{{ $maxlength }}" @endif
                  @if ($required) required @endif
                  @if ($error) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif
                  placeholder="{{ $placeholder }}"
                  class="{{ $base }}">{{ $current }}</textarea>

    @elseif ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" @if ($required) required @endif
                class="{{ $base }}">
            @foreach ($options ?? [] as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected((string) $current === (string) $optValue)>
                    {{ $optLabel }}
                </option>
            @endforeach
        </select>

    @elseif ($type === 'checkbox')
        <label for="{{ $id }}" class="flex cursor-pointer items-center gap-xs">
            <input type="hidden" name="{{ $name }}" value="0">
            <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="1"
                   @checked($current)
                   class="rounded-sm border-outline-variant text-secondary focus:ring-secondary">
            <span class="text-body-md text-on-surface">{{ $help }}</span>
        </label>

    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
               value="{{ $type === 'password' ? '' : $current }}"
               @if ($maxlength) maxlength="{{ $maxlength }}" @endif
               @if ($required) required @endif
               @if ($error) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif
               placeholder="{{ $placeholder }}"
               {{ $attributes->except(['id']) }}
               class="{{ $base }}">
    @endif

    @if ($help && $type !== 'checkbox')
        <p class="mt-1 text-caption text-on-surface-variant">{{ $help }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}_error" role="alert" class="mt-1 flex items-center gap-1 text-caption text-error">
            <span class="material-symbols-outlined text-[16px]">error</span>
            {{ $error }}
        </p>
    @endif
</div>
