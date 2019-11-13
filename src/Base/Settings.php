<?php

return [
    'settings' => [
        'displayErrorDetails' => env('APP_DEBUG', false),
        'determineRouteBeforeAppMiddleware' => true,

        'db' => [
            'driver'        => env('APP_DB_DRIVER', 'mysql'),
            'host'          => env('APP_DB_HOST', 'localhost'),
            'database'      => env('APP_DB_NAME', 'slims_dbs'),
            'username'      => env('APP_DB_USERNAME', 'root'),
            'password'      => env('APP_DB_PASSWORD', ''),
            'charset'       => env('APP_DB_CHARSET', 'utf8'),
            'collation'     => env('APP_DB_COLLATION', 'utf8_unicode_ci'),
            'prefix'        => '',
            'port'          => 3306
        ],

        'logger' => [
            'name' => 'with_slim_log',
            'level' => Monolog\Logger::DEBUG,
            'path' => __DIR__ . ('/../../logs/app.log'),
        ],
    ],

];