import Alpine from 'alpinejs'
import uploader from './uploader'

// Componentes Alpine registrados antes de arrancar.
Alpine.data('uploader', uploader)

window.Alpine = Alpine
Alpine.start()


/**
 * Registra conversiones sin retrasar ni impedir la apertura de WhatsApp.
 * La delegacion cubre enlaces renderizados despues por Livewire o Alpine.
 */
document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href^="https://wa.me/"]')
    if (!link) return

    const endpoint = document.querySelector('meta[name="whatsapp-click-url"]')?.content
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
    if (!endpoint || !csrf) return

    try {
        const whatsappUrl = new URL(link.href)
        const path = window.location.pathname.toLowerCase()
        let source = link.dataset.whatsappSource

        if (!source) {
            if (path.includes('/propiedades/') || path.includes('/properties/')) source = 'property_detail'
            else if (path.includes('/invierte') || path.includes('/invest')) source = 'investment_page'
            else if (path.includes('/contact')) source = 'contact_page'
            else if (path.includes('/sobre-nosotros') || path.includes('/about-us')) source = 'about_page'
            else source = 'website'
        }

        fetch(endpoint, {
            method: 'POST',
            keepalive: true,
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                source,
                property_id: link.dataset.whatsappProperty || null,
                phone_number: whatsappUrl.pathname.replace(/\D/g, ''),
                generated_message: whatsappUrl.searchParams.get('text'),
            }),
        }).catch(() => {})
    } catch {
        // La analitica nunca debe interferir con el enlace de salida.
    }
})
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
