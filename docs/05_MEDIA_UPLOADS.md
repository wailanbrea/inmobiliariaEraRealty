# 05 — Subida y procesamiento de imágenes

El prompt maestro (§9) exige que la subida sea **"fácil, visual e intuitiva"**. Es el punto donde el cliente pasará más tiempo, así que se trata como una funcionalidad de producto, no como un `<input type="file">`.

---

## 1. Almacenamiento

Disco `public` (`storage/app/public`), expuesto vía `php artisan storage:link`.

```
storage/app/public/
├── properties/{property_id}/
│   ├── original/   img_a1b2c3.jpg     ← optimizado, máx 1920px
│   ├── webp/       img_a1b2c3.webp    ← calidad 82
│   └── thumb/      img_a1b2c3.jpg     ← 400×300 cover
├── news/{year}/{month}/
├── agents/
├── settings/       logo, favicon, og
└── media/{year}/{month}/
```

En BD se guardan **rutas relativas** (`properties/12/original/img_a1b2c3.jpg`), nunca absolutas ni URLs completas. Así el sitio sobrevive a un cambio de dominio o a mover el proyecto de carpeta — que es exactamente lo que exige el prompt (§1).

Nombres de archivo aleatorios (`Str::random(12)`), nunca el nombre original: evita colisiones, path traversal y filtrado de información.

---

## 2. Validación — cuatro capas

Ninguna capa por sí sola es suficiente.

| # | Capa | Qué comprueba |
|---|---|---|
| 1 | Cliente | Extensión y tamaño antes de subir — **solo UX, no es seguridad** |
| 2 | Laravel FormRequest | `image`, `mimes:jpg,jpeg,png,webp`, `max:5120` (5 MB), `dimensions:min_width=400,min_height=300` |
| 3 | Contenido real | `getimagesize()` — un `.jpg` que no es una imagen se rechaza aquí |
| 4 | Reescritura | Intervention **decodifica y vuelve a codificar** la imagen. Cualquier payload PHP incrustado en los metadatos muere en el proceso |

Adicional:
- Extensión forzada por el MIME detectado, no por la que traiga el nombre.
- `storage/app/public` no ejecuta PHP (regla de Apache en `09_DEPLOYMENT.md`).
- Límite de **30 imágenes por propiedad** (§9 del prompt), comprobado en servidor.
- EXIF eliminado salvo la orientación (que se aplica y se descarta). Las fotos de propiedades traen GPS del móvil del agente — publicar eso filtraría la ubicación exacta de inmuebles marcados como `show_exact_location = 0`.

---

## 3. Pipeline de procesamiento

```
Archivo recibido
  ├─ 1. Validar (4 capas)
  ├─ 2. Auto-orientar por EXIF, luego eliminar EXIF
  ├─ 3. Redimensionar si el ancho > 1920px (mantiene proporción, sin ampliar)
  ├─ 4. Guardar original optimizado   → original/  (JPEG q85 · PNG comp9)
  ├─ 5. Generar WebP q82              → webp/
  ├─ 6. Generar miniatura 400×300     → thumb/     (cover, centrado)
  ├─ 7. Registrar en BD con width/height/size/mime
  └─ 8. Devolver JSON con preview + id
```

Ejecutado por `ImageProcessingService`. Si la propiedad tiene más de 8 imágenes, el paso 5–6 se despacha a cola (`ProcessPropertyImage`) para que la respuesta no se cuelgue; la miniatura sí se genera en línea para que el preview sea inmediato.

**Ahorro esperado:** una foto de 4 MB de cámara baja a ~380 KB en WebP. Sobre 24 fotos por propiedad, es la diferencia entre una ficha que carga y una que no.

---

## 4. Componente de subida (UX)

Livewire + Alpine + SortableJS.

```
┌─────────────────────────────────────────────────┐
│   ↑  Arrastra tus imágenes aquí                 │
│      o haz clic para seleccionar                │
│      JPG · PNG · WebP · máx 5 MB · hasta 30     │
└─────────────────────────────────────────────────┘

┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐
│ ★ MAIN │ │        │ │        │ │ ▓▓▓░ 68%│
│  ⠿ ✎ ✕ │ │ ⠿ ✎ ✕  │ │ ⠿ ✎ ✕  │ │ subiendo│
└────────┘ └────────┘ └────────┘ └────────┘
```

| Función | Comportamiento |
|---|---|
| Arrastrar y soltar | La zona se ilumina en `secondary` al arrastrar encima |
| Selección múltiple | Diálogo nativo, multi |
| Previsualización | Inmediata vía `URL.createObjectURL`, antes de que termine la subida |
| Progreso | Barra por archivo, subida concurrente máx 3 |
| Reordenar | Drag&drop con hueco fantasma; el orden se persiste al soltar |
| Imagen principal | Clic en la estrella; se desmarca la anterior en la misma transacción |
| Editar alt/título | Modal en línea, guardado sin recargar |
| Eliminar | Confirmación; borra registro **y** los 3 ficheros del disco |
| Errores | Mensaje concreto por archivo ("pesa 7,2 MB, el máximo es 5 MB"), no un genérico |

La subida es **inmediata**, no al guardar el formulario. Requiere que la propiedad exista, así que al pulsar "Nueva propiedad" se crea primero un borrador con id — evita el patrón frustrante de subir 20 fotos y perderlas porque falló una validación del formulario.

---

## 5. Endpoints

```
POST   /admin/propiedades/{property}/imagenes         subir (multipart, N archivos)
DELETE /admin/propiedades/{property}/imagenes/{image} eliminar
POST   /admin/propiedades/{property}/imagenes/orden   reordenar  {order: [id,...]}
POST   /admin/propiedades/{property}/imagenes/{image}/principal
PATCH  /admin/propiedades/{property}/imagenes/{image} actualizar alt/título
```

Todos con `auth`, policy `manage_property_images` y CSRF.

---

## 6. Entrega al front

```blade
<picture>
  <source srcset="{{ $image->webpUrl() }}" type="image/webp">
  <img src="{{ $image->url() }}"
       alt="{{ $image->alt_text ?? $property->title }}"
       width="{{ $image->width }}" height="{{ $image->height }}"
       loading="lazy" decoding="async">
</picture>
```

- `width`/`height` siempre presentes → CLS ≈ 0.
- `loading="lazy"` en todo **menos** la imagen del hero y la principal del detalle (esas son el LCP y deben ir con `fetchpriority="high"`).
- Miniatura en tarjetas y galería; original solo en el lightbox.

---

## 7. Media manager general

Mismo pipeline, contexto distinto (`property`, `news`, `agent`, `logo`, `favicon`, `og`, `banner`, `page`).

Funciones: grid/lista · filtro por contexto y tipo · búsqueda por nombre y alt · subida múltiple · edición de alt/título · copiar URL · **borrado con verificación de uso previo** (si el archivo está referenciado, se avisa dónde y no se borra).

Modal reutilizable: cualquier campo de imagen del panel puede abrir la biblioteca y reutilizar un archivo existente en vez de volver a subirlo.

---

## 8. Casos límite contemplados

| Caso | Respuesta |
|---|---|
| Se borra la única imagen principal | La siguiente por `sort_order` se promueve automáticamente |
| Se elimina una propiedad (soft delete) | Los ficheros **se conservan** — el soft delete es reversible |
| Se fuerza el borrado definitivo | Se elimina la carpeta `properties/{id}/` completa |
| Subida interrumpida | El registro solo se crea al terminar el proceso; no quedan huérfanos en BD |
| Ficheros huérfanos en disco | Comando `media:prune --dry-run` que **lista** antes de tocar nada, nunca borra sin confirmación |
| PNG con transparencia | Se conserva; el WebP mantiene el canal alfa |
| Imagen más pequeña que la miniatura | No se amplía; se rellena con `contain` sobre fondo neutro |
