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

        return $next($request);
    }
}
