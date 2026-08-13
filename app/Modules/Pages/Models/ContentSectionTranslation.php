<?php

namespace App\Modules\Pages\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentSectionTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_section_id', 'locale', 'title', 'subtitle', 'content', 'button_text',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(ContentSection::class, 'content_section_id');
    }
}
