<?php

namespace App\Modules\Pages\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Agents\Models\Agent;
use App\Modules\Pages\Models\ContentSection;
use App\Modules\Properties\Models\Property;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function index(): View
    {
        $secciones = ContentSection::forPageCached('about');

        return view('public.about.index', [
            'hero' => $secciones->get('hero'),
            'story' => $secciones->get('story'),
            'values' => $secciones->get('values'),
            'teamSection' => $secciones->get('team'),
            'cta' => $secciones->get('cta'),

            // El equipo sale de los agentes activos. Si no hay ninguno, la
            // sección entera no se pinta: mejor eso que un hueco vacío.
            'team' => Agent::active()->get(),

            // Cifras reales, no inventadas: se cuentan de la base de datos.
            'stats' => [
                'published' => Property::published()->count(),
                'sold' => Property::query()
                    ->whereIn('status', ['sold', 'rented'])
                    ->count(),
            ],
        ]);
    }
}
