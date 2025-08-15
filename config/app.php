<?php

return [
    'name' => 'Innovant Café',
    'version' => '2.0.0',
    'timezone' => 'Europe/Madrid',
    'debug' => true,
    
    // URLs de la aplicación - se pueden configurar con variables de entorno
    'urls' => [
        'base' => $_ENV['APP_URL'] ?? 'http://localhost:8088',
        'login' => ($_ENV['APP_URL'] ?? 'http://localhost:8088') . '/login',
        'reset_password' => ($_ENV['APP_URL'] ?? 'http://localhost:8088') . '/reset-password',
        'logout' => ($_ENV['APP_URL'] ?? 'http://localhost:8088') . '/logout',
        'index' => $_ENV['APP_URL'] ?? 'http://localhost:8088',
        'admin' => ($_ENV['APP_URL'] ?? 'http://localhost:8088') . '/admin',
        'productos' => ($_ENV['APP_URL'] ?? 'http://localhost:8088') . '/productos',
        'trabajadores' => ($_ENV['APP_URL'] ?? 'http://localhost:8088') . '/trabajadores',
        'reportes' => ($_ENV['APP_URL'] ?? 'http://localhost:8088') . '/reportes',
        'rotacion' => ($_ENV['APP_URL'] ?? 'http://localhost:8088') . '/rotacion',
    ],
    
    // Configuración de la aplicación
    'app' => [
        'name' => 'Innovant Café',
        'version' => '2.0.0',
        'timezone' => 'Europe/Madrid',
        'debug' => true,
    ]
];
