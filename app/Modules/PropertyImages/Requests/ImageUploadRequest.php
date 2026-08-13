<?php

namespace App\Modules\PropertyImages\Requests;

use App\Rules\RealImage;
use Illuminate\Foundation\Http\FormRequest;

class ImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_property_images') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'max:30'],
            'images.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',                 // 5 MB, prompt maestro §9
                new RealImage(allowSvg: false, minSize: 400),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.required' => 'No se recibió ninguna imagen.',
            'images.*.max' => 'La imagen supera los 5 MB permitidos.',
            'images.*.mimes' => 'Solo se admiten JPG, PNG y WebP.',
        ];
    }
}
