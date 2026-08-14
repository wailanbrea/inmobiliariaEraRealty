<?php

namespace App\Modules\Pages\Controllers\Public;

use App\Enums\LeadSource;
use App\Http\Controllers\Controller;
use App\Modules\Leads\Requests\StoreContactRequest;
use App\Modules\Leads\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(LeadService $leads): View
    {
        return view('public.contact.index', ['formToken' => $leads->formToken()]);
    }

    public function store(StoreContactRequest $request, LeadService $leads): RedirectResponse
    {
        $data = $request->validated();
        $leads->create(LeadSource::ContactPage, $data, $request, ['subject' => $data['subject']]);

        return to_route(current_locale().'.contact.index')->with('success', __('contact.success'));
    }
}
