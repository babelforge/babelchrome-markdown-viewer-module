<?php

declare(strict_types=1);

$ready = version_compare(PHP_VERSION, '8.4.0', '>=');

echo json_encode([
    'ready' => $ready,
    'status' => $ready ? 'ready' : 'missing-php-8.4',
    'messages' => [
        'PHP '.PHP_VERSION,
    ],
], JSON_THROW_ON_ERROR);
