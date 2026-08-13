<?php

namespace App\Modules\Pages\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Marcador de posicion temporal.
 *
 * Las rutas publicas existen desde la Fase 0 porque la infraestructura de
 * idioma (prefijos, segmentos traducidos, hreflang, selector) necesita rutas
 * reales para poder probarse. Cada metodo se sustituye por su controlador
 * definitivo en la fase indicada.
 */
class PlaceholderController extends Controller
{
    public function properties(): View
    {
        return $this->pending('common.nav.properties', 4);
    }

    public function propertyDetail(string $slug): View
    {
        return $this->pending('common.nav.properties', 4, $slug);
    }

    public function compare(): View
    {
        return $this->pending('common.nav.compare', 4);
    }

    public function invest(): View
    {
        return $this->pending('common.nav.invest', 4);
    }

    public function about(): View
    {
        return $this->pending('common.nav.about', 4);
    }

    public function news(): View
    {
        return $this->pending('common.nav.news', 6);
    }

    public function newsDetail(string $slug): View
    {
        return $this->pending('common.nav.news', 6, $slug);
    }

    public function contact(): View
    {
        return $this->pending('common.nav.contact', 5);
    }

    public function publish(): View
    {
        return $this->pending('common.nav.publish', 5);
    }

    public function privacy(): View
    {
        return $this->pending('common.footer.privacy', 4);
    }

    public function terms(): View
    {
        return $this->pending('common.footer.terms', 4);
    }

    private function pending(string $titleKey, int $phase, ?string $slug = null): View
    {
        return view('public.pending', [
            'titleKey' => $titleKey,
            'phase' => $phase,
            'slug' => $slug,
        ]);
    }
}
