<?php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'db',
        'dbname' => getenv('DB_NAME') ?: 'cloud',
        'user' => getenv('DB_USER') ?: 'app',
        'pass' => getenv('DB_PASS') ?: 'secret',
    ],
];
