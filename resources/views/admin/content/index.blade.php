@extends('admin.layouts.app')

@section('title', __('admin/content.title'))

@section('content')
    @livewire('content-section-manager', ['pageKey' => 'home'])
@endsection
