<?php

namespace App\Support\Concerns;

/**
 * Paginacion del panel con el mismo estilo que el sitio publico.
 *
 * Livewire no respeta la vista publicada en resources/views/vendor/pagination:
 * mientras renderiza el componente sustituye Paginator::$defaultView por
 * livewire::tailwind, la de serie. Por eso el panel salia con otro diseno y
 * con el texto "Showing X to Y of Z results" en ingles, mientras que las
 * pantallas sin Livewire —leads, noticias, whatsapp— ya usaban la del sitio.
 *
 * SupportPagination consulta paginationView() en el componente antes de caer
 * en la suya, asi que basta con declararla. Ver
 * Livewire\Features\SupportPagination\SupportPagination::paginationView().
 */
trait UsesSitePagination
{
    public function paginationView(): string
    {
        return 'pagination::tailwind';
    }
}
