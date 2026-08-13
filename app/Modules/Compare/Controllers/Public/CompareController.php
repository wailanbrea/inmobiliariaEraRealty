<?php

namespace App\Modules\Compare\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Compare\Services\CompareService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompareController extends Controller
{
    public function __construct(private CompareService $compare) {}

    public function index(Request $request): View
    {
        // Enlace compartible: /comparar?ids=12,45,78
        if ($request->filled('ids')) {
            $ids = collect(explode(',', (string) $request->query('ids')))
                ->map(fn ($id) => (int) trim($id))
                ->filter()
                ->all();

            $this->compare->replace($ids);
        }

        $propiedades = $this->compare->properties();

        return view('public.compare.index', [
            'properties' => $propiedades,
            'amenities' => $this->compare->amenityUnion($propiedades),
            'differences' => $this->compare->differences($propiedades),
            'max' => CompareService::MAX,
        ]);
    }

    /**
     * Alterna una propiedad. Vuelve a donde estaba el visitante, para no
     * sacarlo del listado cada vez que marca una.
     */
    public function toggle(Request $request, int $property): RedirectResponse
    {
        $cabia = $this->compare->toggle($property);

        if (! $cabia) {
            return back()->with('compare_error', __('compare.full', [
                'max' => CompareService::MAX,
            ]));
        }

        return back();
    }

    public function remove(int $property): RedirectResponse
    {
        $this->compare->remove($property);

        return back();
    }

    public function clear(): RedirectResponse
    {
        $this->compare->clear();

        return back();
    }
}
