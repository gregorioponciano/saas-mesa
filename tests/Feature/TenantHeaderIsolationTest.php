<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\TenantEfiCredentials;
use App\Services\EncryptedCredentialService;

function createEfiCredentialsFor(Tenant $tenant): void
{
    $encryptor = app(EncryptedCredentialService::class);

    TenantEfiCredentials::create([
        'tenant_id' => $tenant->id,
        'client_id_encrypted' => $encryptor->encrypt('Client_Id_tenant'),
        'client_secret_encrypted' => $encryptor->encrypt('Client_Secret_tenant'),
        'pix_key_encrypted' => $encryptor->encrypt('pix@tenant.com'),
        'account_type' => 'sandbox',
        'is_active' => true,
    ]);
}

test('regular tenant admin cannot switch tenant via X-Tenant-Id header', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();

    // A não tem credenciais configuradas; B tem. Se o header fosse respeitado,
    // a resposta refletiria os dados de B.
    createEfiCredentialsFor($tenantB);

    $adminA = createTenantAdmin($tenantA);

    $this->actingAs($adminA)
        ->withHeader('X-Tenant-Id', (string) $tenantB->id)
        ->getJson('/api/settings/efi-credentials')
        ->assertStatus(200)
        ->assertJsonPath('configured', false);
});

test('unauthenticated request cannot switch tenant via X-Tenant-Id header', function () {
    $tenantB = createTenant();
    createEfiCredentialsFor($tenantB);

    $this->withHeader('X-Tenant-Id', (string) $tenantB->id)
        ->getJson('/api/settings/efi-credentials')
        ->assertStatus(401);
});

test('superadmin can switch tenant via X-Tenant-Id header', function () {
    $tenantB = createTenant();
    createEfiCredentialsFor($tenantB);

    $superadmin = createSuperAdmin();

    $this->actingAs($superadmin)
        ->withHeader('X-Tenant-Id', (string) $tenantB->id)
        ->getJson('/api/settings/efi-credentials')
        ->assertStatus(200)
        ->assertJsonPath('configured', true);
});
