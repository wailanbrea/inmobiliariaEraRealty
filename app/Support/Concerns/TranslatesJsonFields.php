<?php

namespace App\Support\Concerns;

use App\Support\Locale;

/**
 * Campos traducibles guardados como JSON en la propia tabla.
 *
 * Para catalogos y textos cortos sin slug ni busqueda: tipos de propiedad,
 * amenidades, cargo y biografia de agentes. Las entidades que si se buscan y
 * posicionan (propiedades, noticias) usan tablas *_translations.
 * Ver docs/15_I18N.md seccion 3.
 *
 * Uso:
 *   protected array $translatable = ['name'];
 *   $tipo->name          -> texto en el idioma activo
 *   $tipo->getTranslation('name', 'en')
 */
trait TranslatesJsonFields
{
    public function initializeTranslatesJsonFields(): void
    {
        foreach ($this->translatable ?? [] as $field) {
            $this->casts[$field] = 'array';
        }
    }

    /**
     * Devuelve el texto del idioma pedido, con respaldo al idioma por defecto
     * y luego al primer valor no vacio. Nunca deja un hueco por una traduccion
     * que falta.
     */
    public function getTranslation(string $field, ?string $locale = null): ?string
    {
        $raw = $this->getAttributeValue($field);

        if (! is_array($raw)) {
            return $raw === null ? null : (string) $raw;
        }

        $locale ??= Locale::current();

        foreach ([$locale, Locale::default()] as $candidate) {
            if (filled($raw[$candidate] ?? null)) {
                return $raw[$candidate];
            }
        }

        foreach ($raw as $value) {
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    /** ¿Existe traduccion propia en ese idioma? */
    public function hasTranslation(string $field, string $locale): bool
    {
        $raw = $this->getAttributeValue($field);

        return is_array($raw) && filled($raw[$locale] ?? null);
    }

    public function isTranslatableField(string $field): bool
    {
        return in_array($field, $this->translatable ?? [], true);
    }

    /**
     * Al leer un campo traducible se devuelve ya el texto del idioma activo,
     * para que las vistas escriban {{ $tipo->name }} sin ceremonia.
     *
     * Se respeta cualquier accessor propio del modelo: si existe, manda el.
     */
    public function getAttribute($key)
    {
        if (
            $this->isTranslatableField($key)
            && ! $this->hasAttributeGetMutator($key)
            && ! method_exists($this, 'get'.str($key)->studly().'Attribute')
        ) {
            return $this->getTranslation($key);
        }

        return parent::getAttribute($key);
    }
}
