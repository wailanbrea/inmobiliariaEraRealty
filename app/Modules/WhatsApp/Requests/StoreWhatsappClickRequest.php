<?php

namespace App\Modules\WhatsApp\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWhatsappClickRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'source' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/'],
            'phone_number' => ['required', 'string', 'max:30', 'regex:/^\d{7,30}$/'],
            'generated_message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
