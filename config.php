<?php
// Configuración del sistema Innovant Café
return [
    'database' => [
        'type' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'innovant_cafe',
        'username' => 'root',
        'password' => 'luis',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci'
    ],
    'app' => [
        'name' => 'Innovant Café',
        'version' => '2.0.0',
        'timezone' => 'Europe/Madrid'
    ],
    'features' => [
        'auto_rotation' => true,
        'email_notifications' => false,
        'price_tracking' => true
    ]
];
