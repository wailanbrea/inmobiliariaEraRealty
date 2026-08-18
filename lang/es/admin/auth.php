<?php

return [

    'forgot' => [
        'title' => 'Recuperar contraseña',
        'intro' => 'Escribe el correo de tu cuenta administrativa. Si está activa, recibirás un enlace temporal.',
        'send' => 'Enviar enlace',
        // Mensaje deliberadamente ambiguo: confirmar que un correo existe
        // permitiria enumerar cuentas administrativas.
        'sent' => 'Si existe una cuenta activa con ese correo, recibirás un enlace para restablecer el acceso.',
    ],

    'reset' => [
        'title' => 'Restablecer contraseña',
        'password' => 'Nueva contraseña',
        'confirmation' => 'Confirmar contraseña',
        'submit' => 'Guardar contraseña',
        'invalid' => 'El enlace no es válido o ya expiró.',
        'success' => 'Contraseña actualizada. Ya puedes iniciar sesión.',

        'mail_subject' => 'Restablece tu acceso administrativo',
        'mail_greeting' => 'Hola, :name',
        'mail_intro' => 'Recibimos una solicitud para restablecer la contraseña de tu cuenta administrativa.',
        'mail_action' => 'Restablecer contraseña',
        'mail_expiry' => 'Este enlace caduca en :minutes minutos.',
        'mail_ignore' => 'Si no solicitaste este cambio, no necesitas hacer nada.',
    ],

    'back' => 'Volver al inicio de sesión',
    'session_expired' => 'La sesión de inicio expiró. Inténtalo de nuevo.',

];
