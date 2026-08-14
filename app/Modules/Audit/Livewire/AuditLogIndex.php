<?php

namespace App\Modules\Audit\Livewire;

use App\Enums\AuditAction;
use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Registro de auditoria.
 *
 * Solo lectura: no hay editar ni borrar. Un registro de auditoria que se
 * puede modificar desde la misma interfaz que audita no vale para nada.
 * La unica forma de que salgan filas es el comando de poda, que se ejecuta
 * desde consola y tiene su propio periodo de retencion.
 */
class AuditLogIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $action = '';

    #[Url(except: '')]
    public string $user = '';

    #[Url(as: 'desde', except: '')]
    public string $from = '';

    #[Url(as: 'hasta', except: '')]
    public string $to = '';

    /** Apunte abierto en el panel de detalle. */
    public ?int $viewing = null;

    public function updated(string $campo): void
    {
        // Cualquier filtro devuelve a la primera pagina: si no, filtrar
        // estando en la pagina 7 deja un listado aparentemente vacio.
        if (in_array($campo, ['action', 'user', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['action', 'user', 'from', 'to']);
        $this->resetPage();
    }

    public function view(int $id): void
    {
        $this->viewing = $id;
    }

    public function closeDetail(): void
    {
        $this->viewing = null;
    }

    public function getDetailProperty(): ?AuditLog
    {
        return $this->viewing ? AuditLog::with('user')->find($this->viewing) : null;
    }

    /**
     * Usuarios que aparecen en el registro, para el desplegable.
     *
     * Se listan los que TIENEN apuntes, no todos los del sistema: un filtro
     * con opciones que no devuelven nada solo hace perder el tiempo.
     */
    public function getAuthorsProperty(): Collection
    {
        return User::query()
            ->whereIn('id', AuditLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function render(): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->action($this->action ?: null)
            ->byUser($this->user ? (int) $this->user : null)
            ->between($this->from ?: null, $this->to ?: null)
            ->latest('created_at')
            ->latest('id')
            ->paginate(30);

        return view('livewire.admin.audit-log-index', [
            'logs' => $logs,
            'acciones' => AuditAction::forFilter(),
        ]);
    }
}
