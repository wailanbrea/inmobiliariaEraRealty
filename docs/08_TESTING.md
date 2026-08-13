# 08 — Estrategia de pruebas

**Framework:** Pest 3 sobre PHPUnit · **BD de pruebas:** SQLite en memoria (`RefreshDatabase`)

> El FULLTEXT de MariaDB no existe en SQLite. Las pruebas de búsqueda por texto corren contra una BD MariaDB dedicada (`era_realty_testing`), no en memoria. El resto usa SQLite por velocidad.

---

## 1. Qué se prueba y qué no

Se prueba lo que rompe dinero o seguridad:

- Que un lead **nunca** se pierda aunque falle el correo.
- Que un borrador **nunca** aparezca en público ni en el sitemap.
- Que no se pueda subir un `.php` disfrazado de imagen.
- Que el link de WhatsApp se genere bien con cualquier formato de número.
- Que un `editor` no pueda tocar la configuración.

No se prueba: que Laravel funcione, que Eloquent guarde, ni el aspecto visual pixel a pixel.

---

## 2. Cobertura por módulo

### Auth
- Login con credenciales válidas / inválidas.
- Rate limit tras 5 intentos.
- Rutas admin redirigen a login sin sesión.
- Recuperación de contraseña con token válido y expirado.
- `login_failed` queda en auditoría.

### Settings
- Guardar y recuperar por tipo (boolean, json, image).
- Los `is_public = 0` **no** llegan a las vistas públicas.
- `mail_password` se guarda cifrada y no se devuelve en claro.
- La caché se invalida al guardar.

### Properties
- CRUD completo.
- Slug único; colisión genera sufijo.
- `reference_code` autogenerado y único.
- Transiciones de estado válidas.
- Soft delete y restauración.
- **Solo `available` + `published_at <= now()` aparece en público.**
- Filtros: cada uno por separado y combinados.
- Ordenamientos.
- Un `agent` solo edita sus propias propiedades.

### PropertyImages
- Sube imagen válida → 3 ficheros en disco + registro.
- Rechaza: >5 MB, extensión no permitida, `.php` renombrado a `.jpg`, archivo que no es imagen.
- Rechaza la número 31.
- Reordenar persiste.
- Marcar principal desmarca la anterior — **exactamente una** principal siempre.
- Borrar la principal promueve la siguiente.
- Borrar elimina los 3 ficheros del disco.
- El EXIF GPS no sobrevive al procesamiento.

### Leads y formularios
- Cada uno de los 5 formularios guarda su lead con el `source` correcto.
- Honeypot lleno → descartado, sin lead.
- Envío en menos de 3 s → marcado `spam`.
- Rate limit tras 5 envíos.
- Se registran IP y user agent.
- **Si el correo falla, el lead persiste.** (test explícito con `Mail::shouldReceive()->andThrow()`)
- Se envía el mailable correcto al destinatario correcto (`Mail::fake()`).

### WhatsApp
Tabla de casos de normalización:

| Entrada | Esperado |
|---|---|
| `(809) 555-0100` | `18095550100` |
| `809-555-0100` | `18095550100` |
| `+1 809 555 0100` | `18095550100` |
| `8295550100` | `18295550100` |
| `18495550100` | `18495550100` |

- Interpolación de `{reference_code}`, `{title}`, `{price}`, `{url}`.
- Codificación URL correcta de acentos y comas.
- `POST /wa/click` registra el clic.
- El registro fallido no rompe la navegación.

### News
- CRUD, slug, estados.
- Programada con fecha futura no aparece en público hasta su fecha.
- **El HTML de TipTap se sanea**: `<script>` y `onerror=` no sobreviven al guardado.
- Búsqueda FULLTEXT.
- Relacionadas por categoría.

### SEO
- Cada página pública devuelve title, description y canonical no vacíos.
- Propiedad en borrador → `noindex` y ausente del sitemap.
- `sitemap.xml` es XML válido y contiene solo publicadas.
- JSON-LD válido y parseable en detalle de propiedad y de noticia.
- `robots.txt` responde.

### Permisos
Matriz de [03_ADMIN_PANEL.md](03_ADMIN_PANEL.md) §3 recorrida como data provider: cada rol × cada ruta → 200 o 403 según corresponda.

### Auditoría
- Crear, editar y borrar propiedad generan su log.
- Cambio de configuración genera log.
- `password` y `mail_password` **no** aparecen en `old_values`/`new_values`.

---

## 3. Pruebas manuales (checklist de Fase 10)

Lo que no se automatiza pero sí se verifica antes de entregar:

**Responsive** — 375 px, 768 px, 1024 px, 1440 px, 1920 px en cada página pública.
**Navegadores** — Chrome, Firefox, Edge, Safari iOS, Chrome Android.
**Efectos** — 60 fps sostenidos; comportamiento correcto con `prefers-reduced-motion` activo; **con JavaScript desactivado el contenido sigue siendo legible**.
**Accesibilidad** — navegación completa por teclado, foco visible, contraste AA, lectura con NVDA de home y detalle.
**Correo real** — envío con SMTP real y revisión de que no caiga en spam (SPF/DKIM).
**WhatsApp real** — abrir cada botón desde un móvil y comprobar el mensaje precargado.
**Carga de imágenes** — subir 30 fotos de 4 MB desde móvil con conexión lenta.

---

## 4. Comandos

```bash
php artisan test                       # todo
php artisan test --filter=Property     # un módulo
php artisan test --coverage            # cobertura
./vendor/bin/pint                      # formato
```

**Objetivo de cobertura:** ≥ 70 % global, ≥ 90 % en Services (donde vive la lógica de negocio).

## 5. Datos de prueba

Factories para todos los modelos. `DemoDataSeeder` (solo local) con 24 propiedades, 8 noticias, 3 agentes y 15 leads, usando las ubicaciones y tipos reales del diseño para que las capturas de revisión se parezcan al sitio final.
