<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En la PRIMERA visita a la portada, lleva al visitante a la version en el
 * idioma de su navegador.
 *
 * Cuatro salvaguardas, y ninguna es opcional:
 *
 * 1. SOLO EN LA RAIZ. Redirigir en cada pagina significa que un enlace
 *    compartido a /propiedades/villa-cap-cana se ve en otro idioma que el que
 *    vio quien lo envio. Cada URL debe servir siempre el mismo contenido.
 *
 * 2. SOLO SI NO HA ELEGIDO. En cuanto el visitante ve una pagina en un idioma
 *    —por el selector o por donde aterrizo— SetLocale guarda una cookie. A
 *    partir de ahi manda su eleccion, no la del navegador. Sin esto, pulsar
 *    "ES" desde /en y volver a la portada te devolveria a ingles: el sitio
 *    ignorando lo que acabas de pedirle.
 *
 * 3. NUNCA A LOS RASTREADORES. Google desaconseja expresamente redirigir por
 *    Accept-Language: su rastreador entra desde EE. UU. anunciando ingles, y
 *    si la portada en espanol siempre le devuelve un 302 puede terminar sin
 *    indexarse. Las etiquetas hreflang ya le dicen que existen las dos.
 *
 * 4. REDIRECCION TEMPORAL (302), nunca permanente. Un 301 se queda cacheado en
 *    el navegador y deja al visitante sin forma de volver a la raiz espanola.
 */
class DetectBrowserLocale
{
    public const COOKIE = 'era_locale';

    /**
     * Fragmentos que identifican a un rastreador. No pretende ser exhaustivo
     * —imposible— sino cubrir a los que de verdad indexan el sitio.
     *
     * @var list<string>
     */
    private const BOTS = [
        'bot', 'crawler', 'spider', 'slurp', 'facebookexternalhit',
        'whatsapp', 'telegrambot', 'lighthouse', 'headlesschrome',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldRedirect($request)) {
            return $next($request);
        }

        $preferido = $this->preferredLocale($request);

        if ($preferido === Locale::default()) {
            return $next($request);
        }

        return redirect()->to(lroute('home', [], $preferido), 302);
    }

    private function shouldRedirect(Request $request): bool
    {
        return $request->isMethod('GET')
            && $request->path() === '/'
            && ! $request->hasCookie(self::COOKIE)
            && ! $this->isBot($request)
            && $request->hasHeader('Accept-Language');
    }

    private function isBot(Request $request): bool
    {
        $agente = strtolower((string) $request->userAgent());

        foreach (self::BOTS as $fragmento) {
            if (str_contains($agente, $fragmento)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Primer idioma soportado de la lista del navegador.
     *
     * getPreferredLanguage compara la cabecera completa respetando los pesos
     * (q=0.9), asi que un navegador con 'fr, en;q=0.8, es;q=0.5' recibe ingles
     * y no espanol solo por estar en la lista.
     */
    private function preferredLocale(Request $request): string
    {
        return $request->getPreferredLanguage(Locale::codes()) ?? Locale::default();
    }
}
