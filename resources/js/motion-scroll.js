/**
 * Capa de movimiento — scroll suave y parallax (GSAP + Lenis).
 *
 * Cargado dinamicamente desde motion.js y SOLO por encima de 768 px.
 * Ver la nota de presupuesto en la cabecera de motion.js.
 */

import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import Lenis from 'lenis'

gsap.registerPlugin(ScrollTrigger)

/**
 * Lenis y ScrollTrigger tienen que compartir el mismo reloj.
 *
 * Si cada uno corre su propio requestAnimationFrame, ScrollTrigger lee la
 * posicion del frame anterior y el parallax va medio frame por detras del
 * fondo: se ve como un temblor. Por eso Lenis se avanza desde el ticker de
 * GSAP y se le desactiva su suavizado de tiempo propio.
 */
function initSmoothScroll() {
    const lenis = new Lenis({ lerp: 0.08, wheelMultiplier: 1 })

    lenis.on('scroll', ScrollTrigger.update)

    gsap.ticker.add((time) => lenis.raf(time * 1000))
    gsap.ticker.lagSmoothing(0)

    /**
     * Red de seguridad para el scroll que NO pasa por Lenis.
     *
     * Lenis solo emite su evento cuando el desplazamiento lo provoca el
     * usuario. Un window.scrollTo(), o la posicion que el navegador restaura
     * al volver atras, mueven la pagina sin avisarle, y ScrollTrigger se
     * queda midiendo contra una posicion vieja: el parallax aparece congelado
     * hasta que se toca la rueda.
     *
     * Se comprueba isScrolling para no duplicar el trabajo mientras Lenis ya
     * esta conduciendo, que es el caso habitual.
     */
    window.addEventListener(
        'scroll',
        () => {
            if (!lenis.isScrolling) ScrollTrigger.update()
        },
        { passive: true }
    )

    // Expuesto a proposito: cualquier desplazamiento programatico futuro
    // (un "volver arriba", un ancla) debe usar lenis.scrollTo, no el nativo.
    window.lenis = lenis

    return lenis
}

/**
 * Parallax de tres capas.
 *
 * Tres es el limite que fija docs/13: mas capas no aportan profundidad
 * perceptible y si cuestan frames. El fondo baja, el texto sube y el
 * buscador sube menos, que es lo que genera la sensacion de distancia.
 *
 * `scrub: true` ata la posicion al scroll en vez de disparar una animacion
 * con duracion propia: asi el movimiento es reversible y nunca se desincroniza
 * de la rueda.
 */
function initParallax() {
    document.querySelectorAll('[data-parallax]').forEach((el) => {
        const shift = Number(el.dataset.parallax)
        if (!Number.isFinite(shift) || shift === 0) return

        gsap.to(el, {
            yPercent: shift,
            ease: 'none',
            scrollTrigger: {
                trigger: el.closest('[data-parallax-scene]') || el.parentElement,
                start: 'top top',
                end: 'bottom top',
                scrub: true,
                // El fondo se agranda un 15 % en Blade justo para que al
                // desplazarse no asome el borde inferior.
                invalidateOnRefresh: true,
            },
        })
    })
}

/**
 * Entradas escalonadas de grupos grandes.
 *
 * `batch()` agrupa lo que entra en el mismo frame bajo UN solo observador.
 * Con un ScrollTrigger por tarjeta, un listado de 24 propiedades crearia 24
 * observadores y el scroll se notaria pesado.
 */
function initBatchReveals() {
    const items = gsap.utils.toArray('[data-reveal-batch]')
    if (!items.length) return

    gsap.set(items, { opacity: 0, y: 24 })

    ScrollTrigger.batch(items, {
        start: 'top 85%',
        once: true,
        onEnter: (batch) =>
            gsap.to(batch, {
                opacity: 1,
                y: 0,
                duration: 0.6,
                stagger: 0.1,
                ease: 'power3.out',
                // Se suelta el will-change al terminar: dejarlo puesto
                // mantiene una capa de GPU viva para siempre.
                clearProps: 'willChange',
            }),
    })
}

/**
 * Dibuja el trazo de la linea temporal de "Invierte" conforme se desciende.
 * `stroke-dasharray` a la longitud total y `stroke-dashoffset` de esa longitud
 * a cero: el trazo parece escribirse.
 */
function initStrokeDraw() {
    document.querySelectorAll('[data-draw]').forEach((path) => {
        const length = path.getTotalLength?.()
        if (!length) return

        gsap.set(path, { strokeDasharray: length, strokeDashoffset: length })

        gsap.to(path, {
            strokeDashoffset: 0,
            ease: 'none',
            scrollTrigger: { trigger: path, start: 'top 80%', end: 'bottom 60%', scrub: true },
        })
    })
}

/**
 * Disolucion del hero al salir de pantalla.
 *
 * El contenido central se aleja y se desvanece conforme se desciende, en vez
 * de limitarse a desplazarse hacia arriba. Es lo que da la sensacion de que la
 * pagina tiene profundidad y no es una tira de secciones apiladas.
 *
 * Termina en el 60 % del recorrido, no en el 100 %: si el texto siguiera
 * visible al borde de desaparecer se leeria como un fallo de renderizado.
 */
function initHeroDissolve() {
    const scene = document.querySelector('[data-parallax-scene]')
    const content = scene?.querySelector('[data-hero-content]')
    if (!content) return

    gsap.to(content, {
        opacity: 0,
        scale: 0.94,
        ease: 'none',
        scrollTrigger: {
            trigger: scene,
            start: 'top top',
            end: '60% top',
            scrub: true,
        },
    })
}

export function initScrollMotion() {
    initSmoothScroll()
    initParallax()
    initHeroDissolve()
    initBatchReveals()
    initStrokeDraw()

    // Las imagenes que cargan tarde cambian la altura del documento y dejan
    // los disparadores midiendo contra un layout viejo.
    window.addEventListener('load', () => ScrollTrigger.refresh())
}
