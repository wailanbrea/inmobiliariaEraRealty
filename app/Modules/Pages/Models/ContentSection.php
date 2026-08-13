<?php

namespace App\Modules\Pages\Models;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Bloque de contenido editable de una pagina.
 *
 * Se lee siempre a traves de section(), que cachea el conjunto: el inicio usa
 * seis bloques y no deben costar seis consultas.
 */
class ContentSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_key', 'section_key', 'image', 'button_url',
        'extra_json', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'extra_json' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ContentSectionTranslation::class);
    }

    public function translated(?string $locale = null): ?ContentSectionTranslation
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

    public function getSubtitleAttribute(): ?string
    {
        return $this->translated()?->subtitle;
    }

    public function getContentAttribute(): ?string
    {
        return $this->translated()?->content;
    }

    public function getButtonTextAttribute(): ?string
    {
        return $this->translated()?->button_text;
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::url($this->image) : null;
    }

    public function scopeForPage(Builder $query, string $pageKey): Builder
    {
        return $query->where('page_key', $pageKey)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Todas las secciones de una pagina, cacheadas e indexadas por clave.
     *
     * @return Collection<string, self>
     */
    public static function forPageCached(string $pageKey): Collection
    {
        return Cache::rememberForever(
            "content_sections.{$pageKey}",
            fn () => static::forPage($pageKey)->with('translations')->get()->keyBy('section_key')
        );
    }

    public static function flushCache(?string $pageKey = null): void
    {
        if ($pageKey) {
            Cache::forget("content_sections.{$pageKey}");

            return;
        }

        foreach (static::distinct()->pluck('page_key') as $clave) {
            Cache::forget("content_sections.{$clave}");
        }
    }

    protected static function booted(): void
    {
        static::saved(fn (self $s) => static::flushCache($s->page_key));
        static::deleted(fn (self $s) => static::flushCache($s->page_key));
    }
}
