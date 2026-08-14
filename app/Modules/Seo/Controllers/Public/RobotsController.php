<?php

namespace App\Modules\Seo\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * robots.txt servido desde la configuracion.
     *
     * Existia el ajuste 'seo_robots_txt' en el panel, pero el archivo real era
     * estatico en public/, asi que editarlo desde el panel no hacia nada. Se
     * sirve por ruta para que lo que el administrador escribe sea lo que ven
     * los rastreadores.
     *
     * La linea Sitemap se anade SIEMPRE, la escriba el administrador o no: es
     * la que hace que Google encuentre el sitemap sin registrarlo a mano, y
     * olvidarla es el error mas caro y mas silencioso de este archivo.
     */
    public function index(): Response
    {
        $personalizado = trim((string) setting('seo_robots_txt'));

        $cuerpo = $personalizado !== ''
            ? $personalizado
            : "User-agent: *\nDisallow: /admin\nDisallow: /storage/tmp";

        if (! str_contains(strtolower($cuerpo), 'sitemap:')) {
            $cuerpo .= "\n\nSitemap: ".route('sitemap');
        }

        return response($cuerpo."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
