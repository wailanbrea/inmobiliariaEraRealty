{{-- Los tres catálogos comparten navegación: son la misma tarea. --}}
<nav class="mb-md flex gap-1 overflow-x-auto border-b border-outline-variant/40"
     aria-label="{{ __('admin/catalog.property_types') }}">
    @foreach ([
        'types'     => ['admin.catalog.types',     'admin/catalog.property_types', 'category'],
        'amenities' => ['admin.catalog.amenities', 'admin/catalog.amenities',      'checklist'],
        'locations' => ['admin.catalog.locations', 'admin/catalog.locations',      'location_on'],
    ] as $clave => [$ruta, $etiqueta, $icono])
        @php $esActiva = $active === $clave; @endphp
        <a href="{{ route($ruta) }}"
           @class([
               'flex shrink-0 items-center gap-xs whitespace-nowrap px-sm py-xs text-label-md transition-colors',
               'border-b-2 border-secondary font-semibold text-secondary' => $esActiva,
               'text-on-surface-variant hover:text-secondary' => ! $esActiva,
           ])
           @if ($esActiva) aria-current="page" @endif>
            <span class="material-symbols-outlined text-[18px]">{{ $icono }}</span>
            {{ __($etiqueta) }}
        </a>
    @endforeach
</nav>
