<?php

namespace App\Modules\WhatsApp\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\WhatsApp\Models\WhatsappClick;
use App\Modules\WhatsApp\Requests\StoreWhatsappClickRequest;
use Illuminate\Http\Response;

class WhatsappClickController extends Controller
{
    public function store(StoreWhatsappClickRequest $request): Response
    {
        WhatsappClick::create([
            ...$request->validated(),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'referrer_url' => mb_substr((string) $request->headers->get('referer'), 0, 500),
        ]);

        return response()->noContent();
    }
}
