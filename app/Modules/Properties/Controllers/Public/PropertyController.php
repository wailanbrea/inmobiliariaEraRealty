<?php

namespace App\Modules\Properties\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Locations\Models\Province;
use App\Modules\Properties\Models\Amenity;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertySearchService;
use App\Modules\PropertyTypes\Models\PropertyType;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PropertyController extends Controller
{
    public function __construct(private PropertySearchService $search) {}

    public function index(Request $request): View
    {
        $filtros = $this->search->filtersFromRequest($request);

        return view('public.properties.index', [
            'properties' => $this->search->search($filtros),
            'filters' => $filtros,
            'hasFilters' => $this->search->hasActiveFilters($filtros),
            'types' => PropertyType::active()->get(),
            'provinces' => Province::active()->get(),
            'amenities' => Amenity::active()->get()->groupBy('category'),
            'sorts' => PropertySearchService::SORTS,
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $property = Property::query()
            ->whereHas('translations', fn ($q) => $q->where('slug', $slug))
            ->with(['translations', 'type', 'province', 'city', 'sector', 'agent', 'amenities', 'images'])
            ->first();

        if (! $property) {
            throw new NotFoundHttpException;
        }

        // Las fichas no publicadas solo se ven con el enlace firmado de la
        // vista previa del panel. Ver PropertyController@preview del admin.
        if (! $property->isPublished() && ! $request->hasValidSignature()) {
            throw new NotFoundHttpException;
        }

        $this->registerView($request, $property);

        return view('public.properties.show', [
            'property' => $property,
            'similar' => $this->search->similar($property),
            'isPreview' => ! $property->isPublished(),
        ]);
    }

    /**
     * Cuenta la visita, deduplicando por sesion.
     *
     * El registro detallado con hash de IP llega en la Fase 9; aqui solo se
     * incrementa el contador, sin bloquear la respuesta.
     */
    private function registerView(Request $request, Property $property): void
    {
        if (! $property->isPublished()) {
            return;
        }

        $clave = "viewed_property_{$property->id}";

        if ($request->session()->has($clave)) {
            return;
        }

        $request->session()->put($clave, true);

        $property->incrementQuietly('views_count');
    }
}
