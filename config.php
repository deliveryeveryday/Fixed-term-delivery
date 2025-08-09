<?php

return [
    'pa_api' => [
        'access_key' => getenv('PAAPI_ACCESS_KEY'),
        'secret_key' => getenv('PAAPI_SECRET_KEY'),
        'partner_tag' => getenv('PAAPI_ASSOCIATE_TAG'),
    ],
    'simulation' => [
        'discount_normal' => 0.10,
        'discount_bulk' => 0.15,
    ],
    'cache' => [
        'enabled' => true,
        'ttl_seconds' => 86400,
        'directory' => __DIR__ . '/cache',
    ],
    'logging' => [
        'enabled' => true,
        'level' => 'INFO',
        'file' => __DIR__ . '/app.log',
    ],
];