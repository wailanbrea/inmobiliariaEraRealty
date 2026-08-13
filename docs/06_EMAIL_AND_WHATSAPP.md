# 06 — Correo y WhatsApp

---

# PARTE A — Correo

## 1. Configuración SMTP desde el panel

El prompt (§7) pide configurar el correo sin tocar `.env`. Se resuelve con precedencia clara:

```
BD (settings, grupo mail)  →  si está completa, gana
        ↓ si no
.env                       →  respaldo
```

`MailConfigService` lee los settings y los aplica en runtime vía `config()->set('mail.mailers.smtp', ...)` desde un middleware/service provider. Así el admin cambia de proveedor SMTP sin acceso al servidor.

### Campos

| Campo | Setting | Ejemplo |
|---|---|---|
| Driver | `mail_mailer` | `smtp` |
| Host | `mail_host` | `smtp.gmail.com` |
| Puerto | `mail_port` | `587` |
| Usuario | `mail_username` | |
| Contraseña | `mail_password` | **cifrada** |
| Encriptación | `mail_encryption` | `tls` / `ssl` |
| Remitente | `mail_from_address` | |
| Nombre remitente | `mail_from_name` | `ERA Realty RD` |
| Receptor de formularios | `contact_form_recipient_email` | |

### Seguridad de la contraseña

- Se guarda con `Crypt::encryptString()` (`is_encrypted = 1`, `is_public = 0`).
- En pantalla **nunca** se devuelve el valor: el campo llega vacío con placeholder `••••••••`. Si el admin lo deja vacío al guardar, se conserva la anterior.
- Excluida de `audit_logs` y de cualquier log de errores.
- La clave de cifrado es `APP_KEY`. Perderla implica reconfigurar el SMTP — documentado en [09_DEPLOYMENT.md](09_DEPLOYMENT.md).

### Correo de prueba

Botón **"Enviar correo de prueba"** en la pestaña de correo:

1. Valida el formulario.
2. Construye un mailer temporal con esos datos (sin guardarlos aún).
3. Envía a la dirección indicada.
4. Éxito → guarda la configuración. Fallo → muestra el error real de SMTP (`Connection refused`, `535 Auth failed`…) y **no guarda**.

Esto cumple la exigencia del prompt: *"validar el envío antes de guardar como configuración activa"*. Evita el escenario habitual de guardar credenciales mal escritas y descubrirlo semanas después por leads perdidos.

---

## 2. Correos del sistema

| # | Correo | Disparador | Destinatario |
|---|---|---|---|
| 1 | `ContactFormMail` | `/contactanos` | Admin |
| 2 | `PropertyInquiryMail` | Formulario del detalle | Admin + agente asignado |
| 3 | `PublishPropertyMail` | `/publica-tu-propiedad` | Admin |
| 4 | `InvestmentInquiryMail` | `/invierte` | Admin |
| 5 | `NewLeadNotificationMail` | Cualquier lead | Admin |
| 6 | `LeadConfirmationMail` | Cualquier lead | **Cliente** (opcional, activable) |
| 7 | `TestMail` | Botón de prueba | Quien se indique |
| 8 | `ResetPasswordMail` | Recuperación admin | Usuario |

Plantillas en `resources/views/emails/`, formato Markdown de Laravel, con logo y colores del sitio (`primary-container #131b2e`, `secondary #0058be`).

Contenido de las notificaciones internas: datos del lead, mensaje, propiedad relacionada con enlace directo a la ficha pública y al lead en el panel, origen, fecha, IP.

## 3. Colas y fallos

- Todos los Mailables implementan `ShouldQueue`.
- En local, `QUEUE_CONNECTION=sync` (sin worker que administrar en XAMPP).
- En producción, `database` + worker supervisado.
- **El lead se guarda en BD antes de intentar enviar el correo.** Si el SMTP falla, el lead no se pierde: queda registrado, el fallo se escribe en el log y el admin lo ve igual en el panel. Este orden es innegociable.
- Reintentos: 3, backoff 60 s.

## 4. Anti-spam

| Medida | Detalle |
|---|---|
| Honeypot | Campo `website` oculto por CSS; si viene lleno → se descarta silenciosamente (respuesta 200 falsa, para no enseñarle al bot que fue detectado) |
| Time-trap | Token con marca de tiempo; envíos en < 3 s se marcan como `spam` |
| Rate limit | `throttle:5,1` por IP en todos los POST públicos |
| CSRF | En todos los formularios |
| Validación estricta | Teléfono con formato RD, email válido, longitud máxima de mensaje |
| Palabras clave | Lista configurable → estado `spam` en vez de descartar (revisable) |
| reCAPTCHA v3 | Preparado, **desactivado por defecto** — activable desde settings si aparece spam real |

---

# PARTE B — WhatsApp

## 5. Generación del link

**Regla de diseño: el link nunca se almacena.** Se deriva siempre del número y el mensaje. Guardarlo (como sugería el prompt con `contact_whatsapp_link`) crearía un tercer dato que se desincroniza en cuanto alguien cambie el número y olvide regenerar el link.

```php
WhatsappService::normalize('(809) 555-0100')   // → '18095550100'
WhatsappService::link($number, $message)       // → https://wa.me/18095550100?text=...
```

### Normalización

1. Quitar todo lo que no sea dígito.
2. Si empieza por `+`, se respeta el código de país.
3. Si tiene 10 dígitos y empieza por `809`, `829` o `849` → anteponer `1` (RD).
4. Si tiene 11 y empieza por `1` → ya está bien.
5. Cualquier otro caso → se acepta tal cual y se avisa en el panel para revisión manual.

Ejemplos:

```
(809) 555-0100  → 18095550100
809-555-0100    → 18095550100
+1 809 555 0100 → 18095550100
18295550100     → 18295550100
```

### Mensaje

`rawurlencode()` sobre el texto ya interpolado.

```
Número:  18290000000
Mensaje: Hola, quiero información sobre una propiedad.
Link:    https://wa.me/18290000000?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20una%20propiedad.
```

## 6. Mensajes configurables

| Setting | Uso | Valor inicial |
|---|---|---|
| `contact_whatsapp_message` | General / botón flotante | `Hola, quiero recibir asesoría inmobiliaria.` |
| `whatsapp_property_message` | Detalle de propiedad | `Hola, estoy interesado en la propiedad {reference_code} - {title}. ¿Está disponible?` |
| `whatsapp_investment_message` | Página Invierte | `Hola, quiero información sobre oportunidades de inversión inmobiliaria.` |

Variables disponibles en el mensaje de propiedad: `{reference_code}`, `{title}`, `{price}`, `{location}`, `{url}`.
El panel muestra una **vista previa del link generado en vivo** mientras se edita.

## 7. Dónde aparece

| Ubicación | Mensaje | Origen registrado |
|---|---|---|
| Botón flotante global | General | `float` |
| Header (escritorio) | General | `header` |
| Detalle de propiedad | De propiedad | `property_detail` |
| Barra móvil del detalle | De propiedad | `property_mobile_bar` |
| Página Invierte | Inversión | `investment_page` |
| Contacto | General | `contact_page` |
| Detalle de noticia | General | `news_detail` |
| Tarjeta del agente | Del agente | `agent_card` |

Botón flotante: activable, posición configurable (`bottom-right` / `bottom-left`), color `#25D366`. Entrada animada y pulso periódico — ver [13_MOTION_AND_EFFECTS.md](13_MOTION_AND_EFFECTS.md).

## 8. Registro de clics

```
POST /wa/click  (fetch con keepalive, no bloquea la navegación)
  → whatsapp_clicks(property_id, source, phone_number,
                    generated_message, ip, user_agent, referrer)
```

Se dispara **en paralelo** a abrir WhatsApp; si el registro falla, el usuario llega igual a WhatsApp. La analítica nunca puede romper la conversión.

Opcionalmente crea también un `lead` con `source=whatsapp_click` — activable desde settings, apagado por defecto (generaría leads sin nombre ni teléfono verificado).

Reportes: clics por día, por propiedad, por origen. Ver [03_ADMIN_PANEL.md](03_ADMIN_PANEL.md) §2.13.
