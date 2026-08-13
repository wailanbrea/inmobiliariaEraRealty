# Prompt maestro para desarrollar página web inmobiliaria completa

Actúa como **desarrollador web full stack senior, arquitecto de software, especialista en Laravel, MySQL, paneles administrativos, SEO inmobiliario, carga de imágenes, seguridad, emails transaccionales, formularios de contacto, WhatsApp CTA, gestión de noticias/blog y buenas prácticas de desarrollo mantenible**.

## Objetivo general

Desarrollar desde cero una página web inmobiliaria profesional basada en un diseño ya definido.  
El sistema debe incluir:

- Web pública moderna para mostrar propiedades.
- Buscador de propiedades.
- Detalle de propiedad.
- Página de inversión.
- Página sobre nosotros.
- Página de noticias / infórmate.
- Página de contacto.
- Comparador de propiedades si está contemplado en el diseño.
- Panel administrativo completo.
- Backend para administrar propiedades, imágenes, noticias, configuración general, logo, WhatsApp, correo, formularios y SEO.

El resultado debe ser una web inmobiliaria completa, administrable, rápida, segura, responsive y preparada para SEO.

---

# 1. Stack tecnológico recomendado

Usar este stack salvo que se indique lo contrario:

## Backend

- Laravel 12.
- PHP 8.3+.
- MySQL.
- Laravel Sanctum si se usa API separada.
- Laravel Policies para permisos.
- Laravel Form Requests para validaciones.
- Laravel Resources si se usa API.
- Laravel Storage para archivos.
- Laravel Mail para correos.
- Laravel Queues opcional para envío de emails.
- Laravel Scheduler opcional.
- Laravel Pint para formato.
- PHPUnit o Pest para pruebas.

## Frontend

Escoger una de estas opciones según el proyecto:

### Opción recomendada si será SEO fuerte

- Laravel API + Nuxt 3.
- SSR/SSG para páginas públicas.
- Tailwind CSS.

### Opción más simple y rápida

- Laravel Blade + Alpine.js + Tailwind CSS.
- Vue 3 solo para partes interactivas.

### Opción panel moderno

- Laravel + Vue 3 + TypeScript + Pinia + Tailwind CSS.

Si no se ha decidido, usar:

- **Laravel 12 + Blade/Tailwind para web pública**.
- **Laravel 12 + Vue 3/Tailwind para panel admin**, solo si el diseño lo requiere.

Base de datos:

- MySQL.
- Usar índices en campos de búsqueda.
- Usar slugs únicos para URLs públicas.
- Usar soft deletes en entidades importantes.
- No guardar rutas de imágenes absolutas si se puede evitar.
- Guardar archivos usando Laravel Storage.

---

# 2. Reglas obligatorias de desarrollo

Antes de programar:

1. Revisar el diseño existente.
2. Revisar si ya existe proyecto o estructura.
3. No sobrescribir archivos sin analizar.
4. Crear plan de implementación por fases.
5. Crear documentación inicial.
6. Crear TODO del proyecto.
7. Crear CHANGELOG.
8. Preguntar antes de asumir datos críticos.

Durante el desarrollo:

1. Trabajar por módulos.
2. Mantener código limpio y modular.
3. No mezclar lógica de negocio en vistas.
4. No crear controladores gigantes.
5. Usar validaciones formales.
6. Proteger panel administrativo con login.
7. Validar y sanitizar toda entrada del usuario.
8. Proteger subida de imágenes.
9. Optimizar imágenes.
10. Registrar errores importantes.
11. Mantener SEO desde el inicio.
12. Mantener responsive real.
13. Actualizar documentación después de cada módulo.

Después de cada cambio:

1. Actualizar TODO.
2. Actualizar CHANGELOG.
3. Indicar archivos creados.
4. Indicar archivos modificados.
5. Indicar comandos ejecutados.
6. Indicar pruebas realizadas.
7. Indicar pendientes.

---

# 3. Estructura de carpetas recomendada

Crear una estructura ordenada:

```text
app/
  Modules/
    Auth/
    Dashboard/
    Settings/
    Properties/
    PropertyImages/
    PropertyTypes/
    Locations/
    Agents/
    Leads/
    News/
    Pages/
    Contact/
    Seo/
    Media/
    Reports/
    Audit/

resources/
  views/
    public/
      home.blade.php
      properties/
      property-detail/
      invest/
      about/
      news/
      contact/
    admin/
      dashboard/
      properties/
      news/
      settings/
      leads/
      agents/
      media/
      pages/

public/
  storage/

database/
  migrations/
  seeders/

docs/
  00_PROJECT_OVERVIEW.md
  01_ARCHITECTURE.md
  02_DATABASE_SCHEMA.md
  03_ADMIN_PANEL.md
  04_PUBLIC_PAGES.md
  05_MEDIA_UPLOADS.md
  06_EMAIL_AND_WHATSAPP.md
  07_SEO.md
  08_TESTING.md
  09_DEPLOYMENT.md
  10_TODO_MASTER.md
  11_CHANGELOG.md
  12_KNOWN_ISSUES.md
```

Si no se usa carpeta `Modules`, mantener igualmente separación clara por controladores, servicios, requests, modelos y vistas.

---

# 4. Módulos principales del sistema

El sistema debe tener estos módulos:

1. Autenticación admin.
2. Dashboard administrativo.
3. Configuración general.
4. Gestión de logo.
5. Configuración de WhatsApp.
6. Configuración de correo.
7. Gestión de propiedades.
8. Gestión de imágenes de propiedades.
9. Gestión de tipos de propiedad.
10. Gestión de ubicaciones.
11. Gestión de agentes.
12. Gestión de leads.
13. Gestión de noticias / blog.
14. Gestión de páginas informativas.
15. Formularios de contacto.
16. SEO.
17. Media manager.
18. Reportes básicos.
19. Auditoría básica.

---

# 5. Panel administrativo

Crear un panel administrativo privado.

## Login admin

Debe incluir:

- Email.
- Contraseña.
- Recordar sesión.
- Recuperar contraseña.
- Validación de errores.
- Protección CSRF.
- Rate limiting.

## Dashboard admin

Debe mostrar:

- Total de propiedades.
- Propiedades disponibles.
- Propiedades vendidas.
- Propiedades no disponibles.
- Leads nuevos.
- Noticias publicadas.
- Propiedades más recientes.
- Contactos recibidos.
- Accesos rápidos:
  - Nueva propiedad.
  - Nueva noticia.
  - Configuración.
  - Ver sitio público.

---

# 6. Configuración general del sitio

Crear módulo de configuración editable desde el panel.

Debe permitir cambiar:

- Nombre de la inmobiliaria.
- Logo principal.
- Logo secundario si aplica.
- Favicon.
- Teléfono principal.
- WhatsApp principal.
- Link de WhatsApp generado automáticamente.
- Email principal.
- Email receptor de formularios.
- Dirección.
- Horario.
- Redes sociales.
- Facebook.
- Instagram.
- YouTube.
- TikTok.
- LinkedIn.
- Texto del footer.
- Meta title global.
- Meta description global.
- Imagen Open Graph global.

## Tabla sugerida: settings

```text
settings
- id
- key
- value
- type
- group
- is_public
- created_at
- updated_at
```

Ejemplos de keys:

```text
site_name
site_logo
site_logo_dark
site_favicon
contact_phone
contact_whatsapp_number
contact_whatsapp_message
contact_whatsapp_link
contact_email
contact_form_recipient_email
contact_address
contact_schedule
social_facebook
social_instagram
social_youtube
social_tiktok
footer_text
seo_default_title
seo_default_description
seo_default_og_image
```

## Regla para WhatsApp

El administrador debe escribir el número de WhatsApp en formato internacional o local.  
El sistema debe limpiar el número y generar automáticamente el link:

```text
https://wa.me/{numero_limpio}?text={mensaje_url_encoded}
```

Ejemplo:

```text
Número: 18290000000
Mensaje: Hola, quiero información sobre una propiedad.
Link generado: https://wa.me/18290000000?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20una%20propiedad.
```

Debe permitir editar:

- Número.
- Mensaje general.
- Mensaje por propiedad.
- Mostrar/ocultar botón flotante.
- Posición del botón.

---

# 7. Configuración de correo

Crear pantalla para configurar correo.

Debe permitir configurar:

- Driver de correo.
- Host SMTP.
- Puerto.
- Usuario.
- Contraseña.
- Encriptación.
- Email remitente.
- Nombre remitente.
- Email receptor de formularios.
- Enviar correo de prueba.

Variables esperadas en `.env`:

```text
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=
CONTACT_FORM_RECIPIENT=
```

## Seguridad

- No mostrar contraseñas SMTP completas en pantalla.
- Guardar credenciales sensibles de forma segura.
- Si se guardan en base de datos, cifrarlas.
- Validar el envío de correo antes de guardar como configuración activa.
- Registrar errores de envío.

## Correos que debe enviar el sistema

1. Contacto general.
2. Solicitud de información sobre propiedad.
3. Solicitud para publicar propiedad.
4. Contacto desde página Invierte.
5. Notificación interna al administrador.
6. Confirmación opcional al cliente.

---

# 8. Gestión de propiedades

Crear CRUD completo de propiedades.

## Campos principales

```text
properties
- id
- title
- slug
- reference_code
- operation_type
- property_type_id
- status
- price
- currency
- province_id nullable
- city_id nullable
- sector_id nullable
- address nullable
- show_exact_location boolean
- latitude nullable
- longitude nullable
- bedrooms nullable
- bathrooms nullable
- parking_spaces nullable
- construction_area nullable
- land_area nullable
- floor_level nullable
- maintenance_fee nullable
- is_furnished boolean
- is_featured boolean
- is_investment boolean
- is_project boolean
- short_description
- description
- amenities_json nullable
- features_json nullable
- agent_id nullable
- owner_name nullable
- owner_phone nullable
- meta_title nullable
- meta_description nullable
- og_image nullable
- published_at nullable
- created_by_user_id
- updated_by_user_id nullable
- deleted_at nullable
- created_at
- updated_at
```

## Estados de propiedad

```text
available
sold
rented
reserved
not_available
draft
paused
```

## Tipos de operación

```text
sale
rent
temporary_rent
investment
```

## Tipos de propiedad

Ejemplos:

```text
Apartamento
Casa
Villa
Solar
Local comercial
Nave
Finca
Terreno
Penthouse
Proyecto
Oficina
```

## Funciones del CRUD

El administrador debe poder:

- Crear propiedad.
- Editar propiedad.
- Guardar como borrador.
- Publicar propiedad.
- Pausar propiedad.
- Marcar como vendida.
- Marcar como alquilada.
- Marcar como reservada.
- Marcar como no disponible.
- Destacar propiedad.
- Marcar como oportunidad de inversión.
- Subir imágenes.
- Ordenar imágenes.
- Elegir imagen principal.
- Eliminar imagen.
- Editar SEO.
- Asignar agente.
- Ver leads asociados.
- Ver vista previa pública.

---

# 9. Subida de imágenes de propiedades

La subida de imágenes debe ser **fácil, visual e intuitiva**.

## Requisitos UX

Crear un componente de subida que permita:

- Arrastrar y soltar imágenes.
- Seleccionar múltiples imágenes.
- Ver previsualización.
- Reordenar imágenes con drag and drop.
- Marcar imagen principal.
- Eliminar imágenes.
- Mostrar progreso de subida.
- Mostrar errores claros.
- Comprimir imágenes automáticamente.
- Convertir a WebP si aplica.
- Crear miniaturas.
- Validar tamaño máximo.
- Validar tipo de archivo.
- Permitir editar alt text.
- Permitir editar título de imagen.

## Validaciones

Permitir solo:

```text
jpg
jpeg
png
webp
```

Tamaño máximo recomendado:

```text
5 MB por imagen
```

Cantidad máxima sugerida por propiedad:

```text
30 imágenes
```

## Tabla sugerida

```text
property_images
- id
- property_id
- path
- thumbnail_path nullable
- webp_path nullable
- original_name
- alt_text nullable
- title nullable
- sort_order
- is_main
- size
- mime_type
- uploaded_by_user_id
- created_at
- updated_at
```

## Procesamiento

Al subir imagen:

1. Validar archivo.
2. Guardar original o versión optimizada.
3. Crear miniatura.
4. Crear versión WebP si aplica.
5. Guardar registro en base de datos.
6. Devolver preview.
7. Permitir reordenar.

---

# 10. Media manager general

Crear un módulo de medios reutilizable.

Debe permitir subir imágenes para:

- Propiedades.
- Noticias.
- Logo.
- Favicon.
- Open Graph.
- Páginas informativas.
- Banners.
- Agentes.

Tabla sugerida:

```text
media_files
- id
- disk
- path
- thumbnail_path nullable
- webp_path nullable
- original_name
- mime_type
- size
- width nullable
- height nullable
- alt_text nullable
- title nullable
- context nullable
- uploaded_by_user_id
- created_at
- updated_at
```

Debe tener:

- Biblioteca de archivos.
- Buscar imagen.
- Filtrar por contexto.
- Reutilizar imagen existente.
- Eliminar si no está en uso.
- Copiar URL.
- Vista grid.
- Vista lista.

---

# 11. Noticias / Infórmate

Crear módulo de noticias o blog para la sección “Infórmate”.

Debe permitir subir noticias fácilmente desde el panel.

## Campos de noticia

```text
news_posts
- id
- title
- slug
- excerpt
- content
- featured_image
- status
- category_id nullable
- author_id
- meta_title nullable
- meta_description nullable
- og_image nullable
- published_at nullable
- created_at
- updated_at
- deleted_at nullable
```

## Categorías

```text
news_categories
- id
- name
- slug
- description nullable
- is_active
- created_at
- updated_at
```

## Estados

```text
draft
published
scheduled
archived
```

## Editor de noticias

Debe ser fácil e intuitivo.

Debe incluir:

- Título.
- Slug automático.
- Extracto.
- Imagen destacada.
- Editor visual o Markdown.
- Categoría.
- Estado.
- Fecha de publicación.
- SEO title.
- SEO description.
- Imagen Open Graph.
- Vista previa.
- Guardar borrador.
- Publicar.

## Editor recomendado

Usar uno de estos:

- TipTap.
- Editor.js.
- Quill.
- Markdown editor simple.

El contenido debe permitir:

- Títulos.
- Párrafos.
- Imágenes.
- Listas.
- Enlaces.
- Citas.
- Video embebido opcional.

## Web pública de noticias

Crear:

```text
/informate
/informate/{slug}
```

La página de listado debe tener:

- Noticias recientes.
- Categorías.
- Buscador.
- Paginación.
- Noticias destacadas.

El detalle debe tener:

- Imagen principal.
- Título.
- Fecha.
- Autor.
- Contenido.
- Compartir en WhatsApp.
- Compartir en Facebook.
- Noticias relacionadas.
- CTA final de contacto.

---

# 12. Página pública de propiedades

Crear rutas públicas:

```text
/
 /propiedades
 /propiedades/{slug}
 /invierte
 /sobre-nosotros
 /informate
 /informate/{slug}
 /contactanos
```

## Home

Debe consumir datos reales del backend:

- Propiedades destacadas.
- Propiedades recientes.
- Propiedades de inversión.
- Noticias recientes.
- Configuración de contacto.
- Logo.
- WhatsApp.
- Redes sociales.

## Página propiedades

Debe incluir:

- Buscador.
- Filtros.
- Listado.
- Paginación.
- Ordenamiento.
- Estados.
- Botón WhatsApp.
- Botón comparar si aplica.

## Detalle propiedad

Debe incluir:

- Galería.
- Precio.
- Estado.
- Descripción.
- Características.
- Amenidades.
- Ubicación.
- Agente.
- Formulario.
- WhatsApp con mensaje prellenado.
- Propiedades similares.
- SEO dinámico.

---

# 13. Leads y formularios

Todo formulario debe guardar lead en base de datos y enviar correo.

## Tabla leads

```text
leads
- id
- source
- name
- phone
- email nullable
- message nullable
- property_id nullable
- news_post_id nullable
- interest_type nullable
- status
- assigned_to_user_id nullable
- ip_address nullable
- user_agent nullable
- created_at
- updated_at
```

## Fuentes

```text
contact_page
property_detail
publish_property
investment_page
whatsapp_click
news_contact
```

## Estados

```text
new
contacted
interested
visit_scheduled
negotiating
closed
lost
spam
```

## Formularios públicos

Crear:

1. Formulario de contacto general.
2. Formulario en detalle de propiedad.
3. Formulario “Publica tu propiedad”.
4. Formulario de inversión.
5. Formulario en noticia si aplica.

Cada formulario debe:

- Validar datos.
- Guardar lead.
- Enviar email al administrador.
- Mostrar mensaje de éxito.
- Proteger contra spam.
- Usar honeypot o captcha opcional.
- Registrar IP y user agent.

---

# 14. WhatsApp

El sistema debe manejar WhatsApp de forma configurable.

## Requisitos

- Número editable desde admin.
- Link generado automáticamente.
- Mensaje general editable.
- Mensaje por propiedad editable.
- Botón flotante global.
- Botón por propiedad.
- Botón en página Invierte.
- Botón en contacto.
- Botón en noticias si aplica.
- Registrar clics opcionalmente.

## Tabla sugerida para clics

```text
whatsapp_clicks
- id
- property_id nullable
- source
- phone_number
- generated_message
- ip_address nullable
- user_agent nullable
- created_at
```

## Mensajes base

General:

```text
Hola, quiero recibir asesoría inmobiliaria.
```

Propiedad:

```text
Hola, estoy interesado en la propiedad {reference_code} - {title}. ¿Está disponible?
```

Inversión:

```text
Hola, quiero información sobre oportunidades de inversión inmobiliaria.
```

---

# 15. Agentes inmobiliarios

Crear módulo de agentes.

Tabla:

```text
agents
- id
- user_id nullable
- name
- position nullable
- photo nullable
- phone nullable
- whatsapp nullable
- email nullable
- bio nullable
- is_active
- sort_order
- created_at
- updated_at
```

Funciones:

- Crear agente.
- Editar agente.
- Subir foto.
- Asignar propiedades.
- Mostrar agente en detalle de propiedad.
- Contactar por WhatsApp.
- Contactar por email.

---

# 16. Ubicaciones

Crear módulos de ubicación:

```text
provinces
cities
sectors
```

Campos:

```text
id
name
slug
is_active
created_at
updated_at
```

Las propiedades deben poder filtrarse por:

- Provincia.
- Ciudad.
- Sector.

---

# 17. SEO

Cada página importante debe tener SEO editable.

## Propiedades

- Slug.
- Meta title.
- Meta description.
- OG image.
- Canonical URL.
- Schema.org si aplica.
- Breadcrumbs.

## Noticias

- Slug.
- Meta title.
- Meta description.
- OG image.
- Fecha de publicación.
- Autor.

## Páginas generales

- Home.
- Propiedades.
- Invierte.
- Sobre nosotros.
- Infórmate.
- Contacto.

Crear:

- Sitemap XML.
- Robots.txt.
- URLs amigables.
- Open Graph.
- Twitter Cards.
- Schema.org básico.

URLs sugeridas:

```text
/
 /propiedades
 /propiedades/{slug}
 /invierte
 /sobre-nosotros
 /informate
 /informate/{slug}
 /contactanos
```

---

# 18. Páginas editables

Crear módulo Pages o Content Sections para editar contenido básico.

Debe permitir administrar:

- Invierte.
- Sobre nosotros.
- Contacto.
- Textos del home.
- Banners.
- CTAs.

Tabla sugerida:

```text
pages
- id
- key
- title
- slug
- content
- featured_image nullable
- meta_title nullable
- meta_description nullable
- status
- created_at
- updated_at
```

O usar tabla de secciones:

```text
content_sections
- id
- page_key
- section_key
- title nullable
- subtitle nullable
- content nullable
- image nullable
- button_text nullable
- button_url nullable
- sort_order
- is_active
- created_at
- updated_at
```

---

# 19. Comparador de propiedades

Si el diseño contempla comparador, implementar:

- Agregar propiedad a comparar.
- Guardar en sesión/localStorage.
- Comparar hasta 3 o 4 propiedades.
- Mostrar tabla comparativa.
- Eliminar propiedad.
- Limpiar comparador.

Campos comparables:

- Imagen.
- Título.
- Precio.
- Estado.
- Tipo.
- Ubicación.
- Superficie.
- Habitaciones.
- Baños.
- Parqueos.
- Uso.
- Código.
- Botón contacto.

---

# 20. Reportes básicos

Crear reportes admin:

- Propiedades publicadas.
- Propiedades por estado.
- Leads por fuente.
- Leads por propiedad.
- Noticias publicadas.
- Clics en WhatsApp.
- Propiedades más recientes.
- Formularios recibidos.

---

# 21. Auditoría básica

Registrar acciones importantes:

- Login admin.
- Creación de propiedad.
- Edición de propiedad.
- Eliminación de propiedad.
- Cambio de estado.
- Subida de imagen.
- Eliminación de imagen.
- Cambio de configuración.
- Cambio de logo.
- Cambio de WhatsApp.
- Cambio de correo.
- Publicación de noticia.
- Eliminación de noticia.

Tabla sugerida:

```text
audit_logs
- id
- user_id nullable
- action
- entity_type nullable
- entity_id nullable
- old_values json nullable
- new_values json nullable
- ip_address nullable
- user_agent nullable
- created_at
```

---

# 22. Seguridad

Implementar:

- Login protegido.
- CSRF.
- Rate limiting en formularios.
- Validación de archivos.
- Validación de imágenes.
- Protección contra XSS.
- Sanitización de contenido del editor.
- Protección contra spam.
- Roles y permisos básicos.
- No exponer rutas admin sin auth.
- No mostrar credenciales SMTP.
- No permitir subir scripts como imágenes.
- Limitar tamaño de archivos.
- Usar storage público solo para archivos permitidos.

---

# 23. Roles y permisos

Roles iniciales:

```text
super_admin
admin
editor
agent
```

Permisos sugeridos:

```text
manage_settings
manage_properties
manage_property_images
manage_news
manage_leads
manage_agents
manage_pages
manage_seo
view_reports
```

---

# 24. Fases de desarrollo

## Fase 0 — Preparación

- [ ] Revisar diseño.
- [ ] Crear proyecto.
- [ ] Configurar MySQL.
- [ ] Configurar .env.
- [ ] Crear estructura de carpetas.
- [ ] Crear documentación.
- [ ] Crear TODO.
- [ ] Crear CHANGELOG.
- [ ] Configurar autenticación admin.
- [ ] Configurar storage.
- [ ] Crear `php artisan storage:link`.

## Fase 1 — Configuración general

- [ ] Tabla settings.
- [ ] Pantalla de configuración.
- [ ] Cambio de logo.
- [ ] Cambio de favicon.
- [ ] Teléfono.
- [ ] WhatsApp.
- [ ] Link WhatsApp automático.
- [ ] Email principal.
- [ ] Redes sociales.
- [ ] SEO global.

## Fase 2 — Propiedades

- [ ] Tablas de propiedades.
- [ ] Tipos de propiedad.
- [ ] Ubicaciones.
- [ ] CRUD propiedades.
- [ ] Estados.
- [ ] Slugs.
- [ ] SEO por propiedad.
- [ ] Vista previa.

## Fase 3 — Imágenes

- [ ] Subida múltiple.
- [ ] Drag and drop.
- [ ] Preview.
- [ ] Reordenar.
- [ ] Imagen principal.
- [ ] Eliminar.
- [ ] Optimizar.
- [ ] Miniaturas.
- [ ] WebP.
- [ ] Alt text.

## Fase 4 — Web pública

- [ ] Home.
- [ ] Header.
- [ ] Footer.
- [ ] Buscador.
- [ ] Listado propiedades.
- [ ] Detalle propiedad.
- [ ] Invierte.
- [ ] Sobre nosotros.
- [ ] Contacto.
- [ ] Responsive.

## Fase 5 — Leads y correo

- [ ] Formularios.
- [ ] Guardar leads.
- [ ] Enviar correo.
- [ ] Configurar SMTP.
- [ ] Correo de prueba.
- [ ] Anti-spam.
- [ ] Panel de leads.

## Fase 6 — Noticias

- [ ] Categorías.
- [ ] CRUD noticias.
- [ ] Editor visual.
- [ ] Imagen destacada.
- [ ] SEO.
- [ ] Publicar.
- [ ] Borrador.
- [ ] Listado público.
- [ ] Detalle público.

## Fase 7 — Agentes

- [ ] CRUD agentes.
- [ ] Foto.
- [ ] Datos contacto.
- [ ] Asignar a propiedad.
- [ ] Mostrar en detalle.

## Fase 8 — SEO y optimización

- [ ] Sitemap.
- [ ] Robots.txt.
- [ ] Open Graph.
- [ ] Schema.org.
- [ ] Meta dinámicos.
- [ ] Lazy loading.
- [ ] Compresión de imágenes.
- [ ] Performance móvil.

## Fase 9 — Reportes y auditoría

- [ ] Reportes básicos.
- [ ] Clics WhatsApp.
- [ ] Propiedades más vistas.
- [ ] Audit logs.
- [ ] Historial de cambios.

## Fase 10 — Testing y despliegue

- [ ] Pruebas backend.
- [ ] Pruebas formularios.
- [ ] Pruebas imágenes.
- [ ] Pruebas email.
- [ ] Pruebas WhatsApp.
- [ ] Pruebas responsive.
- [ ] .env.example.
- [ ] Documentar deploy.
- [ ] Optimizar producción.

---

# 25. Criterios de aceptación

El proyecto se considera correcto si cumple:

- [ ] El admin puede iniciar sesión.
- [ ] El admin puede cambiar logo.
- [ ] El admin puede cambiar favicon.
- [ ] El admin puede editar teléfono.
- [ ] El admin puede editar WhatsApp.
- [ ] El sistema genera link de WhatsApp correctamente.
- [ ] El admin puede configurar correo.
- [ ] El admin puede enviar correo de prueba.
- [ ] Los formularios guardan leads.
- [ ] Los formularios envían correos.
- [ ] El admin puede crear propiedades.
- [ ] El admin puede subir múltiples imágenes.
- [ ] El admin puede reordenar imágenes.
- [ ] El admin puede elegir imagen principal.
- [ ] El admin puede eliminar imágenes.
- [ ] Las imágenes se optimizan.
- [ ] El admin puede crear noticias fácilmente.
- [ ] Las noticias tienen imagen destacada.
- [ ] Las noticias tienen SEO.
- [ ] La web pública muestra propiedades reales.
- [ ] La web pública muestra noticias reales.
- [ ] El buscador funciona.
- [ ] Los filtros funcionan.
- [ ] El detalle de propiedad funciona.
- [ ] El botón WhatsApp funciona.
- [ ] La web es responsive.
- [ ] El SEO básico está implementado.
- [ ] Hay sitemap.
- [ ] Hay robots.txt.
- [ ] Hay auditoría básica.
- [ ] El código está documentado.
- [ ] El TODO está actualizado.
- [ ] El CHANGELOG está actualizado.

---

# 26. Formato obligatorio de respuesta de la IA desarrolladora

Cada vez que trabajes, responde así:

1. Objetivo del paso.
2. Archivos analizados.
3. Archivos creados.
4. Archivos modificados.
5. Código implementado.
6. Comandos ejecutados.
7. Pruebas realizadas.
8. Resultado.
9. Pendientes.
10. Riesgos o advertencias.
11. Documentación actualizada.
12. TODO actualizado.
13. Próximo paso recomendado.

No avanzar al siguiente módulo sin indicar qué quedó terminado y qué falta.

---

# 27. Primera tarea obligatoria

Antes de escribir código funcional, entregar:

1. Estructura de carpetas.
2. Esquema inicial de base de datos.
3. Lista de módulos.
4. Plan por fases.
5. Rutas públicas.
6. Rutas admin.
7. Lista de modelos.
8. Lista de controladores.
9. Lista de servicios.
10. Documentos iniciales.
11. TODO_MASTER.md inicial.
12. CHANGELOG.md inicial.
13. Preguntas necesarias antes de programar.

Después de eso, esperar confirmación para empezar a implementar.

---

# 28. Regla final

Desarrollar la página inmobiliaria completa partiendo del diseño ya definido.

Debe incluir obligatoriamente:

- Backend administrable.
- Subida fácil de imágenes.
- Cambio de logo.
- Configuración de WhatsApp.
- Generación automática de link de WhatsApp.
- Configuración de correo.
- Envío de formularios por email.
- Gestión de propiedades.
- Gestión de noticias.
- Editor fácil para noticias.
- SEO.
- Formularios de contacto.
- Leads.
- Panel admin.
- Responsive.
- Seguridad.
- Código mantenible.
- Documentación.
- TODO.
- CHANGELOG.

No hacer una página estática difícil de mantener.  
Todo contenido importante debe poder administrarse desde el panel.