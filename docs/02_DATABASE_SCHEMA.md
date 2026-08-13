# 02 — Esquema de base de datos

**Motor:** MariaDB 11.4.12 · **Charset:** `utf8mb4` · **Collation:** `utf8mb4_unicode_ci`
**BD:** `era_realty` (crear) · **23 tablas** (incluye 3 de Laravel y 3 de spatie/permission)

---

## Mapa de relaciones

```
users ──< agents
users ──< audit_logs
users ──< properties (created_by, updated_by)

provinces ──< cities ──< sectors
                            │
property_types ─────────────┤
agents ─────────────────────┤
                            ▼
                       properties ──< property_images
                            │      ──< leads
                            │      ──< whatsapp_clicks
                            │      ──< property_views
                            └──────>< amenities  (amenity_property)

news_categories ──< news_posts ──< leads
users           ──< news_posts

settings          (clave/valor)
pages             (contenido editable)
content_sections  (bloques del home y páginas)
media_files       (biblioteca reutilizable)
```

---

## 1. `users`

Estándar de Laravel, más dos campos.

| Campo | Tipo | Nota |
|---|---|---|
| id | bigint UNSIGNED PK | |
| name | varchar(150) | |
| email | varchar(190) UNIQUE | |
| email_verified_at | timestamp NULL | |
| password | varchar(255) | bcrypt |
| phone | varchar(30) NULL | |
| avatar | varchar(255) NULL | |
| is_active | boolean DEFAULT 1 | |
| remember_token | varchar(100) NULL | |
| timestamps | | |

Roles vía spatie: `super_admin`, `admin`, `editor`, `agent`.

---

## 2. `settings`

| Campo | Tipo | Nota |
|---|---|---|
| id | bigint PK | |
| key | varchar(100) **UNIQUE** | |
| value | text NULL | |
| type | enum | `string, text, boolean, integer, json, image, email, url` |
| group | varchar(50) | `general, contact, social, whatsapp, mail, seo, footer` |
| is_public | boolean DEFAULT 1 | `false` = nunca sale a las vistas públicas |
| is_encrypted | boolean DEFAULT 0 | para `mail_password` |
| timestamps | | |

`INDEX (group, is_public)`

**Claves iniciales (seeder)**

```
general : site_name, site_logo, site_logo_dark, site_favicon, site_tagline
contact : contact_phone, contact_email, contact_form_recipient_email,
          contact_address, contact_schedule, contact_map_embed
whatsapp: contact_whatsapp_number, contact_whatsapp_message,
          whatsapp_property_message, whatsapp_investment_message,
          whatsapp_float_enabled, whatsapp_float_position
social  : social_facebook, social_instagram, social_youtube,
          social_tiktok, social_linkedin
seo     : seo_default_title, seo_default_description, seo_default_og_image,
          seo_google_analytics_id, seo_google_site_verification
footer  : footer_text, footer_copyright
mail    : mail_mailer, mail_host, mail_port, mail_username,
          mail_password (is_encrypted=1, is_public=0), mail_encryption,
          mail_from_address, mail_from_name
```

`contact_whatsapp_link` **no se almacena**: se deriva en `WhatsappService::link()` a partir del número y el mensaje. Guardarlo sería un dato duplicado que se desincroniza — ver [06_EMAIL_AND_WHATSAPP.md](06_EMAIL_AND_WHATSAPP.md).

---

## 3. `property_types`

| Campo | Tipo |
|---|---|
| id | bigint PK |
| name | varchar(100) |
| slug | varchar(120) UNIQUE |
| icon | varchar(50) NULL — nombre de Material Symbol |
| description | text NULL |
| is_active | boolean DEFAULT 1 |
| sort_order | int DEFAULT 0 |
| timestamps | |

Seeder: Apartamento, Casa, Villa, Penthouse, Solar, Terreno, Local comercial, Nave, Oficina, Finca, Proyecto.

---

## 4. `provinces` / 5. `cities` / 6. `sectors`

```
provinces(id, name, slug UNIQUE, is_active, sort_order, timestamps)
cities   (id, province_id FK→provinces CASCADE, name, slug, is_active, sort_order, timestamps)
sectors  (id, city_id FK→cities CASCADE,      name, slug, is_active, sort_order, timestamps)
```

`cities`: `UNIQUE(province_id, slug)`, `INDEX(province_id, is_active)`
`sectors`: `UNIQUE(city_id, slug)`, `INDEX(city_id, is_active)`

Seeder inicial: las 32 provincias de RD; ciudades y sectores para Santo Domingo (Piantini, Naco, Bella Vista, Evaristo Morales…), Santiago (Los Cerros…), Punta Cana (Cap Cana, Bávaro…) y Las Terrenas — los que aparecen en el diseño.

---

## 7. `agents`

| Campo | Tipo | Nota |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK→users NULL SET NULL | si el agente entra al panel |
| name | varchar(150) | |
| position | varchar(100) NULL | "Senior Broker" |
| photo | varchar(255) NULL | |
| phone / whatsapp / email | varchar | NULL |
| bio | text NULL | |
| social_instagram / social_linkedin | varchar(255) NULL | |
| is_active | boolean DEFAULT 1 | |
| sort_order | int DEFAULT 0 | |
| timestamps | | |

`INDEX (is_active, sort_order)`

---

## 8. `properties` — tabla central

| Campo | Tipo | Nota |
|---|---|---|
| id | bigint PK | |
| title | varchar(200) | |
| slug | varchar(220) **UNIQUE** | |
| reference_code | varchar(30) **UNIQUE** | `ERA-1045`, autogenerado |
| operation_type | enum | `sale, rent, temporary_rent, investment` |
| property_type_id | bigint FK→property_types RESTRICT | |
| status | enum | `draft, available, reserved, sold, rented, not_available, paused` |
| price | decimal(15,2) NULL | NULL = "precio a consultar" |
| currency | char(3) DEFAULT 'USD' | `USD` \| `DOP` |
| price_period | enum NULL | `month, night, year` — solo alquiler |
| province_id / city_id / sector_id | bigint FK NULL SET NULL | |
| address | varchar(255) NULL | |
| show_exact_location | boolean DEFAULT 0 | si 0, el mapa muestra área aproximada |
| latitude / longitude | decimal(10,8) / (11,8) NULL | |
| bedrooms | tinyint UNSIGNED NULL | |
| bathrooms | decimal(3,1) NULL | admite 3.5 (como en el diseño) |
| parking_spaces | tinyint UNSIGNED NULL | |
| construction_area | decimal(10,2) NULL | m² |
| land_area | decimal(10,2) NULL | m² |
| floor_level | varchar(20) NULL | |
| maintenance_fee | decimal(12,2) NULL | |
| year_built | smallint UNSIGNED NULL | |
| is_furnished | boolean DEFAULT 0 | |
| is_featured | boolean DEFAULT 0 | home |
| is_investment | boolean DEFAULT 0 | página Invierte |
| is_project | boolean DEFAULT 0 | en construcción |
| short_description | varchar(500) NULL | tarjetas y OG |
| description | text NULL | |
| features_json | json NULL | características libres |
| video_url | varchar(255) NULL | YouTube/Vimeo |
| virtual_tour_url | varchar(255) NULL | |
| agent_id | bigint FK→agents NULL SET NULL | |
| owner_name / owner_phone / owner_email | varchar NULL | **privado, nunca público** |
| meta_title | varchar(200) NULL | |
| meta_description | varchar(300) NULL | |
| og_image | varchar(255) NULL | |
| views_count | int UNSIGNED DEFAULT 0 | denormalizado |
| published_at | timestamp NULL | |
| created_by_user_id | bigint FK→users NULL | |
| updated_by_user_id | bigint FK→users NULL | |
| deleted_at | timestamp NULL | SoftDeletes |
| timestamps | | |

**Índices** — deducidos de los filtros reales del diseño (`propiedades_era_realty_rd/code.html`):

```sql
INDEX idx_public_list   (status, published_at, deleted_at)
INDEX idx_operation     (operation_type, status)
INDEX idx_type          (property_type_id, status)
INDEX idx_location      (province_id, city_id, sector_id)
INDEX idx_price         (currency, price)
INDEX idx_specs         (bedrooms, bathrooms, parking_spaces)
INDEX idx_featured      (is_featured, status, published_at)
INDEX idx_investment    (is_investment, status)
INDEX idx_agent         (agent_id)
FULLTEXT ft_search      (title, short_description, description)
```

`amenities` sale a tabla propia en vez de `amenities_json` (el prompt lo sugería como json). Motivo: el diseño filtra por amenidad y las muestra con icono; con json no se puede indexar el filtro y el catálogo no es administrable. Es una desviación consciente del prompt maestro — ver [12_KNOWN_ISSUES.md](12_KNOWN_ISSUES.md) #6.

---

## 9. `amenities` + 10. `amenity_property`

```
amenities(id, name varchar(100), slug varchar(120) UNIQUE,
          icon varchar(50) NULL, category varchar(50) NULL,
          is_active, sort_order, timestamps)

amenity_property(property_id FK CASCADE, amenity_id FK CASCADE,
                 PRIMARY KEY(property_id, amenity_id))
```

Seeder: Piscina Infinity, Gimnasio, Seguridad 24/7, Ascensor, Terraza, Pet Friendly, Planta eléctrica, Aire acondicionado, Área social, Jacuzzi, Cancha, Vista al mar, Playa privada, Gazebo, Cisterna, Intercom.

---

## 11. `property_images`

| Campo | Tipo | Nota |
|---|---|---|
| id | bigint PK | |
| property_id | bigint FK CASCADE | |
| path | varchar(255) | original optimizado |
| thumbnail_path | varchar(255) NULL | 400×300 |
| webp_path | varchar(255) NULL | |
| original_name | varchar(255) | |
| alt_text | varchar(255) NULL | SEO |
| title | varchar(255) NULL | |
| sort_order | int DEFAULT 0 | |
| is_main | boolean DEFAULT 0 | |
| width / height | int NULL | evita CLS |
| size | int UNSIGNED | bytes |
| mime_type | varchar(50) | |
| uploaded_by_user_id | bigint FK NULL | |
| timestamps | | |

`INDEX (property_id, sort_order)`, `INDEX (property_id, is_main)`

Invariante: **exactamente una** imagen con `is_main=1` por propiedad. Se garantiza en `PropertyImageService` dentro de una transacción, no con un índice único (no existe UNIQUE parcial en MariaDB).

---

## 12. `media_files`

Biblioteca general reutilizable (logo, favicon, OG, banners, noticias, agentes).

```
id, disk varchar(30) DEFAULT 'public', path, thumbnail_path NULL, webp_path NULL,
original_name, mime_type, size, width NULL, height NULL,
alt_text NULL, title NULL, context varchar(50) NULL, folder varchar(100) NULL,
uploaded_by_user_id FK NULL, timestamps
INDEX (context), INDEX (mime_type)
```

---

## 13. `news_categories` / 14. `news_posts`

```
news_categories(id, name varchar(100), slug varchar(120) UNIQUE,
                description text NULL, color varchar(7) NULL,
                is_active, sort_order, timestamps)
```

| `news_posts` | Tipo | Nota |
|---|---|---|
| id | bigint PK | |
| title | varchar(200) | |
| slug | varchar(220) UNIQUE | |
| excerpt | varchar(500) NULL | |
| content | longtext | HTML de TipTap, **saneado al guardar** |
| featured_image | varchar(255) NULL | |
| status | enum | `draft, published, scheduled, archived` |
| category_id | FK→news_categories NULL SET NULL | |
| author_id | FK→users RESTRICT | |
| is_featured | boolean DEFAULT 0 | |
| reading_time | tinyint NULL | calculado |
| views_count | int UNSIGNED DEFAULT 0 | |
| meta_title / meta_description / og_image | | NULL |
| published_at | timestamp NULL | futuro = programada |
| deleted_at | timestamp NULL | |
| timestamps | | |

```sql
INDEX (status, published_at, deleted_at)
INDEX (category_id, status)
FULLTEXT (title, excerpt, content)
```

---

## 15. `pages` / 16. `content_sections`

```
pages(id, key varchar(50) UNIQUE, title, slug varchar(120) UNIQUE,
      content longtext NULL, featured_image NULL,
      meta_title NULL, meta_description NULL, og_image NULL,
      status enum('draft','published') DEFAULT 'published',
      is_system boolean DEFAULT 0,   -- no borrable desde el panel
      timestamps)
```
Seeder: `invest`, `about`, `contact`, `publish_property`, `privacy`, `terms`.

```
content_sections(id, page_key varchar(50), section_key varchar(50),
                 title NULL, subtitle NULL, content text NULL,
                 image NULL, button_text NULL, button_url NULL,
                 extra_json json NULL, sort_order, is_active, timestamps)
UNIQUE(page_key, section_key)
```
Seeder para `home`: `hero`, `featured_properties`, `investment_cta`, `about_teaser`, `news_teaser`, `stats`.
Esto es lo que hace que los textos del hero del diseño ("Encuentra tu próxima propiedad…") sean editables en vez de estar quemados en el Blade.

---

## 17. `leads`

| Campo | Tipo | Nota |
|---|---|---|
| id | bigint PK | |
| source | enum | `contact_page, property_detail, publish_property, investment_page, whatsapp_click, news_contact` |
| name | varchar(150) | |
| phone | varchar(30) | |
| email | varchar(190) NULL | |
| message | text NULL | |
| property_id | FK NULL SET NULL | |
| news_post_id | FK NULL SET NULL | |
| interest_type | varchar(50) NULL | comprar/alquilar/invertir |
| budget_range | varchar(50) NULL | |
| preferred_contact | enum NULL | `phone, whatsapp, email` |
| status | enum DEFAULT 'new' | `new, contacted, interested, visit_scheduled, negotiating, closed, lost, spam` |
| assigned_to_user_id | FK NULL SET NULL | |
| admin_notes | text NULL | |
| contacted_at | timestamp NULL | |
| ip_address | varchar(45) NULL | IPv6 |
| user_agent | varchar(500) NULL | |
| referrer_url | varchar(500) NULL | |
| timestamps | | |

```sql
INDEX (status, created_at)
INDEX (source, created_at)
INDEX (property_id)
INDEX (assigned_to_user_id, status)
```

Datos personales: ver retención en [12_KNOWN_ISSUES.md](12_KNOWN_ISSUES.md) #8.

---

## 18. `whatsapp_clicks`

```
id, property_id FK NULL SET NULL, source varchar(50),
phone_number varchar(30), generated_message text NULL,
ip_address varchar(45) NULL, user_agent varchar(500) NULL,
referrer_url varchar(500) NULL, created_at
INDEX (created_at), INDEX (property_id), INDEX (source)
```
Sin `updated_at`: es una tabla de solo inserción.

---

## 19. `property_views`

```
id, property_id FK CASCADE, ip_hash char(64), session_id varchar(100) NULL,
referrer_url NULL, created_at
INDEX (property_id, created_at), INDEX (ip_hash, property_id)
```
`ip_hash` = SHA-256 con sal de aplicación. Se guarda el hash y no la IP porque el único uso es deduplicar visitas; guardar la IP en claro sería recolectar un dato personal sin necesitarlo. Deduplicación: 1 vista por IP y propiedad cada 24 h.

---

## 20. `audit_logs`

```
id, user_id FK NULL SET NULL, action varchar(100),
entity_type varchar(100) NULL, entity_id bigint NULL,
old_values json NULL, new_values json NULL,
ip_address varchar(45) NULL, user_agent varchar(500) NULL,
created_at
INDEX (user_id, created_at), INDEX (entity_type, entity_id), INDEX (action, created_at)
```

Acciones registradas: `login`, `login_failed`, `logout`, `property.created|updated|deleted|status_changed`, `property_image.uploaded|deleted`, `news.created|updated|deleted|published`, `settings.updated`, `settings.logo_changed`, `settings.whatsapp_changed`, `settings.mail_changed`, `lead.status_changed`, `user.created|updated`.

`old_values`/`new_values` **excluyen** `password`, `remember_token` y `mail_password`.

---

## 21–23. Tablas de framework

`cache`, `jobs`, `failed_jobs`, `sessions`, `password_reset_tokens` (Laravel)
`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` (spatie)

---

## Orden de migraciones

```
001 users, cache, jobs, sessions          (Laravel)
002 permission_tables                     (spatie)
003 settings
004 property_types
005 provinces → 006 cities → 007 sectors
008 amenities
009 agents
010 properties
011 amenity_property
012 property_images
013 media_files
014 news_categories → 015 news_posts
016 pages → 017 content_sections
018 leads
019 whatsapp_clicks
020 property_views
021 audit_logs
```

## Seeders

| Seeder | Contenido |
|---|---|
| `RolePermissionSeeder` | 4 roles, 9 permisos |
| `AdminUserSeeder` | super_admin inicial — **credenciales por definir, ver Pregunta 6** |
| `SettingsSeeder` | ~40 claves con valores del diseño |
| `PropertyTypeSeeder` | 11 tipos |
| `LocationSeeder` | 32 provincias + ciudades/sectores del diseño |
| `AmenitySeeder` | 16 amenidades |
| `PageSeeder` + `ContentSectionSeeder` | páginas y bloques del home |
| `NewsCategorySeeder` | Mercado, Inversión, Guías, Noticias |
| `DemoDataSeeder` | solo en local: 24 propiedades, 8 noticias, 3 agentes, 15 leads |
