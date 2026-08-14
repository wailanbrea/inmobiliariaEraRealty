@php
    $compare = app(\App\Modules\Compare\Services\CompareService::class);
    $total = $compare->count();
@endphp

@if ($total > 0 && ! request()->routeIs('*.compare.index'))
    {{-- Barra flotante del comparador. Sube desde abajo al marcar la primera
         propiedad. Se oculta en la propia página de comparación. --}}
    <div data-compare-bar
         class="fixed inset-x-0 bottom-0 z-40 border-t border-outline-variant/40
                bg-surface-container-lowest/95 backdrop-blur">
        <div class="mx-auto flex max-w-container-max flex-wrap items-center justify-between gap-xs
                    px-margin-mobile py-xs md:px-gutter">

            <p class="flex items-center gap-xs text-label-md text-on-surface">
                <span class="material-symbols-outlined text-[20px] text-secondary">compare_arrows</span>
                {{ trans_choice('compare.count', $total, ['count' => $total]) }}
            </p>

            <div class="flex items-center gap-xs">
                <form method="POST" action="{{ lroute('compare.clear') }}">
                    @csrf
                    <button type="submit" data-touch-target
                            class="rounded-lg border border-outline-variant px-sm py-xs
                                   text-label-md text-on-surface transition-colors
                                   hover:bg-surface-container-low">
                        {{ __('compare.clear') }}
                    </button>
                </form>

                <a href="{{ lroute('compare.index') }}" data-touch-target
                   class="flex items-center gap-xs rounded-lg bg-primary-container px-md py-xs
                          text-label-md font-semibold text-on-primary transition-all hover-lift">
                    {{ __('compare.view') }}
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Espacio para que la barra no tape el pie --}}
    <div class="h-16" aria-hidden="true"></div>
@endif
