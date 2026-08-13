# 01 — Arquitectura

---

## 1. Decisiones de stack y su justificación

### Backend

| Componente | Versión | Nota |
|---|---|---|
| Laravel | 12.x | Exigido por el prompt maestro |
| PHP | 8.2.33 | Instalado. Laravel 12 requiere `^8.2` → cumple |
| MariaDB | 11.4.12 | Ya corriendo (`mysqld` PID 5192) |
| Composer | 2.8.11 | Instalado |

Paquetes de primera:

```
laravel/framework:^12.0
livewire/livewire:^3.5          # panel admin interactivo
intervention/image:^3.9         # WebP, miniaturas, redimensionado
spatie/laravel-permission:^6.9  # roles y permisos (§23 del prompt)
spatie/laravel-sitemap:^7.3     # sitemap.xml
mews/purifier:^3.4              # saneado del HTML de TipTap (anti-XSS)
laravel/pint                    # formato (dev)
pestphp/pest:^3.0               # pruebas (dev)
```

### Frontend

| Componente | Versión | Uso |
|---|---|---|
| Tailwind CSS | 3.4 | **Compilado con Vite**, no CDN |
| Alpine.js | 3.14 | Menú móvil, tabs, galería, comparador |
| GSAP + ScrollTrigger | 3.12 | Capa de efectos — ver doc 13 |
| Lenis | 1.1 | Scroll suave |
| TipTap | 2.x | Editor de noticias |
| SortableJS | 1.15 | Reordenar imágenes por drag&drop |
| Vite | 6 | Build |

**Nota crítica sobre Tailwind:** las maquetas de Stitch cargan `cdn.tailwindcss.com` y definen los tokens en un `<script>` inline. Eso es inaceptable en producción (JIT en cliente, FOUC, sin purge, dependencia de CDN externo). Los tokens de `estate_elite/DESIGN.md` se trasladan a `tailwind.config.js` y se compila. Ver [12_KNOWN_ISSUES.md](12_KNOWN_ISSUES.md) #3.

### Por qué Blade + Livewire y no Nuxt

| Criterio | Blade + Livewire | Laravel API + Nuxt 3 |
|---|---|---|
| SEO | Render en servidor nativo | SSR, equivalente |
| Complejidad operativa | 1 app, 1 deploy | 2 apps, 2 deploys, CORS, auth de API |
| Tiempo hasta producción | Menor | +30–40 % |
| Interactividad rica | Livewire + Alpine cubre todo lo pedido | Superior, pero no se necesita |
| Efectos llamativos | GSAP funciona igual en ambos | Igual |

El prompt maestro (§1) permite explícitamente ambas y dice: *"Si no se ha decidido, usar Laravel 12 + Blade/Tailwind"*. Se toma esa vía. **Es la decisión más cara de revertir; confirmarla antes de la Fase 0.**

---

## 2. Estructura de carpetas

Se sigue el patrón `app/Modules/` del prompt maestro (§3), con Services separando la lógica de negocio de los controladores.

```
Era Realty/                          ← raíz del proyecto Laravel
├── app/
│   ├── Modules/
│   │   ├── Auth/            {Controllers, Requests}
│   │   ├── Dashboard/       {Controllers, Services}
│   │   ├── Settings/        {Controllers, Requests, Services, Models}
│   │   ├── Properties/      {Controllers, Requests, Services, Models, Livewire}
│   │   ├── PropertyImages/  {Controllers, Services, Models, Livewire}
│   │   ├── PropertyTypes/   {Controllers, Requests, Models}
│   │   ├── Locations/       {Controllers, Requests, Models}
│   │   ├── Agents/          {Controllers, Requests, Models}
│   │   ├── Leads/           {Controllers, Requests, Services, Models}
│   │   ├── News/            {Controllers, Requests, Services, Models}
│   │   ├── Pages/           {Controllers, Requests, Models}
│   │   ├── Contact/         {Controllers, Requests, Services}
│   │   ├── Seo/             {Services}
│   │   ├── Media/           {Controllers, Services, Models, Livewire}
│   │   ├── Reports/         {Controllers, Services}
│   │   ├── Audit/           {Models, Observers, Services}
│   │   ├── WhatsApp/        {Services, Controllers, Models}
│   │   └── Compare/         {Controllers, Services}
│   ├── Http/
│   │   ├── Controllers/Controller.php
│   │   ├── Middleware/          # SetPublicSettings, EnsureAdmin, HoneypotCheck
│   │   └── ViewComposers/       # inyecta settings al layout público
│   ├── Providers/
│   ├── Enums/                   # PropertyStatus, OperationType, LeadStatus, LeadSource, PostStatus
│   ├── Support/                 # helpers: PhoneFormatter, SlugGenerator, MoneyFormatter
│   └── Models/User.php
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── public.blade.php
│   │   │   └── admin.blade.php
│   │   ├── components/          # Blade components reutilizables
│   │   │   ├── property-card.blade.php
│   │   │   ├── status-chip.blade.php
│   │   │   ├── search-bar.blade.php
│   │   │   ├── whatsapp-float.blade.php
│   │   │   ├── seo-meta.blade.php
│   │   │   └── reveal.blade.php        # wrapper de animación (doc 13)
│   │   ├── public/
│   │   │   ├── home.blade.php
│   │   │   ├── properties/{index,show}.blade.php
│   │   │   ├── compare/index.blade.php
│   │   │   ├── invest/index.blade.php
│   │   │   ├── about/index.blade.php
│   │   │   ├── news/{index,show}.blade.php
│   │   │   ├── contact/index.blade.php
│   │   │   └── publish/index.blade.php
│   │   ├── admin/
│   │   │   ├── auth/login.blade.php
│   │   │   ├── dashboard/index.blade.php
│   │   │   ├── properties/{index,create,edit}.blade.php
│   │   │   ├── news/{index,create,edit}.blade.php
│   │   │   ├── leads/{index,show}.blade.php
│   │   │   ├── agents/, media/, pages/, locations/, reports/, audit/
│   │   │   └── settings/{general,whatsapp,mail,seo}.blade.php
│   │   ├── livewire/
│   │   └── emails/              # plantillas Markdown de correo
│   ├── css/app.css
│   └── js/
│       ├── app.js
│       ├── motion.js            # capa GSAP (doc 13)
│       ├── gallery.js
│       ├── compare.js
│       └── admin/{uploader.js, editor.js}
│
├── routes/
│   ├── web.php                  # públicas
│   ├── admin.php                # panel (prefix /admin, middleware auth)
│   └── console.php
│
├── database/
│   ├── migrations/              # ~23 migraciones
│   ├── seeders/
│   └── factories/
│
├── storage/app/public/
│   ├── properties/{id}/{original,thumb,webp}/
│   ├── news/
│   ├── agents/
│   └── settings/                # logo, favicon, og
│
├── public/storage → symlink
├── tests/{Feature,Unit}/
├── docs/                        ← este paquete
└── stitch_era_realty_rd_premium_redesign/   ← referencia, no se despliega
```

---

## 3. Modelos (20)

| Modelo | Tabla | Rasgos |
|---|---|---|
| `User` | users | HasRoles |
| `Setting` | settings | cache, cast por `type` |
| `Property` | properties | SoftDeletes, Sluggable, Auditable, Searchable |
| `PropertyImage` | property_images | ordenable, `is_main` |
| `PropertyType` | property_types | Sluggable |
| `Province` | provinces | Sluggable |
| `City` | cities | belongsTo Province |
| `Sector` | sectors | belongsTo City |
| `Amenity` | amenities | belongsToMany Property |
| `Agent` | agents | belongsTo User (nullable) |
| `Lead` | leads | belongsTo Property/NewsPost |
| `NewsPost` | news_posts | SoftDeletes, Sluggable, Auditable |
| `NewsCategory` | news_categories | Sluggable |
| `Page` | pages | Sluggable |
| `ContentSection` | content_sections | por `page_key` + `section_key` |
| `MediaFile` | media_files | polimórfico por `context` |
| `WhatsappClick` | whatsapp_clicks | solo insert |
| `AuditLog` | audit_logs | solo insert |
| `PropertyView` | property_views | contador de vistas |
| `Role` / `Permission` | (spatie) | |

Traits propios: `HasSlug`, `Auditable`, `HasSeoMeta`, `Sortable`.

---

## 4. Módulos → responsabilidad

| # | Módulo | Responsabilidad |
|---|---|---|
| 1 | **Auth** | Login admin, recordar sesión, recuperar contraseña, rate limit |
| 2 | **Dashboard** | Métricas y accesos rápidos |
| 3 | **Settings** | Clave/valor tipado y cacheado; logo, favicon, contacto, redes, SEO global |
| 4 | **Properties** | CRUD, estados, destacados, inversión, SEO por propiedad, vista previa |
| 5 | **PropertyImages** | Subida múltiple, orden, principal, optimización |
| 6 | **PropertyTypes** | Catálogo de tipos |
| 7 | **Locations** | Provincias → ciudades → sectores |
| 8 | **Agents** | CRUD de asesores, foto, asignación a propiedades |
| 9 | **Leads** | Captura desde 5 formularios, estados, asignación |
| 10 | **News** | Categorías, TipTap, borrador/programado/publicado |
| 11 | **Pages** | Contenido editable de Invierte, Sobre nosotros, Contacto, home |
| 12 | **Contact** | Validación, honeypot, rate limit, despacho de correo |
| 13 | **Seo** | Metas, canonical, OG, Twitter, JSON-LD, sitemap, robots |
| 14 | **Media** | Biblioteca reutilizable de archivos |
| 15 | **Reports** | Conteos por estado, leads por fuente, clics WhatsApp |
| 16 | **Audit** | Log de acciones sensibles vía Observers |
| 17 | **WhatsApp** | Normalización de número, generación de `wa.me`, registro de clics |
| 18 | **Compare** | Comparador hasta 4 propiedades (localStorage + servidor) |
| 19 | **Motion** | Capa de efectos front — doc 13 |

---

## 5. Controladores

### Públicos (`App\Modules\*\Controllers\Public`)

```
HomeController@index
PropertyController@index          /propiedades
PropertyController@show           /propiedades/{slug}
CompareController@index           /comparar
InvestController@index            /invierte
AboutController@index             /sobre-nosotros
NewsController@index              /informate
NewsController@show               /informate/{slug}
ContactController@index           /contactanos
ContactController@store           POST
PublishPropertyController@index   /publica-tu-propiedad
PublishPropertyController@store   POST
LeadController@store              POST /leads  (formulario de detalle e inversión)
WhatsappClickController@store     POST /wa/click
SitemapController@index           /sitemap.xml
```

### Admin (`App\Modules\*\Controllers\Admin`)

```
Auth\LoginController              {showLoginForm, login, logout}
Auth\PasswordResetController      {request, email, reset, update}
DashboardController@index
PropertyController                {index, create, store, edit, update, destroy,
                                   restore, publish, pause, changeStatus, preview}
PropertyImageController           {store, destroy, reorder, setMain, update}
PropertyTypeController            resource
ProvinceController / CityController / SectorController   resource
AgentController                   resource
LeadController                    {index, show, updateStatus, assign, destroy, export}
NewsController                    resource + {publish, schedule, preview}
NewsCategoryController            resource
PageController                    {index, edit, update}
ContentSectionController          {index, update}
MediaController                   {index, store, destroy, update}
SettingsController                {general, updateGeneral, whatsapp, updateWhatsapp,
                                   mail, updateMail, sendTestMail, seo, updateSeo}
ReportController                  {index, properties, leads, whatsapp}
AuditController                   {index, show}
```

Ningún controlador supera ~8 métodos. La lógica pesada vive en Services.

---

## 6. Services

| Service | Qué hace |
|---|---|
| `SettingsService` | get/set tipado, caché `settings.all`, invalidación |
| `PropertyService` | create/update, slug único, código de referencia, cambios de estado |
| `PropertySearchService` | construcción del query de filtros + orden + paginación |
| `ImageProcessingService` | validación, redimensionado, WebP, miniatura, escritura en disco |
| `PropertyImageService` | orden, principal, borrado en cascada de ficheros |
| `MediaLibraryService` | alta, búsqueda, verificación de uso antes de borrar |
| `LeadService` | crear lead, capturar IP/UA, disparar correos |
| `MailConfigService` | leer config SMTP de BD, cifrar/descifrar clave, aplicar en runtime |
| `MailTestService` | envío de prueba con captura de error |
| `WhatsappService` | `normalize()`, `link()`, `messageForProperty()`, `logClick()` |
| `SeoService` | resolver metas por entidad con fallback a settings globales |
| `SchemaOrgService` | JSON-LD de `RealEstateListing`, `Article`, `Organization`, `BreadcrumbList` |
| `SitemapService` | generación del XML |
| `NewsService` | publicación, programación, saneado del HTML de TipTap |
| `AuditService` | escritura de `audit_logs` |
| `ReportService` | agregados del dashboard y reportes |
| `CompareService` | validación y carga de propiedades a comparar |
| `SlugService` | slugs únicos con sufijo incremental |

---

## 7. Rutas

### Públicas — `routes/web.php`

```
GET   /                          home
GET   /propiedades               properties.index
GET   /propiedades/{slug}        properties.show
GET   /comparar                  compare.index
GET   /invierte                  invest.index
GET   /sobre-nosotros            about.index
GET   /informate                 news.index
GET   /informate/{slug}          news.show
GET   /contactanos               contact.index
POST  /contactanos               contact.store          (throttle:5,1 + honeypot)
GET   /publica-tu-propiedad      publish.index
POST  /publica-tu-propiedad      publish.store          (throttle:5,1 + honeypot)
POST  /leads                     leads.store            (throttle:5,1 + honeypot)
POST  /wa/click                  whatsapp.click         (throttle:30,1)
GET   /sitemap.xml               sitemap
GET   /robots.txt                (fichero estático generado)
```

Rutas legales pendientes de confirmar: `/privacidad`, `/terminos` (aparecen en el footer del diseño).

### Admin — `routes/admin.php`, prefijo `/admin`

```
guest:
  GET|POST  /admin/login                    (throttle:5,1)
  GET|POST  /admin/password/reset
auth + verified + role:
  GET   /admin                              dashboard
  res.  /admin/propiedades                  + publish|pause|status|preview
  POST  /admin/propiedades/{id}/imagenes    · DELETE · POST reorder · POST main
  res.  /admin/tipos-propiedad
  res.  /admin/provincias · /ciudades · /sectores
  res.  /admin/agentes
  GET   /admin/leads · show · PATCH status · PATCH assign · GET export
  res.  /admin/noticias  + publish|schedule|preview
  res.  /admin/categorias-noticias
  GET|PUT /admin/paginas · /admin/secciones
  GET|POST|DELETE /admin/media
  GET|PUT /admin/configuracion/{general|whatsapp|correo|seo}
  POST  /admin/configuracion/correo/probar
  GET   /admin/reportes/{propiedades|leads|whatsapp}
  GET   /admin/auditoria · show
```

---

## 8. Flujo de una petición pública

```
Request
  → Middleware SetPublicSettings   (inyecta settings cacheados a las vistas)
  → Controller                     (fino: valida y delega)
  → Service                        (lógica de negocio)
  → Eloquent + caché               (con eager loading contra N+1)
  → Blade + componentes
  → SeoService  → <meta>, OG, JSON-LD
  → motion.js   → animaciones de entrada
Response
```

---

## 9. Rendimiento

- Eager loading obligatorio en listados (`with('mainImage', 'type', 'city')`).
- Índices en todo campo filtrable — ver [02_DATABASE_SCHEMA.md](02_DATABASE_SCHEMA.md).
- Caché de `settings`, tipos de propiedad y ubicaciones (invalidada al guardar).
- `loading="lazy"` + `srcset` en todas las imágenes salvo el hero.
- WebP con fallback JPEG vía `<picture>`.
- Objetivo: LCP < 2.5 s en móvil 4G.
