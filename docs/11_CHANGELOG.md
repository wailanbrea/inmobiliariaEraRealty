# 11 — CHANGELOG

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).
Versionado semántico. `0.x` = pre-lanzamiento.

---

## [Sin publicar]

Nada implementado todavía. El proyecto está en Fase 0, a la espera de confirmación del plan.

---

## [0.1.0] — 2026-08-13 — Planificación

Primera entrega obligatoria del prompt maestro (§27): documentación, esquema y plan **antes** de escribir código funcional.

### Añadido

- `docs/00_PROJECT_OVERVIEW.md` — visión general, stack, cobertura del diseño, fases
- `docs/01_ARCHITECTURE.md` — estructura de carpetas, 19 módulos, 20 modelos, 24 controladores, 18 services, mapa de rutas
- `docs/02_DATABASE_SCHEMA.md` — 23 tablas con campos, índices, relaciones y seeders
- `docs/03_ADMIN_PANEL.md` — 14 pantallas del panel y matriz de permisos por rol
- `docs/04_PUBLIC_PAGES.md` — 11 rutas públicas mapeadas al diseño, con sus datos y origen administrable
- `docs/05_MEDIA_UPLOADS.md` — pipeline de imágenes, validación en 4 capas, WebP, media manager
- `docs/06_EMAIL_AND_WHATSAPP.md` — SMTP configurable desde panel, 8 correos, generación de links `wa.me`
- `docs/07_SEO.md` — cascada de metadatos, JSON-LD, sitemap, presupuesto de rendimiento
- `docs/08_TESTING.md` — estrategia de pruebas por módulo y checklist manual
- `docs/09_DEPLOYMENT.md` — entorno local verificado, despliegue, respaldos, checklist de producción
- `docs/10_TODO_MASTER.md` — 214 tareas en 11 fases + 10 preguntas abiertas
- `docs/11_CHANGELOG.md` — este documento
- `docs/12_KNOWN_ISSUES.md` — 15 riesgos y conflictos identificados
- `docs/13_MOTION_AND_EFFECTS.md` — capa de efectos llamativos (requisito extra del cliente)

### Analizado

- `Prompt maestro para desarrollar página web inmobiliaria completa.md` (1.492 líneas, 28 secciones)
- `stitch_era_realty_rd_premium_redesign/estate_elite/DESIGN.md` (design tokens)
- `stitch_era_realty_rd_premium_redesign/inicio_era_realty_rd/code.html` (405 líneas)
- `stitch_era_realty_rd_premium_redesign/propiedades_era_realty_rd/code.html` (459 líneas)
- `stitch_era_realty_rd_premium_redesign/detalle_de_propiedad_era_realty_rd/code.html` (367 líneas)
- `stitch_era_realty_rd_premium_redesign/comparador_era_realty_rd/code.html` (426 líneas)

### Verificado en la máquina

- PHP 8.2.33 ZTS x64 con OPcache
- Composer 2.8.11
- Node 24.4.0 / npm 11.4.2
- MariaDB 11.4.12 corriendo (`mysqld` PID 5192)
- Apache corriendo (PIDs 4696, 7284)
- No existe proyecto Laravel previo — se parte de cero
- **El proyecto no está bajo control de versiones** (riesgo 🔴 #7)

### Decisiones tomadas

| Decisión | Alternativa descartada | Motivo |
|---|---|---|
| Blade + Alpine + Livewire | Laravel API + Nuxt 3 | Una sola app, un solo deploy, SEO server-side nativo. El prompt lo indica como opción por defecto |
| Tailwind compilado con Vite | CDN como en las maquetas | El CDN sirve Tailwind entero, con JIT en cliente y FOUC. Compilado: ~12 KB |
| `amenities` como tabla | `amenities_json` (sugerido por el prompt) | El diseño filtra por amenidad; un JSON no se indexa ni es administrable |
| Link de WhatsApp derivado | `contact_whatsapp_link` almacenado | Un link guardado se desincroniza del número al primer cambio |
| Hash de IP en `property_views` | IP en claro | Solo se necesita deduplicar; guardar la IP sería recolectar un dato personal sin uso |
| PHP 8.2.33 | Actualizar a 8.3 | Laravel 12 requiere `^8.2`. Tocar esta instalación reconstruida a mano tiene riesgo real y beneficio nulo |
| Efectos por sofisticación | Efectos estridentes | `DESIGN.md` pide "quiet luxury"; el movimiento debe reforzar la percepción de precio alto, no contradecirla |

### Pendiente

Confirmación del cliente sobre las 10 preguntas de [10_TODO_MASTER.md](10_TODO_MASTER.md#preguntas-bloqueantes) — en particular las 4 marcadas 🔴 — antes de comenzar la Fase 0.

---

## Formato para futuras entradas

```
## [0.x.0] — AAAA-MM-DD — Fase N: Nombre

### Añadido       funcionalidad nueva
### Modificado    cambios en lo existente
### Corregido     errores resueltos
### Eliminado     funcionalidad retirada
### Seguridad     cambios con implicación de seguridad
### Archivos      creados / modificados
### Comandos      ejecutados
### Pruebas       realizadas y su resultado
### Pendiente     lo que queda de la fase
```
