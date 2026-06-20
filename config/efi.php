<?php
// config/efi.php
// @deprecated Usado apenas pelo EfiPixService (legado). Novos fluxos usam config/efibank.php.

return [
    'client_id' => env('EFI_CLIENT_ID'),
    'client_secret' => env('EFI_CLIENT_SECRET'),
    'chave_pix' => env('EFI_CHAVE_PIX'),
    'cert_password' => env('EFI_CERT_PASSWORD', ''),

    'base_url' => 'https://pix.api.efipay.com.br/v2/',
    'oauth_url' => 'https://pix.api.efipay.com.br/oauth/token',

    'cert_path' => storage_path('app/cert/certificado.pem'),
    'key_path' => storage_path('app/cert/chave.pem'),

    'sandbox' => false,
];