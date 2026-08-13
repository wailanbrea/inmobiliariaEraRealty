<?php

namespace App\Modules\PropertyImages\Models;

use App\Modules\Properties\Models\Property;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PropertyImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id', 'path', 'thumbnail_path', 'webp_path',
        'original_name', 'alt_text', 'title',
        'sort_order', 'is_main', 'width', 'height', 'size', 'mime_type',
        'uploaded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'sort_order' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'size' => 'integer',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function url(): string
    {
        return Storage::url($this->path);
    }

    public function thumbnailUrl(): string
    {
        return Storage::url($this->thumbnail_path ?? $this->path);
    }

    public function webpUrl(): ?string
    {
        return $this->webp_path ? Storage::url($this->webp_path) : null;
    }

    /**
     * Texto alternativo con respaldo: si el administrador no lo escribio, se
     * usa el titulo de la propiedad. Una imagen sin alt penaliza el SEO y deja
     * fuera a quien usa lector de pantalla.
     */
    public function altText(): string
    {
        return $this->alt_text
            ?: ($this->title ?: (string) $this->property?->title);
    }

    /** Todos los ficheros de esta imagen, para borrarlos juntos. */
    public function allPaths(): array
    {
        return array_filter([$this->path, $this->thumbnail_path, $this->webp_path]);
    }
}
