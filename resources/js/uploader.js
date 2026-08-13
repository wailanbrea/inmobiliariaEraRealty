import Sortable from 'sortablejs'

/**
 * Subida de imagenes de propiedad.
 *
 * La subida es INMEDIATA, no al guardar el formulario. Requiere que la
 * propiedad exista, por eso al crear una nueva se guarda primero como
 * borrador. Evita el patron frustrante de subir 20 fotos y perderlas porque
 * fallo una validacion del formulario.
 *
 * Ver docs/05_MEDIA_UPLOADS.md seccion 4.
 */
export default function uploader(config) {
    return {
        images: config.images || [],
        endpoints: config.endpoints,
        maxImages: config.maxImages || 30,
        maxSizeMb: config.maxSizeMb || 5,

        dragging: false,
        queue: [],          // { id, name, progress, error }
        errors: [],
        sortable: null,
        editing: null,      // id de la imagen cuyo alt se esta editando
        editAlt: '',
        editTitle: '',

        init() {
            this.initSortable()
        },

        initSortable() {
            const grid = this.$refs.grid
            if (!grid) return

            this.sortable?.destroy()

            this.sortable = Sortable.create(grid, {
                animation: 200,
                handle: '[data-drag-handle]',
                ghostClass: 'opacity-40',
                onEnd: () => this.persistOrder(),
            })
        },

        get remaining() {
            return this.maxImages - this.images.length
        },

        get isFull() {
            return this.remaining <= 0
        },

        // ------------------------------------------------------------------
        // Seleccion de archivos
        // ------------------------------------------------------------------

        onDrop(event) {
            this.dragging = false
            this.handleFiles(event.dataTransfer.files)
        },

        onSelect(event) {
            this.handleFiles(event.target.files)
            event.target.value = ''   // permite volver a elegir el mismo archivo
        },

        handleFiles(fileList) {
            this.errors = []

            const files = Array.from(fileList)

            if (files.length > this.remaining) {
                this.errors.push(
                    `Solo caben ${this.remaining} imágenes más. Se subirán las primeras.`
                )
            }

            const aceptados = []

            for (const file of files.slice(0, this.remaining)) {
                // Validacion en cliente: solo para dar respuesta inmediata.
                // La de verdad la hace el servidor.
                if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
                    this.errors.push(`«${file.name}»: solo se admiten JPG, PNG y WebP.`)
                    continue
                }

                if (file.size > this.maxSizeMb * 1024 * 1024) {
                    const mb = (file.size / 1024 / 1024).toFixed(1)
                    this.errors.push(
                        `«${file.name}» pesa ${mb} MB, el máximo son ${this.maxSizeMb} MB.`
                    )
                    continue
                }

                aceptados.push(file)
            }

            // De tres en tres: subir 20 a la vez satura la conexion movil y
            // hace que todas vayan lentas.
            this.uploadInBatches(aceptados, 3)
        },

        async uploadInBatches(files, size) {
            for (let i = 0; i < files.length; i += size) {
                await Promise.all(files.slice(i, i + size).map(f => this.upload(f)))
            }
        },

        upload(file) {
            return new Promise(resolve => {
                const item = {
                    id: `tmp-${Date.now()}-${Math.round(performance.now() * 1000)}`,
                    name: file.name,
                    progress: 0,
                    error: null,
                }

                this.queue.push(item)

                const form = new FormData()
                form.append('images[]', file)

                const xhr = new XMLHttpRequest()
                xhr.open('POST', this.endpoints.store)
                xhr.setRequestHeader('X-CSRF-TOKEN', config.csrf)
                xhr.setRequestHeader('Accept', 'application/json')

                xhr.upload.addEventListener('progress', e => {
                    if (e.lengthComputable) {
                        item.progress = Math.round((e.loaded / e.total) * 100)
                    }
                })

                xhr.addEventListener('load', () => {
                    this.queue = this.queue.filter(q => q.id !== item.id)

                    let data = {}
                    try { data = JSON.parse(xhr.responseText) } catch { /* respuesta no JSON */ }

                    if (xhr.status === 201 || xhr.status === 200) {
                        this.images.push(...(data.images || []))
                        ;(data.errors || []).forEach(e =>
                            this.errors.push(`«${e.file}»: ${e.message}`)
                        )
                        this.$nextTick(() => this.initSortable())
                    } else {
                        const mensaje = data.errors
                            ? Object.values(data.errors).flat()[0]
                            : `No se pudo subir «${file.name}».`
                        this.errors.push(mensaje)
                    }

                    resolve()
                })

                xhr.addEventListener('error', () => {
                    this.queue = this.queue.filter(q => q.id !== item.id)
                    this.errors.push(`Falló la conexión al subir «${file.name}».`)
                    resolve()
                })

                xhr.send(form)
            })
        },

        // ------------------------------------------------------------------
        // Acciones sobre imagenes ya subidas
        // ------------------------------------------------------------------

        async persistOrder() {
            const order = Array.from(this.$refs.grid.children)
                .map(el => parseInt(el.dataset.imageId))
                .filter(Boolean)

            // Se reordena el array local para que coincida con el DOM.
            this.images.sort((a, b) => order.indexOf(a.id) - order.indexOf(b.id))

            await this.post(this.endpoints.reorder, { order })
        },

        async setMain(image) {
            await this.post(this.endpoints.main.replace(':id', image.id))
            this.images.forEach(i => { i.is_main = i.id === image.id })
        },

        async remove(image) {
            if (!confirm(`¿Eliminar «${image.original_name}»?`)) return

            const respuesta = await fetch(this.endpoints.destroy.replace(':id', image.id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': config.csrf, 'Accept': 'application/json' },
            })

            if (!respuesta.ok) {
                this.errors.push('No se pudo eliminar la imagen.')
                return
            }

            const eraPrincipal = image.is_main
            this.images = this.images.filter(i => i.id !== image.id)

            // El servidor promueve la siguiente a principal; se refleja aqui
            // para no tener que recargar.
            if (eraPrincipal && this.images.length) {
                this.images[0].is_main = true
            }
        },

        startEdit(image) {
            this.editing = image.id
            this.editAlt = image.alt_text || ''
            this.editTitle = image.title || ''
        },

        async saveEdit(image) {
            const respuesta = await fetch(this.endpoints.update.replace(':id', image.id), {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': config.csrf,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ alt_text: this.editAlt, title: this.editTitle }),
            })

            if (respuesta.ok) {
                image.alt_text = this.editAlt
                image.title = this.editTitle
            } else {
                this.errors.push('No se pudieron guardar los datos de la imagen.')
            }

            this.editing = null
        },

        post(url, body = {}) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': config.csrf,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(body),
            })
        },

        formatSize(bytes) {
            if (!bytes) return ''
            return bytes > 1024 * 1024
                ? `${(bytes / 1024 / 1024).toFixed(1)} MB`
                : `${Math.round(bytes / 1024)} KB`
        },
    }
}
