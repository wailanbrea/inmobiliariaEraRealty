# 11 — CHANGELOG

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).
Versionado semántico. `0.x` = pre-lanzamiento.

---

## [0.2.0] — 2026-08-13 — Fase 0: base del proyecto

Plan confirmado por el cliente: **Blade + Livewire**, diseños derivados de los
tokens existentes, **USD y DOP** ambos.

### Añadido

- Laravel 12.66.0 sobre PHP 8.2.33 / MariaDB 11.4.12
- Paquetes: Livewire 3.5, Intervention Image 3.9, spatie/laravel-permission 6.9,
  spatie/laravel-sitemap 7.3, mews/purifier 3.4, Pest 3
- Design tokens de `estate_elite/DESIGN.md` trasladados a `resources/css/app.css`
  (Tailwind 4, configuración CSS-first)
- Estructura `app/Modules/` con los 18 módulos
- Autenticación del panel: login, recordar sesión, cierre de sesión,
  bloqueo de usuarios desactivados, doble limitador de intentos
- `RolePermissionSeeder`: 4 roles, 11 permisos
- `AdminUserSeeder`: contraseña aleatoria mostrada una sola vez, con
  `must_change_password` para forzar el cambio
- Layout del panel con sidebar/drawer y layout de acceso
- Dashboard provisional con el alcance de fases visible
- 14 pruebas Pest (37 aserciones), todas en verde
- `docs/14_RESPONSIVE.md` — requisito de responsive como condición de cierre de cada fase

### Modificado

- `php.ini` (**respaldado antes** en `php.ini.bak-2026-08-13-era-realty`):
  extensión `exif` activada; `upload_max_filesize` 2M→10M; `post_max_size` 8M→60M;
  `max_file_uploads` 20→30; `max_execution_time` 30→120

### Corregido

- **Colisión de la escala de espaciado con `max-w-*`.** Los nombres `xs/sm/md/lg/xl`
  de `DESIGN.md` ganaban sobre la escala de anchos de Tailwind 4: `max-w-sm` valía
  16 px en vez de 24 rem. Habría roto contenedores en todo el sitio. Resuelto
  declarando el namespace `--max-width-*` completo.
- **Objetivos táctiles insuficientes** (botones 40 px, inputs 42 px). Regla base
  bajo `@media (pointer: coarse)` que lleva todo control a 44 px.
- **Alternancia frágil del drawer**: dos clases de transform coexistiendo.
  Sustituido por ternario.

### Seguridad

- `.env` fuera del control de versiones desde el primer commit (verificado)
- Mensajes de login genéricos: no permiten enumerar correos existentes
- Dos limitadores con funciones distintas: 5 intentos por correo+IP con mensaje
  útil, y 20 por IP como tope contra el rociado de correos
- El panel completo va con `noindex, nofollow`
- Contraseña del administrador nunca escrita en código ni en el repositorio

### Decisiones

| Decisión | Alternativa | Motivo |
|---|---|---|
| **Tailwind 4** (CSS-first) | Tailwind 3 con `tailwind.config.js`, como decía el plan | Es lo que trae el scaffolding de Laravel 12; pelearse con el default costaba más de lo que aportaba |
| Contraseña admin aleatoria en consola | Contraseña fija en el seeder | Una contraseña en el repositorio acaba llegando a producción |

### Comandos ejecutados

```
git init
composer create-project laravel/laravel:^12.0
composer require livewire/livewire intervention/image spatie/laravel-permission spatie/laravel-sitemap mews/purifier
composer require --dev pestphp/pest pestphp/pest-plugin-laravel
npm install -D tailwindcss@4 @tailwindcss/forms @tailwindcss/typography
npm install alpinejs gsap lenis sortablejs
php artisan key:generate · storage:link · migrate · db:seed
npx vite build
php artisan test
./vendor/bin/pint
```

### Pruebas

`php artisan test` → **14 pasadas, 37 aserciones**.
Responsive verificado por medición en navegador en 375, 768 y 1440 px para
login y dashboard. Registro en [14_RESPONSIVE.md](14_RESPONSIVE.md) §4.

### Pendiente

- Recuperación de contraseña (se hará en Fase 1, junto a la configuración de correo)
- VirtualHost `era-realty.test`
- **Apache necesita reinicio** para tomar los nuevos límites de `php.ini`
- Sitio bilingüe español/inglés: sin decidir. Bloquea la Fase 2

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
