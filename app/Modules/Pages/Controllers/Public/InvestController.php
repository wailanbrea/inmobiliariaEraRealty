<?php

namespace App\Modules\Pages\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Pages\Models\ContentSection;
use App\Modules\Properties\Models\Property;
use Illuminate\View\View;

class InvestController extends Controller
{
    public function index(): View
    {
        $secciones = ContentSection::forPageCached('invest');

        return view('public.invest.index', [
            'hero' => $secciones->get('hero'),
            'whyInvest' => $secciones->get('why_invest'),
            'process' => $secciones->get('process'),
            'disclaimer' => $secciones->get('disclaimer'),
            'cta' => $secciones->get('cta'),

            'properties' => Property::query()
                ->published()
                ->investment()
                ->forListing()
                ->orderByDesc('published_at')
                ->limit(6)
                ->get(),
        ]);
    }
}
