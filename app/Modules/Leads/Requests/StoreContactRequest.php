<?php

namespace App\Modules\Leads\Requests;

class StoreContactRequest extends PublicLeadRequest
{
    public function rules(): array
    {
        return [
            ...$this->contactRules(),
            'subject' => ['required', 'string', 'max:150'],
            'interest_type' => ['required', 'in:buy,rent,invest,sell,other'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }
}
