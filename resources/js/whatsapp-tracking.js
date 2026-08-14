/**
 * Analitica de clics de WhatsApp.
 *
 * Vive en su propio modulo porque lo usan los DOS paquetes: el del sitio
 * publico y el del panel. Duplicar el listener era la alternativa, y un
 * listener duplicado registra cada clic dos veces.
 */
export function initWhatsappTracking() {
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
}
