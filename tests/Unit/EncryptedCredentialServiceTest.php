<?php

declare(strict_types=1);

use App\Services\EncryptedCredentialService;

test('encrypt and decrypt returns original value', function () {
    $service = app(EncryptedCredentialService::class);
    $original = 'super-secret-efi-client-id';

    $encrypted = $service->encrypt($original);
    $decrypted = $service->decrypt($encrypted);

    expect($decrypted)->toBe($original);
});

test('encrypted value is different from original', function () {
    $service = app(EncryptedCredentialService::class);
    $original = 'my-secret-value';

    $encrypted = $service->encrypt($original);

    expect($encrypted)->not->toBe($original);
});

test('same value produces different encrypted outputs', function () {
    $service = app(EncryptedCredentialService::class);
    $original = 'same-value';

    $encrypted1 = $service->encrypt($original);
    $encrypted2 = $service->encrypt($original);

    expect($encrypted1)->not->toBe($encrypted2);
});

test('invalid encrypted payload throws exception', function () {
    $service = app(EncryptedCredentialService::class);

    $this->expectException(RuntimeException::class);

    $service->decrypt('invalid-base64!');
});

test('tampered encrypted payload throws exception', function () {
    $service = app(EncryptedCredentialService::class);

    $encrypted = $service->encrypt('important-data');
    $tampered = substr($encrypted, 0, -3).'abc';

    $this->expectException(RuntimeException::class);

    $service->decrypt($tampered);
});
