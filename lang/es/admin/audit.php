<?php

return [
    'title' => 'Auditoría',
    'subtitle' => 'Quién hizo qué y cuándo. Solo lectura.',
    'system' => 'Sistema',

    'filters' => [
        'action' => 'Acción',
        'user' => 'Usuario',
        'from' => 'Desde',
        'to' => 'Hasta',
        'all_actions' => 'Todas las acciones',
        'all_users' => 'Todos los usuarios',
        'clear' => 'Limpiar filtros',
    ],

    'table' => [
        'when' => 'Cuándo',
        'who' => 'Quién',
        'what' => 'Acción',
        'target' => 'Sobre qué',
        'origin' => 'Origen',
        'detail' => 'Ver detalle',
    ],

    'detail' => [
        'title' => 'Detalle del apunte',
        'no_changes' => 'Este apunte no registra cambios de campos.',
        'field' => 'Campo',
        'before' => 'Antes',
        'after' => 'Después',
        'empty_value' => '(vacío)',
        'redacted' => 'Valor oculto por seguridad',
        'ip' => 'Dirección IP',
        'agent' => 'Navegador',
        'close' => 'Cerrar',
    ],

    'empty' => [
        'title' => 'No hay apuntes que coincidan',
        'body' => 'Prueba a quitar algún filtro o a ampliar el rango de fechas.',
    ],

    'retention' => 'Los apuntes se conservan :days días. Después los borra el comando programado.',

    'actions' => [
        'login' => 'Inicio de sesión',
        'login_failed' => 'Intento de acceso fallido',
        'logout' => 'Cierre de sesión',
        'property_created' => 'Propiedad creada',
        'property_updated' => 'Propiedad editada',
        'property_deleted' => 'Propiedad eliminada',
        'property_status_changed' => 'Estado de propiedad cambiado',
        'image_uploaded' => 'Imagen subida',
        'image_deleted' => 'Imagen eliminada',
        'settings_changed' => 'Configuración cambiada',
        'logo_changed' => 'Logotipo cambiado',
        'whatsapp_changed' => 'WhatsApp cambiado',
        'mail_changed' => 'Correo cambiado',
        'news_published' => 'Noticia publicada',
        'news_deleted' => 'Noticia eliminada',
    ],
];
