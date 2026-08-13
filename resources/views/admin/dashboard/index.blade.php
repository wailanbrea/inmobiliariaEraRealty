@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
    <div class="flex items-start gap-sm">
        <span class="material-symbols-outlined text-[32px] text-secondary">construction</span>
        <div>
            <h2 class="font-heading text-title-lg text-on-surface">Fase 0 completada</h2>
            <p class="mt-base max-w-2xl text-body-md text-on-surface-variant">
                La base del proyecto está operativa: Laravel 12, MariaDB, autenticación con roles
                y los design tokens de <code class="rounded-sm bg-surface-container px-1">estate_elite/DESIGN.md</code>
                compilados con Tailwind. Las métricas de este dashboard llegan en la Fase 1,
                cuando existan las tablas de propiedades, leads y noticias.
            </p>
        </div>
    </div>
</div>

{{-- Placeholders de las métricas definidas en docs/03_ADMIN_PANEL.md 2.3 --}}
<div class="mt-md grid grid-cols-2 gap-sm lg:grid-cols-4">
    @foreach ([
        ['home_work',     'Propiedades',      'Fase 2'],
        ['check_circle',  'Disponibles',      'Fase 2'],
        ['contact_page',  'Leads nuevos',     'Fase 5'],
        ['article',       'Noticias',         'Fase 6'],
    ] as [$icon, $label, $phase])
        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm ambient-shadow">
            <div class="flex items-center justify-between">
                <span class="material-symbols-outlined text-[20px] text-outline">{{ $icon }}</span>
                <span class="rounded-full bg-surface-container px-xs py-0.5 text-caption text-on-surface-variant">
                    {{ $phase }}
                </span>
            </div>
            <p class="mt-xs font-heading text-headline-md-mobile text-outline-variant">—</p>
            <p class="text-caption text-on-surface-variant">{{ $label }}</p>
        </div>
    @endforeach
</div>

<div class="mt-md rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
    <h2 class="font-heading text-title-lg text-on-surface">Siguiente fase</h2>
    <p class="mt-base text-body-md text-on-surface-variant">
        <strong class="text-on-surface">Fase 1 — Configuración general:</strong>
        tabla <code class="rounded-sm bg-surface-container px-1">settings</code>, cambio de logo y favicon,
        WhatsApp con generación automática del link, configuración de correo con envío de prueba,
        y SEO global.
    </p>
    <p class="mt-xs text-caption text-on-surface-variant">
        Checklist completo en <code class="rounded-sm bg-surface-container px-1">docs/10_TODO_MASTER.md</code>
    </p>
</div>

@endsection
