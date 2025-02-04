<?php

return [
    'default' => 'log',

    // 'default' => env('BROADCAST_DRIVER', 'pusher'),

    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
                'useTLS' => false,
                'encrypted' => false,
                'host' => env('WEBSOCKETS_HOST', '127.0.0.1'),
                'port' => env('WEBSOCKETS_PORT', 6001),
                'scheme' => env('WEBSOCKETS_SCHEME', 'http'),
                'debug' => env('WEBSOCKETS_DEBUG', false),
            ],
        ],
    ],
];




