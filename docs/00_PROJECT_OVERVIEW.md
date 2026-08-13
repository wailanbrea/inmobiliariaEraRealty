# 00 — Visión general del proyecto

**Proyecto:** ERA Realty RD — Plataforma inmobiliaria administrable
**Ubicación:** `C:\xampp\php\www\Era Realty`
**Estado:** Fase 0 — Planificación. **Sin código funcional todavía.**
**Fecha de este documento:** 2026-08-13

---

## 1. Qué se va a construir

Una web inmobiliaria profesional para el mercado dominicano, con dos mitades:

| Mitad | Quién la usa | Qué hace |
|---|---|---|
| **Web pública** | Compradores, inversionistas | Buscar, filtrar, comparar propiedades; leer noticias; contactar por formulario o WhatsApp |
| **Panel admin** | ERA Realty RD | Publicar propiedades e imágenes, escribir noticias, gestionar leads, agentes, SEO y toda la configuración del sitio |

**Regla rectora:** nada de contenido quemado en el HTML. Logo, teléfono, WhatsApp, textos de secciones, SEO, correo — todo se edita desde el panel.

---

## 2. Fuentes de verdad

Este proyecto tiene tres insumos, y en este orden de autoridad:

1. **`Prompt maestro para desarrollar página web inmobiliaria completa.md`** — requisitos funcionales, esquema de datos sugerido, fases y criterios de aceptación. Es el contrato.
2. **`stitch_era_realty_rd_premium_redesign/estate_elite/DESIGN.md`** — design tokens (colores, tipografía, espaciado, radios, sombras). Es la fuente de verdad visual.
3. **Los 4 `code.html` de Stitch** — maquetas de referencia. Son HTML generado, no producción: usan Tailwind por CDN, imágenes de `lh3.googleusercontent.com` y datos ficticios. Se traducen a Blade + Tailwind compilado, no se copian.

Requisito adicional del cliente, fuera del prompt maestro:

> **"Deberá tener efectos llamativos."**

Se documenta en [13_MOTION_AND_EFFECTS.md](13_MOTION_AND_EFFECTS.md) como una capa de diseño de primera clase, no como adorno improvisado.

---

## 3. Qué existe hoy en la carpeta

```
Era Realty/
├── Prompt maestro ... .md                       ← requisitos (1.492 líneas)
├── docs/                                         ← ESTE paquete de planificación
└── stitch_era_realty_rd_premium_redesign/
    ├── estate_elite/DESIGN.md                    ← design tokens
    ├── inicio_era_realty_rd/{code.html,screen.png}
    ├── propiedades_era_realty_rd/{code.html,screen.png}
    ├── detalle_de_propiedad_era_realty_rd/{code.html,screen.png}
    └── comparador_era_realty_rd/{code.html,screen.png}
```

No hay proyecto Laravel, ni `composer.json`, ni base de datos creada. Se parte de cero.

---

## 4. Cobertura del diseño — importante

De las pantallas que el prompt maestro exige, **solo 4 de 10+ están diseñadas**:

| Pantalla pública | Ruta | ¿Diseñada? |
|---|---|---|
| Inicio | `/` | ✅ `inicio_era_realty_rd` |
| Listado de propiedades | `/propiedades` | ✅ `propiedades_era_realty_rd` |
| Detalle de propiedad | `/propiedades/{slug}` | ✅ `detalle_de_propiedad_era_realty_rd` |
| Comparador | `/comparar` | ✅ `comparador_era_realty_rd` |
| Invierte | `/invierte` | ❌ **falta** |
| Sobre nosotros | `/sobre-nosotros` | ❌ **falta** |
| Infórmate (listado) | `/informate` | ❌ **falta** |
| Infórmate (detalle) | `/informate/{slug}` | ❌ **falta** |
| Contáctanos | `/contactanos` | ❌ **falta** |
| Publica tu propiedad | `/publica-tu-propiedad` | ❌ **falta** (el botón sí existe en el header) |

| Panel admin | ¿Diseñado? |
|---|---|
| Login, dashboard, CRUDs, media manager, editor de noticias, configuración | ❌ **ninguno** |

**Cómo se resuelve:** las 6 pantallas públicas faltantes se derivan de los tokens de `DESIGN.md` y de los patrones ya establecidos en las 4 maquetas (header, footer, tarjetas, formularios, chips de estado). El panel admin usa un layout propio, sobrio y denso — el "quiet luxury" es para el público, no para una tabla de 200 propiedades.

Esto está abierto como decisión en [Preguntas](#8-preguntas-abiertas).

---

## 5. Stack elegido

| Capa | Elección | Por qué |
|---|---|---|
| Framework | **Laravel 12** | Lo pide el prompt maestro |
| PHP | **8.2.33** (instalado) | Laravel 12 requiere `^8.2`. Cumple. El prompt pedía 8.3+ — ver [12_KNOWN_ISSUES.md](12_KNOWN_ISSUES.md) |
| BD | **MariaDB 11.4.12** (corriendo) | Compatible MySQL. Ya operativa en el equipo |
| Web pública | **Blade + Tailwind 3 + Alpine.js** | Opción "simple y rápida" del prompt (§1). SSR nativo = SEO fuerte sin montar Nuxt |
| Panel admin | **Blade + Tailwind + Alpine + Livewire 3** | Interactividad (drag&drop, uploads, filtros) sin SPA separada ni build de Vue |
| Editor noticias | **TipTap** | Recomendado por el prompt (§11). Salida HTML limpia y saneable |
| Imágenes | **Intervention Image v3** | Redimensionado, WebP, miniaturas |
| Animación | **GSAP + ScrollTrigger + Lenis** | Ver [13_MOTION_AND_EFFECTS.md](13_MOTION_AND_EFFECTS.md) |

**Por qué NO Nuxt:** el prompt lo ofrece como "opción recomendada si será SEO fuerte". Blade renderiza en servidor igual de bien para SEO, y evita mantener dos aplicaciones, dos despliegues y una API intermedia. Para una inmobiliaria de un solo país, Nuxt es coste sin retorno. Si se quiere revisar, es la decisión más cara de cambiar después — ver Pregunta 1.

Detalle completo en [01_ARCHITECTURE.md](01_ARCHITECTURE.md).

---

## 6. Módulos (19)

Auth · Dashboard · Settings · Properties · PropertyImages · PropertyTypes · Locations · Agents · Leads · News · Pages · Contact · Seo · Media · Reports · Audit · WhatsApp · Compare · Motion(front)

Detalle en [01_ARCHITECTURE.md](01_ARCHITECTURE.md) §4.

---

## 7. Fases

11 fases (0 → 10), cada una con entregable verificable. Detalle y checklist vivo en [10_TODO_MASTER.md](10_TODO_MASTER.md).

```
F0  Preparación e infraestructura
F1  Configuración general (settings, logo, WhatsApp, SEO global)
F2  Propiedades (CRUD, tipos, ubicaciones, estados, slugs)
F3  Imágenes (subida múltiple, drag&drop, WebP, miniaturas)
F4  Web pública (home, listado, detalle, comparador, páginas estáticas)
F5  Leads y correo (formularios, SMTP, anti-spam, panel de leads)
F6  Noticias (categorías, TipTap, listado y detalle públicos)
F7  Agentes
F8  SEO, rendimiento y CAPA DE EFECTOS
F9  Reportes y auditoría
F10 Testing y despliegue
```

---

## 8. Preguntas abiertas

Están todas juntas, con recomendación, en [10_TODO_MASTER.md § Preguntas bloqueantes](10_TODO_MASTER.md#preguntas-bloqueantes). Las tres que más cambian el trabajo:

1. **¿Blade+Livewire o Laravel API + Nuxt 3?** — Recomendación: Blade+Livewire.
2. **Las 6 pantallas públicas sin diseño y todo el panel: ¿los derivo yo de los tokens, o esperas maquetas de Stitch?** — Recomendación: derivarlos, y que revises antes de cada una.
3. **Idioma y moneda:** ¿solo español? ¿USD y DOP a la vez con conversión, o precio en una sola moneda por propiedad?

---

## 9. Documentos de este paquete

| Doc | Contenido |
|---|---|
| [00_PROJECT_OVERVIEW.md](00_PROJECT_OVERVIEW.md) | Este documento |
| [01_ARCHITECTURE.md](01_ARCHITECTURE.md) | Estructura de carpetas, módulos, modelos, controladores, servicios, rutas |
| [02_DATABASE_SCHEMA.md](02_DATABASE_SCHEMA.md) | 23 tablas, campos, índices, relaciones, seeders |
| [03_ADMIN_PANEL.md](03_ADMIN_PANEL.md) | Pantallas del panel, roles y permisos |
| [04_PUBLIC_PAGES.md](04_PUBLIC_PAGES.md) | Cada página pública, sus datos y su mapeo al diseño |
| [05_MEDIA_UPLOADS.md](05_MEDIA_UPLOADS.md) | Pipeline de imágenes, validación, WebP, media manager |
| [06_EMAIL_AND_WHATSAPP.md](06_EMAIL_AND_WHATSAPP.md) | SMTP configurable, plantillas de correo, generación de links wa.me |
| [07_SEO.md](07_SEO.md) | Metas dinámicas, sitemap, robots, Schema.org, Open Graph |
| [08_TESTING.md](08_TESTING.md) | Estrategia de pruebas y cobertura por fase |
| [09_DEPLOYMENT.md](09_DEPLOYMENT.md) | Entorno local XAMPP y despliegue a producción |
| [10_TODO_MASTER.md](10_TODO_MASTER.md) | Checklist vivo + preguntas abiertas |
| [11_CHANGELOG.md](11_CHANGELOG.md) | Historial de cambios |
| [12_KNOWN_ISSUES.md](12_KNOWN_ISSUES.md) | Riesgos, conflictos y deuda conocida |
| [13_MOTION_AND_EFFECTS.md](13_MOTION_AND_EFFECTS.md) | Capa de efectos llamativos — requisito extra del cliente |
