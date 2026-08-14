@extends('admin.layouts.app')

@section('title', __('admin/content.title'))

@section('content')

{{-- Guia por pasos para quien administra el sitio sin ser tecnico. Se abre
     sola la primera vez y despues queda a mano en «Ver guia». --}}
{{-- La pagina ya trae su propio texto explicativo bajo las pestanas, asi
     que aqui solo va el disparador de la guia. --}}
<div class="mb-md flex justify-end">
    <x-admin.guided-tour id="contenido"
        :steps="collect(__('admin/content.guide.steps'))
            ->map(fn ($s, $i) => $s + ['icono' => ['ads_click','edit_note','translate','add_photo_alternate','check_circle'][$i]])
            ->all()" />
</div>


{{-- Selector de página --}}
<nav class="mb-md flex gap-1 overflow-x-auto border-b border-outline-variant/40"
     aria-label="{{ __('admin/content.title') }}">
    @foreach ($pages as $clave)
        @php $activa = $pageKey === $clave; @endphp
        <a href="{{ route('admin.content.index', ['pagina' => $clave]) }}"
           @class([
               'flex shrink-0 items-center gap-xs whitespace-nowrap px-sm py-xs text-label-md transition-colors',
               'border-b-2 border-secondary font-semibold text-secondary' => $activa,
               'text-on-surface-variant hover:text-secondary' => ! $activa,
           ])
           @if ($activa) aria-current="page" @endif>
            <span class="material-symbols-outlined text-[18px]">
                {{ $clave === 'home' ? 'home' : 'trending_up' }}
            </span>
            {{ __("admin/content.pages.{$clave}") }}
        </a>
    @endforeach
</nav>

@livewire('content-section-manager', ['pageKey' => $pageKey], key($pageKey))

@endsection
