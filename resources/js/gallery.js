/**
 * Galeria del detalle de propiedad con lightbox de transicion compartida.
 *
 * Ver docs/13_MOTION_AND_EFFECTS.md seccion 3.4.
 *
 * La miniatura no "abre un modal": crece desde su posicion real hasta ocupar
 * la pantalla. Se consigue con FLIP —medir, invertir, reproducir— en vez de
 * animar width/height, que forzaria layout en cada frame.
 */

export default (config = {}) => ({
    images: config.images || [],
    active: config.active || 0,

    open: false,
    /** Elemento que tenia el foco antes de abrir, para devolverselo al cerrar. */
    previousFocus: null,
    touchStartX: null,

    get current() {
        return this.images[this.active] || null
    },

    select(index) {
        this.active = ((index % this.images.length) + this.images.length) % this.images.length
    },

    next() {
        this.select(this.active + 1)
    },

    previous() {
        this.select(this.active - 1)
    },

    /* ----------------------------------------------------------------------
       Apertura y cierre
       -------------------------------------------------------------------- */

    openLightbox(index, event) {
        this.select(index)
        this.previousFocus = document.activeElement
        this.open = true

        // El scroll del fondo se congela: sin esto, la rueda desplaza la
        // pagina de detras mientras el lightbox esta encima.
        document.body.style.overflow = 'hidden'

        const origin = event?.currentTarget?.querySelector('img') || event?.currentTarget

        this.$nextTick(() => {
            this.$refs.panel?.classList.add('is-open')
            this.$refs.close?.focus()
            this.playSharedTransition(origin)
        })
    },

    closeLightbox() {
        this.open = false
        document.body.style.overflow = ''
        this.previousFocus?.focus()
    },

    /**
     * FLIP: la imagen grande arranca superpuesta exactamente sobre la
     * miniatura que se pulso y desde ahi vuelve a su tamano real.
     *
     * Se anima solo con transform, nunca con width/height: es la unica forma
     * de que el navegador no recalcule el layout en cada uno de los frames.
     */
    playSharedTransition(origin) {
        const target = this.$refs.image
        if (!origin || !target || typeof target.animate !== 'function') return

        const from = origin.getBoundingClientRect()
        const to = target.getBoundingClientRect()
        if (!to.width || !to.height) return

        const scaleX = from.width / to.width
        const scaleY = from.height / to.height
        const dx = from.left + from.width / 2 - (to.left + to.width / 2)
        const dy = from.top + from.height / 2 - (to.top + to.height / 2)

        target.animate(
            [
                { transform: `translate(${dx}px, ${dy}px) scale(${scaleX}, ${scaleY})`, opacity: 0.6 },
                { transform: 'none', opacity: 1 },
            ],
            { duration: 420, easing: 'cubic-bezier(0.16, 1, 0.3, 1)' }
        )
    },

    /* ----------------------------------------------------------------------
       Teclado y gestos
       -------------------------------------------------------------------- */

    onKeydown(event) {
        if (!this.open) return

        if (event.key === 'Escape') return this.closeLightbox()
        if (event.key === 'ArrowRight') return this.next()
        if (event.key === 'ArrowLeft') return this.previous()
        if (event.key === 'Tab') this.trapFocus(event)
    },

    /**
     * Trampa de foco. Sin esto, tabular saca el foco a los enlaces de la
     * pagina de detras, que el lector de pantalla anuncia aunque esten
     * visualmente tapados.
     */
    trapFocus(event) {
        const focusables = this.$refs.panel?.querySelectorAll(
            'button:not([disabled]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        )
        if (!focusables?.length) return

        const first = focusables[0]
        const last = focusables[focusables.length - 1]

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault()
            last.focus()
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault()
            first.focus()
        }
    },

    onTouchStart(event) {
        this.touchStartX = event.changedTouches[0].screenX
    },

    onTouchEnd(event) {
        if (this.touchStartX === null) return

        const delta = event.changedTouches[0].screenX - this.touchStartX

        // 50 px de umbral: por debajo suele ser un toque con la mano
        // temblando, no un deslizamiento intencionado.
        if (Math.abs(delta) > 50) {
            delta < 0 ? this.next() : this.previous()
        }

        this.touchStartX = null
    },
})
