<?php

return [
    'title' => 'Usuarios',
    'subtitle' => 'Quién puede entrar al panel y con qué permisos.',
    'no_mail_notice' => 'Las contraseñas se generan aquí y se muestran una sola vez: no hace falta que el correo esté configurado.',

    'actions' => [
        'new' => 'Nuevo usuario',
        'edit' => 'Editar',
        'reset_password' => 'Restablecer contraseña',
        'activate' => 'Activar',
        'deactivate' => 'Desactivar',
        'delete' => 'Eliminar',
        'save' => 'Guardar',
        'cancel' => 'Cancelar',
        'copy' => 'Copiar',
        'understood' => 'Ya la copié',
    ],

    'fields' => [
        'name' => 'Nombre',
        'email' => 'Correo',
        'phone' => 'Teléfono',
        'role' => 'Rol',
        'status' => 'Estado',
        'current_password' => 'Contraseña actual',
        'new_password' => 'Nueva contraseña',
        'confirm_password' => 'Repite la nueva contraseña',
    ],

    'roles' => [
        'super_admin' => 'Super administrador',
        'admin' => 'Administrador',
        'editor' => 'Editor',
        'agent' => 'Asesor',
    ],

    'roles_help' => [
        'super_admin' => 'Todo, incluida la gestión de usuarios.',
        'admin' => 'Todo el contenido, los reportes y la auditoría.',
        'editor' => 'Propiedades, noticias y contenido. Sin acceso a leads ni configuración.',
        'agent' => 'Solo sus propias propiedades.',
    ],

    'status' => [
        'active' => 'Activo',
        'inactive' => 'Desactivado',
        'pending_password' => 'Debe cambiar la contraseña',
    ],

    'generated' => [
        'title' => 'Contraseña de :name',
        'body' => 'Esta es la única vez que se muestra. Cópiala y pásasela por WhatsApp o en persona.',
        'must_change' => 'Se le pedirá cambiarla la primera vez que entre.',
    ],

    'delete' => [
        'title' => '¿Eliminar a :name?',
        'body' => 'Se borra la cuenta y no se puede deshacer. Si solo quieres impedirle el acceso, desactívala.',
        'confirm' => 'Sí, eliminar',
    ],

    'change_password' => [
        'title' => 'Cambia tu contraseña',
        'intro' => 'Tu contraseña actual la generó otra persona, así que solo sirve para entrar una vez. Elige una nueva para continuar.',
        'submit' => 'Guardar y continuar',
    ],

    'errors' => [
        'self_deactivate' => 'No puedes desactivar tu propia cuenta.',
        'self_delete' => 'No puedes eliminar tu propia cuenta.',
        'self_demote' => 'No puedes quitarte a ti mismo el rol de super administrador. Pídeselo a otro super administrador.',
        'last_super_admin' => 'Es el único super administrador activo. Si lo quitas, nadie podría volver a gestionar usuarios.',
        'same_password' => 'La nueva contraseña tiene que ser distinta de la actual.',
    ],

    'created' => 'Usuario creado.',
    'saved' => 'Usuario actualizado.',
    'deleted' => 'Usuario eliminado.',
    'activated' => 'Usuario activado.',
    'deactivated' => 'Usuario desactivado.',
    'password_changed' => 'Contraseña actualizada.',

    'empty' => 'No hay más usuarios todavía.',
];
