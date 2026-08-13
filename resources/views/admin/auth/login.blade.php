@extends('admin.layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
{{--
    Pantalla partida: a la izquierda la fotografía (que es el activo principal
    de una inmobiliaria), a la derecha el formulario. En móvil la imagen
    desaparece y el formulario ocupa todo el ancho.
--}}
<div class="flex min-h-full">

    {{-- Panel izquierdo --}}
    <div class="relative hidden w-1/2 lg:block bg-primary-container">
        <div class="absolute inset-0 bg-gradient-to-t from-primary-container via-primary-container/70 to-primary-container/30"></div>

        <div class="relative z-10 flex h-full flex-col justify-between p-xl">
            <div class="flex items-center gap-xs">
                <span class="material-symbols-outlined text-[32px] text-secondary-fixed-dim">real_estate_agent</span>
                <span class="text-title-lg font-bold text-on-secondary-container">{{ config('app.name') }}</span>
            </div>

            <div>
                <h2 class="font-display text-display-lg text-on-secondary-container max-w-md">
                    Panel de administración
                </h2>
                <p class="mt-sm max-w-sm text-body-lg text-on-primary-container">
                    Gestiona propiedades, noticias y contactos desde un solo lugar.
                </p>
            </div>

            <p class="text-caption text-on-primary-container/60">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </p>
        </div>
    </div>

    {{-- Formulario --}}
    <div class="flex w-full flex-col justify-center px-margin-mobile py-lg lg:w-1/2 lg:px-xl">
        <div class="mx-auto w-full max-w-sm">

            <div class="mb-lg flex items-center gap-xs lg:hidden">
                <span class="material-symbols-outlined text-[32px] text-secondary">real_estate_agent</span>
                <span class="text-title-lg font-bold text-secondary">{{ config('app.name') }}</span>
            </div>

            <h1 class="font-heading text-headline-md text-on-surface">Iniciar sesión</h1>
            <p class="mt-base text-body-md text-on-surface-variant">
                Accede con tu cuenta de administrador.
            </p>

            {{-- Un solo bloque de error, genérico. No revela si el correo existe. --}}
            @if ($errors->any())
                <div role="alert"
                     class="mt-md flex items-start gap-xs rounded-lg bg-error-container px-sm py-xs text-on-error-container">
                    <span class="material-symbols-outlined text-[20px]">error</span>
                    <p class="text-body-md">{{ $errors->first() }}</p>
                </div>
            @endif

            @if (session('status'))
                <div role="status"
                     class="mt-md flex items-start gap-xs rounded-lg bg-tertiary-fixed px-sm py-xs text-on-tertiary-fixed">
                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                    <p class="text-body-md">{{ session('status') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" class="mt-md space-y-sm">
                @csrf

                <div>
                    <label for="email" class="mb-base block text-caption font-medium text-on-surface-variant">
                        Correo electrónico
                    </label>
                    <input id="email" name="email" type="email" required autofocus
                           autocomplete="username"
                           value="{{ old('email') }}"
                           @error('email') aria-invalid="true" @enderror
                           class="w-full rounded-lg border border-outline-variant bg-surface-container-low px-sm py-xs
                                  text-body-md text-on-surface transition-colors
                                  focus:border-secondary focus:ring-1 focus:ring-secondary"
                           placeholder="tu@correo.com">
                </div>

                <div>
                    <label for="password" class="mb-base block text-caption font-medium text-on-surface-variant">
                        Contraseña
                    </label>
                    <div class="relative" x-data="{ visible: false }">
                        <input id="password" name="password" required
                               autocomplete="current-password"
                               :type="visible ? 'text' : 'password'"
                               class="w-full rounded-lg border border-outline-variant bg-surface-container-low px-sm py-xs pr-lg
                                      text-body-md text-on-surface transition-colors
                                      focus:border-secondary focus:ring-1 focus:ring-secondary"
                               placeholder="••••••••">
                        <button type="button" @click="visible = !visible"
                                :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                class="absolute right-xs top-1/2 -translate-y-1/2 p-1 text-on-surface-variant
                                       transition-colors hover:text-secondary">
                            <span class="material-symbols-outlined text-[20px]"
                                  x-text="visible ? 'visibility_off' : 'visibility'">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-base">
                    <label for="remember" class="flex cursor-pointer items-center gap-xs">
                        <input id="remember" name="remember" type="checkbox" value="1"
                               class="rounded-sm border-outline-variant text-secondary focus:ring-secondary">
                        <span class="text-body-md text-on-surface-variant">Recordar sesión</span>
                    </label>

                    {{-- Fase 0: recuperación de contraseña --}}
                    <span class="text-body-md text-outline" title="Disponible próximamente">
                        ¿Olvidaste tu contraseña?
                    </span>
                </div>

                <button type="submit"
                        class="mt-md flex w-full items-center justify-center gap-xs rounded-lg bg-primary-container
                               px-md py-xs text-label-md font-semibold text-on-primary shadow-sm
                               transition-all duration-200 hover:shadow-ambient-hover
                               focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary">
                    <span class="material-symbols-outlined text-[20px]">login</span>
                    Entrar
                </button>
            </form>

            <p class="mt-lg text-center text-caption text-on-surface-variant">
                Acceso restringido. Los intentos fallidos quedan registrados.
            </p>
        </div>
    </div>
</div>
@endsection
