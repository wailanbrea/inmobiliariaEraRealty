<?php

namespace App\Modules\Audit\Observers;

use App\Enums\AuditAction;
use App\Enums\NewsStatus;
use App\Modules\Audit\Services\AuditService;
use App\Modules\News\Models\NewsPost;

/**
 * Auditoria de noticias.
 *
 * El prompt maestro pide registrar la PUBLICACION y el borrado, no cada
 * guardado de un borrador. Un editor puede guardar veinte veces mientras
 * redacta; lo que importa auditar es el momento en que ese texto se hizo
 * visible para el publico.
 */
class NewsPostObserver
{
    public function __construct(private AuditService $audit) {}

    public function created(NewsPost $post): void
    {
        if ($post->status === NewsStatus::Published) {
            $this->log($post, ['status' => null]);
        }
    }

    public function updated(NewsPost $post): void
    {
        $cambios = $post->getChanges();

        if (! array_key_exists('status', $cambios)) {
            return;
        }

        // Solo la transicion HACIA publicada. Despublicar es otra cosa y hoy
        // no esta en la lista de acciones a registrar.
        if ($post->status !== NewsStatus::Published) {
            return;
        }

        $this->log($post, ['status' => $post->getOriginal('status')]);
    }

    public function deleted(NewsPost $post): void
    {
        $this->audit->log(
            AuditAction::NewsDeleted,
            $post,
            old: ['status' => $post->status?->value],
            label: $this->label($post),
        );
    }

    /** @param  array<string, mixed>  $antes */
    private function log(NewsPost $post, array $antes): void
    {
        $this->audit->log(
            AuditAction::NewsPublished,
            $post,
            old: $antes,
            new: ['status' => NewsStatus::Published->value, 'published_at' => (string) $post->published_at],
            label: $this->label($post),
        );
    }

    private function label(NewsPost $post): string
    {
        return $post->title ?: 'Noticia #'.$post->getKey();
    }
}
