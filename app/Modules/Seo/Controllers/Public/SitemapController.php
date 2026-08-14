<?php

namespace App\Modules\Seo\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Seo\Services\SitemapService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(private SitemapService $sitemap) {}

    public function index(): Response
    {
        // Una hora de cache en el navegador y en cualquier proxy intermedio:
        // el sitemap no cambia con cada visita y regenerarlo recorre toda la
        // tabla de propiedades.
        return response()
            ->view('sitemap', ['entries' => $this->sitemap->entries()])
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
