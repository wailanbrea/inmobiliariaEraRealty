# 12 — Riesgos, conflictos y deuda conocida

Estado: **planificación**. Todo lo listado son riesgos identificados antes de escribir código, no fallos existentes.

Severidad: 🔴 bloquea · 🟠 decidir pronto · 🟡 vigilar · 🔵 informativo

---

## Conflictos entre el prompt maestro y la realidad

### 🟡 #1 — PHP 8.2.33 instalado, el prompt pide 8.3+

El prompt maestro (§1) especifica "PHP 8.3+". La máquina tiene **8.2.33**.

Laravel 12 requiere `^8.2`, así que **el proyecto funciona sin cambios**. No se usa ninguna sintaxis exclusiva de 8.3.

Actualizar PHP en esta máquina implicaría tocar una instalación reconstruida a mano el 08/08/2026 tras un incidente — riesgo operativo real a cambio de un beneficio nulo.

**Decisión propuesta:** quedarse en 8.2.33 y documentarlo. Requiere confirmación (Pregunta 5).

---

### 🟠 #2 — Solo 4 de 10+ pantallas tienen diseño

Diseñadas: inicio, propiedades, detalle, comparador.
**Sin diseño:** Invierte, Sobre nosotros, Infórmate (listado y detalle), Contáctanos, Publica tu propiedad, Privacidad, Términos, y **todo el panel administrativo** (~20 pantallas).

**Impacto:** es la mayor incertidumbre de alcance del proyecto. Derivar el diseño consume tiempo y expone a rehacer trabajo si al cliente no le convence el resultado.

**Mitigación:** derivarlas de `estate_elite/DESIGN.md` y de los patrones ya establecidos, y someter cada una a revisión **antes** de implementarla. Ver Pregunta 2.

---

### 🟠 #3 — Las maquetas usan Tailwind por CDN

Los 4 `code.html` cargan `cdn.tailwindcss.com` y configuran los tokens en un `<script>` inline.

Inaceptable en producción: compilación JIT en el navegador, parpadeo sin estilos al cargar, sin purga (se sirve Tailwind entero), y dependencia de un CDN de terceros para que el sitio se vea.

**Resolución:** los tokens pasan a `tailwind.config.js` y se compila con Vite. Los `code.html` quedan como referencia visual, no como código de partida. Coste: ~2 h en Fase 4. Beneficio: de servir Tailwind completo a ~12 KB purgados.

---

### 🟡 #4 — Las imágenes del diseño son externas y temporales

Todas apuntan a `lh3.googleusercontent.com/aida-public/...`. Son URLs generadas por Stitch que pueden caducar y que **no se pueden usar en producción**.

**Resolución:** en desarrollo se usan placeholders locales; el contenido real lo sube el cliente. Los atributos `data-alt` de las maquetas son descripciones muy detalladas y sirven como briefing fotográfico — se conservan por eso.

---

### 🟡 #5 — Incoherencia dentro del propio `DESIGN.md`

La prosa dice *"anchored by Deep Navy #0F172A"*, pero los tokens definen `primary: #000000` y `primary-container: #131b2e`. Ninguno es `#0F172A`.

Las maquetas usan los **tokens**, no la prosa.

**Resolución:** los tokens mandan (`#131b2e` como navy de marca). `#0F172A` se usa solo en las sombras ambientales (`rgba(15,23,42,.04)`), que es donde las maquetas efectivamente lo aplican. Consistente con lo implementado, no con lo escrito.

Además, la prosa menciona un radio de 12px para las tarjetas, pero los tokens definen `xl: 0.75rem` = 12px y las maquetas usan `rounded-xl`. Coincide; se sigue el token.

---

### 🔵 #6 — Desviación deliberada: amenidades en tabla, no en JSON

El prompt sugiere `amenities_json` en `properties`. Se implementa como tabla `amenities` + pivote.

**Motivo:** el diseño del listado filtra por amenidad. Con un campo JSON ese filtro no se puede indexar (recorrido completo de tabla) y el catálogo no sería administrable ni consistente ("Piscina" vs "piscina" vs "Pisina").

`features_json` **sí** se mantiene como JSON: son características libres, no filtrables.

Desviación consciente del prompt maestro, documentada para revisión.

---

## Riesgos técnicos

### 🔴 #7 — El proyecto no está bajo control de versiones

`git init` no se ha ejecutado. Sin Git no hay historial, ni vuelta atrás, ni ramas por fase.

Dado el historial de esta máquina (borrado recursivo del perfil el 06/08/2026, con pérdida definitiva de `.azure` y `source`), trabajar sin Git es el riesgo operativo más serio abierto.

**Acción:** `git init` es la **primera tarea** de la Fase 0, antes de instalar Laravel.

---

### 🟠 #8 — Datos personales de leads sin política de retención

`leads` almacena nombre, teléfono, email, IP y user agent. `whatsapp_clicks` y `property_views` almacenan IP.

Sin política de retención, esos datos se acumulan indefinidamente.

**Mitigaciones ya en el diseño:** `property_views` guarda un **hash con sal** de la IP, no la IP (solo se necesita deduplicar). `audit_logs` excluye contraseñas.

**Pendiente de decisión del cliente:** cuánto tiempo conservar leads, si hace falta aviso de cookies, y qué textos legales van en `/privacidad`. Ver Pregunta 8.

---

### 🟡 #9 — Espacio en el nombre de la carpeta

`C:\xampp\php\www\Era Realty` — el espacio rompe comandos sin comillas y complica algunos scripts en Windows.

**Opciones:** (a) renombrar a `era-realty` ahora, mientras la carpeta solo tiene documentos y el diseño; (b) dejarlo y entrecomillar siempre.

Renombrar cuesta minutos ahora y horas más adelante. Ver Pregunta 9.

---

### 🟡 #10 — Extensión GD/Imagick sin verificar

Todo el pipeline de imágenes depende de una de las dos. El `php.ini` de esta máquina fue escrito a mano el 08/08/2026 y puede no tenerlas activas.

**Acción:** `php -m` en la Fase 0. Si falta, activar **con copia previa del `php.ini`**.

---

### 🟡 #11 — Sin datos reales del negocio

Todo lo que aparece en las maquetas es ficticio: `info@erarealtyrd.com`, `+1 (809) 555-0100`, `Av. Winston Churchill`, "ERA Realty RD", el agente "Carlos Mendoza".

Van al seeder como valores por defecto para que el sitio arranque, pero **deben sustituirse antes de producción**. Están en el checklist de [09_DEPLOYMENT.md](09_DEPLOYMENT.md) §7. Ver Pregunta 7.

---

### 🔵 #12 — "ERA" es una marca registrada

ERA Real Estate es una franquicia internacional (Realogy/Anywhere). Si "ERA Realty RD" es una franquicia legítima, habrá manual de marca con reglas de logo, color y tipografía que probablemente entren en conflicto con `estate_elite/DESIGN.md`.

Si no lo es, existe un riesgo legal de marca que no me corresponde evaluar.

**Acción:** preguntar al cliente si hay manual de marca de franquicia. Ver Pregunta 7. Se señala ahora porque descubrirlo después de maquetar 10 pantallas es caro.

---

### 🔵 #13 — Los efectos pueden comerse el rendimiento móvil

GSAP + ScrollTrigger + Lenis ≈ 53 KB comprimidos, más el coste de ejecución. El requisito de "efectos llamativos" y el objetivo de Lighthouse ≥ 90 tiran en direcciones opuestas.

**Mitigaciones en [13_MOTION_AND_EFFECTS.md](13_MOTION_AND_EFFECTS.md):** carga dinámica del módulo (no se descarga en `prefers-reduced-motion`), presupuesto de 60 KB, solo `transform`/`opacity`, sin parallax bajo 768 px, medición en Fase 8.

**Regla adoptada:** si un efecto rompe el presupuesto, cae el efecto.

---

### 🔵 #14 — Moneda: USD y DOP conviven

El mercado inmobiliario dominicano cotiza en ambas. El esquema soporta `currency` por propiedad, pero no hay conversión ni tasa de cambio.

Si el cliente quiere un selector USD/DOP en el buscador, hace falta una tabla de tasas y una fuente que las actualice — trabajo no contemplado en el prompt maestro. Ver Pregunta 3.

---

### 🔵 #15 — Sitio monolingüe

Todo el diseño está en español. Si el objetivo incluye inversores extranjeros (probable para Cap Cana y Las Terrenas), faltaría inglés, y eso cambia rutas, esquema y `hreflang`.

Añadirlo después de la Fase 4 significa rehacer todas las vistas y migrar contenido. **Es una decisión que conviene tomar ahora.** Ver Pregunta 3.

---

## Deuda técnica aceptada

| Deuda | Motivo | Cuándo se paga |
|---|---|---|
| Sin caché Redis | XAMPP local; caché en BD basta | Si el tráfico lo exige |
| Sin CDN de imágenes | Sobredimensionado para el volumen inicial | Fase de crecimiento |
| Sin Meilisearch | FULLTEXT de MariaDB es suficiente | Si la búsqueda se queda corta |
| Sin API pública | Nadie la consume aún | Si aparece app móvil o portales |
| Sin tests E2E | Pest Feature + checklist manual cubre | Si el equipo crece |
| Sin CI/CD | Un solo desarrollador | Al haber más de uno |
