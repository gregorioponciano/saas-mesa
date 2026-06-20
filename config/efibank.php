<?php

declare(strict_types=1);

return [
    'sandbox' => (bool) env('EFI_SANDBOX', false),

    'saas' => [
        'client_id' => env('EFI_CLIENT_ID'),
        'client_secret' => env('EFI_CLIENT_SECRET'),
        'pix_key' => env('EFI_PIX_KEY', env('EFI_CHAVE_PIX')),
        'certificate_path' => env('EFI_CERTIFICATE_PATH') ? base_path(env('EFI_CERTIFICATE_PATH')) : null,
        'cert_password' => env('EFI_CERT_PASSWORD', ''),
        'key_path' => env('EFI_KEY_PATH') ? base_path(env('EFI_KEY_PATH')) : null,
        'key_password' => env('EFI_KEY_PASSWORD', ''),
    ],

    'webhook_secret' => env('EFI_WEBHOOK_SECRET'),

    'urls' => [
        'pix' => env('EFI_SANDBOX', false)
            ? 'https://pix.api.efipay.com.br/v2/'
            : 'https://pix.api.efipay.com.br/v2/',
        'oauth' => 'https://pix.api.efipay.com.br/oauth/token',
        'charges' => env('EFI_SANDBOX', false)
            ? 'https://api.efipay.com.br/v1/'
            : 'https://api.efipay.com.br/v1/',
        'subscriptions' => env('EFI_SANDBOX', false)
            ? 'https://api.efipay.com.br/v1/'
            : 'https://api.efipay.com.br/v1/',
    ],

    'suspension_after_days' => (int) env('EFI_SUSPENSION_AFTER_DAYS', 5),

    'idempotency_ttl_minutes' => 120,
];
