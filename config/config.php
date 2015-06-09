<?php

return [
    'api' => [
        'path' => 'api',
        'version' => '2.0'
    ],
    'db' => [
        'user' => 'postgres',
        'password' => '',
    ],
    'roles' => [
        'guest' => 0,
        'user' => 666,
    ],
    'files' => [
        'photo1' => dirname(__DIR__) . '/data/photo_1.jpg',
    ],
];