<?php

namespace App\Modules\Pages\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Pages\Models\ContentSection;
use App\Modules\Properties\Models\Property;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $secciones = ContentSection::forPageCached('home');
        $featured = Cache::remember('home.featured_properties', now()->addMinutes(10), fn () => Property::query()
            ->published()
            ->featured()
            ->forListing()
            ->orderByDesc('published_at')
            ->limit(6)
            ->get());

        $investment = Cache::remember('home.investment_properties', now()->addMinutes(10), fn () => Property::query()
            ->published()
            ->investment()
            ->forListing()
            ->orderByDesc('published_at')
            ->limit(3)
            ->get());

        return view('public.home', [
            // Bloques editables desde el panel
            'hero' => $secciones->get('hero'),
            'featuredSection' => $secciones->get('featured_properties'),
            'investmentSection' => $secciones->get('investment_cta'),
            'stats' => $secciones->get('stats'),
            'finalCta' => $secciones->get('final_cta'),

            'featured' => $featured,
            'investment' => $investment,
        ]);
    }
}
