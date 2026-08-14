<?php

namespace App\Modules\News\Models;

use App\Enums\NewsStatus;
use App\Models\User;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'author_id', 'featured_image', 'status', 'is_featured',
        'reading_time', 'views_count', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => NewsStatus::class,
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(NewsPostTranslation::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function translated(?string $locale = null): ?NewsPostTranslation
    {
        $locale ??= Locale::current();
        $translations = $this->relationLoaded('translations') ? $this->translations : $this->translations()->get();

        return $translations->firstWhere('locale', $locale)
            ?? $translations->firstWhere('locale', Locale::default())
            ?? $translations->first();
    }

    public function getTitleAttribute(): ?string
    {
        return $this->translated()?->title;
    }

    public function getSlugAttribute(): ?string
    {
        return $this->translated()?->slug;
    }

    public function getExcerptAttribute(): ?string
    {
        return $this->translated()?->excerpt;
    }

    public function getContentAttribute(): ?string
    {
        return $this->translated()?->content;
    }

    public function translatedSlug(string $locale): ?string
    {
        return ($this->relationLoaded('translations') ? $this->translations : $this->translations()->get())
            ->firstWhere('locale', $locale)?->slug;
    }

    public function isPublished(): bool
    {
        return in_array($this->status, [NewsStatus::Published, NewsStatus::Scheduled], true)
            && $this->published_at?->isPast();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', [NewsStatus::Published, NewsStatus::Scheduled])
            ->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
