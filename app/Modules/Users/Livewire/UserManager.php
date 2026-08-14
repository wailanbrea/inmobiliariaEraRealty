<?php

namespace App\Modules\Users\Livewire;

use App\Enums\AuditAction;
use App\Models\User;
use App\Modules\Users\Services\UserGuard;
use App\Support\Concerns\NotifiesInline;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Gestion de usuarios del panel.
 *
 * Existe sobre todo para que el acceso no dependa del correo: un
 * super_admin puede crear cuentas y restablecer contrasenas sin que salga un
 * solo mensaje del servidor. Ver docs/03_ADMIN_PANEL.md.
 */
class UserManager extends Component
{
    use NotifiesInline;

    public ?int $editing = null;

    public bool $showForm = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'editor';

    /** Contrasena recien generada, que se muestra UNA vez. */
    public ?string $generatedPassword = null;

    public ?string $generatedFor = null;

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($this->editing),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(UserGuard::ROLES)],
        ];
    }

    /* ------------------------------------------------------------------
       Alta y edicion
       ------------------------------------------------------------------ */

    public function create(): void
    {
        $this->reset(['editing', 'name', 'email', 'phone', 'generatedPassword', 'generatedFor']);
        $this->role = 'editor';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);

        $this->editing = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->role = $user->getRoleNames()->first() ?? 'editor';

        $this->reset(['generatedPassword', 'generatedFor']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->reset(['editing', 'name', 'email', 'phone', 'showForm']);
        $this->resetValidation();
    }

    public function save(UserGuard $guard): void
    {
        $datos = $this->validate();

        if ($this->editing) {
            $user = User::findOrFail($this->editing);

            if ($motivo = $guard->cannotChangeRole($user, $this->role, auth()->user())) {
                $this->addError('role', $motivo);

                return;
            }

            $user->update([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'phone' => $datos['phone'] ?: null,
            ]);

            $user->syncRoles([$this->role]);

            audit()->log(AuditAction::SettingsChanged, $user,
                new: ['role' => $this->role], label: 'Usuario: '.$user->name);

            $this->notify(__('admin/users.saved'));
        } else {
            // Se genera una contrasena y se muestra al administrador para que
            // se la pase por el canal que quiera. Asi el alta funciona sin
            // correo, que es el motivo de que esta pantalla exista.
            $clave = $this->newPassword();

            $user = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'phone' => $datos['phone'] ?: null,
                'password' => $clave,
                'is_active' => true,
                'must_change_password' => true,
            ]);

            $user->assignRole($this->role);

            audit()->log(AuditAction::SettingsChanged, $user,
                new: ['role' => $this->role], label: 'Alta de usuario: '.$user->name);

            $this->generatedPassword = $clave;
            $this->generatedFor = $user->name;
            $this->notify(__('admin/users.created'));
        }

        $this->cancel();
    }

    /* ------------------------------------------------------------------
       Acceso
       ------------------------------------------------------------------ */

    public function resetPassword(int $id): void
    {
        $user = User::findOrFail($id);

        $clave = $this->newPassword();

        $user->forceFill([
            // El cast 'hashed' del modelo se encarga; pasar la clave en claro
            // es correcto y evita el riesgo de hashear dos veces.
            'password' => $clave,
            // Obligatorio: si no, quien restablece conoce la contrasena del
            // otro indefinidamente. Con esto, la clave generada solo sirve
            // para entrar una vez y cambiarla.
            'must_change_password' => true,
            'remember_token' => Str::random(60),   // invalida sesiones «recordadas»
        ])->save();

        audit()->log(AuditAction::SettingsChanged, $user,
            new: ['password' => '(restablecida)'], label: 'Contraseña restablecida: '.$user->name);

        $this->generatedPassword = $clave;
        $this->generatedFor = $user->name;
    }

    public function dismissPassword(): void
    {
        $this->reset(['generatedPassword', 'generatedFor']);
    }

    public function toggleActive(int $id, UserGuard $guard): void
    {
        $user = User::findOrFail($id);

        if ($user->is_active && ($motivo = $guard->cannotDeactivate($user, auth()->user()))) {
            $this->notifyError($motivo);

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);

        $this->notify($user->is_active ? __('admin/users.activated') : __('admin/users.deactivated'));
    }

    /* ------------------------------------------------------------------
       Borrado
       ------------------------------------------------------------------ */

    public function confirmDelete(int $id, UserGuard $guard): void
    {
        $user = User::findOrFail($id);

        if ($motivo = $guard->cannotDelete($user, auth()->user())) {
            $this->notifyError($motivo);

            return;
        }

        $this->confirmingDelete = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = null;
    }

    public function delete(UserGuard $guard): void
    {
        $user = User::findOrFail($this->confirmingDelete);

        // Se vuelve a comprobar: entre abrir el modal y confirmar puede haber
        // cambiado el estado de otro usuario en otra pestana.
        if ($motivo = $guard->cannotDelete($user, auth()->user())) {
            $this->notifyError($motivo);
            $this->confirmingDelete = null;

            return;
        }

        $nombre = $user->name;
        $user->delete();

        audit()->log(AuditAction::SettingsChanged, label: 'Usuario eliminado: '.$nombre);

        $this->confirmingDelete = null;
        $this->notify(__('admin/users.deleted'));
    }

    /**
     * Contrasena legible pero no adivinable: cuatro grupos de cuatro
     * caracteres sin los que se confunden al dictarla por telefono (0/O, 1/l).
     */
    private function newPassword(): string
    {
        $alfabeto = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $grupos = [];

        foreach (range(1, 4) as $g) {
            $grupo = '';
            foreach (range(1, 4) as $c) {
                $grupo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
            $grupos[] = $grupo;
        }

        return implode('-', $grupos);
    }

    public function render(): View
    {
        return view('livewire.admin.user-manager', [
            'usuarios' => User::with('roles')->orderBy('name')->get(),
            'roles' => UserGuard::ROLES,
        ]);
    }
}
