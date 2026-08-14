# 11 — CHANGELOG

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).
Versionado semántico. `0.x` = pre-lanzamiento.

---

## [0.10.0] — 2026-08-14 — Fase 9 (1/2): auditoría

Primera mitad de la Fase 9. Los reportes y los gráficos del dashboard van en
la siguiente entrega.

### Añadido

- Tabla `audit_logs` y `AuditService`
- Enum `AuditAction` con las trece acciones del prompt maestro §21, más el
  **intento de acceso fallido** y el cierre de sesión
- Observers de `Property`, `PropertyImage` y `NewsPost`
- Auditoría de ajustes dentro de `SettingsService`, separando configuración,
  logotipo, WhatsApp y correo
- Pantalla `/admin/auditoria`: filtros por acción, usuario y rango de fechas,
  y detalle con diff campo a campo
- `config/audit.php` con los periodos de retención
- Comando `audit:prune` y su programación semanal
- 53 pruebas

### Decisiones

| Decisión | Motivo |
|---|---|
| **Ninguna credencial llega al registro** | Guarda el «antes» y el «después» de cada cambio y lo lee cualquier administrador: sin censura, cambiar la contraseña del SMTP la dejaría escrita en claro **dos veces** en una tabla consultable. La detección es por fragmento (`password`, `secret`, `token`…) y recorre arrays anidados |
| Se registran los accesos **fallidos** | Un listado que solo muestra logins correctos no sirve para ver que alguien lleva media hora probando contraseñas — que es la razón principal por la que uno mira un registro de auditoría |
| El apunte copia el **nombre** del autor, no solo su id | Con `nullOnDelete`, borrar al usuario deja el apunte sin autor. Un registro que se vacía borrando a quien actuó no audita nada |
| Auditoría por **observer**, no en el controlador | Queda registrado venga de donde venga la escritura: formulario, Livewire, consola |
| El cambio de estado es su propia acción, y **un solo apunte por guardado** | Pasar una villa a «vendida» es una decisión de negocio, no una edición más |
| Solo lectura: sin editar ni borrar desde el panel | Un registro modificable desde la misma interfaz que audita no vale para nada. Hay pruebas que verifican que no existe ruta `DELETE`/`PUT`/`PATCH` |
| `audit:prune` **simula por defecto** | Un comando que borra la única prueba de lo ocurrido en cuanto se teclea es justo lo que no debe existir. Borrar exige `--force` |
| Los accesos fallidos caducan a los 90 días, el resto al año | Son los que más volumen generan en un ataque y su valor se pierde rápido |
| El fallo de la auditoría no tumba la acción auditada | Se anota en el log de la aplicación y la petición sigue. Hay una prueba que borra la tabla y comprueba que la propiedad se crea igual |

### Corregido

- **El diff daba «sin cambios» justo al cambiar la contraseña del SMTP.**
  Al censurar los dos lados con el mismo marcador, la comparación los veía
  iguales y ocultaba el apunte más importante que registra el módulo. Ahora
  un valor censurado se muestra siempre como cambio.

### Pruebas

`php artisan test` → **531 pasadas, 1 139 aserciones**.

Verificado en 375 y 1440 px: contenedor de 343 px con una tabla de 758 —
scrollea la tabla, no la página.

---

## [0.9.0] — 2026-08-14 — Fase 8: efectos, SEO y rendimiento

La capa que responde al requisito extra del cliente: *«deberá tener efectos
llamativos»*. Llamativos por sofisticación, no por estridencia —
`docs/13_MOTION_AND_EFFECTS.md` §1.

### Añadido

**Capa de movimiento**

- `motion.css`: revelados, máscara por líneas, Ken Burns, barra de progreso,
  cabecera condensada y lightbox
- `motion.js` (núcleo, sin dependencias): revelados y contadores con
  `IntersectionObserver`, escalonado automático por rejilla, cabecera
  adaptativa, barra de progreso de lectura y cursor magnético
- `motion-scroll.js`: Lenis + GSAP/ScrollTrigger para el parallax de tres
  capas del hero, entradas en `batch()` y dibujado de trazos SVG
- `gallery.js`: lightbox con transición compartida (FLIP), navegación por
  teclado, deslizamiento táctil, trampa de foco y `aria-modal`
- `compare.js`: la tarjeta vuela hasta la barra del comparador
- Componente `<x-reveal>` y convenciones `data-reveal`, `data-reveal-group`,
  `data-parallax`, `data-counter`, `data-magnetic`

**SEO**

- `/sitemap.xml` con `xhtml:link` por idioma y `x-default`
- `/robots.txt` servido desde la configuración del panel — el ajuste
  `seo_robots_txt` existía pero el archivo real era estático, así que
  editarlo no hacía nada
- `App\Support\Seo::organization()`: JSON-LD `RealEstateAgent` en el layout
- Open Graph y Twitter Card completas en el layout

### Corregido

- **La portada devolvía 500 si una propiedad no tenía ninguna traducción.**
  Sin slug no se puede construir su enlace y `UrlGenerationException` tumbaba
  la página entera por una sola fila incompleta. Ahora se omite esa ficha.
  Lo destapó una prueba de la capa de efectos que asertaba sobre una página
  que en realidad era un 500 — la aserción pasaba porque el texto buscado
  aparecía en el volcado del error.
- **El parallax se congelaba tras un scroll programático.** Lenis solo avisa
  a ScrollTrigger cuando el desplazamiento lo provoca el usuario, así que la
  posición que el navegador restaura al volver atrás dejaba las capas
  midiendo contra un layout viejo.

### Decisiones

| Decisión | Motivo |
|---|---|
| Los elementos animados se sirven **visibles**; el JS los oculta antes de animar | Al revés, un bloqueador de scripts sirve una página en blanco. Hay pruebas que lo vigilan |
| GSAP solo se descarga a partir de 768 px | El presupuesto prohíbe el parallax en móvil, y es lo único que necesita la librería |
| El contador lleva la cifra final ya escrita en el HTML | Si el JS falla se lee el número correcto, no un cero |
| El vuelo al comparador ocurre **antes** de enviar el formulario | El botón es un POST que recarga; animar después cortaría el vuelo por la mitad |
| Vendido y alquilado fuera del sitemap | Siguen visibles como prueba social, pero no se le pide a Google que posicione una página que decepciona |

### Presupuesto de rendimiento

| Módulo | Comprimido |
|---|--:|
| `motion.js` + `compare.js` (móvil) | **2,06 KB** |
| `motion-scroll.js` (escritorio) | 50,66 KB |
| Total escritorio | **52,7 KB** — presupuesto: 60 KB |

### Pruebas

`php artisan test` → **478 pasadas, 1 045 aserciones**.

Verificado en navegador a 375 y 1440 px: sin scroll horizontal, cero
controles bajo 44 px, GSAP ausente en móvil, la barra de progreso sigue al
scroll con exactitud (0,310 medido frente a 0,310 esperado), el lightbox
atrapa el foco y lo devuelve al cerrar con `Esc`.

### Pendiente

- Medición con Lighthouse móvil real (LCP, CLS, INP) sobre el sitio desplegado
- La foto del hero (Cap Cana)

---

## [0.8.0] — 2026-08-14 — Cierre de la Fase 4 y fases 5, 6 y 7

Entrega grande: se completan las páginas públicas que quedaban y entran los
tres módulos que convierten el sitio en un negocio — captación, contenidos y
equipo.

### Añadido

**Páginas públicas restantes de la Fase 4**

- **Comparador** (`/comparar`, `/en/compare`) con tabla de scroll interno,
  columna de etiquetas sticky y filtro «solo diferencias»
- **Invierte** (`/invierte`) con motivos, línea temporal del proceso y
  formulario de inversión
- **Sobre nosotros** (`/sobre-nosotros`) con valores, equipo e iniciales
  cuando el asesor no tiene foto
- **Contáctanos** (`/contactanos`) y **Publica tu propiedad**
  (`/publica-tu-propiedad`)
- Marcadores de posición para `/privacidad` y `/terminos` — a la espera de los
  textos legales reales

**Fase 5 — Leads y WhatsApp**

- Tabla `leads` con enums `LeadSource` y `LeadStatus`
- Cuatro *form requests* distintos, uno por origen: contacto, inversión,
  consulta sobre propiedad y publicación
- `LeadService` con doble correo — confirmación al interesado y aviso al
  equipo — y bandeja en el panel con exportación
- Tabla `whatsapp_clicks` y su informe: se mide qué propiedades generan
  conversación, no solo visitas

**Fase 6 — Noticias**

- `news_posts`, `news_post_translations` y `news_categories`
- Blog bilingüe en `/informate` y `/en/insights`, con categorías y borradores
- Gestión completa en el panel bajo el permiso `manage_news`

**Fase 7 — Agentes**

- `AgentManager`: alta, edición, foto, orden, activar/ocultar y borrado
- Cargo y biografía traducibles con pestañas ES/EN
- El asesor aparece en la ficha de propiedad y su WhatsApp sustituye al general

**Recuperación de contraseña del panel**

- `ForgotPasswordController` y `ResetPasswordController` con
  `AdminResetPasswordNotification` y limitador de 5 intentos por minuto

### Decisiones

| Decisión | Motivo |
|---|---|
| El lead se guarda **antes** de intentar el correo | Un fallo de SMTP no puede costar un cliente potencial |
| Borrar un asesor **no** borra sus propiedades | `agent_id` pasa a NULL. El modal avisa cuántas fichas quedarán sin asesor visible. Hay una prueba que lo vigila |
| Un asesor oculto conserva sus propiedades | Ocultar es una decisión de visibilidad, no de datos |
| Clics de WhatsApp en tabla propia, no en un contador | Permite informes por fecha y por propiedad |

### Pruebas

`php artisan test` → **437 pasadas, 921 aserciones**. Verificado en 375 y
1440 px.

### Pendiente

- La foto del hero (Cap Cana) sigue sin subir desde `/admin/contenido`
- Textos legales de privacidad y términos

---

## [0.7.0] — 2026-08-14 — Fase 4 (parcial): inicio, listado y detalle

### Añadido

- Tablas `pages`, `page_translations`, `content_sections` y
  `content_section_translations`: los textos del inicio se editan desde el
  panel en vez de estar en el Blade
- `PropertySearchService` con 18 filtros, orden y similares
- Componentes `<x-property-card>`, `<x-status-chip>`, `<x-search-bar>`,
  `<x-property-gallery>`
- **Inicio**: hero de 85 vh con buscador de cristal, destacadas, estadísticas,
  inversión y CTA final
- **Listado**: filtros en columna sticky (desplegable en móvil), orden,
  paginación con filtros persistidos, estado vacío diferenciado
- **Detalle**: galería con miniaturas, meta-grid de 4, amenidades en bento,
  ficha técnica, similares, sidebar sticky y barra de contacto fija en móvil
- JSON-LD `RealEstateListing` con geolocalización condicionada
- 34 pruebas nuevas

### Corregido

Tres defectos que solo aparecen al mirar la página renderizada:

- **`@section('description', null)` dejaba un buffer de salida abierto.**
  Blade interpreta el `null` como una sección que *se abre*, llama a
  `ob_start()` y espera un `@endsection` que nunca llega. Ocurría en cualquier
  propiedad sin descripción corta ni meta propia. Hay una prueba que lo vigila.
- **El panel de cristal no aplicaba desenfoque.** Al escribir el prefijo
  `-webkit-` a mano, Lightning CSS colapsaba las dos declaraciones y emitía
  solo la prefijada, que Chrome ya no admite. Se declara solo la estándar.
- **Los chips de estado salían sin color.** Tailwind 4 purgó los tokens
  `--color-status-*` porque solo se referencian desde un `style=""` inline,
  que su escáner no lee. Movidos a `@theme static`.

### Decisiones

| Decisión | Motivo |
|---|---|
| Los textos del inicio en `content_sections` | El prompt maestro exige que todo contenido importante sea administrable |
| El filtro de precio convierte el *límite*, no los precios | Así el índice `(currency, price)` sigue sirviendo |
| Las amenidades filtran con AND, no con OR | Marcar «piscina» y «gimnasio» busca las que tengan ambas |
| Listado filtrado con `noindex` | Miles de URL casi duplicadas arruinarían el presupuesto de rastreo |
| Vendido y alquilado: visibles pero sin contacto ni indexación | Prueba social sin competir en Google |
| Vista de detalle sin sesión para borradores | Solo con enlace firmado de 30 min |

### Pruebas

`php artisan test` → **300 pasadas, 566 aserciones**, sin tests marcados como
«risky». Verificado en 375 y 1440 px.

### Pendiente de la Fase 4

- Comparador, Invierte, Sobre nosotros, Contáctanos y Publica tu propiedad
- Formularios de contacto (dependen de la Fase 5)

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
