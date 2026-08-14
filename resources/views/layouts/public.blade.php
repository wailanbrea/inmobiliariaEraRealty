@php
    use App\Support\Locale;
    $meta = Locale::meta();
    $alternates = Locale::alternates();
@endphp
<!DOCTYPE html>
<html lang="{{ $meta['html_lang'] }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="whatsapp-click-url" content="{{ route('whatsapp.click') }}">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', __('common.footer.tagline'))">

    {{-- hreflang: cada idioma declara al otro, y x-default apunta al espanol.
         Ver docs/15_I18N.md seccion 6. --}}
    @foreach ($alternates as $code => $url)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $url }}">
    @endforeach
    @if (isset($alternates[Locale::default()]))
        <link rel="alternate" hreflang="x-default" href="{{ $alternates[Locale::default()] }}">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:locale" content="{{ $meta['og_locale'] }}">
    @foreach (Locale::codes() as $code)
        @if ($code !== Locale::current())
            <meta property="og:locale:alternate" content="{{ Locale::meta($code)['og_locale'] }}">
        @endif
    @endforeach
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="website">

    {{-- Open Graph y Twitter.
         Se emiten AQUI y no en cada vista para que ninguna pagina se quede sin
         tarjeta al compartirla. El detalle de propiedad y la noticia empujan su
         propia og:image desde @push('head'); como las etiquetas repetidas las
         resuelve el ultimo valor leido, la suya gana sobre esta por defecto.

         Importa mas de lo que parece: en este mercado los enlaces circulan por
         WhatsApp, y un enlace sin imagen se ve como un enlace sospechoso. --}}
    <meta property="og:title" content="@yield('title', setting('seo_default_title') ?: config('app.name'))">
    <meta property="og:description" content="@yield('description', setting('seo_default_description') ?: __('common.footer.tagline'))">
    <meta property="og:url" content="{{ url()->current() }}">

    @php
        $ogPorDefecto = setting('seo_default_og_image') ?: setting('site_logo');
    @endphp
    @if ($ogPorDefecto)
        <meta property="og:image" content="{{ url(Storage::url($ogPorDefecto)) }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', setting('seo_default_title') ?: config('app.name'))">
    <meta name="twitter:description" content="@yield('description', setting('seo_default_description') ?: __('common.footer.tagline'))">

    {{-- La organizacion se declara una sola vez, en el layout: Google la usa
         para el panel de conocimiento y para vincular las redes sociales. --}}
    <script type="application/ld+json">
        {!! json_encode(App\Support\Seo::organization(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if (setting('seo_google_site_verification'))
        <meta name="google-site-verification" content="{{ setting('seo_google_site_verification') }}">
    @endif

    @stack('head')
</head>
<body class="flex min-h-full flex-col bg-background font-body text-on-background"
      x-data="{ menuOpen: false }">

@php
    $nav = [
        ['home',             'common.nav.home'],
        ['properties.index', 'common.nav.properties'],
        ['invest.index',     'common.nav.invest'],
        ['about.index',      'common.nav.about'],
        ['news.index',       'common.nav.news'],
        ['contact.index',    'common.nav.contact'],
    ];
@endphp

{{-- data-header: motion.js le pone .is-condensed al pasar los 100 px de
     scroll (80 -> 64 px de alto, fondo translucido con desenfoque). --}}
<header data-header class="sticky top-0 z-50 w-full bg-surface-container-lowest shadow-sm">
    <div class="mx-auto flex h-20 w-full max-w-container-max items-center justify-between
                px-margin-mobile md:px-gutter">

        <a href="{{ lroute('home') }}" class="group flex items-center gap-xs">
            @if (setting('site_logo'))
                <img src="{{ Storage::url(setting('site_logo')) }}"
                     alt="{{ setting('site_name') }}" class="h-10 w-auto">
            @else
                <span class="material-symbols-outlined text-[32px] text-secondary transition-transform group-hover:scale-110">
                    real_estate_agent
                </span>
                <span class="text-title-lg font-bold text-secondary">{{ setting('site_name') }}</span>
            @endif
        </a>

        <nav class="hidden items-center gap-md lg:flex" aria-label="{{ __('common.nav.menu') }}">
            @foreach ($nav as [$route, $label])
                @php $active = request()->routeIs(Locale::current().'.'.$route); @endphp
                <a href="{{ lroute($route) }}"
                   @class([
                       'text-label-md transition-colors',
                       'border-b-2 border-secondary pb-1 font-bold text-secondary' => $active,
                       'rounded px-xs py-1 text-on-surface-variant hover:bg-surface-container-low hover:text-secondary' => ! $active,
                   ])
                   @if ($active) aria-current="page" @endif>
                    {{ __($label) }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-xs">
            <x-language-switcher class="hidden sm:flex" />

            <a href="{{ lroute('publish.index') }}"
               class="hidden items-center gap-base rounded-lg border border-primary px-sm py-xs
                      text-label-md text-primary transition-colors hover:bg-surface-container-low xl:flex">
                <span class="material-symbols-outlined text-[18px]">add_home</span>
                {{ __('common.nav.publish') }}
            </a>

            <button @click="menuOpen = true" aria-label="{{ __('common.nav.open_menu') }}"
                    class="p-1 text-on-surface transition-colors hover:text-secondary lg:hidden">
                <span class="material-symbols-outlined text-[28px]">menu</span>
            </button>
        </div>
    </div>
</header>

{{-- Menu movil --}}
<div x-show="menuOpen" x-cloak class="fixed inset-0 z-[60] lg:hidden"
     @keydown.escape.window="menuOpen = false" role="dialog" aria-modal="true">

    <div x-show="menuOpen" x-transition.opacity @click="menuOpen = false"
         class="absolute inset-0 bg-primary/50"></div>

    <div x-show="menuOpen"
         x-transition:enter="transition-transform duration-300"
         x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-200"
         x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="absolute inset-y-0 right-0 flex w-4/5 max-w-xs flex-col gap-sm
                overflow-y-auto bg-surface-container-lowest p-sm">

        <div class="flex items-center justify-between">
            <span class="text-title-lg font-bold text-secondary">{{ __('common.nav.menu') }}</span>
            <button @click="menuOpen = false" aria-label="{{ __('common.nav.close_menu') }}"
                    class="p-1 text-on-surface-variant hover:text-secondary">
                <span class="material-symbols-outlined text-[24px]">close</span>
            </button>
        </div>

        <nav class="flex flex-col" aria-label="{{ __('common.nav.menu') }}">
            @foreach ($nav as [$route, $label])
                <a href="{{ lroute($route) }}"
                   class="rounded-lg px-xs py-sm text-body-lg text-on-surface transition-colors hover:bg-surface-container-low">
                    {{ __($label) }}
                </a>
            @endforeach
            <a href="{{ lroute('publish.index') }}"
               class="rounded-lg px-xs py-sm text-body-lg text-secondary transition-colors hover:bg-surface-container-low">
                {{ __('common.nav.publish') }}
            </a>
        </nav>

        <x-language-switcher variant="mobile" />
    </div>
</div>

<main class="flex-1">
    @yield('content')
</main>

<footer class="mt-xl bg-primary-container text-on-primary-container">
    <div class="mx-auto grid max-w-container-max grid-cols-1 gap-gutter px-margin-mobile
                py-xl md:grid-cols-4 md:px-gutter">

        <div>
            @if (setting('site_logo_dark'))
                <img src="{{ Storage::url(setting('site_logo_dark')) }}"
                     alt="{{ setting('site_name') }}" class="mb-sm h-12 w-auto">
            @else
                <div class="mb-sm flex items-center gap-xs text-headline-md-mobile text-on-secondary-container">
                    <span class="material-symbols-outlined text-[32px]">real_estate_agent</span>
                    {{ setting('site_name') }}
                </div>
            @endif
            <p class="mb-md max-w-xs text-body-md text-on-primary-container/80">
                {{ setting('footer_text') }}
            </p>

            @php
                $redes = collect([
                    'social_facebook' => 'Facebook',
                    'social_instagram' => 'Instagram',
                    'social_youtube' => 'YouTube',
                    'social_tiktok' => 'TikTok',
                    'social_linkedin' => 'LinkedIn',
                ])->filter(fn ($n, $k) => filled(setting($k)));
            @endphp

            @if ($redes->isNotEmpty())
                <ul class="flex gap-xs">
                    @foreach ($redes as $key => $nombre)
                        <li>
                            <a href="{{ setting($key) }}" target="_blank" rel="noopener noreferrer"
                               aria-label="{{ $nombre }}"
                               class="flex size-10 items-center justify-center rounded-full
                                      bg-on-primary-fixed-variant/20 transition-colors
                                      hover:bg-on-primary-fixed-variant/40">
                                <span class="material-symbols-outlined text-on-secondary-container">link</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <h2 class="mb-sm text-title-lg text-on-secondary-container">{{ __('common.footer.navigation') }}</h2>
            <ul class="space-y-sm">
                @foreach (array_slice($nav, 0, 4) as [$route, $label])
                    <li>
                        <a href="{{ lroute($route) }}"
                           class="inline-block py-1 text-label-md text-on-primary-container/80 transition-opacity hover:text-on-primary-container">
                            {{ __($label) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="mb-sm text-title-lg text-on-secondary-container">{{ __('common.footer.support') }}</h2>
            <ul class="space-y-sm">
                @foreach ([['news.index','common.nav.news'], ['contact.index','common.nav.contact'], ['privacy','common.footer.privacy'], ['terms','common.footer.terms']] as [$route, $label])
                    <li>
                        <a href="{{ lroute($route) }}"
                           class="inline-block py-1 text-label-md text-on-primary-container/80 transition-opacity hover:text-on-primary-container">
                            {{ __($label) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="mb-sm text-title-lg text-on-secondary-container">{{ __('common.footer.contact') }}</h2>
            <ul class="space-y-sm text-body-md text-on-primary-container/80">
                @if (setting('contact_address'))
                    <li class="flex items-start gap-xs">
                        <span class="material-symbols-outlined mt-1 text-[20px]">location_on</span>
                        <span>{{ setting('contact_address') }}</span>
                    </li>
                @endif
                @if (setting('contact_email'))
                    <li class="flex items-center gap-xs">
                        <span class="material-symbols-outlined text-[20px]">mail</span>
                        <a href="mailto:{{ setting('contact_email') }}" class="hover:text-on-primary-container">
                            {{ setting('contact_email') }}
                        </a>
                    </li>
                @endif
                @if (setting('contact_phone'))
                    <li class="flex items-center gap-xs">
                        <span class="material-symbols-outlined text-[20px]">call</span>
                        <a href="tel:{{ preg_replace('/\D+/', '', setting('contact_phone')) }}"
                           class="hover:text-on-primary-container">
                            {{ setting('contact_phone') }}
                        </a>
                    </li>
                @endif
                @if (setting('contact_schedule'))
                    <li class="flex items-start gap-xs">
                        <span class="material-symbols-outlined mt-1 text-[20px]">schedule</span>
                        <span>{{ setting('contact_schedule') }}</span>
                    </li>
                @endif
            </ul>
        </div>
    </div>

    <div class="border-t border-on-primary-container/20 px-margin-mobile py-sm text-center md:px-gutter">
        <p class="text-body-md text-on-primary-container/60">
            &copy; {{ date('Y') }} {{ setting('site_name') }}. {{ setting('footer_copyright') }}
        </p>
    </div>
</footer>

<x-compare-bar />
<x-whatsapp-float />

</body>
</html>
