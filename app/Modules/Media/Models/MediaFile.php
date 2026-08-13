<?php

namespace App\Modules\Media\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'disk', 'path', 'thumbnail_path', 'webp_path',
        'original_name', 'mime_type', 'size', 'width', 'height',
        'alt_text', 'title', 'context', 'folder', 'uploaded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function thumbnailUrl(): string
    {
        return Storage::disk($this->disk)->url($this->thumbnail_path ?? $this->path);
    }

    public function webpUrl(): ?string
    {
        return $this->webp_path ? Storage::disk($this->disk)->url($this->webp_path) : null;
    }

    /** @return list<string> */
    public function allPaths(): array
    {
        return array_filter([$this->path, $this->thumbnail_path, $this->webp_path]);
    }

    public function humanSize(): string
    {
        return $this->size > 1048576
            ? round($this->size / 1048576, 1).' MB'
            : round($this->size / 1024).' KB';
    }

    public function scopeContext(Builder $query, ?string $context): Builder
    {
        return $context ? $query->where('context', $context) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('original_name', 'like', "%{$term}%")
                ->orWhere('alt_text', 'like', "%{$term}%")
                ->orWhere('title', 'like', "%{$term}%");
        });
    }
}
