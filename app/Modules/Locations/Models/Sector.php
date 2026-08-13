<?php

namespace App\Modules\Locations\Models;

use App\Modules\Properties\Models\Property;
use App\Support\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Sector extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = ['city_id', 'name', 'slug', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('name');
    }

    /** Unico dentro de su ciudad, no globalmente. */
    public static function uniqueSlug(string $value, mixed $ignoreId = null): string
    {
        return Str::slug($value);
    }
}
