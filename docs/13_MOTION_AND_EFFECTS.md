# 13 — Capa de movimiento y efectos llamativos

> Requisito extra del cliente: **"como requisito extra deberá tener efectos llamativos"**.
> No está en el prompt maestro; se trata aquí como capa de diseño de primera clase.

---

## 1. El problema a resolver

`DESIGN.md` describe el sistema visual como *"quiet luxury"*, *"calm confidence"*, *"institutional stability"*. Un carrusel con explosiones de partículas contradice frontalmente eso y hace ver barata a una inmobiliaria que vende villas de 1,2 M USD.

**Criterio adoptado: efectos llamativos por sofisticación, no por estridencia.** Lo que debe llamar la atención es la fotografía y la precisión del movimiento — profundidad, parallax, revelados cinematográficos, transiciones fluidas. Lo caro se ve caro cuando se mueve con calma y exactitud.

Toda animación cumple tres reglas:

1. **Sirve a un propósito** — dirige la mirada, comunica jerarquía o da feedback.
2. **Nunca bloquea** — el contenido es legible y usable aunque el JS falle.
3. **Respeta `prefers-reduced-motion`** — sin excepciones.

---

## 2. Herramientas

| Librería | Peso | Para qué |
|---|---|---|
| **GSAP 3.12** + ScrollTrigger | ~50 KB gz | Timeline maestro, parallax, revelados |
| **Lenis 1.1** | ~3 KB | Scroll suave con inercia |
| **Alpine.js 3.14** | ~15 KB | Estado de UI (menú, tabs, lightbox) |
| CSS puro | 0 | Hovers, chips, transiciones simples |

Todo cargado en `resources/js/motion.js`, con **code splitting**: el módulo GSAP se importa dinámicamente y solo si `prefers-reduced-motion: no-preference`.

```js
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  const { initMotion } = await import('./motion.js')
  initMotion()
}
```

En reduced-motion no se descargan ~53 KB de JS que no se van a usar.

---

## 3. Catálogo de efectos por pantalla

### 3.1 Global

| Efecto | Detalle |
|---|---|
| **Smooth scroll** | Lenis, `lerp: 0.08`. Da la sensación de peso de una web premium |
| **Header adaptativo** | Al pasar 100 px: altura 80→64 px, fondo `rgba(255,255,255,.85)` + `backdrop-blur(12px)`, sombra ambiente. 300 ms `ease-out` |
| **Indicador de progreso** | Barra de 2 px en `secondary (#0058be)` fija arriba, ligada al scroll |
| **Reveal on scroll** | Componente `<x-reveal>`: `opacity 0→1`, `translateY 24px→0`, 600 ms, stagger 80 ms entre hermanos. Se dispara al 85 % del viewport |
| **Cursor magnético** | Solo escritorio con puntero fino: los botones primarios atraen al cursor hasta 8 px. Desactivado en táctil |
| **Transición de página** | Cortina que barre desde abajo en `primary-container (#131b2e)`, 400 ms in / 500 ms out |
| **WhatsApp flotante** | Entra con `scale 0→1` + rebote a los 2 s. Pulso suave cada 8 s. Tooltip al hover |

### 3.1.b Hero cinematográfico (implementado en la Fase 9)

Petición explícita del cliente: *«quiero tener un efecto bonito y moderno en el HERO»*.

Cinco capas de fondo, de atrás hacia delante. El detalle está en
`resources/css/motion.css`, sección «Hero cinematográfico».

| # | Capa | Qué hace | Coste |
|--:|---|---|---|
| 1 | **Foto** | Ken Burns dentro, parallax fuera, y un **revelado de cortina** con `clip-path` al cargar | `transform` + `clip-path` |
| 2 | **Aurora** | Dos resplandores de marca —turquesa y azul institucional— que derivan en contrafase | `transform` |
| 3 | **Grano** | Textura de película, SVG en línea, **estática** | 0 |
| 4 | **Viñeta** | Oscurece los bordes y empuja la mirada al centro | 0 |
| 5 | **Foco del cursor** | Resplandor suave que sigue al ratón. Solo puntero fino | 2 variables CSS por frame |

Más el **brillo del título** (una sola pasada, 900 ms después de cargar) y la
**disolución al salir**: el contenido se aleja y se desvanece al descender, lo
que da profundidad en vez de una tira de secciones apiladas.

**Ni un `filter: blur()`.** Las auroras son `radial-gradient` sobre elementos
que se mueven, no blobs desenfocados: el desenfoque obliga a la GPU a
repintar cada frame, y en el hero eso se nota justo cuando menos CPU sobra.

**Las duraciones son primas entre sí** (26 s y 34 s) para que las dos auroras
no vuelvan a coincidir nunca y el movimiento no se perciba como un bucle.

**La aurora se pinta aunque no haya foto.** Mientras el cliente no suba la
portada de Cap Cana es lo único que separa el hero de un rectángulo azul
plano — y con foto le añade profundidad de color.

#### El defecto que costó encontrar 🔴

`background-clip: text` solo deja ver el degradado si el relleno del texto es
**transparente**. La primera versión ponía esa transparencia en la regla base,
lo que significaba que el titular del hero —lo primero que lee un visitante—
dependía de que el degradado se pintara.

Peor: bajo `prefers-reduced-motion`, la animación se suprime, el evento
`animationend` **nunca llega**, el JS no retira la clase y el título se queda
invisible. Justamente para quien pidió *menos* movimiento.

Se corrigió por dos vías:

1. La transparencia vive solo dentro de `.is-shining`, que el JS quita al
   terminar. Fuera de ese segundo y medio el título es texto opaco normal.
2. El bloque de `prefers-reduced-motion` **devuelve el relleno opaco**, en vez
   de limitarse a cancelar la animación. Así la garantía no depende del JS.

Detectado midiendo en el navegador: `webkitTextFillColor` daba `rgba(0,0,0,0)`
con la clase aún presente.

### 3.2 Home (`inicio_era_realty_rd`)

| Efecto | Detalle |
|---|---|
| **Hero Ken Burns** | La imagen de fondo escala 1.0→1.08 en 20 s, alterna. Da vida sin distraer |
| **Parallax del hero** | Fondo a `y: 30%` del scroll, texto a `-15%`, buscador a `-5%`. Genera profundidad de 3 capas |
| **Entrada del título** | Máscara por líneas: cada línea sube desde `translateY(100%)` bajo `overflow:hidden`. 800 ms, stagger 100 ms. Es el efecto editorial que hace que Playfair Display luzca |
| **Buscador de cristal** | El `.glass-panel` ya existe en el diseño. Se le añade: entrada 400 ms después del título con `scale .96→1`; al enfocar un campo, el borde transiciona a `secondary` con glow suave |
| **Tarjetas de propiedad** | Entran escalonadas 100 ms. Al hover: `translateY(-6px)`, sombra 4 %→10 %, imagen `scale(1.06)` en 500 ms, y el precio sube 4 px. Todo con `will-change` acotado |
| **Contadores** | Sección de estadísticas: números que cuentan desde 0 al entrar en viewport (1 200 ms, `easeOutExpo`) |
| **Corte diagonal** | Separadores SVG entre secciones con desplazamiento parallax leve |

### 3.3 Listado (`propiedades_era_realty_rd`)

| Efecto | Detalle |
|---|---|
| **Filtros en vivo** | Livewire actualiza sin recargar. Salida `opacity→0` 150 ms, entrada escalonada 60 ms |
| **Skeletons** | Placeholders con shimmer durante la carga. Nunca un spinner solo |
| **Sidebar sticky** | Se fija al hacer scroll y colapsa a drawer inferior en móvil |
| **Slider de precio** | Doble control con relleno animado y valor que sigue al pulgar |
| **Chips de filtro** | Al aplicar, el chip entra con `scale .8→1`; al quitar, sale con `scale→.8` + fade |
| **Contador de resultados** | El número transiciona dígito a dígito al cambiar |
| **Vista grid/lista** | FLIP: las tarjetas se reposicionan animadas en vez de saltar |

### 3.4 Detalle (`detalle_de_propiedad_era_realty_rd`)

| Efecto | Detalle |
|---|---|
| **Galería lightbox** | Al abrir, la miniatura crece hasta pantalla completa desde su posición real (transición compartida). Navegación con teclado, swipe en móvil |
| **Cambio de imagen** | Crossfade 400 ms + `scale 1.02→1`. Las miniaturas marcan la activa con barra inferior animada |
| **Sidebar sticky** | El formulario acompaña el scroll (ya en el diseño), con entrada suave |
| **Meta-grid** | Los 4 iconos (habitaciones/baños/parqueos/m²) entran escalonados con `scale .8→1` |
| **Amenidades bento** | Revelado en cascada diagonal. Al hover, el icono rota 5° y el fondo pasa a `surface-container` |
| **Barra móvil** | La barra fija de contacto se desliza desde abajo tras 400 px de scroll |
| **Mapa** | Aparece con fade al entrar en viewport; el marcador cae con rebote. Si `show_exact_location=0`, un círculo de área pulsa suavemente |
| **Estado de envío** | El botón del formulario se transforma en spinner y luego en check verde (`tertiary`) antes de mostrar el mensaje |

### 3.5 Comparador (`comparador_era_realty_rd`)

| Efecto | Detalle |
|---|---|
| **Añadir a comparar** | La tarjeta "vuela" en miniatura hasta la barra del comparador (transición FLIP). Feedback físico inconfundible |
| **Barra flotante** | Sube desde abajo al añadir la primera propiedad. Contador con rebote |
| **Entrada de columnas** | Cada columna entra desde la derecha, escalonada 120 ms |
| **Resaltado de diferencias** | Toggle "solo diferencias": las filas iguales colapsan en altura animada. El mejor valor de cada fila se marca con un pulso en `tertiary` |
| **Quitar propiedad** | La columna colapsa en anchura y las restantes se reacomodan animadas |

### 3.6 Noticias e Invierte

| Efecto | Detalle |
|---|---|
| **Portada de noticia** | Parallax de la imagen destacada al hacer scroll |
| **Progreso de lectura** | Barra superior ligada al avance del artículo |
| **Invierte — ROI** | Gráficos que dibujan su trazo al entrar en viewport (`stroke-dasharray`) |
| **Timeline de inversión** | La línea se dibuja progresivamente y los hitos aparecen al alcanzarla |

### 3.7 Panel admin

Discreto y funcional. Nada de parallax en una tabla de datos.

| Efecto | Detalle |
|---|---|
| **Subida de imágenes** | Zona de drop que se ilumina al arrastrar; barra de progreso por archivo; la miniatura entra con `scale` al terminar |
| **Reordenar** | SortableJS con hueco fantasma y transición 200 ms |
| **Marcar principal** | Estrella con animación de relleno + reordenación automática al frente |
| **Toasts** | Entran desde la derecha, auto-cierre a 4 s, barra de progreso |
| **Guardado** | Botón → spinner → check, sin recargar |
| **Contadores del dashboard** | Cuenta hasta el valor al cargar |

---

## 4. Rendimiento — presupuesto y reglas

Los efectos no pueden costar el SEO ni el móvil.

| Regla | Motivo |
|---|---|
| Animar **solo** `transform` y `opacity` | Son las únicas propiedades que no fuerzan layout/paint |
| `will-change` puesto y **quitado** | Dejarlo fijo consume GPU permanentemente |
| Nada de animación en el hero durante el LCP | Retrasaría la métrica que Google mide |
| ScrollTriggers en `batch()` | Un observer, no 40 |
| `IntersectionObserver` sobre listeners de scroll | Sin thrashing |
| Parallax limitado a 3 capas | Más no aporta y sí cuesta |
| Sin animación bajo 768 px salvo entradas y feedback | El parallax en móvil es caro y marea |

**Presupuesto de rendimiento:**

```
LCP           < 2.5 s (móvil 4G)
CLS           < 0.1     ← toda imagen con width/height
INP           < 200 ms
JS de motion  < 60 KB comprimido
FPS           60 sostenidos durante scroll
```

Verificación en Fase 8 con Lighthouse móvil. Si un efecto no cabe en el presupuesto, **se cae el efecto**, no el presupuesto.

---

## 5. Accesibilidad

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: .01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: .01ms !important;
    scroll-behavior: auto !important;
  }
}
```

Además:

- Lenis se desactiva por completo en reduced-motion (devuelve el scroll nativo).
- Los elementos con reveal arrancan **visibles** en el CSS; el JS los oculta antes de animar. Si el JS falla, todo el contenido se ve. Nunca al revés.
- Foco visible en todo elemento interactivo, con anillo en `secondary`.
- Lightbox: trampa de foco, cierre con `Esc`, `aria-modal`.
- Ningún efecto transmite información por sí solo — siempre hay texto o icono equivalente.
- Sin parpadeos entre 3 y 50 Hz (riesgo fotosensible).

---

## 6. Implementación

- **Fase 4:** efectos base junto a cada página — hovers, revelados, sticky, entradas.
- **Fase 8:** capa completa (parallax, FLIP, lightbox, cortinas, contadores) + medición Lighthouse.

Separarlo así evita el error clásico de animar sobre una maqueta que aún va a cambiar.

**Ficheros:**

```
resources/js/motion.js         # init, Lenis, ScrollTrigger, reveals, parallax
resources/js/gallery.js        # lightbox y galería del detalle
resources/js/compare.js        # FLIP del comparador
resources/css/motion.css       # keyframes, reduced-motion, utilidades
resources/views/components/reveal.blade.php
```
