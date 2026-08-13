# 07 — SEO

Objetivo: que las fichas de propiedad y los artículos posicionen en búsquedas del tipo *"apartamento en venta Piantini"* o *"villa Cap Cana"*.

---

## 1. Cascada de metadatos

`SeoService` resuelve con fallback, nunca deja una página sin meta:

```
meta propio de la entidad
    ↓ si está vacío
plantilla automática por tipo de entidad
    ↓ si no aplica
settings.seo_default_*
```

### Plantillas automáticas

| Entidad | Title | Description |
|---|---|---|
| Propiedad | `{title} en {sector}, {city} \| {site_name}` | `{operacion} {tipo} de {m²} m², {hab} hab, {baños} baños en {ubicación}. {precio}. Ref. {ref}.` |
| Listado | `Propiedades en {ubicación} \| {site_name}` | Descripción con los filtros activos |
| Noticia | `{title} \| {site_name}` | `excerpt` o primeros 155 caracteres del contenido |
| Página | `{page.title} \| {site_name}` | `page.meta_description` |

Límites aplicados: title 60 car., description 155 car. El panel muestra un **contador y una vista previa del resultado en Google** mientras se escribe.

---

## 2. Etiquetas por página

```html
<title>…</title>
<meta name="description" content="…">
<link rel="canonical" href="…">
<meta name="robots" content="index,follow">   <!-- noindex si status ≠ published -->

<!-- Open Graph -->
<meta property="og:type"        content="website|article">
<meta property="og:title"       content="…">
<meta property="og:description" content="…">
<meta property="og:image"       content="…">   <!-- 1200×630 -->
<meta property="og:url"         content="…">
<meta property="og:locale"      content="es_DO">
<meta property="og:site_name"   content="ERA Realty RD">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
```

Fallback de `og:image`: imagen principal de la propiedad → `og_image` propio → `settings.seo_default_og_image`.

**Regla dura:** toda propiedad en `draft`, `paused` o `not_available` sale con `noindex,nofollow` y fuera del sitemap. Publicar un borrador por accidente en Google es difícil de deshacer.

---

## 3. Datos estructurados (JSON-LD)

`SchemaOrgService` genera:

| Tipo | Dónde |
|---|---|
| `Organization` + `RealEstateAgent` | Layout global |
| `WebSite` + `SearchAction` | Home |
| `RealEstateListing` | Detalle de propiedad |
| `Article` | Detalle de noticia |
| `BreadcrumbList` | Todas menos home |
| `Person` | Fichas de agentes |
| `FAQPage` | Invierte, si hay FAQ |

Ejemplo de propiedad:

```json
{
  "@context": "https://schema.org",
  "@type": "RealEstateListing",
  "name": "Apartamento de Lujo Piantini",
  "url": "https://.../propiedades/apartamento-de-lujo-piantini",
  "image": ["…"],
  "offers": { "@type": "Offer", "price": 450000, "priceCurrency": "USD",
              "availability": "https://schema.org/InStock" },
  "address": { "@type": "PostalAddress",
               "addressLocality": "Piantini",
               "addressRegion": "Santo Domingo",
               "addressCountry": "DO" },
  "numberOfRooms": 3,
  "numberOfBathroomsTotal": 3.5,
  "floorSize": { "@type": "QuantitativeValue", "value": 210, "unitCode": "MTK" }
}
```

`geo` solo se incluye si `show_exact_location = 1`.

---

## 4. URLs

```
/                              /informate
/propiedades                   /informate/{slug}
/propiedades/{slug}            /contactanos
/invierte                      /publica-tu-propiedad
/sobre-nosotros                /comparar
```

- Slugs en español, minúsculas, sin acentos, con guiones.
- Únicos, con sufijo incremental (`-2`) si colisionan.
- **Inmutables tras publicar.** Si el admin cambia el título, el slug no se regenera solo; el panel ofrece cambiarlo con un aviso explícito y crea una redirección 301 desde el anterior (tabla `redirects`, añadida en Fase 8).
- Filtros como query string, no como segmentos de ruta.
- Paginación con `?page=N` + `rel="prev"/"next"`.

Reglas de indexación de listados filtrados: la página 1 sin filtros indexa; con filtros aplicados se emite `canonical` hacia `/propiedades` para no generar miles de URLs casi duplicadas.

---

## 5. Sitemap

`GET /sitemap.xml` — índice que apunta a sub-sitemaps:

```
/sitemap-pages.xml       estáticas (prioridad 1.0 – 0.7)
/sitemap-properties.xml  publicadas y visibles (0.8, lastmod = updated_at)
/sitemap-news.xml        publicadas (0.6)
/sitemap-locations.xml   listados por provincia/ciudad (0.5)
```

Regenerado por scheduler diario y al publicar/despublicar. Cacheado 1 h.

## 6. robots.txt

```
User-agent: *
Allow: /
Disallow: /admin
Disallow: /admin/*
Disallow: /leads
Disallow: /wa/click
Disallow: /*?page=
Sitemap: https://{dominio}/sitemap.xml
```

Editable desde `/admin/configuracion/seo`.

---

## 7. Rendimiento (es SEO)

| Medida | Detalle |
|---|---|
| WebP con fallback | Ver [05_MEDIA_UPLOADS.md](05_MEDIA_UPLOADS.md) |
| Lazy loading | Todo menos hero y principal del detalle |
| `width`/`height` explícitos | CLS ≈ 0 |
| CSS/JS minificado y versionado | Vite |
| Tailwind purgado | El CDN de Stitch sirve **todo** Tailwind; compilado quedan ~12 KB |
| Fuentes | `font-display: swap` + `preconnect` a Google Fonts |
| Preload | Imagen del hero con `fetchpriority="high"` |
| Caché de settings y catálogos | Menos consultas por petición |
| Gzip/Brotli | Configuración de Apache |

**Objetivos:** LCP < 2,5 s · CLS < 0,1 · INP < 200 ms · Lighthouse móvil ≥ 90 en Performance, SEO y Accesibilidad.

Los efectos del doc 13 se miden contra estos objetivos. Si un efecto los rompe, se elimina el efecto.

---

## 8. Contenido e i18n

- `<html lang="es">`, `og:locale` `es_DO`.
- Un solo `<h1>` por página.
- Jerarquía correcta de encabezados.
- `alt` obligatorio en imágenes de contenido; el panel avisa si falta.
- Enlazado interno: similares, categorías de noticias, listados por ubicación.
- **Sitio monolingüe en español.** Si más adelante se quiere inglés (mercado inversor extranjero), habría que rehacer rutas y añadir `hreflang` — decisión que conviene tomar antes de la Fase 4, no después. Ver Pregunta 3.

## 9. Analítica

- Google Analytics 4 vía `settings.seo_google_analytics_id` (vacío = no se inyecta script).
- Verificación de Search Console por meta tag.
- Eventos: `whatsapp_click`, `form_submit`, `property_view`, `compare_add`.
- Aviso de cookies pendiente de decisión legal — ver Pregunta 8.
