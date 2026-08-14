@extends('admin.layouts.app')
@section('title', __('admin/whatsapp.title'))
@section('content')

{{-- Se explica que mide y, sobre todo, que NO hace: el modulo se
     confundia con una integracion de mensajeria. --}}
<p class="mb-md max-w-3xl text-body-md text-on-surface-variant">
    {{ __('admin/whatsapp.subtitle') }}
</p>

<form method="GET" class="mb-md grid gap-xs rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm md:grid-cols-4">
    <label class="grid gap-1 text-label-md"><span>{{ __('admin/whatsapp.filters.source') }}</span><select name="source" class="min-h-11 rounded-lg border border-outline-variant px-xs"><option value="">{{ __('admin/whatsapp.filters.all') }}</option>@foreach($sources as $source)<option value="{{ $source }}" @selected(request('source') === $source)>{{ $source }}</option>@endforeach</select></label>
    <label class="grid gap-1 text-label-md"><span>{{ __('admin/whatsapp.filters.from') }}</span><input type="date" name="from" value="{{ request('from') }}" class="min-h-11 rounded-lg border border-outline-variant px-xs"></label>
    <label class="grid gap-1 text-label-md"><span>{{ __('admin/whatsapp.filters.to') }}</span><input type="date" name="to" value="{{ request('to') }}" class="min-h-11 rounded-lg border border-outline-variant px-xs"></label>
    <div class="flex items-end gap-xs"><button class="min-h-11 flex-1 rounded-lg bg-primary-container px-sm text-label-md text-on-primary">{{ __('admin/whatsapp.filters.apply') }}</button><a href="{{ route('admin.whatsapp.index') }}" class="flex min-h-11 items-center rounded-lg border border-outline-variant px-sm">{{ __('admin/whatsapp.filters.clear') }}</a></div>
</form>
<div class="mb-md grid gap-sm sm:grid-cols-3">
    @foreach([__('admin/whatsapp.metrics.total') => $total, __('admin/whatsapp.metrics.today') => $today, __('admin/whatsapp.metrics.unique') => $uniqueNumbers] as $label => $value)
        <article class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow"><p class="text-caption text-on-surface-variant">{{ $label }}</p><p class="mt-xs text-display-md-mobile font-semibold text-on-surface">{{ number_format($value) }}</p></article>
    @endforeach
</div>
@if($bySource->isNotEmpty())
    <section class="mb-md rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md"><h2 class="mb-sm text-title-lg">{{ __('admin/whatsapp.metrics.sources') }}</h2><div class="flex flex-wrap gap-xs">@foreach($bySource as $item)<span class="rounded-full bg-surface-container-low px-sm py-xs text-label-md">{{ $item->source }}: <strong>{{ $item->total }}</strong></span>@endforeach</div></section>
@endif
<div class="table-scroll rounded-xl border border-outline-variant/40 bg-surface-container-lowest">
    <table class="min-w-full text-left text-body-md"><thead class="bg-surface-container-low text-label-md"><tr><th class="p-sm">{{ __('admin/whatsapp.table.date') }}</th><th class="p-sm">{{ __('admin/whatsapp.table.source') }}</th><th class="p-sm">{{ __('admin/whatsapp.table.phone') }}</th><th class="p-sm">{{ __('admin/whatsapp.table.property') }}</th><th class="p-sm">{{ __('admin/whatsapp.table.message') }}</th></tr></thead>
    <tbody class="divide-y divide-outline-variant/30">@forelse($clicks as $click)<tr><td class="whitespace-nowrap p-sm">{{ $click->created_at->format('d/m/Y H:i') }}</td><td class="p-sm">{{ $click->source }}</td><td class="p-sm">{{ $click->phone_number }}</td><td class="p-sm">@if($click->property)<a class="text-secondary hover:underline" href="{{ route('admin.properties.edit', $click->property) }}">{{ $click->property->reference_code }}</a>@else - @endif</td><td class="max-w-md truncate p-sm" title="{{ $click->generated_message }}">{{ $click->generated_message ?: '-' }}</td></tr>@empty<tr><td colspan="5" class="p-xl text-center text-on-surface-variant">{{ __('admin/whatsapp.empty') }}</td></tr>@endforelse</tbody></table>
</div>
<div class="mt-md">{{ $clicks->links() }}</div>
@endsection
