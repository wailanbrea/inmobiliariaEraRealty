# ERA Realty RD

Sitio web inmobiliario y panel de administración para **ERA Realty RD**
(República Dominicana). Bilingüe español/inglés, doble moneda USD/DOP,
100 % responsive y con una capa de efectos construida sobre el diseño
`estate_elite`.

Sustituye al sitio anterior en WordPress (`erarealtyrd.com`), del que se
importa el catálogo con un comando incluido.

---

## 1. Qué hay dentro

| | |
|---|---|
| **Framework** | Laravel 12 · PHP 8.2 · MariaDB 11.4 |
| **Frontend** | Blade + Livewire 3 + Alpine · Tailwind CSS 4 |
| **Efectos** | GSAP + Lenis, cargados solo cuando aportan (ver §7) |
| **Pruebas** | Pest 3 — **619 pruebas, 1 330 aserciones** |
| **Catálogo** | 122 propiedades reales con 674 fotos |
| **Idiomas** | Español (sin prefijo) e inglés (`/en`), con URL traducidas |

### Sitio público

Inicio · Propiedades con 18 filtros · Ficha de propiedad con galería y
lightbox · Comparador de hasta 4 · Invierte · Sobre nosotros · Noticias ·
Contáctanos · Publica tu propiedad.

### Panel `/admin`

Dashboard con métricas · Propiedades · Imágenes · Media · Noticias · Leads ·
Agentes · Catálogo · Contenido editable · WhatsApp · Reportes · Auditoría ·
Usuarios · Configuración.

---

## 2. Instalación local

Requiere PHP 8.2+, Composer, Node 20+ y MySQL/MariaDB.

```bash
git clone https://github.com/wailanbrea/inmobiliariaEraRealty.git
cd inmobiliariaEraRealty
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Crea la base de datos y ajusta `.env`:

```
DB_DATABASE=era_realty
DB_USERNAME=root
DB_PASSWORD=
APP_URL=http://localhost:8000
```

Luego:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Abre **http://localhost:8000**.

### Acceso al panel

`migrate --seed` crea un super administrador y **muestra su contraseña en la
consola**:

```
Usuario administrador creado
  Correo:     admin@erarealtyrd.com
  Contrasena: k7Qm2xR9vT4nB8sL
```

| | |
|---|---|
| URL | http://localhost:8000/admin/login |
| Correo | `admin@erarealtyrd.com` (o `ADMIN_EMAIL` del `.env`) |
| Contraseña | La que imprime el seeder — **aleatoria en cada instalación** |

Se pide cambiarla en el primer acceso.

> Este README no publica ninguna contraseña a propósito: el repositorio es
> público, y una credencial escrita aquí acabaría en producción tarde o
> temprano.

Si pierdes la contraseña o el seeder ya corrió:

```bash
php artisan admin:password admin@erarealtyrd.com
```

Genera una nueva, la muestra en pantalla y obliga a cambiarla al entrar.
`--list` enseña los usuarios existentes y `--promote` da el rol de super
administrador.

**El panel no necesita correo configurado.** Un super administrador puede
crear cuentas y restablecer contraseñas desde `/admin/usuarios`; la clave se
genera y se muestra una sola vez. Ver §6.

---

## 3. Importar el catálogo del sitio anterior

```bash
php artisan era:import --dry-run          # simula, no escribe nada
php artisan era:import --limit=45         # importa de verdad
php artisan era:import --force            # reimporta las existentes
```

Lee la API REST de WordPress de `erarealtyrd.com`, interpreta las fichas y
descarga la galería, convirtiéndola a WebP.

Trae **las 122 fichas del catálogo** con sus fotos.

**Qué importa y qué no.** El sitio anterior tiene los campos estructurados
—«No. Habitaciones», «Baños»— **vacíos en las 122 fichas**; los datos van en
texto libre. Se importa lo que se lee con certeza (título, descripción,
referencia, ubicación, precio, moneda, operación, tipo, fecha) y se deja en
blanco lo demás, en vez de inventarlo.

**Dos formatos.** Las fichas recientes usan `Ref. 735-V`, y el sufijo da el
tipo: `V` villa, `A` apartamento, `S` solar. Las 21 más antiguas usan otro
—`Número de Referencia LA-JA-0234` y una primera línea `Provincia – Tipo`—,
así que el tipo se deduce del texto. Descartarlas por no encajar en el primer
patrón dejaba fuera una sexta parte del catálogo.

**Los baños solo se leen cuando la ficha da una cifra explícita.** El sitio
anterior los describe habitación por habitación —«Baño de visita», «Habitación
principal con baño», «Medio baño en área social»— y contar esas menciones da
números equivocados: en una ficha real el recuento sale 5 cuando la respuesta
es 4,5. Publicar baños inventados en una inmobiliaria es peor que dejarlos
vacíos.

Al terminar, el comando enumera lo que necesita una decisión humana:

- **Referencias repetidas en origen.** `719-A` está en dos fichas distintas.
  Se importan ambas y a la segunda se le añade el id de WordPress.
- **Precios por m².** Quince fichas dicen «US$55.00 x m²». Guardar 55 como
  precio de un terreno de 30 491 m² publicaría una propiedad de casi dos
  millones a 55 dólares, así que quedan sin precio y el dato literal va al
  principio de la descripción.

---

## 4. Despliegue

### 4.1 Requisitos del servidor

| | |
|---|---|
| PHP | 8.2 o superior, con `gd`, `curl`, `openssl`, `mbstring`, `pdo_mysql`, `zip` |
| Base de datos | MySQL 8 / MariaDB 10.6+ |
| Servidor web | Apache o Nginx apuntando a `public/` |
| Cron | Una línea, para las tareas programadas |

### 4.2 Pasos

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Cron del servidor:

```
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Ejecuta semanalmente la poda del registro de auditoría y mensualmente la
revisión de la biblioteca de medios.

### 4.3 `.env` de producción

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://erarealtyrd.com
```

`APP_DEBUG=false` **no es opcional**: con `true`, cualquier error muestra el
código fuente y las variables de entorno al visitante.

### 4.4 opcache: mide donde toca

| Cómo se sirve | TTFB del listado |
|---|--:|
| `php artisan serve` | **420 ms** |
| Apache + opcache | **51 ms** |

La diferencia no es Apache: es **opcache**, que viene apagado en el SAPI de
consola (`opcache.enable_cli=0`). Sin él, PHP recompila ~900 archivos en cada
petición.

**Nunca juzgues el rendimiento con `artisan serve`.** Comprueba en producción:

```php
opcache_get_status(false)['opcache_enabled']
```

### 4.5 Certificados TLS en Windows/XAMPP

Si `era:import` o el envío de correo fallan con *«unable to get local issuer
certificate»*, a `php.ini` le falta el paquete de certificados raíz:

```ini
curl.cainfo = "C:\xampp\php\extras\cacert.pem"
openssl.cafile = "C:\xampp\php\extras\cacert.pem"
```

Sirve el que trae Git for Windows
(`C:\Program Files\Git\mingw64\etc\ssl\certs\ca-bundle.crt`). **No desactives
la verificación** como atajo.

---

## 5. Correo

El sitio envía tres correos: aviso interno de lead, confirmación al interesado
y recuperación de contraseña. **Ninguno es imprescindible** — el panel
funciona sin correo (§6) y las respuestas a clientes salen del buzón o el
WhatsApp del asesor.

Tres formas de configurarlo, de mejor a peor entregabilidad:

1. **API HTTP** (`resend`, `postmark`, `ses`). Ya están en `config/mail.php`;
   solo falta el paquete y una clave. Sin SMTP de por medio.
2. **SMTP** con las credenciales del hosting.
3. **`sendmail`**, sin configuración. Sin SPF/DKIM, muchos correos caen en
   spam.

Se configura desde `/admin/configuracion/correo`, con envío de prueba
incluido. La contraseña se guarda cifrada y **nunca aparece en la auditoría**.

---

## 6. Acceso sin depender del correo

Tres capas, pensadas para que nadie se quede fuera del panel:

1. **`/admin/usuarios`** — un super administrador crea cuentas y restablece
   contraseñas. La clave se genera en formato `XXXX-XXXX-XXXX-XXXX`, sin
   caracteres que se confundan al dictarla por teléfono, y se muestra una
   sola vez.
2. **Segundo super administrador.** No cuesta nada y cubre el caso realista.
3. **`php artisan admin:password`** — la última red, con acceso al servidor.
   Acepta `--list` y `--promote`.

Reglas que el sistema impone y no se pueden saltar desde la interfaz:

- Nadie puede quitarse a sí mismo el rol de super administrador.
- Nadie puede desactivar su propia cuenta.
- **El último super administrador activo es intocable.**

---

## 7. Rendimiento

Medido sobre el catálogo completo (**122 propiedades, 674 fotos**):

| Pantalla | Consultas | HTML |
|---|--:|--:|
| Listado | 11 | 104 KB |
| Detalle con galería | 11 | 56 KB |
| Sitemap (110 URL) | — | — |

**Las consultas no crecen con el catálogo:** el listado hacía 11 con 12
propiedades y hace 11 con 122. Lo garantiza `chaperone()` en las relaciones de
imagen, más una prueba que falla si alguien reintroduce un N+1.

Bajo Apache con opcache el listado responde en ~51 ms.

Navegador, listado completo: FCP 608 ms · carga 753 ms · **CLS 0,0000** ·
723 KB, de los cuales 401 KB son imágenes.

**El móvil no paga la capa de efectos.** GSAP solo hace falta para el
parallax, y el presupuesto lo prohíbe por debajo de 768 px:

| Módulo | Comprimido | Cuándo se descarga |
|---|--:|---|
| `motion.js` + `compare.js` | **2,3 KB** | Siempre |
| `motion-scroll.js` (GSAP + Lenis) | 50,7 KB | **Solo ≥ 768 px** |

Todo respeta `prefers-reduced-motion`: si el sistema operativo tiene las
animaciones desactivadas, la capa entera no se descarga.

---

## 8. Documentación

En [`docs/`](docs/) hay dieciséis documentos con las decisiones y su porqué:

| | |
|---|---|
| [`01_ARCHITECTURE`](docs/01_ARCHITECTURE.md) | Estructura modular y mapa de rutas |
| [`02_DATABASE_SCHEMA`](docs/02_DATABASE_SCHEMA.md) | Tablas e índices |
| [`09_DEPLOYMENT`](docs/09_DEPLOYMENT.md) | Despliegue y respaldos |
| [`11_CHANGELOG`](docs/11_CHANGELOG.md) | Historial de versiones |
| [`13_MOTION_AND_EFFECTS`](docs/13_MOTION_AND_EFFECTS.md) | Capa de efectos |
| [`14_RESPONSIVE`](docs/14_RESPONSIVE.md) | Método de verificación y defectos |
| [`15_I18N`](docs/15_I18N.md) | Estrategia bilingüe |

---

## 9. Pruebas

```bash
php artisan test
```

619 pruebas. Vigilan, entre otras cosas, que:

- Ninguna credencial llegue al registro de auditoría.
- Borrar un asesor **no** borre sus propiedades.
- El lead se guarde **antes** de intentar el correo.
- El contenido sea legible **sin JavaScript**.
- El listado no lance una consulta por tarjeta.
