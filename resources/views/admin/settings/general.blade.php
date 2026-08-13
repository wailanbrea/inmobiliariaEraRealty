@extends('admin.settings.partials.layout')

@section('settings')

{{-- Formularios de borrado, fuera del formulario principal para no anidarlos --}}
@foreach (['site_logo', 'site_logo_dark', 'site_favicon'] as $img)
    @if ($images[$img] ?? null)
        <form id="remove_{{ $img }}" method="POST"
              action="{{ route('admin.settings.image.remove', $img) }}" class="hidden">
            @csrf @method('DELETE')
        </form>
    @endif
@endforeach

<form method="POST" action="{{ route('admin.settings.general.update') }}"
      enctype="multipart/form-data" class="space-y-md">
    @csrf @method('PUT')

    {{-- Identidad --}}
    <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <h2 class="mb-sm font-heading text-title-lg text-on-surface">
            {{ __('admin/settings.general.heading') }}
        </h2>

        <div class="grid gap-sm md:grid-cols-2">
            <x-admin.field name="site_name" :label="__('admin/settings.general.site_name')"
                           :value="$values['site_name']" required />

            <x-admin.translatable-field name="site_tagline"
                :label="__('admin/settings.general.site_tagline')"
                :translations="$translations['site_tagline']" :locales="$locales" />
        </div>

        <div class="mt-md grid gap-md md:grid-cols-3">
            <x-admin.image-field name="site_logo" :label="__('admin/settings.general.site_logo')"
                :current="$images['site_logo']" :help="__('admin/settings.general.logo_help')" />

            <x-admin.image-field name="site_logo_dark" :label="__('admin/settings.general.site_logo_dark')"
                :current="$images['site_logo_dark']" />

            <x-admin.image-field name="site_favicon" :label="__('admin/settings.general.site_favicon')"
                :current="$images['site_favicon']" :help="__('admin/settings.general.favicon_help')"
                accept="image/png,image/webp,image/svg+xml" />
        </div>
    </section>

    {{-- Contacto --}}
    <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <h2 class="mb-sm font-heading text-title-lg text-on-surface">
            {{ __('admin/settings.general.contact_heading') }}
        </h2>

        <div class="grid gap-sm md:grid-cols-2">
            <x-admin.field name="contact_phone" :label="__('admin/settings.general.contact_phone')"
                           :value="$values['contact_phone']" type="tel" placeholder="+1 (809) 000-0000" />

            <x-admin.field name="contact_email" :label="__('admin/settings.general.contact_email')"
                           :value="$values['contact_email']" type="email" />

            <x-admin.field name="contact_form_recipient_email"
                           :label="__('admin/settings.general.contact_form_recipient_email')"
                           :value="$values['contact_form_recipient_email']" type="email"
                           :help="__('admin/settings.general.recipient_help')" />

            <x-admin.field name="contact_address" :label="__('admin/settings.general.contact_address')"
                           :value="$values['contact_address']" />

            <div class="md:col-span-2">
                <x-admin.translatable-field name="contact_schedule"
                    :label="__('admin/settings.general.contact_schedule')"
                    :translations="$translations['contact_schedule']" :locales="$locales" />
            </div>
        </div>
    </section>

    {{-- Redes --}}
    <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <h2 class="mb-base font-heading text-title-lg text-on-surface">
            {{ __('admin/settings.general.social_heading') }}
        </h2>
        <p class="mb-sm text-caption text-on-surface-variant">
            {{ __('admin/settings.general.social_help') }}
        </p>

        <div class="grid gap-sm md:grid-cols-2">
            @foreach (['social_facebook' => 'Facebook', 'social_instagram' => 'Instagram',
                       'social_youtube' => 'YouTube', 'social_tiktok' => 'TikTok',
                       'social_linkedin' => 'LinkedIn'] as $key => $label)
                <x-admin.field :name="$key" :label="$label" :value="$values[$key]"
                               type="url" placeholder="https://" />
            @endforeach
        </div>
    </section>

    {{-- Moneda --}}
    <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <h2 class="mb-base font-heading text-title-lg text-on-surface">
            {{ __('admin/settings.currency.heading') }}
        </h2>
        <p class="mb-sm text-caption text-on-surface-variant">
            {{ __('admin/settings.currency.intro') }}
        </p>

        <div class="grid gap-sm md:grid-cols-2">
            <x-admin.field name="currency_default" :label="__('admin/settings.currency.default')"
                           type="select" :value="$values['currency_default']" required
                           :options="['USD' => 'USD — Dólar estadounidense', 'DOP' => 'DOP — Peso dominicano']" />

            <x-admin.field name="currency_usd_to_dop" :label="__('admin/settings.currency.usd_to_dop')"
                           type="number" step="0.01" :value="$values['currency_usd_to_dop']" required
                           :help="__('admin/settings.currency.rate_help')" />
        </div>

        @if (setting('currency_rate_updated_at'))
            <p class="mt-xs text-caption text-on-surface-variant">
                {{ __('admin/settings.currency.rate_updated', [
                    'date' => \Carbon\Carbon::parse(setting('currency_rate_updated_at'))->format('d/m/Y H:i'),
                ]) }}
            </p>
        @endif
    </section>

    {{-- Pie --}}
    <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <h2 class="mb-sm font-heading text-title-lg text-on-surface">
            {{ __('admin/settings.general.footer_heading') }}
        </h2>

        <div class="grid gap-sm md:grid-cols-2">
            <x-admin.translatable-field name="footer_text"
                :label="__('admin/settings.general.footer_text')" type="textarea"
                :translations="$translations['footer_text']" :locales="$locales" />

            <x-admin.translatable-field name="footer_copyright"
                :label="__('admin/settings.general.footer_copyright')"
                :translations="$translations['footer_copyright']" :locales="$locales" />
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
