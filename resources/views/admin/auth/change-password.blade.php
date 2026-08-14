@extends('admin.layouts.guest')

@section('title', __('admin/users.change_password.title'))

@section('content')
@php
    $campo = 'h-12 w-full rounded-lg border border-outline-variant bg-surface-container-lowest
              px-sm text-body-md text-on-surface outline-none transition-shadow
              focus:border-secondary focus:ring-2 focus:ring-secondary';
@endphp

{{--
    Sin enlaces de navegación a propósito: el middleware ForcePasswordChange
    redirige aquí desde cualquier otra pantalla, así que ofrecer salidas solo
    llevaría de vuelta a este mismo sitio. La única salida real es cambiar la
    contraseña o cerrar sesión.
--}}
<div class="flex min-h-full items-center justify-center px-margin-mobile py-xl">
    <div class="w-full max-w-md">

        <div class="mb-md flex items-center gap-xs">
            <span class="material-symbols-outlined text-[32px] text-secondary">key</span>
            <span class="text-title-lg font-bold text-secondary">{{ config('app.name') }}</span>
        </div>

        <h1 class="font-heading text-headline-md-mobile text-on-surface">
            {{ __('admin/users.change_password.title') }}
        </h1>

        <p class="mt-xs text-body-md text-on-surface-variant">
            {{ __('admin/users.change_password.intro') }}
        </p>

        <form method="POST" action="{{ route('admin.password.forced') }}" class="mt-md space-y-sm">
            @csrf

            <label class="block">
                <span class="mb-1 block text-caption text-on-surface-variant">
                    {{ __('admin/users.fields.current_password') }}
                </span>
                <input type="password" name="current_password" class="{{ $campo }}"
                       autocomplete="current-password" required autofocus>
                @error('current_password')
                    <span class="mt-1 block text-caption text-error">{{ $message }}</span>
                @enderror
            </label>

            <label class="block">
                <span class="mb-1 block text-caption text-on-surface-variant">
                    {{ __('admin/users.fields.new_password') }}
                </span>
                <input type="password" name="password" class="{{ $campo }}"
                       autocomplete="new-password" required>
                @error('password')
                    <span class="mt-1 block text-caption text-error">{{ $message }}</span>
                @enderror
            </label>

            <label class="block">
                <span class="mb-1 block text-caption text-on-surface-variant">
                    {{ __('admin/users.fields.confirm_password') }}
                </span>
                <input type="password" name="password_confirmation" class="{{ $campo }}"
                       autocomplete="new-password" required>
            </label>

            <button type="submit"
                    class="w-full rounded-lg bg-primary-container px-md py-sm text-label-md
                           font-semibold text-on-primary transition-all hover-lift">
                {{ __('admin/users.change_password.submit') }}
            </button>
        </form>

        <form method="POST" action="{{ route('admin.logout') }}" class="mt-sm text-center">
            @csrf
            <button type="submit" class="text-label-md text-on-surface-variant hover:text-secondary">
                Cerrar sesión
            </button>
        </form>
    </div>
</div>
@endsection
