/**
 * Paquete del panel administrativo.
 *
 * AQUI NO SE IMPORTA NI SE ARRANCA ALPINE. Es deliberado y es la razon de que
 * este fichero exista.
 *
 * Livewire 3 trae su propia copia de Alpine y la arranca el mismo. Si la
 * pagina arranca ademas otra —`import Alpine from 'alpinejs'` seguido de
 * `Alpine.start()`— quedan DOS instancias compitiendo, y Livewire no llega a
 * registrar sus componentes: `Livewire.all()` devuelve una lista vacia aunque
 * el DOM tenga sus `wire:id`.
 *
 * El sintoma no es un error en consola, sino algo peor: TODOS los botones del
 * panel dejan de responder al clic, en silencio. «Añadir asesor», «Nuevo
 * usuario», «Editar» contenido, los filtros del listado, la busqueda... todo.
 * Se pulsa, no pasa nada, no hay peticion de red y no hay traza que seguir.
 *
 * Los componentes de Alpine se registran en el evento 'alpine:init', que
 * Livewire dispara justo antes de arrancar su Alpine. Es el punto de enganche
 * oficial y funciona sin que nosotros toquemos el ciclo de vida.
 *
 * El sitio publico NO usa Livewire, asi que sigue con resources/js/app.js, que
 * si arranca Alpine por su cuenta.
 */

import uploader from './uploader'
import { initWhatsappTracking } from './whatsapp-tracking'

document.addEventListener('alpine:init', () => {
    window.Alpine.data('uploader', uploader)
})

// La vista previa de una propiedad abre el sitio publico en otra pestana, pero
// el panel tambien muestra enlaces de WhatsApp en la ficha de un lead.
initWhatsappTracking()
