@extends('admin.settings.partials.layout')

@section('settings')

@php
    $wa = whatsapp();
    $numeroActual = $values['contact_whatsapp_number'];
@endphp

<form method="POST" action="{{ route('admin.settings.whatsapp.update') }}" class="space-y-md">
    @csrf @method('PUT')

    <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <h2 class="mb-sm font-heading text-title-lg text-on-surface">
            {{ __('admin/settings.whatsapp.heading') }}
        </h2>

        <div class="grid gap-sm md:grid-cols-2">
            <x-admin.field name="contact_whatsapp_number"
                           :label="__('admin/settings.whatsapp.number')"
                           :value="$numeroActual"
                           :help="__('admin/settings.whatsapp.number_help')"
                           placeholder="(809) 555-0100" />

            <x-admin.field name="whatsapp_float_position"
                           :label="__('admin/settings.whatsapp.float_position')"
                           type="select" :value="$values['whatsapp_float_position']" required
                           :options="[
                               'bottom-right' => __('admin/settings.whatsapp.position_right'),
                               'bottom-left'  => __('admin/settings.whatsapp.position_left'),
                           ]" />
        </div>

        <div class="mt-sm">
            <x-admin.field name="whatsapp_float_enabled" type="checkbox"
                           :label="__('admin/settings.whatsapp.float_enabled')"
                           :value="$values['whatsapp_float_enabled']"
                           :help="__('admin/settings.whatsapp.float_enabled')" />
        </div>

        {{-- Vista previa del enlace generado.
             El enlace no se guarda: se deriva del numero y el mensaje. --}}
        <div class="mt-md rounded-lg bg-surface-container-low p-sm">
            <p class="mb-1 text-caption font-medium text-on-surface-variant">
                {{ __('admin/settings.whatsapp.preview') }}
            </p>

            @if ($enlace = $wa->generalLink())
                <a href="{{ $enlace }}" target="_blank" rel="noopener noreferrer"
                   class="break-all font-mono text-caption text-secondary hover:underline">
                    {{ $enlace }}
                </a>
                <p class="mt-1 text-caption text-on-surface-variant">
                    {{ $wa->formatForDisplay($numeroActual) }}
                    &middot; {{ __('admin/settings.whatsapp.preview_help') }}
                </p>
            @else
                <p class="text-caption text-on-surface-variant">
                    {{ __('admin/settings.whatsapp.no_number') }}
                </p>
            @endif
        </div>
    </section>

    <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <div class="space-y-md">
            <x-admin.translatable-field name="contact_whatsapp_message"
                :label="__('admin/settings.whatsapp.message')" type="textarea" :rows="2"
                :translations="$translations['contact_whatsapp_message']" :locales="$locales"
                :help="__('admin/settings.whatsapp.message_help')" />

            <x-admin.translatable-field name="whatsapp_property_message"
                :label="__('admin/settings.whatsapp.property_message')" type="textarea" :rows="2"
                :translations="$translations['whatsapp_property_message']" :locales="$locales"
                :help="__('admin/settings.whatsapp.property_message_help', [
                    'vars' => '{reference_code} {title} {price} {location} {url}',
                ])" />

            <x-admin.translatable-field name="whatsapp_investment_message"
                :label="__('admin/settings.whatsapp.investment_message')" type="textarea" :rows="2"
                :translations="$translations['whatsapp_investment_message']" :locales="$locales" />
        </div>
    </section>

    <div class="sticky bottom-0 flex justify-end border-t border-outline-variant/40
                bg-surface/95 py-sm backdrop-blur">
        <button type="submit"
                class="flex items-center gap-xs rounded-lg bg-primary-container px-md py-xs
                       text-label-md font-semibold text-on-primary shadow-sm
                       transition-all hover:shadow-ambient-hover">
            <span class="material-symbols-outlined text-[20px]">save</span>
            {{ __('admin/settings.save') }}
        </button>
    </div>
</form>

@endsection
