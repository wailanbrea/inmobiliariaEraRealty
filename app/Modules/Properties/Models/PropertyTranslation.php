<?php

namespace App\Modules\Properties\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Textos de una propiedad en un idioma.
 * Ver docs/15_I18N.md seccion 3.1.
 */
class PropertyTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id', 'locale', 'title', 'slug',
        'short_description', 'description',
        'meta_title', 'meta_description',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
