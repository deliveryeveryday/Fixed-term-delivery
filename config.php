<?php

// 設定ファイル

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
        'ttl_seconds' => 86400, // 24時間 (60秒 * 60分 * 24時間)
        'directory' => __DIR__ . '/cache',
    ],
    'logging' => [
        'enabled' => true,
        'level' => 'DEBUG', // 'INFO', 'WARNING', 'ERROR'などに変更可能
        'file' => __DIR__ . '/app.log',
    ],
];