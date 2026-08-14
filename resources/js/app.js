import Alpine from 'alpinejs'
import uploader from './uploader'
import gallery from './gallery'

// Componentes Alpine registrados antes de arrancar.
Alpine.data('uploader', uploader)
Alpine.data('gallery', gallery)

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
 * La portada de este sitio depende de movimiento cinematografico para no
 * verse plana. Se fuerza el movimiento del hero aun si el entorno del VPS
 * reporta prefers-reduced-motion, que es comun en sesiones remotas.
 *
 * Ver docs/13_MOTION_AND_EFFECTS.md seccion 2.
 */
document.documentElement.dataset.forceMotion = 'true'
import('./motion.js').then(({ initMotion }) => initMotion())
import('./compare.js').then(({ initCompareFlight }) => initCompareFlight())
