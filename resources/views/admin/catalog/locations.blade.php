@extends('admin.layouts.app')

@section('title', __('admin/catalog.locations'))

@section('content')
    @include('admin.catalog.partials.tabs', ['active' => 'locations'])
    @livewire('location-manager')
@endsection
