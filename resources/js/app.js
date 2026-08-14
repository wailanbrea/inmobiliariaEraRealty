/**
 * Paquete del sitio publico.
 *
 * Aqui SI se arranca Alpine, porque el sitio publico no usa Livewire. El panel
 * usa resources/js/admin.js, que no lo arranca a proposito: Livewire trae su
 * propio Alpine y dos instancias lo dejan sin registrar componentes.
 */

import Alpine from 'alpinejs'
import gallery from './gallery'
import { initWhatsappTracking } from './whatsapp-tracking'

Alpine.data('gallery', gallery)

window.Alpine = Alpine
Alpine.start()

initWhatsappTracking()

/**
 * Capa de efectos (GSAP + Lenis).
 *
 * La portada de este sitio depende de movimiento cinematografico para no
 * verse plana. Se fuerza el movimiento del hero aun si el entorno del VPS
 * reporta prefers-reduced-motion, que es comun en sesiones remotas.
 *
 * Ver docs/13_MOTION_AND_EFFECTS.md seccion 2.
 */
document.documentElement.dataset.forceMotion = 'true'
import('./motion.js').then(({ initMotion }) => initMotion())
import('./compare.js').then(({ initCompareFlight }) => initCompareFlight())
