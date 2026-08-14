@extends('admin.layouts.app')
@section('title', __('admin/leads.detail').' #'.$lead->id)
@section('content')
<a href="{{ route('admin.leads.index') }}" class="mb-md inline-flex items-center gap-xs text-label-md text-secondary"><span class="material-symbols-outlined text-[18px]">arrow_back</span>{{ __('admin/leads.actions.back') }}</a>
@if(session('success'))<div role="status" class="mb-md rounded-lg bg-tertiary-fixed p-sm">{{ session('success') }}</div>@endif
<div class="grid gap-md lg:grid-cols-3">
    <section class="space-y-sm rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow lg:col-span-2">
        <div><p class="text-caption text-on-surface-variant">{{ __('admin/leads.fields.contact') }}</p><h2 class="text-title-lg">{{ $lead->name }}</h2><p><a href="tel:{{ preg_replace('/\D+/', '', $lead->phone) }}" class="text-secondary">{{ $lead->phone }}</a>@if($lead->email) | <a href="mailto:{{ $lead->email }}" class="text-secondary">{{ $lead->email }}</a>@endif</p></div>
        <div><p class="text-caption text-on-surface-variant">{{ __('admin/leads.fields.message') }}</p><p class="whitespace-pre-line text-body-md">{{ $lead->message ?: '-' }}</p></div>
        @if($lead->property)<div><p class="text-caption text-on-surface-variant">{{ __('admin/leads.fields.property') }}</p><a href="{{ route('admin.properties.edit', $lead->property) }}" class="text-secondary hover:underline">{{ $lead->property->reference_code }} | {{ $lead->property->title }}</a></div>@endif
        @if($lead->details)<div class="grid gap-xs rounded-lg bg-surface-container-low p-sm md:grid-cols-2">@foreach($lead->details as $key => $value)<p><span class="text-caption text-on-surface-variant">{{ $key }}</span><br>{{ is_scalar($value) ? $value : json_encode($value) }}</p>@endforeach</div>@endif
        <details class="rounded-lg border border-outline-variant/40 p-sm"><summary class="cursor-pointer text-label-md">{{ __('admin/leads.fields.technical') }}</summary><dl class="mt-sm text-caption text-on-surface-variant"><dt>IP</dt><dd>{{ $lead->ip_address ?: '-' }}</dd><dt>User agent</dt><dd class="break-all">{{ $lead->user_agent ?: '-' }}</dd><dt>Referrer</dt><dd class="break-all">{{ $lead->referrer_url ?: '-' }}</dd></dl></details>
    </section>
    <aside class="h-fit rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <form method="POST" action="{{ route('admin.leads.update', $lead) }}" class="space-y-sm">
            @csrf @method('PUT')
            <label class="grid gap-1 text-label-md"><span>{{ __('admin/leads.fields.status') }}</span><select name="status" class="min-h-11 rounded-lg border border-outline-variant px-xs">@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(old('status', $lead->status->value) === $status->value)>{{ $status->value }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-label-md"><span>{{ __('admin/leads.fields.assignee') }}</span><select name="assigned_to_user_id" class="min-h-11 rounded-lg border border-outline-variant px-xs"><option value="">-</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string) old('assigned_to_user_id', $lead->assigned_to_user_id) === (string) $user->id)>{{ $user->name }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-label-md"><span>{{ __('admin/leads.fields.notes') }}</span><textarea name="admin_notes" rows="8" class="rounded-lg border border-outline-variant px-xs py-xs">{{ old('admin_notes', $lead->admin_notes) }}</textarea></label>
            <button class="w-full rounded-lg bg-primary-container px-sm py-sm text-label-md font-semibold text-on-primary">{{ __('admin/leads.actions.save') }}</button>
        </form>
    </aside>
</div>
@endsection
