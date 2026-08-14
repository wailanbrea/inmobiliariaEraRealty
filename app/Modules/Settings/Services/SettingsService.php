<?php

namespace App\Modules\Settings\Services;

use App\Enums\AuditAction;
use App\Modules\Settings\Models\Setting;
use App\Support\Locale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Acceso cacheado a la configuracion del sitio.
 *
 * El footer solo ya necesita una docena de claves; sin cache serian una docena
 * de consultas en cada peticion. Se cachea la coleccion entera y se invalida
 * al guardar.
 */
class SettingsService
{
    private const CACHE_KEY = 'settings.all';

    /** @var Collection<string, Setting>|null */
    private ?Collection $loaded = null;

    /**
     * @return Collection<string, Setting>
     */
    public function all(): Collection
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        $this->loaded = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => Setting::all()->keyBy('key')
        );

        return $this->loaded;
    }

    public function get(string $key, mixed $default = null, ?string $locale = null): mixed
    {
        $setting = $this->all()->get($key);

        if ($setting === null) {
            return $default;
        }

        return $setting->resolvedValue($locale) ?? $default;
    }

    /**
     * Valor crudo para los formularios del panel: sin resolver idioma, pero ya
     * descifrado.
     */
    public function raw(string $key): ?string
    {
        return $this->all()->get($key)?->decrypted();
    }

    /**
     * Traducciones de una clave traducible, indexadas por idioma.
     *
     * @return array<string, string|null>
     */
    public function translations(string $key): array
    {
        $raw = $this->raw($key);
        $decoded = json_decode((string) $raw, true);

        $out = [];

        foreach (Locale::codes() as $code) {
            $out[$code] = is_array($decoded) ? ($decoded[$code] ?? null) : null;
        }

        return $out;
    }

    public function has(string $key): bool
    {
        return $this->all()->has($key);
    }

    /**
     * Guarda una clave. Si la clave no existe, no se crea: el catalogo lo
     * define el seeder. Asi un typo en un formulario no ensucia la tabla con
     * claves fantasma.
     */
    public function set(string $key, mixed $value): bool
    {
        $setting = $this->all()->get($key);

        if ($setting === null) {
            return false;
        }

        $original = $setting->value;
        $setting->value = $this->prepare($setting, $value);
        $cambio = $setting->isDirty('value');

        $setting->save();

        $this->flush();

        if ($cambio) {
            $this->auditar([$key => $original], [$key => $setting->value]);
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        $antes = [];
        $despues = [];

        foreach ($values as $key => $value) {
            $setting = $this->all()->get($key);

            if ($setting === null) {
                continue;
            }

            $original = $setting->value;
            $setting->value = $this->prepare($setting, $value);

            // Solo se anota lo que cambio de verdad: guardar el formulario
            // sin tocar nada no debe ensuciar la auditoria.
            if ($setting->isDirty('value')) {
                $antes[$key] = $original;
                $despues[$key] = $setting->value;
            }

            $setting->save();
        }

        $this->flush();

        if ($antes !== []) {
            $this->auditar($antes, $despues);
        }
    }

    /**
     * Registra el cambio de ajustes con la accion que le corresponde.
     *
     * Va aqui y no en el controlador para que quede constancia venga de donde
     * venga la escritura. La censura de credenciales la aplica AuditService:
     * 'mail_password' nunca llega escrito al registro.
     *
     * @param  array<string, mixed>  $antes
     * @param  array<string, mixed>  $despues
     */
    private function auditar(array $antes, array $despues): void
    {
        // Un guardado puede tocar claves de varios grupos, asi que se agrupan
        // y se emite un apunte por cada accion afectada. Asi el listado
        // distingue "cambio el WhatsApp" de "cambio el SMTP" sin tener que
        // abrir el detalle.
        $porAccion = [];

        foreach ($antes as $key => $valor) {
            $porAccion[$this->accionPara($key)->value][$key] = $valor;
        }

        foreach ($porAccion as $accion => $claves) {
            audit()->log(
                AuditAction::from($accion),
                old: $claves,
                new: array_intersect_key($despues, $claves),
                label: implode(', ', array_slice(array_keys($claves), 0, 5)),
            );
        }
    }

    private function accionPara(string $key): AuditAction
    {
        return match (true) {
            str_starts_with($key, 'mail_') => AuditAction::MailChanged,
            str_contains($key, 'whatsapp') => AuditAction::WhatsappChanged,
            in_array($key, ['site_logo', 'site_favicon'], true) => AuditAction::LogoChanged,
            default => AuditAction::SettingsChanged,
        };
    }

    /**
     * Solo las claves publicas, resueltas al idioma activo.
     * Es lo unico que llega a las vistas del sitio publico.
     *
     * @return array<string, mixed>
     */
    public function publicValues(?string $locale = null): array
    {
        $locale ??= Locale::current();

        return $this->all()
            ->filter(fn (Setting $s) => $s->is_public)
            ->mapWithKeys(fn (Setting $s) => [$s->key => $s->resolvedValue($locale)])
            ->all();
    }

    /**
     * @return Collection<string, Setting>
     */
    public function group(string $group): Collection
    {
        return $this->all()->filter(fn (Setting $s) => $s->group === $group);
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }

    /**
     * Normaliza el valor antes de guardarlo: booleanos a '1'/'0', arrays a
     * JSON, y cifrado si la clave lo pide.
     */
    private function prepare(Setting $setting, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $prepared = match (true) {
            $setting->type === 'boolean' => $value ? '1' : '0',
            is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };

        return $setting->is_encrypted
            ? Crypt::encryptString($prepared)
            : $prepared;
    }
}
