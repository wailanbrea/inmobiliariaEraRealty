@extends('admin.settings.partials.layout')

@section('settings')

@if ($images['seo_default_og_image'] ?? null)
    <form id="remove_seo_default_og_image" method="POST"
          action="{{ route('admin.settings.image.remove', 'seo_default_og_image') }}" class="hidden">
        @csrf @method('DELETE')
    </form>
@endif

<form method="POST" action="{{ route('admin.settings.seo.update') }}"
      enctype="multipart/form-data" class="space-y-md">
    @csrf @method('PUT')

    <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <h2 class="mb-base font-heading text-title-lg text-on-surface">
            {{ __('admin/settings.seo.heading') }}
        </h2>
        <p class="mb-sm text-caption text-on-surface-variant">
            {{ __('admin/settings.seo.intro') }}
        </p>

        <div class="space-y-md">
            <x-admin.translatable-field name="seo_default_title"
                :label="__('admin/settings.seo.default_title')" :maxlength="120"
                :translations="$translations['seo_default_title']" :locales="$locales"
                :help="__('admin/settings.seo.title_limit')" />

            <x-admin.translatable-field name="seo_default_description"
                :label="__('admin/settings.seo.default_description')" type="textarea"
                :rows="3" :maxlength="300"
                :translations="$translations['seo_default_description']" :locales="$locales"
                :help="__('admin/settings.seo.description_limit')" />

            <x-admin.image-field name="seo_default_og_image"
                :label="__('admin/settings.seo.default_og_image')"
                :current="$images['seo_default_og_image']"
                :help="__('admin/settings.seo.og_help')"
                accept="image/png,image/jpeg,image/webp" />
        </div>
    </section>

    <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <div class="grid gap-sm md:grid-cols-2">
            <x-admin.field name="seo_google_analytics_id"
                           :label="__('admin/settings.seo.analytics_id')"
                           :value="$values['seo_google_analytics_id']"
                           placeholder="G-XXXXXXXXXX"
                           :help="__('admin/settings.seo.analytics_help')" />

            <x-admin.field name="seo_google_site_verification"
                           :label="__('admin/settings.seo.site_verification')"
                           :value="$values['seo_google_site_verification']" />
        </div>

        <div class="mt-md">
            <x-admin.field name="seo_robots_txt" :label="__('admin/settings.seo.robots')"
                           type="textarea" :rows="8" :value="$values['seo_robots_txt']"
                           :help="__('admin/settings.seo.robots_help')"
                           class="font-mono" />
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
