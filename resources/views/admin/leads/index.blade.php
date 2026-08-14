@extends('admin.layouts.app')
@section('title', __('admin/leads.title'))
@section('content')
<form method="GET" class="mb-md grid gap-xs rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm md:grid-cols-4">
    <label class="grid gap-1 text-label-md"><span>{{ __('admin/leads.filters.search') }}</span><input name="q" value="{{ request('q') }}" class="min-h-11 rounded-lg border border-outline-variant px-xs"></label>
    <label class="grid gap-1 text-label-md"><span>{{ __('admin/leads.filters.status') }}</span><select name="status" class="min-h-11 rounded-lg border border-outline-variant px-xs"><option value="">{{ __('admin/leads.filters.all') }}</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->value }}</option>@endforeach</select></label>
    <label class="grid gap-1 text-label-md"><span>{{ __('admin/leads.filters.source') }}</span><select name="source" class="min-h-11 rounded-lg border border-outline-variant px-xs"><option value="">{{ __('admin/leads.filters.all') }}</option>@foreach($sources as $source)<option value="{{ $source->value }}" @selected(request('source') === $source->value)>{{ $source->value }}</option>@endforeach</select></label>
    <div class="flex items-end gap-xs"><button class="min-h-11 flex-1 rounded-lg bg-primary-container px-sm text-label-md text-on-primary">{{ __('admin/leads.filters.apply') }}</button><a href="{{ route('admin.leads.index') }}" class="flex min-h-11 items-center rounded-lg border border-outline-variant px-sm">{{ __('admin/leads.filters.clear') }}</a></div>
</form>
<div class="mb-sm flex justify-end"><a href="{{ route('admin.leads.export', request()->query()) }}" class="inline-flex min-h-11 items-center gap-xs rounded-lg border border-outline-variant px-sm text-label-md"><span class="material-symbols-outlined text-[18px]">download</span>{{ __('admin/leads.actions.export') }}</a></div>
<div class="table-scroll rounded-xl border border-outline-variant/40 bg-surface-container-lowest">
    <table class="min-w-full text-left text-body-md">
        <thead class="bg-surface-container-low text-label-md"><tr><th class="p-sm">{{ __('admin/leads.fields.date') }}</th><th class="p-sm">{{ __('admin/leads.fields.contact') }}</th><th class="p-sm">{{ __('admin/leads.fields.source') }}</th><th class="p-sm">{{ __('admin/leads.fields.status') }}</th><th class="p-sm"><span class="sr-only">{{ __('admin/leads.actions.view') }}</span></th></tr></thead>
        <tbody class="divide-y divide-outline-variant/30">
        @forelse($leads as $lead)
            <tr><td class="whitespace-nowrap p-sm">{{ $lead->created_at->format('d/m/Y H:i') }}</td><td class="p-sm"><strong>{{ $lead->name }}</strong><br><span class="text-caption text-on-surface-variant">{{ $lead->phone }}{{ $lead->email ? ' | '.$lead->email : '' }}</span></td><td class="p-sm">{{ $lead->source->value }}</td><td class="p-sm"><span class="rounded-full bg-surface-container-low px-xs py-1 text-caption">{{ $lead->status->value }}</span></td><td class="p-sm text-right"><a href="{{ route('admin.leads.show', $lead) }}" class="text-label-md text-secondary hover:underline">{{ __('admin/leads.actions.view') }}</a></td></tr>
        @empty
            <tr><td colspan="5" class="p-xl text-center text-on-surface-variant">{{ __('admin/leads.empty') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-md">{{ $leads->links() }}</div>
@endsection
