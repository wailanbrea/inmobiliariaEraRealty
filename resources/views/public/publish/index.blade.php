@extends('layouts.public')
@section('title', __('publish.title').' · '.setting('site_name'))
@section('description', __('publish.description'))
@section('content')
<section class="bg-primary-container py-xl">
    <div class="mx-auto max-w-container-max px-margin-mobile md:px-gutter">
        <nav aria-label="breadcrumb" class="mb-sm text-caption text-on-primary-container"><a href="{{ lroute('home') }}">{{ __('common.nav.home') }}</a> / <span class="text-on-secondary-container">{{ __('publish.title') }}</span></nav>
        <h1 class="font-display text-display-lg-mobile text-on-primary md:text-display-lg">{{ __('publish.title') }}</h1>
        <p class="mt-sm max-w-2xl text-body-lg text-on-primary/90">{{ __('publish.intro') }}</p>
    </div>
</section>
<section class="mx-auto max-w-4xl px-margin-mobile py-xl md:px-gutter">
    @if (session('success'))<div role="status" class="mb-md rounded-lg bg-tertiary-fixed p-sm text-on-tertiary-fixed">{{ session('success') }}</div>@endif
    @if ($errors->any())<div role="alert" class="mb-md rounded-lg bg-error-container p-sm text-on-error-container">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ lroute('publish.store') }}" class="space-y-md">
        @csrf
        <input type="hidden" name="form_token" value="{{ $formToken }}">
        <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
        <fieldset class="grid gap-sm rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow md:grid-cols-2">
            <legend class="px-xs font-heading text-title-lg">{{ __('publish.owner') }}</legend>
            @foreach (['name' => __('leads.fields.name'), 'phone' => __('leads.fields.phone'), 'email' => __('leads.fields.email')] as $field => $label)
                <label class="grid gap-1 text-label-md"><span>{{ $label }} @if($field !== 'email')*@endif</span><input name="{{ $field }}" value="{{ old($field) }}" @required($field !== 'email') @if($field === 'email') type="email" @elseif($field === 'phone') type="tel" @endif class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm text-body-md"></label>
            @endforeach
            <label class="grid gap-1 text-label-md"><span>{{ __('contact.preferred') }}</span><select name="preferred_contact" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"><option value="">?</option>@foreach(__('contact.channels') as $value => $label)<option value="{{ $value }}" @selected(old('preferred_contact') === $value)>{{ $label }}</option>@endforeach</select></label>
        </fieldset>
        <fieldset class="grid gap-sm rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow md:grid-cols-2">
            <legend class="px-xs font-heading text-title-lg">{{ __('publish.property') }}</legend>
            <label class="grid gap-1 text-label-md"><span>{{ __('publish.type') }} *</span><select name="property_type_id" required class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm">@foreach($propertyTypes as $type)<option value="{{ $type->id }}" @selected((string) old('property_type_id') === (string) $type->id)>{{ $type->name }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-label-md"><span>{{ __('publish.operation') }} *</span><select name="operation_type" required class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm">@foreach(__('publish.operations') as $value => $label)<option value="{{ $value }}" @selected(old('operation_type') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-label-md"><span>{{ __('publish.province') }} *</span><select name="province_id" required class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm">@foreach($provinces as $province)<option value="{{ $province->id }}" @selected((string) old('province_id') === (string) $province->id)>{{ $province->name }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-label-md"><span>{{ __('publish.location') }} *</span><input name="location" required value="{{ old('location') }}" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"></label>
            @foreach (['bedrooms' => __('publish.bedrooms'), 'bathrooms' => __('publish.bathrooms'), 'area' => __('publish.area'), 'expected_price' => __('publish.price')] as $field => $label)
                <label class="grid gap-1 text-label-md"><span>{{ $label }}</span><input type="number" min="0" step="{{ $field === 'bathrooms' ? '0.5' : ($field === 'area' ? '0.01' : '1') }}" name="{{ $field }}" value="{{ old($field) }}" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"></label>
            @endforeach
            <label class="grid gap-1 text-label-md"><span>{{ __('publish.currency') }}</span><select name="currency" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"><option>USD</option><option @selected(old('currency') === 'DOP')>DOP</option></select></label>
            <label class="grid gap-1 text-label-md md:col-span-2"><span>{{ __('publish.message') }}</span><textarea name="message" rows="5" class="rounded-lg border border-outline-variant bg-surface px-sm py-xs">{{ old('message') }}</textarea></label>
            <label class="flex items-start gap-xs text-body-md md:col-span-2"><input type="checkbox" name="consent" value="1" required class="mt-1 size-5"><span>{{ __('publish.consent') }}</span></label>
        </fieldset>
        <button class="w-full rounded-lg bg-primary-container px-md py-sm text-label-md font-semibold text-on-primary">{{ __('publish.send') }}</button>
    </form>
</section>
@endsection
