@extends('admin.layouts.app')

@section('title', __('admin/audit.title'))

@section('content')
    <p class="mb-md text-body-md text-on-surface-variant">
        {{ __('admin/audit.subtitle') }}
        <span class="ml-1">{{ __('admin/audit.retention', ['days' => config('audit.retention_days')]) }}</span>
    </p>

    @livewire('audit-log-index')
@endsection
