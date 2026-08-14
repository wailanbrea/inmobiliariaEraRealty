<?php

namespace App\Modules\News\Models;

use App\Support\Concerns\TranslatesJsonFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsCategory extends Model
{
    use TranslatesJsonFields;

    protected array $translatable = ['name', 'description'];

    protected $fillable = ['name', 'slug', 'description', 'color', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(NewsPost::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('slug');
    }
}
