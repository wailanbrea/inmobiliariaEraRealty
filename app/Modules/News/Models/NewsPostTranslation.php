<?php

namespace App\Modules\News\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsPostTranslation extends Model
{
    protected $fillable = [
        'news_post_id', 'locale', 'title', 'slug', 'excerpt', 'content',
        'meta_title', 'meta_description',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(NewsPost::class, 'news_post_id');
    }
}
