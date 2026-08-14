<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Panel') &middot; {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet">

    {{-- admin.js y NO app.js: el panel usa el Alpine que trae Livewire.
         Ver la cabecera de resources/js/admin.js. --}}
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @livewireStyles
</head>
<body class="h-full bg-surface font-body text-on-surface"
      x-data="{ sidebarOpen: false }">

{{-- Sidebar --}}
{{--
    El estado se expresa con un ternario, no anadiendo 'translate-x-0' sobre un
    '-translate-x-full' fijo: eso dejaria las dos clases presentes a la vez y el
    resultado dependeria del orden en que Tailwind las emita. Asi solo hay una.
--}}
<aside class="fixed inset-y-0 left-0 z-40 w-64 bg-primary-container
              transition-transform duration-300 lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

    <div class="flex h-20 items-center gap-xs px-sm">
        <span class="material-symbols-outlined text-[28px] text-secondary-fixed-dim">real_estate_agent</span>
        <span class="text-title-lg font-bold text-on-secondary-container">ERA Realty</span>
    </div>

    <nav class="mt-xs space-y-1 px-xs" aria-label="Navegación principal">
        @php
            // Los módulos aún no implementados se muestran deshabilitados en vez
            // de ocultarse: el cliente ve el alcance completo desde el día uno.
            // [icono, etiqueta, ruta, activo, patrón para marcar la sección]
            $nav = [
                ['dashboard',      'Dashboard',     'admin.dashboard', true,  'admin.dashboard'],
                ['home_work',      'Propiedades',   'admin.properties.index', true, 'admin.properties.*'],
                ['photo_library',  'Media',         'admin.media.index', true, 'admin.media.*'],
                ['article',        'Noticias',      'admin.news.posts.index', true, 'admin.news.*'],
                ['contact_page',   'Leads',         'admin.leads.index', true, 'admin.leads.*'],
                ['badge',          'Agentes',       'admin.agents.index', true, 'admin.agents.*'],
                ['category',       'Catálogo',      'admin.catalog.types', true, 'admin.catalog.*'],
                ['description',    'Contenido',     'admin.content.index', true, 'admin.content.*'],
                // Se llama «Clics de WhatsApp» y no «WhatsApp» porque no es una
                // integracion de mensajeria: no envia nada. Mide cuantas
                // veces se pulsa un enlace de WhatsApp y desde donde.
                ['ads_click',      'Clics de WhatsApp', 'admin.whatsapp.index', true, 'admin.whatsapp.*'],
                // Los reportes cruzan leads, dinero y comportamiento: mismo
                // criterio que la auditoría, solo administradores.
                ['insights',       'Reportes',      'admin.reports.index',
                    auth()->user()->hasAnyRole(['admin', 'super_admin']), 'admin.reports.*'],
                // La auditoría solo la ven administradores: es información de
                // seguridad (quién entró, desde qué IP), no de contenido. Un
                // editor la vería deshabilitada, que es más honesto que
                // ofrecerle un enlace que devuelve 403.
                ['history',        'Auditoría',     'admin.audit.index',
                    auth()->user()->hasAnyRole(['admin', 'super_admin']), 'admin.audit.*'],
                // Repartir accesos es lo único reservado al super_admin: un
                // 'admin' toca todo el contenido pero no puede crear cuentas.
                ['group',          'Usuarios',      'admin.users.index',
                    auth()->user()->isSuperAdmin(), 'admin.users.*'],
                ['settings',       'Configuración', 'admin.settings.general', true, 'admin.settings.*'],
            ];
        @endphp

        @foreach ($nav as [$icon, $label, $route, $enabled, $pattern])
            @if ($enabled)
                @php $activo = request()->routeIs($pattern); @endphp
                <a href="{{ route($route) }}"
                   @class([
                       'flex items-center gap-xs rounded-lg px-xs py-xs text-label-md transition-colors',
                       'bg-on-primary-fixed-variant/30 text-on-secondary-container font-semibold' => $activo,
                       'text-on-primary-container hover:bg-on-primary-fixed-variant/20 hover:text-on-secondary-container' => ! $activo,
                   ])
                   @if ($activo) aria-current="page" @endif>
                    <span class="material-symbols-outlined text-[20px]">{{ $icon }}</span>
                    {{ $label }}
                </a>
            @else
                <span class="flex cursor-not-allowed items-center gap-xs rounded-lg px-xs py-xs
                             text-label-md text-on-primary-container/40"
                      title="Disponible en una fase posterior">
                    <span class="material-symbols-outlined text-[20px]">{{ $icon }}</span>
                    {{ $label }}
                </span>
            @endif
        @endforeach
    </nav>
</aside>

{{-- Velo en móvil --}}
<div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
     class="fixed inset-0 z-30 bg-primary/40 lg:hidden" x-cloak></div>

{{-- Contenido --}}
<div class="lg:pl-64">

    <header class="sticky top-0 z-20 flex h-20 items-center justify-between gap-sm
                   border-b border-outline-variant/40 bg-surface-container-lowest px-margin-mobile md:px-gutter">

        <div class="flex items-center gap-xs">
            <button @click="sidebarOpen = !sidebarOpen"
                    aria-label="Abrir menú"
                    class="p-1 text-on-surface-variant transition-colors hover:text-secondary lg:hidden">
                <span class="material-symbols-outlined text-[24px]">menu</span>
            </button>
            <h1 class="font-heading text-headline-md-mobile text-on-surface">@yield('title', 'Panel')</h1>
        </div>

        <div class="flex items-center gap-sm">
            <a href="{{ url('/') }}" target="_blank" rel="noopener"
               class="hidden items-center gap-base rounded-lg border border-outline-variant px-sm py-xs
                      text-label-md text-on-surface transition-colors hover:bg-surface-container-low sm:flex">
                <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                Ver sitio
            </a>

            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open"
                        class="flex items-center gap-xs rounded-lg px-xs py-xs transition-colors hover:bg-surface-container-low">
                    <span class="flex size-8 items-center justify-center rounded-full bg-primary-container
                                 text-caption font-semibold text-on-primary">
                        {{ Str::upper(Str::substr(auth()->user()->name, 0, 2)) }}
                    </span>
                    <span class="hidden text-label-md text-on-surface sm:block">{{ auth()->user()->name }}</span>
                    <span class="material-symbols-outlined text-[20px] text-on-surface-variant">expand_more</span>
                </button>

                <div x-show="open" x-transition x-cloak
                     class="absolute right-0 mt-xs w-56 rounded-lg border border-outline-variant/40
                            bg-surface-container-lowest py-xs shadow-ambient-hover">
                    <div class="border-b border-outline-variant/40 px-sm pb-xs">
                        <p class="text-label-md text-on-surface">{{ auth()->user()->name }}</p>
                        <p class="text-caption text-on-surface-variant">{{ auth()->user()->email }}</p>
                        <p class="mt-1 text-caption text-secondary">
                            {{ auth()->user()->getRoleNames()->first() ?? 'sin rol' }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex w-full items-center gap-xs px-sm py-xs text-left text-label-md
                                       text-on-surface transition-colors hover:bg-surface-container-low">
                            <span class="material-symbols-outlined text-[20px]">logout</span>
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-[1440px] px-margin-mobile py-md md:px-gutter">
        @yield('content')
    </main>
</div>

@livewireScripts
@stack('scripts')
</body>
</html>
