<?php

namespace App\Support\Concerns;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Slug unico generado desde un campo origen.
 *
 * El slug NO se regenera al cambiar el titulo de una entidad ya publicada:
 * cambiar la URL de una ficha indexada tira su posicionamiento y rompe los
 * enlaces compartidos por WhatsApp. El panel ofrece cambiarlo a mano, con
 * aviso. Ver docs/07_SEO.md seccion 4.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function (Model $model) {
            $slugField = $model->slugField();
            $sourceValue = $model->slugSourceValue();

            if (blank($model->{$slugField}) && filled($sourceValue)) {
                $model->{$slugField} = static::uniqueSlug(
                    $sourceValue,
                    $model->getKey(),
                );
            } elseif (filled($model->{$slugField}) && $model->isDirty($slugField)) {
                // Si el administrador lo escribe a mano, igualmente se
                // normaliza y se garantiza que sea unico.
                $model->{$slugField} = static::uniqueSlug(
                    $model->{$slugField},
                    $model->getKey(),
                );
            }
        });
    }

    public function slugSource(): string
    {
        return 'name';
    }

    /**
     * Texto del que se deriva el slug.
     *
     * En los catalogos traducibles se toma siempre el espanol: es el idioma
     * por defecto y el que fija la URL. Si se tomara el idioma activo, el
     * slug dependeria de quien estuviera guardando.
     */
    public function slugSourceValue(): ?string
    {
        $source = $this->slugSource();

        if (method_exists($this, 'getTranslation') && $this->isTranslatableField($source)) {
            return $this->getTranslation($source, Locale::default());
        }

        return $this->{$source};
    }

    public function slugField(): string
    {
        return 'slug';
    }

    /**
     * Anade -2, -3... hasta encontrar uno libre.
     */
    public static function uniqueSlug(string $value, mixed $ignoreId = null): string
    {
        $model = new static;
        $field = $model->slugField();

        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $i = 2;

        while (
            static::query()
                ->where($field, $slug)
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                ->when(
                    in_array(SoftDeletes::class, class_uses_recursive(static::class), true),
                    fn ($q) => $q->withTrashed(),
                )
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return $this->slugField();
    }
}
