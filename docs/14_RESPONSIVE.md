# 14 — Responsive: requisito y método de verificación

> Requisito explícito del cliente: **la web pública y el panel deben funcionar al
> 100 % desde cualquier dispositivo — PC, celular y tableta.**

Esto **no** es una tarea de la Fase 10. Es una condición de cierre de **cada fase**:
ninguna pantalla se da por terminada hasta pasar la verificación de abajo.

---

## 1. Puntos de corte

Se usan los de Tailwind por defecto. No se inventan.

| Nombre | Ancho | Dispositivo típico | Layout |
|---|---|---|---|
| *(base)* | < 640 px | Móvil vertical | 1 columna, drawer, barra inferior |
| `sm` | ≥ 640 px | Móvil apaisado | 1–2 columnas |
| `md` | ≥ 768 px | Tableta vertical | 2 columnas, filtros colapsables |
| `lg` | ≥ 1024 px | Tableta apaisada / portátil | Sidebar fija, 3 columnas |
| `xl` | ≥ 1280 px | Escritorio | Contenedor máx. 1280 px |
| `2xl` | ≥ 1536 px | Monitor grande | Contenedor centrado, sin estirar |

**Anchos de prueba obligatorios:** 375 · 768 · 1024 · 1440 · 1920.
375 px es el suelo real (iPhone SE / mini). Nada puede romperse ahí.

---

## 2. Reglas duras

Estas no se negocian por estética:

| Regla | Motivo |
|---|---|
| **Nunca scroll horizontal** en `<body>` | Es el síntoma número uno de layout roto en móvil |
| **Objetivo táctil ≥ 44 × 44 px** | WCAG 2.5.5 / Apple HIG. Implementado en `app.css` con `@media (pointer: coarse)` |
| Un `<a>` estilado como botón lleva **`data-touch-target`** | La regla base cubre `button`, `input`, `select` y `textarea`, pero no puede distinguir un enlace-botón de un enlace de texto. El atributo lo marca explícitamente y es greppable |
| Enlaces de navegación del pie: **≥ 24 px** de alto | WCAG 2.5.8 (AA). Se consigue con `inline-block py-1`, sin cambiar el aspecto |
| **Inputs con fuente ≥ 16 px en móvil** | Por debajo, iOS hace zoom automático al enfocar y descoloca la página |
| **Imágenes con `width`/`height`** | Sin ellos el layout salta al cargar (CLS) |
| Tablas anchas → scroll **dentro** de su contenedor | Nunca desbordan la página |
| Menú de escritorio → **drawer** en móvil | Con velo, cierre al tocar fuera y `Esc` |
| Sin `hover` como única vía de interacción | En táctil no existe el hover |
| Comparador → scroll horizontal por columnas en móvil | Es la única forma legible de comparar 4 propiedades en 375 px |
| Barra fija de contacto en el detalle en móvil | Llamar / WhatsApp / Consultar siempre accesibles |

---

## 3. Método de verificación

Medición real en navegador, no inspección visual. Para cada pantalla y en cada
ancho se comprueba:

```js
{
  overflowX:        document.documentElement.scrollWidth > window.innerWidth,  // debe ser false
  objetivosTactiles: /* todo button/input/select ≥ 44px con pointer:coarse */
  fuenteInputs:      /* ≥ 16px por debajo de 768px */
  columnasGrid:      /* las esperadas por breakpoint */
  navegacion:        /* drawer abre, cierra con velo y con Esc */
}
```

**Aviso sobre el método:** si se renderiza una vista a un `.html` suelto para
inspeccionarla, hay que forzar `app.url` al host que sirve la página. Con
`APP_URL=http://era-realty.test`, `@vite` genera rutas a ese host, la hoja de
estilos no carga y **todo parece roto sin estarlo**. Ocurrió durante la Fase 0 y
costó un diagnóstico en falso.

---

## 4. Registro de verificaciones

| Pantalla | 375 | 768 | 1024 | 1440 | Fecha | Notas |
|---|:--:|:--:|:--:|:--:|---|---|
| `/admin/login` | ✅ | ✅ | — | ✅ | 2026-08-13 | Panel izquierdo oculto < 1024. Controles a 44 px |
| `/admin` (dashboard) | ✅ | ✅ | — | ✅ | 2026-08-13 | Drawer OK. Tarjetas 2→2→4 columnas |
| Layout público (`/invierte`, `/en/invest`) | ✅ | ✅ | — | ✅ | 2026-08-13 | Nav → drawer < 1024. Selector de idioma en ambas variantes. Contenedor a 1280 px. Footer 4 columnas |
| Configuración × 4 pestañas | ✅ | — | — | ✅ | 2026-08-13 | Grids 1→2/3 columnas. Pestañas con scroll interno. Barra de guardar sticky. Cero controles < 44 px |
| Listado de propiedades | ✅ | — | — | ✅ | 2026-08-13 | **La tabla scrollea dentro de su contenedor, no la página.** Filtros 1→4 columnas |
| Formulario de propiedad | ✅ | — | — | ✅ | 2026-08-13 | 9 pestañas con scroll interno, una sección visible a la vez. Selects encadenados. Pestañas ES/EN |
| Inicio público | — | — | — | ✅ | 2026-08-14 | Hero 85vh, buscador de cristal, tarjetas 1→3 columnas, contenedor 1280 px |
| Listado público | — | — | — | ✅ | 2026-08-14 | Filtros en columna sticky (escritorio) / desplegable (móvil). Tarjetas 1→2→3 |
| Detalle público | ✅ | — | — | ✅ | 2026-08-14 | Meta-grid 2→4 columnas. Barra de contacto fija en móvil. Sidebar sticky |
| Comparador | ✅ | — | — | ✅ | 2026-08-14 | Tabla con scroll interno y columna de etiquetas sticky. Toggle «solo diferencias» |
| Invierte | ✅ | — | — | ✅ | 2026-08-14 | Motivos 1→2 columnas. Línea temporal con numeración dentro de pantalla |

---

## 5. Defectos encontrados y corregidos en la Fase 0

### 5.1 Colisión de la escala de espaciado con `max-w-*` 🔴

`DESIGN.md` nombra el espaciado `xs/sm/md/lg/xl`. Al declararlos como
`--spacing-sm: 16px`, Tailwind 4 los hizo ganar sobre su propia escala de
anchos: **`max-w-sm` pasó a valer 16 px en vez de 24 rem.**

Detectado en el login a 375 px: el campo de correo medía 34 px.

Habría roto en silencio `max-w-xs`, `max-w-sm`, `max-w-md`, `max-w-lg` y
`max-w-xl` en todo el sitio — y las maquetas de Stitch usan varias de ellas
(`max-w-xs` en el footer, `max-w-4xl` y `max-w-2xl` en el hero).

**Solución:** declarar el namespace `--max-width-*` completo y explícito en
`app.css`. Está comentado en el propio archivo para que nadie lo borre.

### 5.2 Objetivos táctiles por debajo del mínimo 🟠

Botones a 40 px e inputs a 42 px.

**Solución:** regla base bajo `@media (pointer: coarse)` que lleva todo control
interactivo a 44 px. Se limita a punteros gruesos para no engordar las tablas
densas del panel cuando se usa ratón.

### 5.4 Objetivo táctil de las casillas 🟠

Las casillas de verificación medían 16 px. Agrandarlas a 44 px se ve absurdo;
lo que tiene que crecer es **su etiqueta**, que es lo que el dedo toca.

**Solución:** regla base con `label:has(> input[type="checkbox"])` bajo
`@media (pointer: coarse)`. Cubre todas las casillas del proyecto, presentes y
futuras, sin tener que acordarse en cada formulario.

### 5.3 Alternancia frágil del drawer 🟡

La sidebar combinaba `-translate-x-full` fijo con un `translate-x-0` añadido por
Alpine. Las dos clases coexistían y el resultado dependía del orden de emisión
de Tailwind. Funcionaba por casualidad.

**Solución:** ternario, de modo que solo una de las dos clases está presente en
cada momento.
