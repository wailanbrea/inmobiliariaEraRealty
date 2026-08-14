# 09 — Entorno local y despliegue

---

## 1. Entorno local verificado (13/08/2026)

| Componente | Versión | Estado |
|---|---|---|
| PHP | 8.2.33 ZTS x64, OPcache activo | ✅ instalado |
| Composer | 2.8.11 | ✅ |
| Node.js | 24.4.0 | ✅ |
| npm | 11.4.2 | ✅ |
| MariaDB | 11.4.12 | ✅ corriendo (`mysqld` PID 5192) |
| Apache | httpd | ✅ corriendo (PIDs 4696, 7284) |

Ruta del proyecto: `C:\xampp\php\www\Era Realty`

> **Contexto de esta máquina:** PHP, MariaDB y Apache fueron reinstalados el 08/08/2026 tras un incidente de borrado. Sus ficheros de configuración (`php.ini`, `my.ini`, `httpd.conf`) están **escritos a mano**, no son los originales de XAMPP. Cualquier ajuste que se les haga debe respaldarse antes.

### Extensiones PHP requeridas

`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`, **`gd` o `imagick`** (obligatoria para Intervention Image), `zip`, `intl`.

**A verificar en Fase 0** antes de tocar nada: si `gd` no está activa en el `php.ini` artesanal, todo el pipeline de imágenes falla. Se comprueba con `php -m` y se activa descomentando la línea correspondiente, **con copia previa del `php.ini`**.

### Ajustes de `php.ini` para la subida de imágenes

```ini
upload_max_filesize = 10M
post_max_size       = 60M     ; permite 10 imágenes de 5 MB en un envío
max_file_uploads    = 30
memory_limit        = 256M
max_execution_time  = 120
```

Se aplican **tras respaldar** el fichero actual.

---

## 2. Puesta en marcha (Fase 0)

```bash
composer create-project laravel/laravel:^12.0 .
composer require livewire/livewire intervention/image spatie/laravel-permission \
                 spatie/laravel-sitemap mews/purifier
composer require --dev pestphp/pest laravel/pint
npm install
npm install -D tailwindcss@3 postcss autoprefixer
npm install alpinejs gsap lenis sortablejs @tiptap/core @tiptap/starter-kit
```

Base de datos:

```sql
CREATE DATABASE era_realty
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE era_realty_testing
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
npm run dev
```

### Acceso local

Opción A (rápida): `http://localhost/Era%20Realty/public`
Opción B (recomendada): VirtualHost apuntando a `.../Era Realty/public` con `ServerName era-realty.test` + entrada en `hosts`. Evita problemas de rutas absolutas y de espacio en el nombre de carpeta.

**El espacio en "Era Realty" puede dar problemas** con algunas herramientas de línea de comandos en Windows. Todos los comandos de la documentación llevan la ruta entrecomillada. Ver Pregunta 9 sobre renombrar la carpeta a `era-realty`.

---

## 3. `.env` local

```env
APP_NAME="ERA Realty RD"
APP_ENV=local
APP_KEY=            # php artisan key:generate
APP_DEBUG=true
APP_URL=http://era-realty.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=era_realty
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public
QUEUE_CONNECTION=sync
CACHE_STORE=database
SESSION_DRIVER=database

MAIL_MAILER=log        # en local no se envía correo real
MAIL_FROM_ADDRESS="no-reply@erarealtyrd.com"
MAIL_FROM_NAME="${APP_NAME}"
CONTACT_FORM_RECIPIENT="info@erarealtyrd.com"
```

`.env.example` se mantiene actualizado y **sin credenciales reales**. `.env` va en `.gitignore` desde el primer commit.

---

## 4. Producción

### Requisitos mínimos del servidor

PHP 8.2+ · MySQL 8 o MariaDB 10.6+ · Apache con `mod_rewrite` o Nginx · HTTPS obligatorio · ≥ 2 GB RAM · SSD.

### Diferencias de `.env`

```env
APP_ENV=production
APP_DEBUG=false          # innegociable
APP_URL=https://erarealtyrd.com
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
LOG_LEVEL=error
```

### Secuencia de despliegue

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan storage:link
php artisan queue:restart
```

### Permisos

```
storage/            775 (escritura del servidor web)
bootstrap/cache/    775
.env                600
```

### Seguridad del servidor

- El **DocumentRoot debe apuntar a `public/`**, nunca a la raíz del proyecto. Si apunta a la raíz, `.env` queda accesible por HTTP.
- Denegar la ejecución de PHP dentro de `storage/app/public`:

```apache
<Directory "/ruta/public/storage">
    php_flag engine off
    <FilesMatch "\.(php|phtml|phar)$">
        Require all denied
    </FilesMatch>
</Directory>
```

- Cabeceras: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, HSTS.
- Ocultar la versión del servidor.
- Bloquear acceso a `.env`, `.git`, `composer.json`, `docs/`.

### Tareas programadas

```cron
* * * * * cd /ruta && php artisan schedule:run >> /dev/null 2>&1
```

Programa: publicación de noticias programadas (cada minuto), regeneración del sitemap (diaria 03:00), limpieza de `property_views` > 1 año (semanal), poda de logs (mensual).

### Worker de colas

```ini
[program:era-realty-worker]
command=php /ruta/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=1
```

---

## 5. Respaldos

Regla de esta máquina: **rescatar → verificar la copia → luego actuar.** Nada de limpiar, restaurar o reinstalar con datos sin respaldar.

| Qué | Frecuencia | Retención |
|---|---|---|
| Base de datos (`mariadb-dump`) | Diaria | 30 días |
| `storage/app/public` | Diaria incremental | 30 días |
| `.env` | En cada cambio | Permanente, fuera del servidor |
| Código | Git | Permanente |

```bash
mariadb-dump -u USER -p era_realty | gzip > backup_$(date +%F).sql.gz
```

**Antes de cualquier migración destructiva en producción:** dump completo + verificación de que el dump se restaura. Sin excepción.

Restauración probada al menos una vez antes de salir a producción. Un respaldo que nunca se ha restaurado no es un respaldo.

---

## 6. Control de versiones

`git init` en la Fase 0 — el proyecto **no está bajo control de versiones todavía**, y eso es el mayor riesgo operativo abierto.

`.gitignore`: `/vendor`, `/node_modules`, `/public/build`, `/public/storage`, `/storage/*.key`, `.env`, `.env.*` (salvo `.env.example`), `/.idea`, `/.vscode`, `*.log`.

`stitch_era_realty_rd_premium_redesign/` **sí** se versiona: es la referencia de diseño y debe sobrevivir.

Ramas: `main` (producción) · `develop` (integración) · `feature/*` por fase.

## 7. Checklist previo a producción

- [ ] `APP_DEBUG=false` y `APP_ENV=production`
- [ ] `APP_KEY` generada y respaldada fuera del servidor
- [ ] HTTPS con certificado válido y redirección forzada
- [ ] DocumentRoot en `public/`
- [ ] PHP desactivado en `storage/app/public`
- [ ] Contraseña del admin cambiada respecto a la del seeder
- [ ] SMTP real configurado y **correo de prueba enviado con éxito**
- [ ] SPF y DKIM del dominio configurados
- [ ] Número de WhatsApp real verificado desde un móvil
- [ ] Sitemap accesible y enviado a Search Console
- [ ] `robots.txt` correcto — **sin `Disallow: /`**
- [ ] Analytics configurado
- [ ] Respaldos automáticos activos y **restauración probada**
- [ ] Lighthouse móvil ≥ 90
- [ ] Páginas 404 y 500 personalizadas
- [ ] Textos legales publicados

---

## opcache y `php artisan serve`: no midas donde no toca

Medido el 14/08/2026 sobre 31 propiedades reales con 138 fotos.

| Cómo se sirve | TTFB del listado |
|---|--:|
| `php artisan serve` (CLI) | **420 ms** |
| Apache + opcache | **51 ms** |

La diferencia **no** es Apache: es **opcache**. La extensión está cargada, pero
`opcache.enable_cli` viene apagado por defecto, y `php artisan serve` usa el
SAPI de CLI. Sin opcache, PHP recompila los ~900 archivos del framework en
cada petición.

**Consecuencia práctica:** nunca juzgues el rendimiento con `artisan serve`.
Un TTFB de 400 ms ahí puede ser 50 ms en producción, y al revés — una consulta
lenta se disimula entre el ruido de la compilación.

Comprobar en producción que opcache está activo:

```php
opcache_get_status(false)['opcache_enabled']
```

## Presupuesto medido

| Pantalla | TTFB | Consultas | HTML |
|---|--:|--:|--:|
| Listado (12 tarjetas) | 51 ms | 11 | 94 KB |
| Detalle con galería | 42 ms | 14 | 53 KB |
| Comparador | 82 ms | 2 | 21 KB |
| Sitemap (26 URL) | 23 ms | — | 25 KB |

Navegador, listado completo: FCP 608 ms · carga 753 ms · **CLS 0,0000** ·
723 KB totales, de los cuales 401 KB son imágenes.
