<?php

namespace App\Modules\News\Services;

use App\Enums\NewsStatus;
use App\Modules\News\Models\NewsPost;
use App\Modules\News\Models\NewsPostTranslation;
use App\Support\Locale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class NewsService
{
    public function create(array $data): NewsPost
    {
        return DB::transaction(function () use ($data) {
            $post = NewsPost::create($this->commonData($data) + ['author_id' => auth()->id()]);
            $this->syncTranslations($post, $data);
            $this->refreshReadingTime($post);

            return $post->fresh(['translations', 'category', 'author']);
        });
    }

    public function update(NewsPost $post, array $data): NewsPost
    {
        return DB::transaction(function () use ($post, $data) {
            $post->update($this->commonData($data));
            $this->syncTranslations($post, $data);
            $this->refreshReadingTime($post);

            return $post->fresh(['translations', 'category', 'author']);
        });
    }

    private function commonData(array $data): array
    {
        $status = NewsStatus::from($data['status']);
        $publishedAt = filled($data['published_at'] ?? null) ? $data['published_at'] : null;
        if ($status === NewsStatus::Published && ! $publishedAt) {
            $publishedAt = now();
        }

        return [
            'category_id' => $data['category_id'] ?? null,
            'status' => $status,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'published_at' => $publishedAt,
            'featured_image' => $data['featured_image'] ?? null,
        ];
    }

    private function syncTranslations(NewsPost $post, array $data): void
    {
        foreach (Locale::codes() as $locale) {
            $title = $data["title_{$locale}"] ?? null;
            if (blank($title)) {
                $post->translations()->where('locale', $locale)->delete();

                continue;
            }

            $existing = $post->translations()->where('locale', $locale)->first();
            $slugInput = $data["slug_{$locale}"] ?? null;
            $slug = filled($slugInput)
                ? $this->uniqueSlug($slugInput, $locale, $existing?->id)
                : ($existing?->slug ?? $this->uniqueSlug($title, $locale));

            $post->translations()->updateOrCreate(['locale' => $locale], [
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $data["excerpt_{$locale}"] ?? null,
                'content' => Purifier::clean($data["content_{$locale}"] ?? ''),
                'meta_title' => $data["meta_title_{$locale}"] ?? null,
                'meta_description' => $data["meta_description_{$locale}"] ?? null,
            ]);
        }
    }

    private function uniqueSlug(string $value, string $locale, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $suffix = 2;
        while (NewsPostTranslation::where('locale', $locale)->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function refreshReadingTime(NewsPost $post): void
    {
        $words = $post->translations()->get()->max(fn ($translation) => str_word_count(strip_tags($translation->content))) ?: 0;
        $post->update(['reading_time' => min(65535, max(1, (int) ceil($words / 200)))]);
    }
}
