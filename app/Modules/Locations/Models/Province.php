<?php

namespace App\Modules\Locations\Models;

use App\Modules\Properties\Models\Property;
use App\Support\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = ['name', 'slug', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class)->orderBy('sort_order')->orderBy('name');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('name');
    }
}
