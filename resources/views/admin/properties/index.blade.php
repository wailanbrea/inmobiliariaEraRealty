@extends('admin.layouts.app')

@section('title', __('admin/properties.title'))

@section('content')
    @livewire('property-index')
@endsection
