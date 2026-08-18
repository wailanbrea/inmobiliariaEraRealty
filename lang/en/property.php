<?php

return [

    'status' => [
        'draft' => 'Draft',
        'available' => 'Available',
        'reserved' => 'Reserved',
        'sold' => 'Sold',
        'rented' => 'Rented',
        'not_available' => 'Not available',
        'paused' => 'Paused',
    ],

    'operation' => [
        'sale' => 'For sale',
        'rent' => 'For rent',
        'temporary_rent' => 'Short-term rental',
        'investment' => 'Investment',
    ],

    'period' => [
        'month' => 'Per month',
        'night' => 'Per night',
        'year' => 'Per year',
    ],

    'period_short' => [
        'month' => '/month',
        'night' => '/night',
        'year' => '/year',
    ],

    'currency' => [
        'USD' => 'US Dollar',
        'DOP' => 'Dominican Peso',
    ],

    'price_on_request' => 'Price on request',

    'specs' => [
        'bedrooms' => 'Bedrooms',
        'bathrooms' => 'Bathrooms',
        'parking' => 'Parking',
        'parking_one' => 'Parking space',
        'parking_many' => 'Parking spaces',
        'area' => 'Square metres',
        'land_area' => 'Land area',
        'floor' => 'Floor',
        'year_built' => 'Year built',
        'maintenance' => 'Maintenance fee',
        'reference' => 'Reference',
        'furnished' => 'Furnished',
    ],

    'specs_short' => [
        'bedrooms' => 'Beds',
        'bathrooms' => 'Baths',
        'parking' => 'Parking',
        'parking_one' => 'Parking space',
        'parking_many' => 'Parking spaces',
    ],

    'sections' => [
        'description' => 'Property description',
        'amenities' => 'Amenities',
        'features' => 'Features',
        'location' => 'Location',
        'similar' => 'Similar properties',
        'gallery' => 'Gallery',
    ],

    'labels' => [
        'featured' => 'Featured',
        'investment' => 'Investment opportunity',
        // Version corta para los chips de tarjeta: la larga desbordaba
        // y tapaba al chip de estado. La completa sigue como tooltip.
        'investment_short' => 'Investment',
        'project' => 'Under construction',
        'approximate_location' => 'Approximate location',
        'photos' => 'View :count photos',
    ],

];
