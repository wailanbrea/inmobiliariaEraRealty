# 11 — CHANGELOG

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).
Versionado semántico. `0.x` = pre-lanzamiento.

---

## [0.6.0] — 2026-08-13 — Fase 3: imágenes

### Añadido

- Tabla `property_images` con `width`/`height` para evitar el salto de layout
- `ImageProcessingService`: orientación por EXIF y **borrado del EXIF**,
  reducción a 1920 px, original JPEG q85, WebP q82 y miniatura 400×300
- `PropertyImageService`: alta, orden, imagen principal, borrado en cascada
  y reparación de la invariante
- Componente de subida con arrastrar y soltar, progreso por archivo,
  reordenación con SortableJS, marcar principal y editar texto alternativo
- Subida por lotes de 3: subir 20 a la vez satura la conexión móvil
- 25 pruebas nuevas

### Corregido

- **`ensureSingleMain()` dejaba cero imágenes principales.** El modelo en
  memoria ya tenía `is_main = true`, así que tras la actualización masiva a
  `false` el `->update(true)` no lo veía sucio y no escribía nada. Resuelto
  actualizando por consulta.

### Decisiones

| Decisión | Motivo |
|---|---|
| El EXIF se aplica y se descarta | Las fotos vienen del móvil del agente con GPS: publicarlo filtraría la ubicación de inmuebles marcados como no exactos |
| La primera imagen es principal automáticamente | Si no, la ficha saldría sin portada |
| Al borrar la principal se promueve la siguiente | Sin eso, borrar la portada deja la ficha sin foto |
| Los ficheros se borran **después** de confirmar la transacción | Si falla la BD, no se pierden los archivos |
| El soft delete de una propiedad conserva las imágenes | La acción es reversible; borrar las fotos la haría irreversible |
| La subida exige que la propiedad exista | Evita subir 20 fotos y perderlas por un fallo de validación del formulario |

### Nota sobre el entorno

**Windows Defender borró el archivo de pruebas** por contener la cadena de un
webshell (`<?php system($_GET[...]) ?>`) usada para verificar que se rechaza.
El payload se compone ahora en tiempo de ejecución para que esa firma no exista
en disco. Si vuelve a pasar con otro test, es la misma causa.

### Pruebas

`php artisan test` → **243 pasadas, 454 aserciones**.
Uploader verificado en 375 y 1440 px: rejilla 2→4 columnas, modal dentro de
pantalla y con cierre por `Escape`, cero controles bajo 44 px.

### Añadido — biblioteca de medios (cierre de la Fase 3)

- Tabla `media_files` y `MediaLibraryService` con el mismo pipeline
  (orientar, descartar EXIF, WebP, miniatura)
- Gestor con vista de cuadrícula y de lista, búsqueda por nombre y por texto
  alternativo, filtro por contexto, subida múltiple y edición de alt/título
- Botón de copiar URL con confirmación visual
- **Borrado con verificación de uso previa**: antes de eliminar se muestra
  dónde está usado el archivo (configuración, noticias). Borrar un logo en
  uso deja un hueco en todas las páginas
- Comando `media:prune`: por defecto **solo lista**; borrar exige `--force`
  **y** confirmación interactiva, porque un huérfano puede ser un archivo
  legítimo subido por otra vía
- 22 pruebas más

---

## [0.5.0] — 2026-08-13 — Fase 2: propiedades

### Añadido — modelo de datos

- 9 tablas: `property_types`, `provinces`, `cities`, `sectors`, `amenities`,
  `agents`, `properties`, `property_translations`, `amenity_property`
- Enums `PropertyStatus`, `OperationType`, `PricePeriod`, `Currency`
- Traits `TranslatesJsonFields` y `HasSlug`
- `PropertyService`: creación, actualización, traducciones, código de
  referencia y transiciones de estado
- Seeders: 11 tipos, 21 amenidades, **32 provincias con 18 ciudades y 47 sectores**
- Factories con estados (`published`, `draft`, `translated`, `spanishOnly`…)

### Añadido — panel

- `PropertyPolicy` con reglas por rol
- Listado Livewire: buscador, 6 filtros, orden, paginación, acciones en lote,
  papelera y filtros persistidos en la URL
- Formulario en 9 pestañas con campos ES/EN sobre los textos
- Selects encadenados provincia → ciudad → sector, cargados por petición
- Publicar, pausar, cambiar estado, papelera y restaurar
- Vista previa con enlace firmado de 30 minutos
- 32 pruebas nuevas

### Corregido

- Las factories no resolvían: Laravel las busca asumiendo `App\Models` y los
  modelos viven en `app/Modules/*/Models`. Resuelto con
  `Factory::guessFactoryNamesUsing()`, válido para todos los modelos.
- `AuthorizesRequests` no venía en el controlador base de Laravel 12
- Las rutas de ubicaciones resolvían por slug en vez de por id
- **Un editor podía borrar propiedades.** Corregido: borrar queda en admin y
  super_admin

### Decisiones

| Decisión | Motivo |
|---|---|
| Textos en `property_translations`, no en JSON | El slug debe ser único por idioma y la búsqueda necesita `FULLTEXT` por idioma |
| Código de referencia por `MAX()`, no por conteo | Borrar una propiedad generaría un código repetido |
| El slug no se regenera al editar un título publicado | Cambiar la URL de una ficha indexada tira su posicionamiento |
| Vendido y alquilado siguen visibles, pero sin contacto ni indexación | El diseño los contempla y son prueba social |
| Un idioma sin título se ignora | Mejor eso que una ficha en blanco |
| Borrar reservado a admin | `manage_properties` habilita crear y editar, no destruir |
| Binding por `id` en el panel, por `slug` en público | Un borrador puede no tener slug |

### Pruebas

`php artisan test` → **192 pasadas, 354 aserciones**.
Listado y formulario verificados en 375 y 1440 px: la tabla scrollea dentro de
su contenedor, cero controles bajo 44 px, sin desbordamiento de página.

### Pendiente de la Fase 2

- CRUD de tipos de propiedad, ubicaciones y amenidades (el catálogo ya está
  sembrado; falta la pantalla para editarlo)
- `DemoDataSeeder` con datos de muestra

---

## [0.4.0] — 2026-08-13 — Fase 1: configuración general

### Añadido

- Tabla `settings` (42 claves) con `is_public`, `is_translatable` e `is_encrypted`
- `SettingsService` con caché invalidada al guardar y resolución por idioma
- `WhatsappService`: normalización de números, generación de `wa.me`,
  mensajes con variables, botón flotante
- `SettingsImageService`: subida de logo, logo oscuro, favicon e imagen OG
- `MailConfigService`: SMTP desde el panel, con precedencia sobre `.env`
- `MailTestService`: envío de prueba **antes** de guardar
- Regla de validación `RealImage` (capa 3 de `docs/05_MEDIA_UPLOADS.md`)
- Panel de configuración con 4 pestañas: General, WhatsApp, Correo, SEO
- Componentes de formulario: `x-admin.field`, `x-admin.translatable-field`
  (pestañas ES/EN), `x-admin.image-field` (con vista previa)
- Componente `x-whatsapp-float`
- Helpers `setting()` y `whatsapp()`
- Layout público conectado a datos reales: logo, contacto, redes, pie
- 76 pruebas nuevas

### Corregido

- **Un `.php` renombrado a `.png` reventaba el panel con un error 500.**
  Las reglas `image`/`mimes` de Laravel se fían del tipo declarado; la capa
  que mira el contenido real estaba documentada pero no implementada.
  Añadida `RealImage`, que valida en memoria con `getimagesizefromstring()`.
- **SVG con `<script>` o `onload` se aceptaban como logo.** Ahora se rechazan
  en validación y, además, se sanean al guardar.
- **Objetivo táctil de las casillas: 16 px.** Agrandar la casilla se ve mal;
  lo que crece es su etiqueta. Regla base con `:has()` que cubre todas las
  casillas del proyecto, presentes y futuras.

### Decisiones

| Decisión | Motivo |
|---|---|
| El enlace de WhatsApp **no** se almacena | Un enlace guardado se desincroniza en cuanto cambie el número |
| `ImageManager` inyectado, sin `intervention/image-laravel` | Evita una dependencia entera solo por un facade |
| El seeder no crea claves desde el formulario | Un typo en un campo no debe ensuciar la tabla con claves fantasma |
| Contraseña SMTP cifrada y nunca devuelta al formulario | Vacía = conservar la actual |
| Si el correo de prueba falla, **no se guarda nada** | Exigencia del prompt maestro §7 |
| Tasa USD→DOP editable a mano | El sitio opera en ambas monedas; una fuente automática de tasas no estaba en el alcance |

### Pruebas

`php artisan test` → **133 pasadas, 251 aserciones**.
Responsive verificado en 375, 768 y 1440 px en las cuatro pantallas de
configuración: cero controles por debajo de 44 px y sin scroll horizontal.

### Pendiente

- Recuperación de contraseña del panel
- Middleware que aplique `MailConfigService::apply()` en cada envío (Fase 5,
  cuando existan correos reales que enviar)
- Reinicio de Apache para tomar los límites de subida (requiere elevación)

---

## [0.3.0] — 2026-08-13 — Sitio bilingüe español / inglés

El cliente confirma que el sitio será en ambos idiomas con selector visible.
Decisión tomada **antes** de crear las tablas de contenido: hacerlo tras la
Fase 2 habría obligado a migrar el esquema y reescribir todas las vistas.

### Añadido

- `docs/15_I18N.md` — plan completo: qué se traduce, estrategia de URLs,
  almacenamiento, SEO bilingüe e impacto por fase
- `config/locales.php` — idiomas, prefijos y **segmentos de URL traducidos**
- `App\Support\Locale` — resolución de segmentos, URL alternativa y `hreflang`
- Middleware `SetLocale` — el idioma lo fija la URL, no la cookie ni el navegador
- Helper global `lroute()` para rutas públicas con idioma
- 22 rutas públicas registradas en ambos idiomas
- `lang/es` y `lang/en` (`common.php`, `home.php`)
- **Layout público**: header sticky, nav de escritorio, drawer móvil, footer de
  4 columnas, `hreflang`, `canonical` y `og:locale`
- Componente `<x-language-switcher>` (variantes escritorio y móvil)
- Home provisional y plantilla de secciones pendientes
- 29 pruebas nuevas de i18n

### Corregido

- Claves de traducción sin prefijo de archivo en `PlaceholderController`:
  las páginas internas mostraban `nav.invest` en crudo. Detectado en navegador,
  no por las pruebas, porque estas solo miraban el layout.
  Añadida una prueba que recorre las 20 URLs públicas y **falla si queda
  cualquier clave sin resolver**.
- `POST /admin/login` quedaba con nombre de ruta vacío (`admin.`)

### Decisiones

| Decisión | Motivo |
|---|---|
| Español sin prefijo, inglés en `/en` | El prompt maestro fija `/propiedades`, `/invierte`… como requisito, y el público principal es dominicano |
| Segmentos traducidos (`/en/properties`, no `/en/propiedades`) | Un comprador anglófono busca *"properties for sale punta cana"*; tener la palabra en la URL es el motivo de ser bilingüe |
| Tablas `*_translations` para propiedades y noticias | El slug debe ser único por idioma y la búsqueda necesita `FULLTEXT` por idioma; con JSON no se puede |
| JSON para catálogos, agentes y settings | No llevan slug ni búsqueda por texto: la simplicidad gana |
| Sin autodetección por IP ni `Accept-Language` | Google penaliza la redirección automática al rastrear |
| Panel admin solo en español | El equipo es dominicano; lo bilingüe es el *contenido*, no la herramienta |
| Respaldo al español si falta traducción | Mejor una ficha en español que un hueco |

### Pruebas

`php artisan test` → **43 pasadas**.
Responsive del layout público verificado en 375, 768 y 1440 px, incluido el
cambio de idioma desde el menú móvil.

### Pendiente

- Los slugs traducidos de propiedades y noticias se resuelven en las Fases 2 y 6
  (`Locale::alternateUrl` ya tiene el punto de enganche vía `translatedSlug()`)
- Sitemap bilingüe: Fase 8

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
