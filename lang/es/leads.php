<?php

return [

    'fields' => [
        'name' => 'Nombre',
        'phone' => 'Teléfono',
        'email' => 'Correo',
        'source' => 'Origen',
    ],

    'mail' => [
        'subject' => 'Nuevo contacto de :name',
        'heading' => 'Nuevo contacto recibido',
    ],

    'confirmation' => [
        'subject' => 'Recibimos tu solicitud en :site',
        'heading' => 'Gracias por escribirnos, :name',
        'body' => 'Tu solicitud quedó registrada. Un asesor revisará la información y se pondrá en contacto contigo.',
        'closing' => 'Saludos',
    ],

];
