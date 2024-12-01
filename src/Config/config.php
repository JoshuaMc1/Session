<?php

return [
    'driver' => 'file',
    'drivers' => [
        'file' => [
            'path' => sprintf('%s/../storage/sessions', __DIR__)
        ],
        'sqlite' => [
            'table' => 'sessions',
            'lifetime' => 3600,
            'database' => sprintf('%s/../storage/sessions/sessions.sqlite', __DIR__),
            // generate with: bin2hex(random_bytes(16))
            'encryption_key' => '5db19e2a04d0c8b1255dea477394c146',
        ],
        'mysql' => [
            'table' => 'sessions',
            'lifetime' => 3600,
            'host' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => '',
            'port' => 3306,
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            // generate with: bin2hex(random_bytes(16))
            'encryption_key' => '42600da106159ca83dd584de49c1ae87',
        ]
    ]
];
