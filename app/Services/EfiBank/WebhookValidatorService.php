<?php

declare(strict_types=1);

namespace App\Services\EfiBank;

use Illuminate\Support\Facades\Log;

class WebhookValidatorService
{
    private const EFI_BANK_IPS_PRODUCTION = [
        '54.94.56.243',
        '54.94.43.18',
        '54.232.206.88',
    ];

    private const EFI_BANK_IPS_SANDBOX = [
        '177.71.168.182',
        '54.94.56.243',
    ];

    public function validate(string $payload, ?string $signature, ?string $secret = null): bool
    {
        $secret = $secret ?? config('efibank.webhook_secret');

        if (empty($secret)) {
            Log::warning('Webhook validation skipped: no secret configured');

            return false;
        }

        if (empty($signature)) {
            Log::warning('Webhook validation failed: no signature header');

            return false;
        }

        $expected = $this->generateHmac($payload, $secret);
        $isValid = hash_equals($expected, $signature);

        if (! $isValid) {
            Log::warning('Webhook signature mismatch', [
                'expected' => substr($expected, 0, 16).'...',
                'received' => substr($signature, 0, 16).'...',
            ]);
        }

        return $isValid;
    }

    public function validateIp(string $ip): bool
    {
        $sandbox = config('efibank.sandbox', false);

        $allowedIps = $sandbox
            ? array_merge(self::EFI_BANK_IPS_PRODUCTION, self::EFI_BANK_IPS_SANDBOX)
            : self::EFI_BANK_IPS_PRODUCTION;

        return in_array($ip, $allowedIps, true);
    }

    private function generateHmac(string $payload, string $secret): string
    {
        return base64_encode(
            hash_hmac('sha256', $payload, $secret, true)
        );
    }
}
