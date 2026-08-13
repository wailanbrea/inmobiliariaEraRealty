<?php

return [

    'title' => 'Configuración',

    'tabs' => [
        'general' => 'General',
        'whatsapp' => 'WhatsApp',
        'mail' => 'Correo',
        'seo' => 'SEO',
    ],

    'saved' => 'Configuración guardada.',
    'save' => 'Guardar cambios',
    'remove_image' => 'Quitar imagen',
    'current_image' => 'Imagen actual',
    'no_image' => 'Sin imagen',

    'general' => [
        'heading' => 'Datos de la inmobiliaria',
        'site_name' => 'Nombre de la inmobiliaria',
        'site_tagline' => 'Lema',
        'site_logo' => 'Logo principal',
        'site_logo_dark' => 'Logo para fondo oscuro',
        'site_favicon' => 'Favicon',
        'logo_help' => 'PNG, JPG, WebP o SVG. Máximo 2 MB. Se optimiza automáticamente.',
        'favicon_help' => 'Cuadrado, mínimo 96×96 px.',

        'contact_heading' => 'Contacto',
        'contact_phone' => 'Teléfono principal',
        'contact_email' => 'Correo público',
        'contact_form_recipient_email' => 'Correo que recibe los formularios',
        'recipient_help' => 'No se muestra en el sitio. Es el buzón donde llegan los leads.',
        'contact_address' => 'Dirección',
        'contact_schedule' => 'Horario',

        'social_heading' => 'Redes sociales',
        'social_help' => 'Deja en blanco las que no uses: no aparecerán en el pie.',

        'footer_heading' => 'Pie de página',
        'footer_text' => 'Texto del pie',
        'footer_copyright' => 'Aviso de derechos',
    ],

    'whatsapp' => [
        'heading' => 'WhatsApp',
        'number' => 'Número de WhatsApp',
        'number_help' => 'Escríbelo como quieras: (809) 555-0100, 809-555-0100 o +1 809 555 0100. El sistema lo normaliza.',
        'message' => 'Mensaje general',
        'message_help' => 'Se usa en el botón flotante, el header y la página de contacto.',
        'property_message' => 'Mensaje desde una propiedad',
        'property_message_help' => 'Variables disponibles: :vars',
        'investment_message' => 'Mensaje desde la página Invierte',
        'float_enabled' => 'Mostrar botón flotante',
        'float_position' => 'Posición del botón',
        'position_right' => 'Abajo a la derecha',
        'position_left' => 'Abajo a la izquierda',
        'preview' => 'Enlace generado',
        'preview_help' => 'Este enlace se genera solo a partir del número y el mensaje. No se guarda.',
        'no_number' => 'Configura un número para ver el enlace.',
    ],

    'mail' => [
        'heading' => 'Servidor de correo',
        'intro' => 'Si dejas estos campos vacíos, se usa la configuración del archivo .env.',
        'mailer' => 'Driver',
        'host' => 'Servidor SMTP',
        'port' => 'Puerto',
        'username' => 'Usuario',
        'password' => 'Contraseña',
        'password_help' => 'Se guarda cifrada. Déjala vacía para conservar la actual.',
        'password_set' => 'Hay una contraseña guardada.',
        'encryption' => 'Encriptación',
        'from_address' => 'Correo remitente',
        'from_name' => 'Nombre del remitente',
        'send_client_confirmation' => 'Enviar confirmación al cliente que rellena un formulario',

        'test_heading' => 'Probar el envío',
        'test_intro' => 'Antes de guardar, envía un correo de prueba. Si falla, la configuración no se guarda.',
        'test_recipient' => 'Enviar prueba a',
        'test_button' => 'Enviar correo de prueba',
        'test_ok' => 'Correo de prueba enviado a :email. La configuración se guardó.',
        'test_failed' => 'No se pudo enviar. La configuración NO se guardó.',
        'test_subject' => 'Correo de prueba de :site',
        'test_heading_mail' => 'Funciona',
        'test_body' => 'Si estás leyendo esto, la configuración de correo de :site es correcta.',
        'test_sent_at' => 'Enviado el',
        'test_footer' => 'Este es un mensaje automático de prueba. No hace falta responder.',
    ],

    'seo' => [
        'heading' => 'SEO global',
        'intro' => 'Estos valores se usan cuando una página no tiene SEO propio.',
        'default_title' => 'Título por defecto',
        'default_description' => 'Descripción por defecto',
        'default_og_image' => 'Imagen para redes sociales',
        'og_help' => 'Recomendado 1200×630 px.',
        'analytics_id' => 'ID de Google Analytics',
        'analytics_help' => 'Déjalo vacío para no cargar el script.',
        'site_verification' => 'Verificación de Google Search Console',
        'robots' => 'robots.txt',
        'robots_help' => 'Déjalo vacío para usar el valor por defecto.',
        'chars' => 'caracteres',
        'title_limit' => 'Recomendado: máximo 60 caracteres.',
        'description_limit' => 'Recomendado: máximo 155 caracteres.',
    ],

    'currency' => [
        'heading' => 'Moneda',
        'intro' => 'El sitio muestra precios en dólares y en pesos dominicanos.',
        'default' => 'Moneda por defecto',
        'usd_to_dop' => 'Tasa de cambio (1 USD en DOP)',
        'rate_help' => 'Se usa para convertir precios y para el filtro de búsqueda.',
        'rate_updated' => 'Actualizada el :date',
    ],

];
