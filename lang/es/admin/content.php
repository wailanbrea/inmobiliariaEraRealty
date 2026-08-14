<?php

return [
    'guide' => [
        'open' => 'Ver guía',
        'title' => 'Cómo editar el contenido del sitio',
        'next' => 'Siguiente',
        'back' => 'Atrás',
        'done' => 'Entendido',
        'steps' => [
            ['titulo' => '1 · Elige la página', 'texto' => 'Arriba están las páginas del sitio. Pulsa la que quieras cambiar: cada una tiene sus propias secciones.'],
            ['titulo' => '2 · Abre la sección', 'texto' => 'Cada tarjeta es un bloque de la página real. Pulsa «Editar» en la que quieras cambiar y se abrirá el formulario debajo.'],
            ['titulo' => '3 · Escribe el texto', 'texto' => 'Cambia el título, el subtítulo y el texto del botón. Las pestañas ES e IN son el mismo bloque en español y en inglés: si dejas el inglés vacío, se muestra el español.'],
            ['titulo' => '4 · Cambia la imagen', 'texto' => 'Si la sección lleva foto, súbela desde el mismo formulario. Se recorta y se optimiza sola. La anterior se borra al guardar la nueva.'],
            ['titulo' => '5 · Guarda y comprueba', 'texto' => 'Pulsa «Guardar». El cambio sale en el sitio al instante: ábrelo con «Ver sitio», arriba a la derecha, para confirmarlo.'],
        ],
    ],

    'title' => 'Contenido del inicio',
    'intro' => 'Textos e imágenes de las secciones de la página de inicio. Lo que cambies aquí se ve en el sitio al instante.',

    'saved' => 'Sección guardada.',
    'image_removed' => 'Imagen eliminada.',

    'edit' => 'Editar',
    'save' => 'Guardar',
    'cancel' => 'Cancelar',
    'remove_image' => 'Quitar imagen',
    'no_image' => 'Sin imagen de fondo',

    'fields' => [
        'title' => 'Título',
        'subtitle' => 'Subtítulo',
        'content' => 'Texto',
        'button_text' => 'Texto del botón',
        'button_url' => 'Enlace del botón',
        'image' => 'Imagen de fondo',
    ],

    'hero_image_help' => 'Recomendado: horizontal, mínimo 1920×1080 px. Se optimiza y convierte a WebP automáticamente. Es la primera imagen que ve el visitante: usa una foto de propiedad real, no un genérico.',
    'image_help' => 'JPG, PNG o WebP. Máximo 5 MB.',

    'pages' => [
        'home' => 'Inicio',
        'invest' => 'Invierte',
        'about' => 'Sobre nosotros',
    ],

    'sections' => [
        'hero' => 'Portada principal',
        'featured_properties' => 'Propiedades destacadas',
        'stats' => 'Cifras',
        'investment_cta' => 'Bloque de inversión',
        'final_cta' => 'Llamada a la acción final',
        'why_invest' => 'Por qué invertir',
        'process' => 'Cómo trabajamos',
        'disclaimer' => 'Aviso legal',
        'cta' => 'Llamada a la acción',
        'story' => 'Quiénes somos',
        'values' => 'Cómo trabajamos',
        'team' => 'El equipo',
    ],

    'section_help' => [
        'hero' => 'El titular grande y la foto de fondo de la portada.',
        'featured_properties' => 'Encabezado de la sección. Las propiedades salen de las marcadas como destacadas.',
        'stats' => 'Las cifras se editan por ahora desde la base de datos.',
        'investment_cta' => 'Encabezado del bloque de oportunidades de inversión.',
        'final_cta' => 'La banda de contacto del final de la página.',
        'why_invest' => 'Encabezado de los cuatro motivos. Los bloques se editan por ahora desde la base de datos.',
        'process' => 'Encabezado de los pasos. El contenido de cada paso se edita por ahora desde la base de datos.',
        'disclaimer' => 'Aviso de que la información no es asesoría legal ni fiscal. Escríbelo en el campo «Texto».',
        'cta' => 'La banda de contacto del final de la página.',
        'story' => 'El texto principal de presentación. Escríbelo en el campo «Texto», separando párrafos con una línea en blanco.',
        'values' => 'Encabezado de los tres compromisos. Los bloques se editan por ahora desde la base de datos.',
        'team' => 'Encabezado del equipo. Las fichas salen de los agentes activos (Fase 7).',
    ],

    'status' => [
        'visible' => 'Visible',
        'hidden' => 'Oculta',
    ],

];
