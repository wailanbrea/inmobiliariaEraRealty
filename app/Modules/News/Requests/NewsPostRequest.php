<?php

namespace App\Modules\News\Requests;

use App\Enums\NewsStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewsPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_news') ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:news_categories,id'],
            'status' => ['required', Rule::enum(NewsStatus::class)],
            'is_featured' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date', 'required_if:status,'.NewsStatus::Scheduled->value],
            'featured_image' => ['nullable', 'string', 'max:255'],
            'title_es' => ['required', 'string', 'max:200'],
            'slug_es' => ['nullable', 'string', 'max:220'],
            'excerpt_es' => ['nullable', 'string', 'max:500'],
            'content_es' => ['required', 'string', 'max:200000'],
            'meta_title_es' => ['nullable', 'string', 'max:70'],
            'meta_description_es' => ['nullable', 'string', 'max:170'],
            'title_en' => ['nullable', 'string', 'max:200'],
            'slug_en' => ['nullable', 'string', 'max:220'],
            'excerpt_en' => ['nullable', 'string', 'max:500'],
            'content_en' => ['nullable', 'required_with:title_en', 'string', 'max:200000'],
            'meta_title_en' => ['nullable', 'string', 'max:70'],
            'meta_description_en' => ['nullable', 'string', 'max:170'],
        ];
    }
}
