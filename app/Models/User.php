<?php

namespace App\Models;

use App\Modules\Agents\Models\Agent;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'is_active',
        'must_change_password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Solo las cuentas activas pueden autenticarse.
     * Desactivar es reversible; borrar un usuario no lo es, asi que el panel
     * desactiva en lugar de borrar.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Ficha de agente asociada, si la tiene.
     * La usa PropertyPolicy para saber que propiedades le corresponden.
     */
    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class);
    }
}
