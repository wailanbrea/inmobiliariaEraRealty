@props(['variant' => 'desktop'])

@php
    use App\Support\Locale;

    $current = Locale::current();
    $options = [];

    foreach (Locale::codes() as $code) {
        if ($code === $current) {
            continue;
        }

        // Si no hay equivalente de esta pagina en el otro idioma (por ejemplo
        // una propiedad sin traducir), se cae a la portada de ese idioma en
        // lugar de generar un enlace roto. Ver docs/15_I18N.md seccion 5.
        $options[$code] = Locale::alternateUrl($code) ?? lroute('home', [], $code);
    }
@endphp

@if ($variant === 'mobile')

    <div class="flex items-center gap-xs border-t border-outline-variant/40 pt-sm">
        <span class="material-symbols-outlined text-[20px] text-on-surface-variant">language</span>
        <span class="sr-only">{{ __('common.locale.switch') }}</span>

        @foreach (Locale::codes() as $code)
            @if ($code === $current)
                <span aria-current="true"
                      class="rounded-lg bg-surface-container px-xs py-1 text-label-md font-semibold text-on-surface">
                    {{ Locale::meta($code)['short'] }}
                </span>
            @else
                <a href="{{ $options[$code] }}" hreflang="{{ $code }}"
                   class="rounded-lg px-xs py-1 text-label-md text-on-surface-variant transition-colors hover:text-secondary">
                    {{ Locale::meta($code)['short'] }}
                </a>
            @endif
        @endforeach
    </div>

@else

    <div class="flex items-center rounded-lg border border-outline-variant"
         role="group" aria-label="{{ __('common.locale.switch') }}">
        @foreach (Locale::codes() as $code)
            @if ($code === $current)
                <span aria-current="true"
                      class="rounded-lg bg-primary-container px-xs py-1 text-label-md font-semibold text-on-primary">
                    {{ Locale::meta($code)['short'] }}
                </span>
            @else
                <a href="{{ $options[$code] }}" hreflang="{{ $code }}"
                   title="{{ Locale::meta($code)['name'] }}"
                   class="rounded-lg px-xs py-1 text-label-md text-on-surface-variant
                          transition-colors hover:bg-surface-container-low hover:text-secondary">
                    {{ Locale::meta($code)['short'] }}
                </a>
            @endif
        @endforeach
    </div>

@endif
