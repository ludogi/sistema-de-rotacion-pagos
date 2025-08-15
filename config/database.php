<?php

return [
    'mysql' => [
        'host' => $_ENV['DB_HOST'] ?? 'mysql', // Host de la base de datos (localhost para desarrollo local)
        'port' => $_ENV['DB_PORT'] ?? '3306', // Puerto de la base de datos
        'database' => $_ENV['DB_NAME'] ?? 'innovant_cafe',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? 'luis',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    ],
    'app' => [
        'name' => 'Innovant Café',
        'version' => '2.0.0',
        'timezone' => 'Europe/Madrid',
        'debug' => true
    ]
];
