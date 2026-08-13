<?php

use App\Modules\Settings\Services\SettingsService;
use App\Modules\WhatsApp\Services\WhatsappService;
use App\Support\Locale;

if (! function_exists('lroute')) {
    /**
     * Genera la URL de una ruta publica en el idioma indicado (o el activo).
     *
     * Las rutas publicas estan registradas una vez por idioma con el nombre
     * prefijado, asi que lroute('properties.index') resuelve a
     * 'es.properties.index' o 'en.properties.index' segun corresponda.
     *
     * @param  array<string, mixed>  $parameters
     */
    function lroute(string $name, array $parameters = [], ?string $locale = null): string
    {
        $locale ??= Locale::current();

        return route("{$locale}.{$name}", $parameters);
    }
}

if (! function_exists('current_locale')) {
    function current_locale(): string
    {
        return Locale::current();
    }
}

if (! function_exists('setting')) {
    /**
     * Valor de configuracion, resuelto al idioma activo y cacheado.
     *
     * Solo devuelve claves publicas cuando se llama desde una vista publica:
     * la comprobacion la hace SettingsService, no quien llama.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsService::class)->get($key, $default);
    }
}

if (! function_exists('whatsapp')) {
    /**
     * Servicio de WhatsApp, para usar en las vistas:
     *   whatsapp()->generalLink()
     */
    function whatsapp(): WhatsappService
    {
        return app(WhatsappService::class);
    }
}
