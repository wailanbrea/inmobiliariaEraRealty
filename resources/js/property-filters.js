/**
 * Actualiza el catalogo sin reconstruir toda la pagina.
 * Si la peticion falla, el navegador conserva el submit normal como respaldo.
 */
export function initPropertyFilters() {
    initLocationCascade()

    const loadResults = async (url) => {
        const current = document.querySelector('#property-results')
        if (!current) return

        current.setAttribute('aria-busy', 'true')
        current.classList.add('opacity-60', 'transition-opacity')

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })

            if (!response.ok) throw new Error(`Property results request failed: ${response.status}`)

            const html = await response.text()
            const nextDocument = new DOMParser().parseFromString(html, 'text/html')
            const next = nextDocument.querySelector('#property-results')

            if (!next) throw new Error('Property results fragment not found')

            current.replaceWith(next)
            syncFilterCounts(url)
            window.history.pushState({}, '', url)
        } catch (error) {
            console.warn(error)
            window.location.assign(url)
        }
    }

    const urlFromForm = (form) => {
        const url = new URL(form.action || window.location.href, window.location.origin)
        url.search = new URLSearchParams(new FormData(form)).toString()
        return url.toString()
    }

    const countActiveFilters = (url) => {
        const params = new URL(url, window.location.origin).searchParams
        const keys = new Set()

        params.forEach((value, key) => {
            if (key === 'orden' || key === 'moneda') return
            if (value !== '') keys.add(key === 'amenidades[]' ? 'amenidades' : key)
        })

        return keys.size
    }

    const syncFilterCounts = (url) => {
        const count = countActiveFilters(url)
        document.querySelectorAll('[data-property-filter-count]').forEach((target) => {
            target.textContent = String(count)
            target.classList.toggle('hidden', count === 0)
        })
    }

    document.addEventListener('submit', (event) => {
        const form = event.target
        const isFilterForm = form.matches?.('[data-property-filter-form]')
        const isResultsForm = form.closest?.('#property-results')

        if (!isFilterForm && !isResultsForm) return

        event.preventDefault()
        loadResults(urlFromForm(form))
    })

    document.addEventListener('click', (event) => {
        const clear = event.target.closest?.('[data-property-filter-clear]')
        if (clear) {
            event.preventDefault()
            document.querySelector('[data-property-filter-form]')?.reset()
            loadResults(clear.href)
            return
        }

        const link = event.target.closest?.('#property-results a[href]')
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return

        const url = new URL(link.href, window.location.origin)
        if (url.origin !== window.location.origin) return

        event.preventDefault()
        loadResults(url.toString())
    })
}

function initLocationCascade() {
    const form = document.querySelector('[data-location-cascade]')
    if (!form || form.dataset.locationCascadeReady) return

    const province = form.querySelector('[name="provincia"]')
    const city = form.querySelector('[name="ciudad"]')
    const sector = form.querySelector('[name="sector"]')
    if (!province || !city || !sector) return

    form.dataset.locationCascadeReady = 'true'

    const cityOptions = Array.from(city.options).slice(1).map((option) => option.cloneNode(true))
    const sectorOptions = Array.from(sector.options).slice(1).map((option) => option.cloneNode(true))
    const anyLabel = city.options[0]?.textContent ?? ''
    const sectorAnyLabel = sector.options[0]?.textContent ?? ''

    const renderCities = (selected = '') => {
        const provinceValue = province.value
        city.replaceChildren(new Option(anyLabel, ''))

        cityOptions
            .filter((option) => !provinceValue || option.dataset.province === provinceValue)
            .forEach((option) => city.appendChild(option.cloneNode(true)))

        city.value = city.querySelector(`option[value="${CSS.escape(selected)}"]`) ? selected : ''
    }

    const renderSectors = (selected = '') => {
        const cityValue = city.value
        const provinceValue = province.value
        sector.replaceChildren(new Option(sectorAnyLabel, ''))

        sectorOptions
            .filter((option) => {
                if (cityValue) return option.dataset.cityId === cityValue
                if (provinceValue) return option.dataset.province === provinceValue
                return true
            })
            .forEach((option) => sector.appendChild(option.cloneNode(true)))

        sector.value = sector.querySelector(`option[value="${CSS.escape(selected)}"]`) ? selected : ''
    }

    const initialCity = city.value
    const initialSector = sector.value
    renderCities(initialCity)
    renderSectors(initialSector)

    province.addEventListener('change', () => {
        renderCities()
        renderSectors()
    })

    city.addEventListener('change', () => renderSectors())

    form.addEventListener('reset', () => {
        requestAnimationFrame(() => {
            renderCities()
            renderSectors()
        })
    })
}
