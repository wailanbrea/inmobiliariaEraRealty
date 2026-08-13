<?php

namespace App\Modules\Properties\Models;

use App\Enums\Currency;
use App\Enums\OperationType;
use App\Enums\PricePeriod;
use App\Enums\PropertyStatus;
use App\Models\User;
use App\Modules\Agents\Models\Agent;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Province;
use App\Modules\Locations\Models\Sector;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Una propiedad es UNA, con dos textos.
 *
 * Los datos comunes (precio, ubicacion, habitaciones) viven aqui; los textos
 * en property_translations. No se duplica nada mas.
 */
class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_code', 'operation_type', 'property_type_id', 'status',
        'price', 'currency', 'price_period', 'maintenance_fee',
        'province_id', 'city_id', 'sector_id', 'address',
        'show_exact_location', 'latitude', 'longitude',
        'bedrooms', 'bathrooms', 'parking_spaces',
        'construction_area', 'land_area', 'floor_level', 'year_built', 'is_furnished',
        'is_featured', 'is_investment', 'is_project',
        'features_json', 'video_url', 'virtual_tour_url',
        'agent_id', 'owner_name', 'owner_phone', 'owner_email', 'internal_notes',
        'og_image', 'published_at',
        'created_by_user_id', 'updated_by_user_id',
    ];

    /** Datos que nunca deben salir a una vista publica ni a una API. */
    protected $hidden = [
        'owner_name', 'owner_phone', 'owner_email', 'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'operation_type' => OperationType::class,
            'status' => PropertyStatus::class,
            'currency' => Currency::class,
            'price_period' => PricePeriod::class,
            'price' => 'decimal:2',
            'maintenance_fee' => 'decimal:2',
            'bathrooms' => 'decimal:1',
            'construction_area' => 'decimal:2',
            'land_area' => 'decimal:2',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'show_exact_location' => 'boolean',
            'is_furnished' => 'boolean',
            'is_featured' => 'boolean',
            'is_investment' => 'boolean',
            'is_project' => 'boolean',
            'features_json' => 'array',
            'published_at' => 'datetime',
        ];
    }

    // ------------------------------------------------------------------
    // Relaciones
    // ------------------------------------------------------------------

    public function translations(): HasMany
    {
        return $this->hasMany(PropertyTranslation::class);
    }

    public function translation(?string $locale = null): HasOne
    {
        return $this->hasOne(PropertyTranslation::class)
            ->where('locale', $locale ?? Locale::current());
    }

    /** Traduccion del idioma por defecto, para el respaldo. */
    public function defaultTranslation(): HasOne
    {
        return $this->hasOne(PropertyTranslation::class)
            ->where('locale', Locale::default());
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // ------------------------------------------------------------------
    // Textos traducidos
    // ------------------------------------------------------------------

    /**
     * Devuelve la traduccion del idioma pedido, con respaldo al idioma por
     * defecto. Nunca deja una ficha en blanco por una traduccion que falta.
     */
    public function translated(?string $locale = null): ?PropertyTranslation
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

    public function getSlugAttribute(): ?string
    {
        return $this->translated()?->slug;
    }

    public function getShortDescriptionAttribute(): ?string
    {
        return $this->translated()?->short_description;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->translated()?->description;
    }

    /**
     * Slug en otro idioma. Lo usa el selector de idioma para llevar al
     * visitante a la MISMA propiedad, no a la portada.
     * Ver App\Support\Locale::alternateUrl().
     */
    public function translatedSlug(string $locale): ?string
    {
        $traduccion = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();

        return $traduccion?->slug;
    }

    public function hasTranslation(string $locale): bool
    {
        return $this->translatedSlug($locale) !== null;
    }

    // ------------------------------------------------------------------
    // Precio
    // ------------------------------------------------------------------

    public function formattedPrice(): string
    {
        if ($this->price === null) {
            return __('property.price_on_request');
        }

        $precio = $this->currency->format($this->price);

        return $this->price_period
            ? $precio.' '.$this->price_period->suffix()
            : $precio;
    }

    /** Precio convertido a la otra moneda, o null si no hay tasa. */
    public function priceInOtherCurrency(): ?string
    {
        if ($this->price === null) {
            return null;
        }

        $otra = $this->currency === Currency::USD ? Currency::DOP : Currency::USD;
        $convertido = $this->currency->convertTo($otra, (float) $this->price);

        return $convertido === null ? null : $otra->format($convertido);
    }

    // ------------------------------------------------------------------
    // Ubicacion
    // ------------------------------------------------------------------

    /** "Piantini, Santo Domingo" — como en el diseno. */
    public function locationLabel(): string
    {
        return collect([
            $this->sector?->name,
            $this->city?->name,
            $this->province?->name,
        ])->filter()->take(2)->implode(', ');
    }

    /** Solo se exponen coordenadas si el administrador lo autorizo. */
    public function publicCoordinates(): ?array
    {
        if (! $this->show_exact_location || $this->latitude === null || $this->longitude === null) {
            return null;
        }

        return ['lat' => (float) $this->latitude, 'lng' => (float) $this->longitude];
    }

    // ------------------------------------------------------------------
    // Estado
    // ------------------------------------------------------------------

    public function isPublished(): bool
    {
        return $this->status->isPublic()
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    /** Lo que se ve en el sitio publico. */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->whereIn('status', array_column(PropertyStatus::publicCases(), 'value'))
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInvestment(Builder $query): Builder
    {
        return $query->where('is_investment', true);
    }

    /**
     * Evita el N+1 en cualquier listado. Se usa siempre que se pinten
     * tarjetas de propiedad.
     */
    public function scopeForListing(Builder $query): Builder
    {
        return $query->with(['translations', 'type', 'city', 'sector', 'province']);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Resolucion por slug del idioma activo, con respaldo al otro idioma.
     * Asi un enlace antiguo o cruzado sigue encontrando la propiedad.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return static::query()
            ->whereHas('translations', fn (Builder $q) => $q->where('slug', $value))
            ->forListing()
            ->first();
    }
}
