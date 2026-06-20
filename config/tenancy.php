<?php

declare(strict_types=1);

return [
    'main_domain' => env('SAAS_MAIN_DOMAIN', 'saasmesa.com.br'),
    'subdomain_pattern' => env('TENANT_SUBDOMAIN_PATTERN', '*.saasmesa.com.br'),
    'credential_encryption_key' => env('TENANT_CREDENTIAL_ENCRYPTION_KEY'),
];
