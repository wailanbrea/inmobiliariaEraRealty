<?php

namespace App\Support\Concerns;

/**
 * Avisos para componentes Livewire.
 *
 * NO se usa session()->flash(): Livewire re-renderiza en el sitio, sin recargar
 * la pagina, asi que un mensaje en flash se quedaria en la sesion y aparecena
 * pegado en la SIGUIENTE navegacion completa, fuera de contexto.
 * Con propiedades del componente el aviso vive y muere con la interaccion.
 */
trait NotifiesInline
{
    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function notify(string $message): void
    {
        $this->successMessage = $message;
        $this->errorMessage = null;
    }

    public function notifyError(string $message): void
    {
        $this->errorMessage = $message;
        $this->successMessage = null;
    }

    public function clearNotifications(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }
}
