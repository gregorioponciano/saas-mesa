<?php

return [
    'paths' => ['api/*', 'webhook/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('APP_URL', 'https://saasmesa.com.br')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => true,
];
