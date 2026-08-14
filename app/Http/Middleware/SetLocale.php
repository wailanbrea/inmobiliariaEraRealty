<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija el idioma de la peticion a partir del prefijo de la ruta.
 *
 * El idioma lo determina la URL, no la cookie ni el navegador: cada URL debe
 * servir siempre el mismo contenido, o Google indexa lo que no toca y un
 * enlace compartido se ve en otro idioma que el que vio quien lo envio.
 * La cookie solo recuerda la preferencia para el selector.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route()?->getPrefix();
        $locale = trim((string) $locale, '/');

        if (! Locale::isSupported($locale)) {
            $locale = Locale::default();
        }

        app()->setLocale($locale);

        $response = $next($request);

        // Se recuerda el idioma que el visitante esta viendo de verdad.
        //
        // No hace falta que el selector haga nada especial: al pulsar "ES" se
        // navega a una URL espanola, esta linea escribe 'es', y a partir de
        // ahi DetectBrowserLocale deja de redirigir la portada. La eleccion
        // del visitante gana siempre sobre la del navegador, que es lo unico
        // razonable una vez que ha elegido.
        //
        // Un ano de vida: la preferencia de idioma no caduca en una sesion.
        return $response->withCookie(
            cookie(DetectBrowserLocale::COOKIE, $locale, 60 * 24 * 365)
        );
    }
}
