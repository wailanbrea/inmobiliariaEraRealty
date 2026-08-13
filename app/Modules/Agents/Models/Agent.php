<?php

namespace App\Modules\Agents\Models;

use App\Models\User;
use App\Modules\Properties\Models\Property;
use App\Modules\WhatsApp\Services\WhatsappService;
use App\Support\Concerns\TranslatesJsonFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Agent extends Model
{
    use HasFactory, TranslatesJsonFields;

    /** @var list<string> */
    protected array $translatable = ['position', 'bio'];

    protected $fillable = [
        'user_id', 'name', 'position', 'bio', 'photo',
        'phone', 'whatsapp', 'email',
        'social_instagram', 'social_linkedin',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? Storage::url($this->photo) : null;
    }

    /** Enlace de WhatsApp propio del agente, si tiene numero. */
    public function whatsappLink(?string $message = null): ?string
    {
        if (blank($this->whatsapp)) {
            return null;
        }

        return app(WhatsappService::class)->link($this->whatsapp, $message);
    }
}
