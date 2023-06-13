<?php
/**
 * User: Sy Dai
 * Date: 15-Sep-16
 * Time: 11:52
 */
return [
    'default'     => 'mysql',
    'connections' => [
        'mysql' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', 'localhost'),
            'database'  => env('DB_DATABASE', 'forge'),
            'username'  => env('DB_USERNAME', 'forge'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'prefix'    => '',

        ],
        'mysql2' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST_SECOND', 'localhost'),
            'database'  => env('DB_DATABASE_SECOND', 'forge'),
            'username'  => env('DB_USERNAME_SECOND', 'forge'),
            'password'  => env('DB_PASSWORD_SECOND', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'prefix'    => '',
        ]
    ],
    'redis'       => [
        'client'  => 'predis',
        'cluster' => false,
        'default' => [
            'host'               => env('REDIS_HOST', 'localhost'),
            'password'           => env('REDIS_PASSWORD', null),
            'port'               => env('REDIS_PORT', 6379),
            'database'           => 0,
            'read_write_timeout' => 60,
        ],
        'cache'   => [
            'host'     => env('REDIS_HOST', 'localhost'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => env('REDIS_CACHE_DB', 1),
        ],
    ],
];
