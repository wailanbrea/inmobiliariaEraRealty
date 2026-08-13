@extends('admin.layouts.app')

@section('title', __('admin/catalog.property_types'))

@section('content')
    @include('admin.catalog.partials.tabs', ['active' => 'types'])
    @livewire('catalog-manager', ['catalog' => 'property-types'])
@endsection
