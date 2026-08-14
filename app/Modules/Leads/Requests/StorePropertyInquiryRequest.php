<?php

namespace App\Modules\Leads\Requests;

class StorePropertyInquiryRequest extends PublicLeadRequest
{
    public function rules(): array
    {
        return [
            ...$this->contactRules(),
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }
}
