# 03 — Panel administrativo

**Prefijo:** `/admin` · **Middleware:** `auth` + `role|permission` · **UI:** Blade + Tailwind + Livewire 3 + Alpine

> **Sin diseño de referencia.** Stitch no generó ninguna pantalla del panel. Se construye con los mismos tokens de `estate_elite/DESIGN.md` pero con densidad de aplicación de gestión: sidebar oscura en `primary-container (#131b2e)`, contenido sobre `surface (#f7f9fb)`, tablas compactas, Inter en todo (Playfair Display solo en títulos de página). Ver Pregunta 2 en [10_TODO_MASTER.md](10_TODO_MASTER.md).

---

## 1. Layout

```
┌──────────────┬──────────────────────────────────────────┐
│  SIDEBAR     │  TOPBAR: título · buscar · perfil · salir │
│  #131b2e     ├──────────────────────────────────────────┤
│              │                                           │
│  Dashboard   │            CONTENIDO                      │
│  Propiedades │            (max 1440px)                   │
│  Noticias    │                                           │
│  Leads       │                                           │
│  Agentes     │                                           │
│  Ubicaciones │                                           │
│  Media       │                                           │
│  Páginas     │                                           │
│  Reportes    │                                           │
│  Auditoría   │                                           │
│  Config ▾    │                                           │
└──────────────┴──────────────────────────────────────────┘
```

Sidebar colapsable a iconos; en móvil se vuelve drawer. El ítem activo se marca con barra izquierda en `secondary`.

---

## 2. Pantallas

### 2.1 Login — `/admin/login`

Campos: email, contraseña, "recordar sesión". Enlace a recuperación.
Seguridad: CSRF, `throttle:5,1` por IP+email, mensajes genéricos ante fallo (no revelar si el email existe), registro de `login_failed` en auditoría.
Visual: pantalla partida — izquierda imagen de propiedad con overlay `primary-container`, derecha formulario centrado.

### 2.2 Recuperación de contraseña

`/admin/password/reset` → email con token (60 min) → formulario de nueva contraseña. Rate limit `3,10`.

### 2.3 Dashboard — `/admin`

**Tarjetas de métrica** (contadores animados):

| Métrica | Fuente |
|---|---|
| Total propiedades | `properties` sin borradas |
| Disponibles | `status = available` |
| Vendidas | `status = sold` |
| Alquiladas | `status = rented` |
| No disponibles | `status IN (not_available, paused)` |
| Borradores | `status = draft` |
| Leads nuevos | `leads.status = new` |
| Noticias publicadas | `news_posts.status = published` |
| Clics WhatsApp (30 d) | `whatsapp_clicks` |

**Bloques:**
- Gráfico de leads por día (30 días).
- Distribución de propiedades por estado (dona).
- Últimas 5 propiedades creadas.
- Últimos 10 leads con acceso directo.
- Propiedades más vistas (top 5).

**Accesos rápidos:** Nueva propiedad · Nueva noticia · Configuración · Ver sitio público.

### 2.4 Propiedades — `/admin/propiedades`

**Listado (Livewire):** buscador por título/referencia, filtros (estado, operación, tipo, provincia, agente, destacada, inversión), orden, paginación 20, selección múltiple.

Columnas: miniatura · título + referencia · tipo · operación · precio · estado (chip) · ubicación · agente · vistas · leads · fecha · acciones.

Acciones en lote: publicar, pausar, destacar, cambiar estado, eliminar (soft).

**Formulario (crear/editar)** en pestañas:

| Pestaña | Contenido |
|---|---|
| General | Título, slug (auto+editable), referencia (auto), operación, tipo, estado, destacada, inversión, proyecto |
| Precio | Precio, moneda, periodo, cuota de mantenimiento |
| Ubicación | Provincia→ciudad→sector (selects encadenados), dirección, mostrar ubicación exacta, mapa para fijar lat/lng |
| Características | Habitaciones, baños, parqueos, área construcción, área terreno, nivel, año, amueblado |
| Amenidades | Checkboxes agrupadas por categoría |
| Descripción | Descripción corta (con contador de caracteres) + descripción larga |
| Imágenes | Uploader — ver [05_MEDIA_UPLOADS.md](05_MEDIA_UPLOADS.md) |
| Multimedia | URL de video, tour virtual |
| Agente y propietario | Agente asignado; datos privados del propietario |
| SEO | Meta title, meta description (con vista previa de resultado de Google), OG image |

Barra de acciones fija abajo: **Guardar borrador · Guardar y publicar · Vista previa · Cancelar**.
Vista previa: firma temporal de 30 min que permite ver la ficha pública aunque esté en borrador.

### 2.5 Imágenes

Integrado en la pestaña Imágenes. Drag&drop, subida múltiple con progreso, reordenación (SortableJS), marcar principal, editar alt/título en línea, borrar con confirmación. Detalle técnico en [05_MEDIA_UPLOADS.md](05_MEDIA_UPLOADS.md).

### 2.6 Noticias — `/admin/noticias`

Listado con filtros por estado/categoría/autor.

Editor: título, slug, extracto, imagen destacada (desde media manager o subida), **TipTap** (H2/H3, negrita, cursiva, listas, enlaces, cita, imagen, embed de video, alineación, deshacer), categoría, destacada, estado, fecha de publicación (futura = programada), bloque SEO, vista previa, autoguardado de borrador cada 60 s.

Saneado: el HTML de TipTap pasa por `mews/purifier` **al guardar** con lista blanca de etiquetas y atributos. Nunca se confía en el editor cliente.

### 2.7 Leads — `/admin/leads`

Listado con filtros (estado, fuente, propiedad, asignado, rango de fechas) y búsqueda por nombre/teléfono/email.
Detalle: datos de contacto, mensaje, propiedad relacionada, IP/UA/referrer, historial de estado, notas internas, acciones directas (llamar, WhatsApp con mensaje precargado, email).
Cambio de estado en línea (kanban opcional). Exportación a CSV.

### 2.8 Agentes — `/admin/agentes`

CRUD con foto (recorte cuadrado), datos de contacto, bio, redes, activo, orden por drag&drop, listado de propiedades asignadas.

### 2.9 Ubicaciones — `/admin/provincias|ciudades|sectores`

Vista de árbol con tres niveles, alta/edición en modal, activar/desactivar. Bloqueo de borrado si hay propiedades asociadas (mensaje explícito con el conteo).

### 2.10 Media manager — `/admin/media`

Grid/lista, filtro por contexto y tipo, búsqueda por nombre/alt, subida múltiple, edición de alt/título, copiar URL, borrado con verificación de uso previo. Selector modal reutilizable desde cualquier campo de imagen.

### 2.11 Páginas y secciones — `/admin/paginas`, `/admin/secciones`

Edición de contenido de Invierte, Sobre nosotros, Contacto, Publica tu propiedad, Privacidad, Términos.
Secciones del home: hero (título, subtítulo, imagen, CTA), bloque de inversión, teaser de nosotros, estadísticas. Cada bloque con activar/desactivar y orden.

### 2.12 Configuración — `/admin/configuracion`

Cuatro pestañas:

| Pestaña | Contenido |
|---|---|
| **General** | Nombre, tagline, logo, logo oscuro, favicon, teléfono, email, email receptor de formularios, dirección, horario, redes sociales, texto de footer |
| **WhatsApp** | Número, mensaje general, mensaje por propiedad (con variables `{reference_code}`, `{title}`, `{price}`, `{url}`), mensaje de inversión, botón flotante on/off, posición, **vista previa del link generado en vivo** |
| **Correo** | Driver, host, puerto, usuario, contraseña (enmascarada), encriptación, remitente, nombre, receptor, **botón "Enviar correo de prueba"** |
| **SEO** | Título y descripción por defecto, OG image, Google Analytics, verificación de Search Console, robots.txt editable |

Subir un logo nuevo **no borra el anterior** de inmediato: queda en la biblioteca. Ningún cambio de configuración destruye un archivo.

### 2.13 Reportes — `/admin/reportes`

Propiedades por estado/tipo/ubicación · leads por fuente y por propiedad · clics de WhatsApp en el tiempo · propiedades más vistas · noticias más leídas. Rango de fechas + exportación CSV.

### 2.14 Auditoría — `/admin/auditoria`

Listado filtrable por usuario, acción, entidad y fecha. Detalle con diff visual de `old_values` vs `new_values`. **Solo lectura** — no se puede editar ni borrar desde el panel.

---

## 3. Roles y permisos

| Permiso | super_admin | admin | editor | agent |
|---|:---:|:---:|:---:|:---:|
| `manage_settings` | ✅ | ✅ | — | — |
| `manage_properties` | ✅ | ✅ | ✅ | solo propias |
| `manage_property_images` | ✅ | ✅ | ✅ | solo propias |
| `manage_news` | ✅ | ✅ | ✅ | — |
| `manage_leads` | ✅ | ✅ | — | solo asignados |
| `manage_agents` | ✅ | ✅ | — | — |
| `manage_pages` | ✅ | ✅ | ✅ | — |
| `manage_seo` | ✅ | ✅ | ✅ | — |
| `view_reports` | ✅ | ✅ | — | — |
| `view_audit` | ✅ | — | — | — |
| `manage_users` | ✅ | — | — | — |

Se aplica con **Policies** de Laravel (`PropertyPolicy`, `NewsPolicy`, `LeadPolicy`, `SettingPolicy`), no solo ocultando botones. El menú se filtra por permiso, pero la autorización real ocurre en el servidor.

`super_admin` no puede ser eliminado ni degradado por otro usuario, y siempre debe existir al menos uno.
