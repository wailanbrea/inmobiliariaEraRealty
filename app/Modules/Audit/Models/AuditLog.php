<?php

namespace App\Modules\Audit\Models;

use App\Enums\AuditAction;
use App\Models\User;
use App\Modules\Audit\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una entrada del registro de auditoria.
 *
 * NO tiene updated_at ni metodos de edicion a proposito: un registro de
 * auditoria que se puede modificar no es un registro de auditoria.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'user_name', 'action', 'entity_type', 'entity_id',
        'entity_label', 'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Nombre a mostrar: el del usuario si aun existe, y si no la copia que se
     * guardo en el momento del hecho.
     */
    public function authorName(): string
    {
        return $this->user?->name
            ?? $this->user_name
            ?? __('admin/audit.system');
    }

    /**
     * Campos que cambiaron de verdad, emparejando antes y despues.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function diff(): array
    {
        $antes = $this->old_values ?? [];
        $despues = $this->new_values ?? [];

        $claves = array_unique([...array_keys($antes), ...array_keys($despues)]);
        sort($claves);

        $diferencias = [];

        foreach ($claves as $clave) {
            $viejo = $antes[$clave] ?? null;
            $nuevo = $despues[$clave] ?? null;

            // Un valor censurado se muestra SIEMPRE, aunque los dos lados
            // lleven el mismo marcador.
            //
            // Al ocultar la contrasena vieja y la nueva con el mismo texto,
            // la comparacion las ve iguales y el detalle diria "sin cambios"
            // justo en el apunte que registra que alguien cambio el SMTP —
            // exactamente el caso que la auditoria existe para vigilar.
            $censurado = $viejo === AuditService::MASK || $nuevo === AuditService::MASK;

            if ($censurado || $viejo !== $nuevo) {
                $diferencias[$clave] = ['old' => $viejo, 'new' => $nuevo];
            }
        }

        return $diferencias;
    }

    // ------------------------------------------------------------------
    // Filtros del listado
    // ------------------------------------------------------------------

    public function scopeAction(Builder $query, ?string $action): Builder
    {
        return $query->when($action, fn (Builder $q) => $q->where('action', $action));
    }

    public function scopeByUser(Builder $query, ?int $userId): Builder
    {
        return $query->when($userId, fn (Builder $q) => $q->where('user_id', $userId));
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('created_at', '<=', $to));
    }

    public function scopeForEntity(Builder $query, string $type, int $id): Builder
    {
        return $query->where('entity_type', $type)->where('entity_id', $id);
    }
}
