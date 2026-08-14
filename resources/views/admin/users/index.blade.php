@extends('admin.layouts.app')

@section('title', __('admin/users.title'))

@section('content')
    @livewire('user-manager')
@endsection
