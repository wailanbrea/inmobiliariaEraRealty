<?php

namespace App\Modules\Leads\Requests;

class StorePublishPropertyRequest extends PublicLeadRequest
{
    public function rules(): array
    {
        return [
            ...$this->contactRules(),
            'property_type_id' => ['required', 'integer', 'exists:property_types,id'],
            'operation_type' => ['required', 'in:sale,rent,temporary_rent'],
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'location' => ['required', 'string', 'max:200'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:99'],
            'bathrooms' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'area' => ['nullable', 'numeric', 'min:1', 'max:9999999'],
            'expected_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'currency' => ['required', 'in:USD,DOP'],
            'message' => ['nullable', 'string', 'max:5000'],
            'consent' => ['accepted'],
        ];
    }
}
