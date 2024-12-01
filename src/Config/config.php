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
            'path' => sprintf('%s/../storage/sessions.db', __DIR__),
            // generate with: bin2hex(random_bytes(32))
            'encryption_key' => '7934416359c56e6abc845f5ebf218f961a1804ea714848e564622d68d8a33c2',
        ],
        'mysql' => [
            'table' => 'sessions',
            'lifetime' => 3600,
            'host' => 'localhost',
            'user' => 'root',
            'password' => '',
            'database' => '',
            'port' => 3306,
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            // generate with: bin2hex(random_bytes(32))
            'encryption_key' => '7934416359c56e6abc845f5ebf218f961a1804ea714848e564622d68d8a33c2',
        ]
    ]
];
