<?php

namespace App\Modules\Pages\Controllers\Public;

use App\Enums\LeadSource;
use App\Http\Controllers\Controller;
use App\Modules\Leads\Requests\StorePublishPropertyRequest;
use App\Modules\Leads\Services\LeadService;
use App\Modules\Locations\Models\Province;
use App\Modules\PropertyTypes\Models\PropertyType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublishPropertyController extends Controller
{
    public function index(LeadService $leads): View
    {
        return view('public.publish.index', [
            'formToken' => $leads->formToken(),
            'propertyTypes' => PropertyType::active()->get(),
            'provinces' => Province::active()->get(),
        ]);
    }

    public function store(StorePublishPropertyRequest $request, LeadService $leads): RedirectResponse
    {
        $data = $request->validated();
        $details = collect($data)->only([
            'property_type_id', 'operation_type', 'province_id', 'location',
            'bedrooms', 'bathrooms', 'area', 'expected_price', 'currency',
        ])->all();
        $leads->create(LeadSource::PublishProperty, $data, $request, $details);

        return to_route(current_locale().'.publish.index')->with('success', __('publish.success'));
    }
}
