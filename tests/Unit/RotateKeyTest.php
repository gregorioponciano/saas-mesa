<?php

declare(strict_types=1);

use App\Models\TenantEfiCredentials;
use App\Services\EncryptedCredentialService;

test('rotateKey re-encrypts credentials so only the new key can decrypt them', function () {
    $oldAppKey = 'base64:' . base64_encode(random_bytes(32));
    $newAppKey = 'base64:' . base64_encode(random_bytes(32));

    $tenant = createTenant();

    // O serviço é singleton; precisa ser esquecido a cada troca de chave.
    app()->forgetInstance(EncryptedCredentialService::class);
    config(['app.key' => $oldAppKey]);

    $service = app(EncryptedCredentialService::class);

    $cred = TenantEfiCredentials::create([
        'tenant_id' => $tenant->id,
        'client_id_encrypted' => $service->encrypt('Client_Id_rotate'),
        'client_secret_encrypted' => $service->encrypt('Client_Secret_rotate'),
        'pix_key_encrypted' => $service->encrypt('pix@rotate.com'),
        'account_type' => 'sandbox',
        'webhook_secret_encrypted' => $service->encrypt('tenant_webhook_secret'),
        'is_active' => true,
    ]);

    // A chave nova precisa ser explicitamente derivada e usada — antes do fix,
    // o rotateKey descriptografava com a chave da instância (APP_KEY atual),
    // nunca com $oldKey, e mutava $this->key no meio do loop.
    $service->rotateKey($oldAppKey, $newAppKey);

    $fresh = $cred->fresh();

    // 1) A chave nova decripta todos os campos.
    app()->forgetInstance(EncryptedCredentialService::class);
    config(['app.key' => $newAppKey]);
    $serviceNew = app(EncryptedCredentialService::class);

    expect($serviceNew->decrypt($fresh->client_id_encrypted))->toBe('Client_Id_rotate');
    expect($serviceNew->decrypt($fresh->client_secret_encrypted))->toBe('Client_Secret_rotate');
    expect($serviceNew->decrypt($fresh->pix_key_encrypted))->toBe('pix@rotate.com');
    expect($serviceNew->decrypt($fresh->webhook_secret_encrypted))->toBe('tenant_webhook_secret');

    // 2) A chave antiga não decripta mais.
    app()->forgetInstance(EncryptedCredentialService::class);
    config(['app.key' => $oldAppKey]);
    $serviceOld = app(EncryptedCredentialService::class);

    expect(fn () => $serviceOld->decrypt($fresh->client_id_encrypted))
        ->toThrow(\RuntimeException::class, 'Decryption failed');
    expect(fn () => $serviceOld->decrypt($fresh->webhook_secret_encrypted))
        ->toThrow(\RuntimeException::class, 'Decryption failed');
});

test('rotateKey decrypts with the old key even when the instance key differs from the old key', function () {
    $oldAppKey = 'base64:' . base64_encode(random_bytes(32));
    $newAppKey = 'base64:' . base64_encode(random_bytes(32));

    $tenant = createTenant();

    config(['app.key']); // "current" key in the container

    // Simulate a record encrypted with an OLD key while the app is already
    // running with the NEW key (the real scenario during a key rotation).
    app()->forgetInstance(EncryptedCredentialService::class);
    $service = app(EncryptedCredentialService::class);
    $oldDerivedKey = hash_hkdf('sha256', $oldAppKey, 32, 'tenant-credentials-encryption');

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('encryptWith');
    $method->setAccessible(true);

    $cred = TenantEfiCredentials::create([
        'tenant_id' => $tenant->id,
        'client_id_encrypted' => $method->invoke($service, 'Client_Id_old_key', $oldDerivedKey),
        'client_secret_encrypted' => $method->invoke($service, 'Client_Secret_old_key', $oldDerivedKey),
        'pix_key_encrypted' => $method->invoke($service, 'pix@old-key.com', $oldDerivedKey),
        'account_type' => 'sandbox',
        'is_active' => true,
    ]);

    $service->rotateKey($oldAppKey, $newAppKey);

    $fresh = $cred->fresh();

    app()->forgetInstance(EncryptedCredentialService::class);
    config(['app.key' => $newAppKey]);
    $serviceNew = app(EncryptedCredentialService::class);

    expect($serviceNew->decrypt($fresh->client_id_encrypted))->toBe('Client_Id_old_key');
    expect($serviceNew->decrypt($fresh->client_secret_encrypted))->toBe('Client_Secret_old_key');
    expect($serviceNew->decrypt($fresh->pix_key_encrypted))->toBe('pix@old-key.com');
});
