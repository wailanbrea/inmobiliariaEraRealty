/**
 * Comparador — vuelo de la tarjeta hasta la barra.
 *
 * Ver docs/13_MOTION_AND_EFFECTS.md seccion 3.5.
 *
 * NOTA SOBRE LA ARQUITECTURA
 * --------------------------
 * El boton de comparar es un POST que recarga la pagina, a proposito: asi el
 * comparador funciona sin JavaScript (ver compare-toggle.blade.php).
 *
 * Eso obliga a invertir el orden habitual del efecto. En vez de animar
 * despues de actuar, se anima ANTES y se envia el formulario al terminar. Si
 * se hiciera al reves, la recarga cortaria el vuelo por la mitad y el usuario
 * veria un parpadeo en lugar de un movimiento.
 *
 * El vuelo dura 380 ms. Es el precio de la confirmacion visual, y se paga
 * solo cuando hay JS: sin el, el formulario envia como siempre.
 */

const FLIGHT_MS = 380

function findTarget() {
    const bar = document.querySelector('[data-compare-bar]')
    if (bar) {
        const box = bar.getBoundingClientRect()
        // La barra puede estar aun fuera de pantalla si esta es la primera
        // propiedad. En ese caso su borde superior sirve igual como destino.
        if (box.width) return { x: box.left + box.width / 2, y: box.top + box.height / 2 }
    }

    // Sin barra todavia: se apunta a donde va a aparecer.
    return { x: window.innerWidth / 2, y: window.innerHeight - 40 }
}

function fly(card, done) {
    const image = card?.querySelector('img')

    // Sin imagen no hay nada que hacer volar; se envia sin ceremonia.
    if (!image || typeof image.animate !== 'function') return done()

    const from = image.getBoundingClientRect()
    const to = findTarget()

    const ghost = image.cloneNode()
    Object.assign(ghost.style, {
        position: 'fixed',
        left: `${from.left}px`,
        top: `${from.top}px`,
        width: `${from.width}px`,
        height: `${from.height}px`,
        objectFit: 'cover',
        borderRadius: '12px',
        zIndex: '90',
        pointerEvents: 'none',
        margin: '0',
    })
    document.body.appendChild(ghost)

    const animation = ghost.animate(
        [
            { transform: 'none', opacity: 1 },
            {
                transform: `translate(${to.x - (from.left + from.width / 2)}px, ${to.y - (from.top + from.height / 2)}px) scale(0.12)`,
                opacity: 0.4,
            },
        ],
        { duration: FLIGHT_MS, easing: 'cubic-bezier(0.16, 1, 0.3, 1)' }
    )

    animation.onfinish = () => {
        ghost.remove()
        done()
    }

    // Si la animacion se cancela —cambio de pestana, por ejemplo— el envio no
    // puede quedarse colgado esperando un onfinish que no llegara.
    animation.oncancel = () => {
        ghost.remove()
        done()
    }
}

export function initCompareFlight() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return

    // Delegado: las tarjetas de propiedad las repinta Livewire al filtrar, y
    // un listener por boton se perderia en cada actualizacion.
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[action*="comparar"], form[action*="compare"]')
        if (!form) return

        // Quitar del comparador no vuela a ninguna parte.
        if (form.querySelector('button[aria-pressed="true"]')) return

        const card = form.closest('[data-compare-card]')
        if (!card) return

        event.preventDefault()
        fly(card, () => form.submit())
    })
}
