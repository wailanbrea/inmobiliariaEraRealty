<?php

namespace App\Modules\Audit\Services;

use App\Enums\AuditAction;
use App\Modules\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * Campos que NUNCA se copian al registro.
     *
     * Esta es la parte delicada de todo el modulo. El registro de auditoria
     * lo puede leer cualquier administrador, y guarda los valores "antes" y
     * "despues" de cada cambio. Sin esta lista, cambiar la contrasena del
     * SMTP desde el panel dejaria la contrasena anterior Y la nueva escritas
     * en claro en una tabla consultable — un agujero peor que el problema que
     * la auditoria venia a resolver.
     *
     * La comparacion es por fragmento y sin distinguir mayusculas, para que
     * cubra tambien nombres nuevos que nadie se acuerde de anadir aqui:
     * 'mail_password', 'smtp_secret', 'api_token'...
     *
     * @var list<string>
     */
    private const REDACTED = [
        'password', 'secret', 'token', 'api_key', 'apikey',
        'remember_token', 'credential', 'private_key', 'signature',
    ];

    /**
     * Marcador que sustituye a un valor censurado. Se guarda algo en vez de
     * omitir la clave para que el diff siga diciendo "esto cambio", que es
     * justo el dato que interesa auditar.
     */
    public const MASK = '••••••••';

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public function log(
        AuditAction $action,
        ?Model $entity = null,
        ?array $old = null,
        ?array $new = null,
        ?string $label = null,
    ): ?AuditLog {
        // La auditoria no puede tumbar la accion que esta auditando. Si el
        // registro falla —tabla bloqueada, disco lleno— se anota en el log de
        // la aplicacion y la peticion del usuario sigue su curso.
        try {
            $user = Auth::user();
            $request = request();

            return AuditLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'action' => $action->value,
                'entity_type' => $entity ? $entity::class : null,
                'entity_id' => $entity?->getKey(),
                'entity_label' => Str::limit($label ?? $this->labelFor($entity), 197),
                'old_values' => $old ? $this->redact($old) : null,
                'new_values' => $new ? $this->redact($new) : null,
                'ip_address' => $request?->ip(),
                'user_agent' => Str::limit((string) $request?->userAgent(), 252),
            ]);
        } catch (\Throwable $e) {
            Log::error('No se pudo escribir en la auditoria', [
                'action' => $action->value,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Registra el cambio de un modelo guardando SOLO lo que de verdad cambio.
     *
     * Copiar el modelo entero convertiria cada edicion menor en un muro de
     * cincuenta campos identicos donde el cambio real pasa desapercibido.
     */
    public function logModelChange(AuditAction $action, Model $model, ?string $label = null): ?AuditLog
    {
        $cambios = $model->getChanges();

        // Las marcas de tiempo cambian en cada guardado y no dicen nada.
        unset($cambios['updated_at'], $cambios['created_at']);

        if ($cambios === []) {
            return null;
        }

        $antes = [];

        foreach (array_keys($cambios) as $campo) {
            $antes[$campo] = $model->getOriginal($campo);
        }

        return $this->log($action, $model, $antes, $cambios, $label);
    }

    /**
     * Sustituye por el marcador todo valor cuya clave suene a credencial.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function redact(array $values): array
    {
        foreach ($values as $clave => $valor) {
            if ($this->isSensitive((string) $clave)) {
                $values[$clave] = self::MASK;

                continue;
            }

            // Los ajustes llegan anidados ('mail' => ['password' => ...]),
            // asi que hay que bajar por el arbol o la censura se saltaria.
            if (is_array($valor)) {
                $values[$clave] = $this->redact($valor);
            }
        }

        return $values;
    }

    public function isSensitive(string $key): bool
    {
        $clave = strtolower($key);

        foreach (self::REDACTED as $fragmento) {
            if (str_contains($clave, $fragmento)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Etiqueta legible de la entidad, probando los nombres habituales.
     */
    private function labelFor(?Model $entity): ?string
    {
        if (! $entity) {
            return null;
        }

        foreach (['title', 'name', 'key', 'original_name'] as $campo) {
            $valor = $entity->getAttribute($campo);

            if (is_string($valor) && $valor !== '') {
                return $valor;
            }
        }

        return class_basename($entity).' #'.$entity->getKey();
    }
}
