# 10 — TODO maestro

**Actualizado:** 2026-08-13 · **Fase actual:** 2 · **Progreso: 34 / 218 tareas** · **133 pruebas en verde**

> Este documento se actualiza **después de cada módulo**, junto con [11_CHANGELOG.md](11_CHANGELOG.md). Es obligación del prompt maestro (§2).

> ## ⚠️ Condición de cierre de TODAS las fases
> Ninguna pantalla se da por terminada sin pasar la verificación responsive en
> **375 · 768 · 1024 · 1440 px**, con objetivos táctiles de 44 px y sin scroll
> horizontal. Requisito explícito del cliente: la web y el panel deben funcionar
> al 100 % desde PC, celular y tableta.
> Método y registro en [14_RESPONSIVE.md](14_RESPONSIVE.md).

---

## Preguntas bloqueantes

Se necesita respuesta a las 4 primeras antes de empezar la Fase 0. Las demás se pueden responder sobre la marcha, pero cuanto antes, mejor.

### 🔴 1. ¿Blade + Livewire, o Laravel API + Nuxt 3?

El prompt maestro ofrece ambas y dice que, si no está decidido, se use Blade.

**Recomendación: Blade + Tailwind + Alpine + Livewire.** Una sola aplicación, un solo despliegue, SEO server-side nativo, y cubre toda la interactividad pedida (uploads con drag&drop, filtros en vivo, comparador). Nuxt añadiría entre un 30 % y un 40 % de tiempo para un beneficio que aquí no se materializa.

Es la decisión **más cara de revertir**. Cambiar de opinión en la Fase 5 significa rehacer todo el front.

---

### 🔴 2. Las 6 pantallas públicas sin diseño y todo el panel: ¿los derivo yo?

Falta el diseño de Invierte, Sobre nosotros, Infórmate (×2), Contáctanos, Publica tu propiedad, más las ~20 pantallas del panel.

**Opciones:**
- **(a)** Los derivo de `estate_elite/DESIGN.md`, y te enseño cada pantalla antes de implementarla. ← recomendada
- **(b)** Esperas a generar más pantallas en Stitch y me las pasas.
- **(c)** Mezcla: tú generas las públicas, yo derivo el panel (que es donde menos importa el lucimiento visual y más la densidad de datos).

---

### 🔴 3. Idioma y moneda

- ¿El sitio es **solo en español**, o hará falta inglés para inversores extranjeros?
- ¿Los precios van en **USD, DOP, o ambos**? Si son ambos: ¿cada propiedad en su moneda (sencillo), o con **conversión y selector** en el buscador (requiere tabla de tasas y una fuente que las actualice — trabajo no contemplado en el prompt)?

Ambas decisiones afectan al esquema y a las rutas. Cambiarlas después de la Fase 4 obliga a rehacer las vistas.

---

### 🔴 4. ¿Empiezo a implementar?

El prompt maestro (§27) exige entregar este plan y **esperar confirmación**. Este documento es esa entrega.

---

### 🟠 5. PHP 8.2.33 vs 8.3+

Instalado: 8.2.33. El prompt pedía 8.3+. Laravel 12 requiere `^8.2`, así que **funciona tal cual**.

**Recomendación: quedarse en 8.2.33.** Actualizar implica tocar una instalación reconstruida a mano tras el incidente del 06/08/2026, con riesgo real y beneficio nulo.

---

### 🟠 6. Usuario administrador inicial

¿Qué email para el `super_admin` del seeder?

La contraseña **no me la envíes por chat**. El seeder generará una aleatoria y la mostrará una sola vez en consola al ejecutar `php artisan db:seed`, con obligación de cambiarla en el primer inicio de sesión.

---

### 🟠 7. Datos reales del negocio

Todo lo de las maquetas es ficticio. Necesito los reales antes de producción:

- Nombre exacto, logo (SVG o PNG a alta resolución), favicon
- Teléfono, **número real de WhatsApp**, email de contacto, email que recibe los formularios
- Dirección física y horario
- Redes sociales (Facebook, Instagram, YouTube, TikTok, LinkedIn)
- Agentes reales: nombre, cargo, foto, teléfono, email
- **¿"ERA Realty RD" es franquicia de ERA Real Estate?** Si lo es, hay manual de marca y sus reglas mandan sobre `DESIGN.md`. Conviene saberlo antes de maquetar 10 pantallas.

Mientras tanto, el seeder usa los valores del diseño para que el sitio arranque.

---

### 🟠 8. Legal y datos personales

- ¿Cuánto tiempo se conservan los leads? (propuesta: 24 meses, con purga automática)
- ¿Hace falta aviso de cookies? (si se activa Google Analytics, sí)
- Los textos de `/privacidad` y `/terminos`: ¿los aporta el cliente, o preparo un borrador base que luego revise un abogado?

---

### 🟡 9. ¿Renombro la carpeta a `era-realty`?

El espacio en `Era Realty` complica comandos en Windows. Renombrar ahora cuesta minutos; después de instalar Laravel, cuesta horas. **Recomendación: renombrar ahora.**

---

### 🟡 10. Preguntas de alcance menor

- **Comparador:** ¿3 o 4 propiedades? (diseño muestra 3 + hueco; prompt dice "3 o 4"). Propuesta: 4.
- **Mapas:** ¿Google Maps (requiere API key y facturación) o Leaflet + OpenStreetMap (gratis)? Propuesta: Leaflet.
- **Confirmación al cliente:** ¿se le envía correo de confirmación al que rellena un formulario? Propuesta: sí, activable.
- **Blog:** ¿los agentes escriben noticias, o solo el admin?
- **Publica tu propiedad:** ¿el formulario permite subir fotos? Propuesta: sí, máximo 5.

---

## FASE 0 — Preparación (18/20) ✅ prácticamente completa

- [x] **`git init` + `.gitignore` + primer commit**
- [x] Verificar extensiones PHP: **gd ✅**, exif faltaba → **activada**
- [x] Respaldar `php.ini` → `php.ini.bak-2026-08-13-era-realty` (verificado idéntico)
- [x] Ajustar `php.ini`: upload 2M→**10M**, post 8M→**60M**, files 20→**30**, exec 30→**120 s**
- [x] Crear BD `era_realty` y `era_realty_testing` (utf8mb4_unicode_ci)
- [x] `composer create-project laravel/laravel:^12.0` → **Laravel 12.66.0**
- [x] Paquetes: Livewire 3, Intervention Image 3, spatie/permission, spatie/sitemap, purifier, Pest 3
- [x] Dependencias npm: Tailwind 4, Alpine, GSAP, Lenis, SortableJS
- [x] Configurar `.env` (MySQL, locale es, zona América/Santo Domingo) + `.env.example` sin secretos
- [x] `php artisan key:generate`
- [x] **Tokens de `DESIGN.md` → `resources/css/app.css`** (Tailwind 4, CSS-first)
- [x] Configurar Vite → build en **14,65 KB gzip**
- [x] Crear estructura `app/Modules/` (18 módulos)
- [x] `php artisan storage:link` + subcarpetas de medios
- [x] Autenticación admin: login, recordar sesión, doble rate limit, usuario desactivado
- [x] Roles y permisos (spatie) + `RolePermissionSeeder` (4 roles, 11 permisos)
- [x] `AdminUserSeeder` con contraseña aleatoria mostrada una sola vez
- [x] Layout del panel + dashboard + **verificación responsive en 375/768/1440**
- [ ] Recuperación de contraseña (movida a Fase 1, junto a la config de correo)
- [ ] VirtualHost `era-realty.test` (pendiente: requiere editar `hosts` y `httpd.conf`)

**Pendiente de decisión:** renombrado de la carpeta (Pregunta 9) — no ejecutado
porque es el directorio de trabajo de la sesión.

## FASE 1 — Configuración general (16/18) ✅ prácticamente completa

- [x] Migración + modelo `Setting` con cast por `type`
- [x] `SettingsService` con caché e invalidación
- [x] `SettingsSeeder` (**42 claves**, idempotente)
- [x] Helpers `setting()` y `whatsapp()` (en vez de middleware: más simple en Blade)
- [x] Layout admin (sidebar, topbar, responsive)
- [x] Pantalla Configuración → General
- [x] Subida de logo, logo oscuro y favicon (+ imagen OG)
- [x] Teléfono, email, dirección, horario
- [x] Redes sociales
- [x] Pestaña WhatsApp + `WhatsappService`
- [x] Vista previa del link `wa.me` en la pantalla
- [x] Pestaña Correo + `MailConfigService` (contraseña cifrada)
- [x] Botón "Enviar correo de prueba" con validación previa al guardado
- [x] Pestaña SEO global
- [x] Campos traducibles ES/EN con pestañas de idioma
- [x] Tasa de cambio USD→DOP editable
- [x] Regla `RealImage` + saneado de SVG
- [x] Tests de settings, WhatsApp, correo e imágenes (76 pruebas)
- [ ] Dashboard con métricas reales (espera a la Fase 2: aún no hay propiedades)
- [ ] Recuperación de contraseña del panel

## FASE 2 — Propiedades (0/22)

- [ ] Migraciones: property_types, provinces, cities, sectors, amenities, agents, properties, amenity_property
- [ ] Modelos + relaciones + enums
- [ ] Traits `HasSlug`, `Auditable`
- [ ] Seeders: tipos, 32 provincias + ciudades/sectores, amenidades
- [ ] CRUD de tipos de propiedad
- [ ] CRUD de ubicaciones (árbol de 3 niveles)
- [ ] CRUD de amenidades
- [ ] `PropertyService` (slug único, referencia, transiciones de estado)
- [ ] Listado admin con filtros Livewire y acciones en lote
- [ ] Formulario en 10 pestañas
- [ ] Selects encadenados provincia→ciudad→sector
- [ ] Selector de coordenadas en mapa
- [ ] Estados y transiciones
- [ ] Destacada / inversión / proyecto
- [ ] Guardar borrador · publicar · pausar
- [ ] Soft delete + papelera + restaurar
- [ ] Pestaña SEO con vista previa de Google
- [ ] Vista previa pública con firma temporal
- [ ] Asignación de agente
- [ ] Policies (`agent` solo ve las suyas)
- [ ] Factories + `DemoDataSeeder`
- [ ] Tests de propiedades

## FASE 3 — Imágenes (0/16)

- [ ] Migración `property_images` + `media_files`
- [ ] `ImageProcessingService` (validación de 4 capas, EXIF, resize, WebP, thumb)
- [ ] Job en cola `ProcessPropertyImage`
- [ ] `PropertyImageService` (orden, principal, borrado en cascada)
- [ ] Componente Livewire de subida
- [ ] Drag & drop + zona resaltada
- [ ] Progreso por archivo (máx 3 concurrentes)
- [ ] Reordenar con SortableJS
- [ ] Marcar principal (invariante de una sola)
- [ ] Editar alt y título en línea
- [ ] Eliminar con confirmación + borrado de ficheros
- [ ] Límite de 30 y errores concretos por archivo
- [ ] Media manager general + modal reutilizable
- [ ] Verificación de uso antes de borrar
- [ ] Comando `media:prune --dry-run`
- [ ] Tests de imágenes (incluido `.php` renombrado y EXIF GPS)

## FASE 4 — Web pública (0/34)

- [ ] Layout público + header sticky + footer desde settings
- [ ] Menú móvil
- [ ] Componentes Blade (12)
- [ ] **Home:** hero, buscador de cristal, destacadas, inversión, estadísticas, teaser nosotros, noticias, CTA
- [ ] `content_sections` para textos del home
- [ ] **Listado:** sidebar de filtros, resultados, orden, paginación, filtros en URL
- [ ] `PropertySearchService`
- [ ] Estados vacíos
- [ ] **Detalle:** galería + lightbox, meta-grid, descripción, amenidades bento, mapa, similares
- [ ] Registro de vistas (deduplicado por hash de IP)
- [ ] Barra móvil de contacto
- [ ] **Comparador:** localStorage, hasta 4, tabla, resaltar diferencias, compartir por URL
- [ ] Páginas Invierte, Sobre nosotros, Contáctanos, Publica tu propiedad
- [ ] Páginas legales
- [ ] 404 y 500 personalizadas
- [ ] Botón flotante de WhatsApp
- [ ] Efectos base (hover, reveals, sticky, entradas)
- [ ] Responsive verificado en 5 anchos
- [ ] Tests de páginas públicas

## FASE 5 — Leads y correo (0/17)

- [ ] Migración `leads`
- [ ] `LeadService`
- [ ] 5 formularios públicos
- [ ] Validación + FormRequests
- [ ] Honeypot + time-trap + rate limit
- [ ] Registro de IP, user agent y referrer
- [ ] 8 Mailables + plantillas Markdown
- [ ] Colas con reintentos
- [ ] **El lead se guarda antes de intentar el envío**
- [ ] Correo de confirmación al cliente (activable)
- [ ] Listado admin de leads con filtros
- [ ] Detalle de lead + acciones directas
- [ ] Cambio de estado y asignación
- [ ] Notas internas
- [ ] Exportación CSV
- [ ] `whatsapp_clicks` + endpoint de registro
- [ ] Tests de leads y correo

## FASE 6 — Noticias (0/18)

- [ ] Migraciones `news_categories`, `news_posts`
- [ ] CRUD de categorías
- [ ] `NewsService` + saneado con purifier
- [ ] Editor TipTap configurado
- [ ] Imagen destacada desde media manager
- [ ] Slug automático + extracto
- [ ] Estados (borrador, publicada, programada, archivada)
- [ ] Publicación programada vía scheduler
- [ ] Autoguardado cada 60 s
- [ ] Vista previa
- [ ] SEO por noticia
- [ ] Tiempo de lectura calculado
- [ ] Listado admin con filtros
- [ ] `/informate`: destacada, grid, categorías, buscador FULLTEXT, paginación
- [ ] `/informate/{slug}`: contenido, compartir, relacionadas, CTA
- [ ] Progreso de lectura
- [ ] Contador de vistas
- [ ] Tests de noticias (incluido saneado XSS)

## FASE 7 — Agentes (0/9)

- [ ] Migración `agents`
- [ ] CRUD admin
- [ ] Subida de foto con recorte
- [ ] Orden por drag&drop
- [ ] Asignación a propiedades
- [ ] Tarjeta en el detalle
- [ ] Sección de equipo en Sobre nosotros
- [ ] WhatsApp y email por agente
- [ ] Tests

## FASE 8 — SEO, rendimiento y efectos (0/24)

- [ ] `SeoService` con cascada de fallbacks
- [ ] Componente `<x-seo-meta>`
- [ ] `SchemaOrgService` (7 tipos de JSON-LD)
- [ ] Open Graph + Twitter Cards
- [ ] Sitemap índice + 4 sub-sitemaps
- [ ] `robots.txt` editable
- [ ] Tabla `redirects` + 301 al cambiar slug
- [ ] Canonical en listados filtrados
- [ ] `noindex` en no publicadas
- [ ] Google Analytics 4 configurable
- [ ] **Capa de efectos completa:**
- [ ] Lenis + header adaptativo + barra de progreso
- [ ] Sistema de reveals con stagger
- [ ] Parallax del hero (3 capas) + Ken Burns
- [ ] Entrada del título por líneas
- [ ] Contadores animados
- [ ] Lightbox con transición compartida
- [ ] FLIP del comparador y del cambio de vista
- [ ] Transiciones de página
- [ ] Cursor magnético (solo puntero fino)
- [ ] `prefers-reduced-motion` en todo + carga dinámica del módulo
- [ ] Optimización de imágenes y lazy loading
- [ ] Medición Lighthouse ≥ 90 móvil
- [ ] Tests de SEO

## FASE 9 — Reportes y auditoría (0/11)

- [ ] Migración `audit_logs` + `AuditService` + Observers
- [ ] Exclusión de campos sensibles del log
- [ ] Registro de las 13 acciones sensibles
- [ ] Listado de auditoría con filtros
- [ ] Detalle con diff visual
- [ ] `property_views` + reporte de más vistas
- [ ] Reportes de propiedades, leads y WhatsApp
- [ ] Gráficos del dashboard
- [ ] Rango de fechas + exportación CSV
- [ ] Comandos de poda programados
- [ ] Tests de auditoría

## FASE 10 — Testing y despliegue (0/16)

- [ ] Suite completa verde
- [ ] Cobertura ≥ 70 % global / ≥ 90 % en Services
- [ ] Pruebas manuales: responsive en 5 anchos
- [ ] Navegadores: Chrome, Firefox, Edge, Safari iOS, Chrome Android
- [ ] Efectos a 60 fps + verificación con reduced-motion
- [ ] **Verificación sin JavaScript: el contenido es legible**
- [ ] Accesibilidad: teclado, contraste, NVDA
- [ ] Correo real con SMTP de producción
- [ ] WhatsApp real desde un móvil
- [ ] Subida de 30 fotos desde móvil con conexión lenta
- [ ] `.env.example` final
- [ ] Optimización de producción (config/route/view cache)
- [ ] Cabeceras de seguridad + PHP desactivado en storage
- [ ] Respaldos automáticos + **restauración probada**
- [ ] Sitemap enviado a Search Console
- [ ] Checklist de [09_DEPLOYMENT.md](09_DEPLOYMENT.md) §7 completo

---

## Criterios de aceptación (prompt maestro §25)

**0 / 33 cumplidos** — se marcan al verificarse, no al implementarse.

- [ ] El admin puede iniciar sesión
- [ ] El admin puede cambiar el logo
- [ ] El admin puede cambiar el favicon
- [ ] El admin puede editar el teléfono
- [ ] El admin puede editar el WhatsApp
- [ ] El sistema genera el link de WhatsApp correctamente
- [ ] El admin puede configurar el correo
- [ ] El admin puede enviar un correo de prueba
- [ ] Los formularios guardan leads
- [ ] Los formularios envían correos
- [ ] El admin puede crear propiedades
- [ ] El admin puede subir múltiples imágenes
- [ ] El admin puede reordenar imágenes
- [ ] El admin puede elegir la imagen principal
- [ ] El admin puede eliminar imágenes
- [ ] Las imágenes se optimizan
- [ ] El admin puede crear noticias fácilmente
- [ ] Las noticias tienen imagen destacada
- [ ] Las noticias tienen SEO
- [ ] La web pública muestra propiedades reales
- [ ] La web pública muestra noticias reales
- [ ] El buscador funciona
- [ ] Los filtros funcionan
- [ ] El detalle de propiedad funciona
- [ ] El botón de WhatsApp funciona
- [ ] La web es responsive
- [ ] El SEO básico está implementado
- [ ] Hay sitemap
- [ ] Hay robots.txt
- [ ] Hay auditoría básica
- [ ] El código está documentado
- [ ] El TODO está actualizado
- [ ] El CHANGELOG está actualizado

**Extra del cliente:**
- [ ] La web tiene efectos llamativos que **no** sacrifican rendimiento ni accesibilidad
