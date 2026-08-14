/**
 * Capa de movimiento — nucleo sin dependencias.
 *
 * Ver docs/13_MOTION_AND_EFFECTS.md
 *
 * Este archivo NO importa GSAP ni Lenis. Todo lo que hay aqui —revelados,
 * contadores, cabecera, barra de progreso, Ken Burns— se resuelve con
 * IntersectionObserver y transiciones CSS, que no cuestan nada.
 *
 * GSAP y Lenis se cargan en un segundo modulo (motion-scroll.js) y SOLO por
 * encima de 768 px, porque lo unico que necesitan es el parallax, y el
 * presupuesto de rendimiento prohibe el parallax en movil:
 *
 *   "Sin animacion bajo 768 px salvo entradas y feedback. El parallax en
 *    movil es caro y marea."   — docs/13, seccion 4
 *
 * Consecuencia practica: un telefono descarga ~0 KB de librerias de animacion
 * y aun asi ve todos los revelados y contadores.
 */

const PRIMED = 'is-primed'
const REVEALED = 'is-revealed'

/* --------------------------------------------------------------------------
   Revelados al entrar en pantalla
   -------------------------------------------------------------------------- */

/**
 * El orden importa: primero se marca .is-primed (que oculta), y solo entonces
 * se observa. Si este JS nunca llega a ejecutarse, nada lleva .is-primed y
 * todo el contenido se ve. Esa es la garantia de la seccion 5 de docs/13.
 */
function initReveals() {
    const targets = document.querySelectorAll('[data-reveal], .line-mask')
    if (!targets.length) return

    targets.forEach((el) => el.classList.add(PRIMED))

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return

                // El escalonado se lee del propio elemento para que cada
                // rejilla decida su ritmo sin tocar este archivo.
                const delay = Number(entry.target.dataset.revealDelay || 0)

                setTimeout(() => {
                    entry.target.classList.remove(PRIMED)
                    entry.target.classList.add(REVEALED)
                }, delay)

                observer.unobserve(entry.target)
            })
        },
        // 15 % de margen inferior negativo = se dispara al 85 % del viewport.
        { rootMargin: '0px 0px -15% 0px', threshold: 0 }
    )

    targets.forEach((el) => observer.observe(el))
}

/**
 * Escalona los hermanos de una rejilla sin tener que escribir el retardo en
 * cada tarjeta desde Blade. Se limita a 8 para que la ultima tarjeta de una
 * lista larga no espere casi un segundo.
 */
function staggerGroups() {
    document.querySelectorAll('[data-reveal-group]').forEach((group) => {
        const step = Number(group.dataset.revealGroup) || 80

        Array.from(group.children).forEach((child, index) => {
            const target = child.matches('[data-reveal]')
                ? child
                : child.querySelector('[data-reveal]')

            if (target) target.dataset.revealDelay = Math.min(index, 8) * step
        })
    })
}

/* --------------------------------------------------------------------------
   Contadores
   -------------------------------------------------------------------------- */

/**
 * Cuenta desde 0 hasta el valor real al entrar en pantalla.
 *
 * El numero final ya esta escrito en el HTML (lo pone Blade), asi que si el
 * JS falla el visitante ve la cifra correcta, no un cero. Se preserva
 * cualquier sufijo — "+", "%", "M USD" — separandolo del numero.
 */
function initCounters() {
    const counters = document.querySelectorAll('[data-counter]')
    if (!counters.length) return

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return

                animateCounter(entry.target)
                observer.unobserve(entry.target)
            })
        },
        { threshold: 0.4 }
    )

    counters.forEach((el) => observer.observe(el))
}

function animateCounter(el) {
    const target = parseFloat(String(el.dataset.counter).replace(/[^\d.-]/g, ''))
    if (!Number.isFinite(target)) return

    // Lo que sobra tras quitar el numero es el sufijo: "1200+" -> "+".
    const suffix = el.textContent.trim().replace(/^[\d.,\s]+/, '')
    const decimals = (String(target).split('.')[1] || '').length
    const duration = 1200
    const start = performance.now()

    const step = (now) => {
        const progress = Math.min((now - start) / duration, 1)
        // easeOutExpo: arranca rapido y frena, que es como se lee un contador.
        const eased = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress)
        const value = target * eased

        el.textContent = value.toLocaleString(document.documentElement.lang, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        }) + suffix

        if (progress < 1) requestAnimationFrame(step)
    }

    requestAnimationFrame(step)
}

/* --------------------------------------------------------------------------
   Cabecera adaptativa y barra de progreso
   -------------------------------------------------------------------------- */

/**
 * Una sola lectura de scroll por frame para las dos cosas. Separarlas en dos
 * listeners duplicaria el trabajo de layout en cada frame.
 */
function initScrollChrome() {
    const header = document.querySelector('[data-header]')

    const bar = document.createElement('div')
    bar.className = 'scroll-progress'
    bar.setAttribute('aria-hidden', 'true')
    document.body.appendChild(bar)

    let ticking = false

    const update = () => {
        const y = window.scrollY
        const scrollable = document.documentElement.scrollHeight - window.innerHeight

        if (header) header.classList.toggle('is-condensed', y > 100)

        bar.style.transform = `scaleX(${scrollable > 0 ? y / scrollable : 0})`
        ticking = false
    }

    window.addEventListener(
        'scroll',
        () => {
            if (ticking) return
            ticking = true
            requestAnimationFrame(update)
        },
        { passive: true }
    )

    update()
}

/* --------------------------------------------------------------------------
   Ken Burns del hero
   -------------------------------------------------------------------------- */

/**
 * Se arranca DESPUES de la carga, nunca antes.
 *
 * El fondo del hero es casi siempre el elemento LCP. Animarlo mientras el
 * navegador aun lo esta midiendo empeora la metrica que Google puntua, y
 * ademas es justo el momento en que menos CPU sobra.
 */
function initKenBurns() {
    const layer = document.querySelector('[data-ken-burns]')
    if (!layer) return

    const start = () => layer.classList.add('is-ken-burns')

    if (document.readyState === 'complete') {
        setTimeout(start, 400)
    } else {
        window.addEventListener('load', () => setTimeout(start, 400), { once: true })
    }
}

/* --------------------------------------------------------------------------
   Hero cinematografico
   -------------------------------------------------------------------------- */

/**
 * Revelado de cortina y brillo del titulo.
 *
 * Igual que el Ken Burns, arranca DESPUES de la carga. La imagen del hero es
 * casi siempre el elemento LCP: recortarla con clip-path mientras el navegador
 * la esta midiendo empeoraria justo la metrica que Google puntua.
 */
function initHeroEntrance() {
    const curtain = document.querySelector('[data-hero-curtain]')
    const shine = document.querySelector('[data-hero-shine]')

    if (!curtain && !shine) return

    const start = () => {
        curtain?.classList.add('is-drawing')

        if (shine) {
            // La clase se quita al terminar. Mientras esta puesta, el relleno
            // del texto es transparente para que se vea el degradado; dejarla
            // para siempre significaria que el titulo depende de un degradado
            // para ser legible. Un segundo y medio, y vuelve a ser texto.
            shine.addEventListener('animationend', () => shine.classList.remove('is-shining'), { once: true })
            shine.classList.add('is-shining')
        }
    }

    if (document.readyState === 'complete') {
        start()
    } else {
        window.addEventListener('load', start, { once: true })
    }
}

/**
 * Foco que sigue al cursor sobre el hero.
 *
 * Solo con puntero fino: en tactil no hay cursor al que seguir.
 *
 * Las coordenadas se escriben en variables CSS y se aplican dentro de un
 * requestAnimationFrame. Sin ese acotado, un raton moviendose rapido dispara
 * cientos de escrituras de estilo por segundo y el resto de la pagina lo nota.
 */
function initHeroSpotlight() {
    const spot = document.querySelector('[data-hero-spotlight]')
    if (!spot) return

    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return

    const scene = spot.closest('[data-parallax-scene]') || spot.parentElement
    let pending = false
    let x = 0
    let y = 0

    scene.addEventListener('mousemove', (event) => {
        const box = scene.getBoundingClientRect()
        x = ((event.clientX - box.left) / box.width) * 100
        y = ((event.clientY - box.top) / box.height) * 100

        if (pending) return
        pending = true

        requestAnimationFrame(() => {
            spot.style.setProperty('--spot-x', `${x}%`)
            spot.style.setProperty('--spot-y', `${y}%`)
            spot.classList.add('is-lit')
            pending = false
        })
    })

    // Al salir se apaga en vez de congelarse: un resplandor parado en el
    // borde donde el raton abandono el hero se lee como un defecto.
    scene.addEventListener('mouseleave', () => spot.classList.remove('is-lit'))
}

/* --------------------------------------------------------------------------
   Cursor magnetico
   -------------------------------------------------------------------------- */

/**
 * Solo con puntero fino. En tactil no existe el hover y "atraer el cursor"
 * no significa nada, ademas de que desplazaria el objetivo bajo el dedo.
 */
function initMagnetic() {
    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return

    document.querySelectorAll('[data-magnetic]').forEach((el) => {
        el.addEventListener('mousemove', (event) => {
            const box = el.getBoundingClientRect()
            const x = (event.clientX - box.left - box.width / 2) * 0.25
            const y = (event.clientY - box.top - box.height / 2) * 0.25
            const max = 8

            el.style.transform = `translate(${clamp(x, -max, max)}px, ${clamp(y, -max, max)}px)`
        })

        el.addEventListener('mouseleave', () => {
            el.style.transform = ''
        })
    })
}

const clamp = (value, min, max) => Math.min(Math.max(value, min), max)

/* --------------------------------------------------------------------------
   Arranque
   -------------------------------------------------------------------------- */

export function initMotion() {
    staggerGroups()
    initReveals()
    initCounters()
    initScrollChrome()
    initKenBurns()
    initHeroEntrance()
    initHeroSpotlight()
    initMagnetic()

    // Livewire reemplaza nodos del DOM al filtrar. Los que entren nuevos
    // tienen que revelarse igual, o el listado se quedaria en blanco tras
    // aplicar un filtro.
    document.addEventListener('livewire:navigated', initReveals)
    document.addEventListener('livewire:update', () => {
        staggerGroups()
        initReveals()
    })

    // El parallax necesita GSAP, asi que solo se pide en pantallas donde el
    // presupuesto lo permite. En movil este import nunca ocurre.
    if (window.matchMedia('(min-width: 768px)').matches) {
        import('./motion-scroll.js')
            .then(({ initScrollMotion }) => initScrollMotion())
            .catch(() => {
                // Sin parallax la pagina sigue completa: los revelados y los
                // contadores ya funcionan desde este mismo modulo.
            })
    }
}
