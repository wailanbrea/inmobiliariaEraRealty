<?php

namespace App\Modules\Leads\Requests;

class StoreInvestmentRequest extends PublicLeadRequest
{
    public function rules(): array
    {
        return [
            ...$this->contactRules(),
            'budget_range' => ['nullable', 'string', 'max:50'],
            'preferred_contact' => ['required', 'in:phone,whatsapp,email'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }
}
