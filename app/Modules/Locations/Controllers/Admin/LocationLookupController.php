<?php

namespace App\Modules\Locations\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Province;
use App\Modules\Locations\Models\Sector;
use Illuminate\Http\JsonResponse;

/**
 * Alimenta los selects encadenados del formulario de propiedades.
 *
 * Se cargan por peticion en lugar de volcar todas las ubicaciones del pais en
 * el HTML: son 32 provincias con sus ciudades y sectores, y el formulario ya
 * es pesado.
 */
class LocationLookupController extends Controller
{
    public function cities(Province $province): JsonResponse
    {
        return response()->json(
            $province->cities()->where('is_active', true)->get(['id', 'name'])
        );
    }

    public function sectors(City $city): JsonResponse
    {
        return response()->json(
            $city->sectors()->where('is_active', true)->get(['id', 'name'])
        );
    }

    public function allSectors(): JsonResponse
    {
        return response()->json(
            Sector::where('is_active', true)->get(['id', 'name', 'city_id'])
        );
    }
}
