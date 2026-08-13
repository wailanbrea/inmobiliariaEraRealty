<?php

namespace App\Modules\Pages\Models;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Page extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'slug', 'featured_image', 'status', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }

    public function translated(?string $locale = null): ?PageTranslation
    {
        $locale ??= Locale::current();

        $cargadas = $this->relationLoaded('translations')
            ? $this->translations
            : $this->translations()->get();

        return $cargadas->firstWhere('locale', $locale)
            ?? $cargadas->firstWhere('locale', Locale::default())
            ?? $cargadas->first();
    }

    public function getTitleAttribute(): ?string
    {
        return $this->translated()?->title;
    }

    public function getContentAttribute(): ?string
    {
        return $this->translated()?->content;
    }

    public function featuredImageUrl(): ?string
    {
        return $this->featured_image ? Storage::url($this->featured_image) : null;
    }

    public static function byKey(string $key): ?self
    {
        return static::with('translations')->where('key', $key)->first();
    }
}
