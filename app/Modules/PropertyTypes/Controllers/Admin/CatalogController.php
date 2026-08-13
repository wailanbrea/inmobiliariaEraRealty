<?php

namespace App\Modules\PropertyTypes\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Models\Property;
use Illuminate\View\View;

/**
 * Pantallas de catalogo: tipos de propiedad, amenidades y ubicaciones.
 *
 * El trabajo real lo hacen los componentes Livewire; el controlador solo
 * comprueba permisos y elige la vista.
 */
class CatalogController extends Controller
{
    public function propertyTypes(): View
    {
        $this->authorize('create', Property::class);

        return view('admin.catalog.types');
    }

    public function amenities(): View
    {
        $this->authorize('create', Property::class);

        return view('admin.catalog.amenities');
    }

    public function locations(): View
    {
        $this->authorize('create', Property::class);

        return view('admin.catalog.locations');
    }
}
