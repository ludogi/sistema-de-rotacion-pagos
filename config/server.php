<?php

return [
    'server' => [
        'host' => 'localhost',
        'port' => '8088',
        'document_root' => __DIR__ . '/../',
        'index_file' => 'index.php'
    ],
    'php' => [
        'version' => '8.0+',
        'extensions' => [
            'pdo',
            'pdo_mysql',
            'mbstring'
        ]
    ],
    'database' => [
        'host' => 'localhost',
        'port' => '3306', // phpMyAdmin
        'name' => 'innovant_cafe'
    ]
];
