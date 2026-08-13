# 04 — Páginas públicas

Mapeo de cada ruta a su diseño, sus datos y su origen administrable.

**Leyenda:** ✅ diseño existente · ⚠️ a derivar de los tokens

---

## Layout común

`resources/views/layouts/public.blade.php`

**Header** (de `inicio_era_realty_rd`): sticky, `h-20`, fondo `surface-container-lowest`.
Marca (logo desde `settings.site_logo`, fallback al icono `real_estate_agent` + `settings.site_name`) · nav de 6 enlaces · botón "Publica tu propiedad" · botón WhatsApp (verde `#009668`) · toggle móvil.

**Footer** (idéntico en las 4 maquetas): 4 columnas sobre `primary-container #131b2e` — marca + tagline + redes · Navegación · Soporte · Contacto (dirección, email, teléfono). Barra inferior con copyright.
**Todo sale de `settings`.** Los datos del diseño (`info@erarealtyrd.com`, `+1 (809) 555-0100`, `Av. Winston Churchill`) son valores del seeder, no texto quemado.

**Componentes globales:** botón flotante de WhatsApp, barra del comparador, avisos flash, meta SEO.

---

## 1. `/` — Inicio ✅ `inicio_era_realty_rd`

| Sección | Datos | Administrable en |
|---|---|---|
| Hero | Título, subtítulo, imagen de fondo | `content_sections(home, hero)` |
| Buscador de cristal | Operación · Tipo · Ubicación · Buscar | `property_types`, `provinces` |
| Propiedades destacadas | 6 con `is_featured=1, status=available` | Propiedades |
| Oportunidades de inversión ⚠️ | 3 con `is_investment=1` | `content_sections(home, investment_cta)` |
| Estadísticas ⚠️ | Propiedades vendidas, clientes, años | `content_sections(home, stats)` |
| Teaser Sobre nosotros ⚠️ | Texto + imagen + CTA | `content_sections(home, about_teaser)` |
| Noticias recientes ⚠️ | 3 últimas publicadas | Noticias |
| CTA final ⚠️ | Banda con WhatsApp | `content_sections(home, final_cta)` |

El diseño de Stitch solo trae hero + destacadas + footer. Las secciones ⚠️ se añaden porque el prompt maestro (§12) las exige en el home.

Efectos: parallax de 3 capas, Ken Burns, entrada del título por líneas, contadores. Ver [13_MOTION_AND_EFFECTS.md](13_MOTION_AND_EFFECTS.md) §3.2.

---

## 2. `/propiedades` — Listado ✅ `propiedades_era_realty_rd`

Layout: hero interno con breadcrumbs + sidebar de filtros a la izquierda + grid de resultados.

**Filtros (sidebar):** búsqueda por texto · operación · tipo · provincia → ciudad → sector · rango de precio · moneda · habitaciones · baños · parqueos · área mín/máx · amenidades · destacadas · inversión · estado.

**Resultados:** contador, orden (recientes · precio ↑↓ · área · más vistas), grid 3 columnas (2 en tablet, 1 en móvil), paginación 12.

Filtros aplicados a la URL como query string (`?operacion=sale&tipo=villa&precio_max=500000`) para que un resultado filtrado sea **compartible e indexable**.

Query: `PropertySearchService`, con `with(['mainImage','type','city','province'])` para evitar N+1.

---

## 3. `/propiedades/{slug}` — Detalle ✅ `detalle_de_propiedad_era_realty_rd`

Grid de 12 columnas: contenido en 8, sidebar sticky en 4.

**Columna izquierda:** breadcrumbs · título + ubicación + chip de estado + precio · galería (principal 500 px + 4 miniaturas + contador "+20") · meta-grid de 4 (habitaciones, baños, parqueos, m²) · descripción · amenidades en bento · características extra · mapa (exacto o área aproximada según `show_exact_location`) · video/tour si existen · propiedades similares.

**Sidebar:** formulario de contacto (nombre, teléfono, email, mensaje **precargado con la referencia**) + "Agendar visita" + "Contactar por WhatsApp"; tarjeta del agente asignado.

**Móvil:** barra fija inferior con Llamar / WhatsApp / Consultar.

**Efectos secundarios de la visita:** incrementa `views_count` y registra en `property_views` (deduplicado por hash de IP, 24 h).

SEO: meta propios con fallback al título/ubicación/precio; JSON-LD `RealEstateListing`; OG con la imagen principal.

---

## 4. `/comparar` — Comparador ✅ `comparador_era_realty_rd`

Hasta **4** propiedades (el prompt dice 3–4; el diseño muestra 3 + hueco vacío).
Estado en `localStorage`, con opción de compartir por URL (`?ids=12,45,78`).

Filas comparadas: imagen + título · precio · operación · tipo · m² · habitaciones · baños · parqueos · ubicación · código de referencia · estado · amenidades · botones de contacto.

Columna de etiquetas sticky a la izquierda. Estado vacío ya contemplado en el diseño.
Toggle "resaltar diferencias". En móvil, scroll horizontal por columnas.

---

## 5. `/invierte` ⚠️

Contenido desde `pages(invest)` + `content_sections(invest, *)`.

Hero propio · por qué invertir en RD (bloques con icono) · propiedades con `is_investment=1` · datos de ROI y plusvalía (editables) · proceso paso a paso (timeline) · formulario específico → `leads(source=investment_page)` · CTA WhatsApp con `whatsapp_investment_message`.

---

## 6. `/sobre-nosotros` ⚠️

`pages(about)` + secciones. Historia · misión/visión/valores · equipo (desde `agents` activos, con foto, cargo y contacto) · logros con contadores · CTA.

---

## 7. `/informate` ⚠️ — Listado de noticias

Noticia destacada grande + grid de 9 · filtro por categoría · buscador (FULLTEXT) · paginación · sidebar con categorías y las más leídas.
Solo `status=published` y `published_at <= now()`.

---

## 8. `/informate/{slug}` ⚠️ — Detalle de noticia

Imagen destacada con parallax · título, fecha, autor, categoría, tiempo de lectura · contenido HTML saneado · compartir en WhatsApp / Facebook / X / copiar enlace · 3 noticias relacionadas · CTA de contacto.
Incrementa `views_count`. JSON-LD `Article`.

---

## 9. `/contactanos` ⚠️

`pages(contact)`. Formulario (nombre, teléfono, email, asunto, mensaje, tipo de interés) → `leads(source=contact_page)` · datos de contacto desde `settings` · mapa embebido · horario · botones directos de WhatsApp/llamada.

---

## 10. `/publica-tu-propiedad` ⚠️

El botón ya existe en el header de las 4 maquetas, pero no hay pantalla.
Formulario en pasos: datos del propietario → datos de la propiedad (tipo, operación, ubicación, características) → fotos opcionales → mensaje.
Crea `leads(source=publish_property)` con los datos en `message`/`interest_type`. **No** crea una `property` automáticamente: la publicación siempre la aprueba un humano desde el panel.

---

## 11. `/privacidad` y `/terminos` ⚠️

Enlazadas desde el footer del diseño. Contenido desde `pages(privacy|terms)`.
**Pendiente:** ¿los textos legales los aporta el cliente o se genera un borrador base? Ver Pregunta 8.

---

## Componentes Blade compartidos

| Componente | Usado en |
|---|---|
| `<x-property-card>` | home, listado, similares, invierte |
| `<x-status-chip>` | tarjetas, detalle, comparador |
| `<x-search-bar>` | home, listado |
| `<x-news-card>` | home, informate |
| `<x-agent-card>` | detalle, sobre nosotros |
| `<x-contact-form>` | detalle, contacto, invierte |
| `<x-whatsapp-button>` | global |
| `<x-compare-toggle>` | tarjetas y detalle |
| `<x-breadcrumbs>` | todas menos home |
| `<x-seo-meta>` | layout |
| `<x-reveal>` | envoltorio de animación |
| `<x-empty-state>` | listados sin resultados |

---

## Estados vacíos y errores

Toda página con listado necesita su estado vacío diseñado (sin resultados de búsqueda, sin noticias, comparador vacío). Además: `404` y `500` con la identidad del sitio y enlaces de vuelta a propiedades e inicio.
