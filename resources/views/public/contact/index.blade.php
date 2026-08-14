@extends('layouts.public')
@section('title', __('contact.title').' · '.setting('site_name'))
@section('description', __('contact.description'))
@section('content')
<section class="bg-primary-container py-xl">
    <div class="mx-auto max-w-container-max px-margin-mobile md:px-gutter">
        <nav aria-label="breadcrumb" class="mb-sm text-caption text-on-primary-container"><a href="{{ lroute('home') }}">{{ __('common.nav.home') }}</a> / <span class="text-on-secondary-container">{{ __('contact.title') }}</span></nav>
        <h1 class="font-display text-display-lg-mobile text-on-primary md:text-display-lg">{{ __('contact.title') }}</h1>
        <p class="mt-sm max-w-2xl text-body-lg text-on-primary/90">{{ __('contact.intro') }}</p>
    </div>
</section>
<section class="mx-auto grid max-w-container-max gap-lg px-margin-mobile py-xl md:px-gutter lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
    <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow md:p-lg">
        <h2 class="mb-md font-heading text-headline-md-mobile md:text-headline-md">{{ __('contact.heading') }}</h2>
        @if (session('success'))<div role="status" class="mb-md rounded-lg bg-tertiary-fixed p-sm text-on-tertiary-fixed">{{ session('success') }}</div>@endif
        @if ($errors->any())<div role="alert" class="mb-md rounded-lg bg-error-container p-sm text-on-error-container">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ lroute('contact.store') }}" class="grid gap-sm md:grid-cols-2">
            @csrf
            <input type="hidden" name="form_token" value="{{ $formToken }}">
            <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
            @foreach (['name' => __('leads.fields.name'), 'phone' => __('leads.fields.phone'), 'email' => __('leads.fields.email'), 'subject' => __('contact.subject')] as $field => $label)
                <label class="grid gap-1 text-label-md">{{ $label }} @if($field !== 'email')<span aria-hidden="true">*</span>@endif<input class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm text-body-md" name="{{ $field }}" value="{{ old($field) }}" @required($field !== 'email') @if($field === 'email') type="email" @elseif($field === 'phone') type="tel" @endif></label>
            @endforeach
            <label class="grid gap-1 text-label-md"><span>{{ __('contact.interest') }}</span><select name="interest_type" required class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm">@foreach(__('contact.interests') as $value => $label)<option value="{{ $value }}" @selected(old('interest_type') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-label-md"><span>{{ __('contact.preferred') }}</span><select name="preferred_contact" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"><option value="">?</option>@foreach(__('contact.channels') as $value => $label)<option value="{{ $value }}" @selected(old('preferred_contact') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-label-md md:col-span-2"><span>{{ __('contact.message') }} *</span><textarea name="message" required rows="6" class="rounded-lg border border-outline-variant bg-surface px-sm py-xs text-body-md">{{ old('message') }}</textarea></label>
            <button class="rounded-lg bg-primary-container px-md py-sm text-label-md font-semibold text-on-primary md:col-span-2">{{ __('contact.send') }}</button>
        </form>
    </div>
    <aside class="h-fit rounded-xl bg-surface-container-low p-md">
        <h2 class="mb-md text-title-lg">{{ __('contact.details') }}</h2>
        <div class="space-y-sm text-body-md text-on-surface-variant">
            @if(setting('contact_phone'))<p><strong>{{ __('leads.fields.phone') }}:</strong><br><a href="tel:{{ preg_replace('/[^+\d]/', '', setting('contact_phone')) }}">{{ setting('contact_phone') }}</a></p>@endif
            @if(setting('contact_email'))<p><strong>{{ __('leads.fields.email') }}:</strong><br><a href="mailto:{{ setting('contact_email') }}">{{ setting('contact_email') }}</a></p>@endif
            @if(setting('contact_address'))<p>{{ setting('contact_address') }}</p>@endif
            @if(setting('contact_schedule'))<p>{{ setting('contact_schedule') }}</p>@endif
        </div>
    </aside>
</section>
@endsection
