<?php

namespace App\Modules\Leads\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class PublicLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function contactRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[+()\d\s.-]{7,30}$/'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'preferred_contact' => ['nullable', 'in:phone,whatsapp,email'],
            'message' => ['nullable', 'string', 'max:5000'],
            'website' => ['nullable', 'string', 'max:200'],
            'form_token' => ['required', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'phone' => trim((string) $this->input('phone')),
            'email' => $this->filled('email') ? trim((string) $this->input('email')) : null,
        ]);
    }
}
