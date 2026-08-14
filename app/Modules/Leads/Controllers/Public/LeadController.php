<?php

namespace App\Modules\Leads\Controllers\Public;

use App\Enums\LeadSource;
use App\Http\Controllers\Controller;
use App\Modules\Leads\Requests\StoreInvestmentRequest;
use App\Modules\Leads\Requests\StorePropertyInquiryRequest;
use App\Modules\Leads\Services\LeadService;
use App\Modules\Properties\Models\Property;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LeadController extends Controller
{
    public function property(StorePropertyInquiryRequest $request, string $slug, LeadService $leads): RedirectResponse
    {
        $property = Property::query()
            ->whereHas('translations', fn ($query) => $query->where('slug', $slug))
            ->with('translations')
            ->first();

        if (! $property?->isPublished() || ! $property->status->acceptsLeads()) {
            throw new NotFoundHttpException;
        }

        $data = $request->validated();
        $data['property_id'] = $property->id;
        $leads->create(
            LeadSource::PropertyDetail,
            $data,
            $request,
            ['reference_code' => $property->reference_code],
        );

        return to_route(current_locale().'.properties.show', $slug)
            ->with('lead_success', __('contact.success'));
    }

    public function investment(StoreInvestmentRequest $request, LeadService $leads): RedirectResponse
    {
        $data = $request->validated();
        $data['interest_type'] = 'invest';
        $leads->create(LeadSource::InvestmentPage, $data, $request);

        return to_route(current_locale().'.invest.index')
            ->with('lead_success', __('contact.success'));
    }
}
