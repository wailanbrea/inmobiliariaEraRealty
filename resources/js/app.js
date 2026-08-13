import Alpine from 'alpinejs'

window.Alpine = Alpine
Alpine.start()

/**
 * Capa de efectos (GSAP + Lenis).
 *
 * Se importa de forma dinamica y solo si el usuario no ha pedido movimiento
 * reducido. Asi, quien tiene 'prefers-reduced-motion: reduce' no descarga
 * ~53 KB de JS que no va a usar.
 *
 * Ver docs/13_MOTION_AND_EFFECTS.md seccion 2.
 */
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    // La capa completa se implementa en la Fase 8.
    // import('./motion.js').then(({ initMotion }) => initMotion())
}
