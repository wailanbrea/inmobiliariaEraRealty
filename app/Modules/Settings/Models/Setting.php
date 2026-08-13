<?php

namespace App\Modules\Settings\Models;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Configuracion del sitio en formato clave/valor tipado.
 *
 * No se lee directamente desde las vistas: se usa SettingsService, que cachea
 * el conjunto completo y evita una consulta por cada dato del footer.
 */
class Setting extends Model
{
    protected $fillable = [
        'key', 'value', 'type', 'group',
        'is_public', 'is_translatable', 'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_translatable' => 'boolean',
            'is_encrypted' => 'boolean',
        ];
    }

    /**
     * Valor listo para usar: descifrado, con el tipo aplicado y, si es
     * traducible, resuelto al idioma pedido.
     */
    public function resolvedValue(?string $locale = null): mixed
    {
        $raw = $this->decrypted();

        if ($raw === null) {
            return null;
        }

        if ($this->is_translatable) {
            $raw = $this->pickTranslation($raw, $locale);
        }

        return $this->castValue($raw);
    }

    /**
     * Valor sin resolver el idioma. Lo usa el panel, que necesita ver y editar
     * los dos idiomas a la vez.
     */
    public function decrypted(): ?string
    {
        if ($this->value === null) {
            return null;
        }

        if (! $this->is_encrypted) {
            return $this->value;
        }

        try {
            return Crypt::decryptString($this->value);
        } catch (\Throwable) {
            // Si cambio la APP_KEY, el valor es irrecuperable. Se devuelve null
            // en lugar de reventar: el sitio sigue en pie y el panel avisa.
            return null;
        }
    }

    /**
     * Idioma pedido, con respaldo al idioma por defecto y, si tampoco lo hay,
     * al primer valor no vacio. Nunca devuelve un hueco por una traduccion
     * que falta. Ver docs/15_I18N.md seccion 3.3.
     */
    private function pickTranslation(string $raw, ?string $locale): ?string
    {
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return $raw;   // guardado antes de marcarlo traducible
        }

        $locale ??= Locale::current();

        foreach ([$locale, Locale::default()] as $candidate) {
            if (filled($decoded[$candidate] ?? null)) {
                return $decoded[$candidate];
            }
        }

        foreach ($decoded as $value) {
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    private function castValue(?string $raw): mixed
    {
        if ($raw === null) {
            return null;
        }

        return match ($this->type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $raw,
            'decimal' => (float) $raw,
            'json' => json_decode($raw, true),
            default => $raw,
        };
    }
}
