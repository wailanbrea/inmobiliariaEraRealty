<?php

namespace App\Support;

use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Utilidades de idioma para el sitio publico.
 *
 * Las rutas se registran una vez por idioma, con el nombre prefijado:
 *   es.properties.index  ->  /propiedades
 *   en.properties.index  ->  /en/properties
 *
 * En las vistas nunca se usa route() directamente para rutas publicas: se usa
 * el helper lroute(), que anade el prefijo del idioma activo.
 */
class Locale
{
    /** @return array<string, array<string, string>> */
    public static function supported(): array
    {
        return config('locales.supported');
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::supported());
    }

    public static function default(): string
    {
        return config('locales.default');
    }

    public static function current(): string
    {
        return app()->getLocale();
    }

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::codes(), true);
    }

    /** @return array<string, string> */
    public static function meta(?string $locale = null): array
    {
        return self::supported()[$locale ?? self::current()];
    }

    /**
     * Traduce un segmento de URL. Si no esta en el mapa, se devuelve tal cual:
     * asi anadir una ruta nueva no revienta, solo queda sin traducir.
     */
    public static function segment(string $key, string $locale): string
    {
        return config("locales.segments.{$key}.{$locale}", $key);
    }

    /**
     * Prefijo de URL del idioma ('' para espanol, 'en' para ingles).
     */
    public static function prefix(string $locale): string
    {
        return self::supported()[$locale]['prefix'] ?? '';
    }

    /**
     * Nombre de ruta sin el prefijo de idioma.
     * 'es.properties.show' -> 'properties.show'
     */
    public static function stripLocale(string $routeName): string
    {
        foreach (self::codes() as $code) {
            if (str_starts_with($routeName, "{$code}.")) {
                return substr($routeName, strlen($code) + 1);
            }
        }

        return $routeName;
    }

    /**
     * URL de la pagina actual en otro idioma.
     *
     * Devuelve null si no se puede resolver, para que la vista caiga en la
     * portada de ese idioma en lugar de generar un enlace roto.
     *
     * Los slugs traducidos de propiedades y noticias se resuelven en la Fase 2
     * y 6, cuando existan las tablas *_translations. Hasta entonces, las rutas
     * con parametros devuelven null.
     */
    public static function alternateUrl(string $locale): ?string
    {
        $route = RouteFacade::current();

        if (! $route || ! $route->getName()) {
            return null;
        }

        $base = self::stripLocale($route->getName());
        $target = "{$locale}.{$base}";

        if (! RouteFacade::has($target)) {
            return null;
        }

        $params = $route->parameters();

        // Un parametro de modelo puede tener slug distinto en cada idioma.
        // Los modelos traducibles expondran translatedSlug() en la Fase 2.
        foreach ($params as $key => $value) {
            if (is_object($value) && method_exists($value, 'translatedSlug')) {
                $translated = $value->translatedSlug($locale);

                if ($translated === null) {
                    return null;   // sin traduccion: mejor el listado que un 404
                }

                $params[$key] = $translated;
            }
        }

        try {
            return route($target, $params);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Enlaces alternativos para las etiquetas hreflang.
     *
     * @return array<string, string>
     */
    public static function alternates(): array
    {
        $links = [];

        foreach (self::codes() as $code) {
            $url = self::alternateUrl($code);

            if ($url !== null) {
                $links[$code] = $url;
            }
        }

        return $links;
    }
}
