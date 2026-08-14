<?php

return [

    'status' => [
        'draft' => 'Borrador',
        'available' => 'Disponible',
        'reserved' => 'Reservado',
        'sold' => 'Vendido',
        'rented' => 'Alquilado',
        'not_available' => 'No disponible',
        'paused' => 'Pausado',
    ],

    'operation' => [
        'sale' => 'Venta',
        'rent' => 'Alquiler',
        'temporary_rent' => 'Alquiler temporal',
        'investment' => 'Inversión',
    ],

    'period' => [
        'month' => 'Por mes',
        'night' => 'Por noche',
        'year' => 'Por año',
    ],

    'period_short' => [
        'month' => '/mes',
        'night' => '/noche',
        'year' => '/año',
    ],

    'currency' => [
        'USD' => 'Dólar estadounidense',
        'DOP' => 'Peso dominicano',
    ],

    'price_on_request' => 'Precio a consultar',

    'specs' => [
        'bedrooms' => 'Habitaciones',
        'bathrooms' => 'Baños',
        'parking' => 'Parqueos',
        'area' => 'Metros cuadrados',
        'land_area' => 'Área de terreno',
        'floor' => 'Nivel',
        'year_built' => 'Año de construcción',
        'maintenance' => 'Mantenimiento',
        'reference' => 'Código',
        'furnished' => 'Amueblado',
    ],

    'specs_short' => [
        'bedrooms' => 'Habs',
        'bathrooms' => 'Baños',
        'parking' => 'Parqueos',
    ],

    'sections' => [
        'description' => 'Descripción de la propiedad',
        'amenities' => 'Amenidades',
        'features' => 'Características',
        'location' => 'Ubicación',
        'similar' => 'Propiedades similares',
        'gallery' => 'Galería',
    ],

    'labels' => [
        'featured' => 'Destacada',
        'investment' => 'Oportunidad de inversión',
        // Version corta para los chips de tarjeta: la larga desbordaba
        // y tapaba al chip de estado. La completa sigue como tooltip.
        'investment_short' => 'Inversión',
        'project' => 'En construcción',
        'approximate_location' => 'Ubicación aproximada',
        'photos' => 'Ver :count fotos',
    ],

];
