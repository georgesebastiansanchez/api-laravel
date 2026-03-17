<?php
// config/cors.php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'], 

    'allowed_origins' => [*], 

    // Aquí es donde garantizamos que la IP 127.0.0.1 en cualquier puerto sea permitida.
    'allowed_origins_patterns' => [
        'http://localhost:\d+', 
        'http://127.0.0.1:\d+', 
        'http://localhost', 
        'http://127.0.0.1',
    ], 

    'allowed_headers' => ['*'], // ESENCIAL para Content-Type y Authorization

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // Déjalo en true si usas cookies o Sanctum
];
