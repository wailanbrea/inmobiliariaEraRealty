@props(['id', 'steps', 'title' => null])

{{--
    Guía por pasos para el panel.

    Pensada para quien administra el sitio sin ser técnico. Tres decisiones
    que la hacen soportable para quien ya sabe usarlo:

    1. Se abre SOLA una única vez, y solo la primera vez. Después queda el
       botón «Ver guía» por si alguien quiere repasarla.
    2. La preferencia vive en localStorage, no en la base de datos: es una
       decisión de cada persona en su navegador, no del sitio, y guardarla
       en el servidor obligaría a una tabla y una petición para algo que no
       lo merece.
    3. Se cierra con Esc, con el botón y tocando fuera. Una ayuda de la que
       cuesta salir deja de ser ayuda.

    'steps' es una lista de ['titulo' => ..., 'texto' => ..., 'icono' => ...].
--}}
@php
    $clave = 'guia_'.$id.'_vista';
@endphp

<div x-data="{
        abierta: false,
        paso: 0,
        total: {{ count($steps) }},
        clave: '{{ $clave }}',

        init() {
            // La primera visita la abre; a partir de ahí, solo a petición.
            if (! localStorage.getItem(this.clave)) {
                this.$nextTick(() => { this.abierta = true })
            }
        },
        abrir() { this.paso = 0; this.abierta = true },
        cerrar() {
            this.abierta = false
            localStorage.setItem(this.clave, '1')
        },
        siguiente() { this.paso < this.total - 1 ? this.paso++ : this.cerrar() },
        anterior() { if (this.paso > 0) this.paso-- },
     }"
     @keydown.escape.window="cerrar()">

    {{-- Disparador siempre visible --}}
    <button type="button" @click="abrir()"
            class="inline-flex items-center gap-xs rounded-lg border border-outline-variant px-sm py-xs
                   text-label-md text-on-surface-variant transition-colors
                   hover:border-secondary hover:text-secondary">
        <span class="material-symbols-outlined text-[18px]">help</span>
        {{ __('admin/content.guide.open') }}
    </button>

    {{-- Panel --}}
    <div x-show="abierta" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-end justify-center bg-primary/50 p-0 sm:items-center sm:p-sm"
         role="dialog" aria-modal="true" :aria-label="'{{ $title ?? __('admin/content.guide.title') }}'">

        <div @click="cerrar()" class="absolute inset-0" aria-hidden="true"></div>

        {{-- Sin x-show ni transicion propia: el contenedor de arriba ya
             controla la visibilidad, y anidar dos x-show con transiciones
             distintas dejaba el panel en opacity-0 sobre el velo ya visible
             —se veia la pagina atenuada y ningun dialogo—. La entrada la da
             una animacion CSS, que no compite con Alpine. --}}
        <div class="relative w-full max-w-lg animate-[dialogo-entra_200ms_ease-out]
                    rounded-t-xl bg-surface-container-lowest p-md
                    shadow-ambient-lg sm:rounded-xl">

            <div class="mb-sm flex items-start justify-between gap-sm">
                <h2 class="font-heading text-title-lg text-on-surface">
                    {{ $title ?? __('admin/content.guide.title') }}
                </h2>
                <button type="button" @click="cerrar()"
                        aria-label="{{ __('common.actions.close') }}"
                        class="rounded-lg p-1 text-on-surface-variant transition-colors hover:text-secondary">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            @foreach ($steps as $i => $step)
                <div x-show="paso === {{ $i }}" x-transition.opacity class="flex gap-sm">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full
                                 bg-primary-container text-on-primary">
                        <span class="material-symbols-outlined text-[22px]">{{ $step['icono'] }}</span>
                    </span>
                    <div>
                        <p class="text-title-lg text-on-surface">{{ $step['titulo'] }}</p>
                        <p class="mt-1 text-body-md text-on-surface-variant">{{ $step['texto'] }}</p>
                    </div>
                </div>
            @endforeach

            {{-- Progreso: puntos, no una barra. Con cinco pasos la barra no
                 aporta precisión y los puntos dicen cuántos quedan de un
                 vistazo. --}}
            <div class="mt-md flex items-center justify-between gap-sm">
                <div class="flex gap-1" aria-hidden="true">
                    @foreach ($steps as $i => $step)
                        <span class="size-2 rounded-full transition-colors"
                              :class="paso === {{ $i }} ? 'bg-secondary' : 'bg-outline-variant'"></span>
                    @endforeach
                </div>

                <div class="flex items-center gap-xs">
                    <button type="button" @click="anterior()" x-show="paso > 0"
                            class="rounded-lg border border-outline-variant px-sm py-xs text-label-md
                                   text-on-surface transition-colors hover:bg-surface-container-low">
                        {{ __('admin/content.guide.back') }}
                    </button>

                    <button type="button" @click="siguiente()"
                            class="rounded-lg bg-primary-container px-md py-xs text-label-md font-semibold
                                   text-on-primary transition-all hover-lift">
                        <span x-text="paso < total - 1
                            ? '{{ __('admin/content.guide.next') }}'
                            : '{{ __('admin/content.guide.done') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
