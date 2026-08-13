@extends('admin.layouts.app')

@section('title', __('admin/catalog.amenities'))

@section('content')
    @include('admin.catalog.partials.tabs', ['active' => 'amenities'])
    @livewire('catalog-manager', ['catalog' => 'amenities'])
@endsection
