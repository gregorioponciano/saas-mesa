<?php

use App\Livewire\Admin\EfiCredentialsManager;
use App\Models\TenantEfiCredentials;
use App\Services\EncryptedCredentialService;
use Livewire\Livewire;

test('efi credentials save via livewire component', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    Livewire::actingAs($user)
        ->test(EfiCredentialsManager::class)
        ->set('client_id', 'Client_Id_livewire')
        ->set('client_secret', 'Client_Secret_livewire')
        ->set('pix_key', 'pix@livewire.com')
        ->set('account_type', 'sandbox')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true)
        ->assertSet('error', null);

    $creds = TenantEfiCredentials::where('tenant_id', $tenant->id)->first();
    expect($creds)->not->toBeNull();
});

test('efi credentials update keeps existing certificate when not uploading new one', function () {
    $tenant = createTenant();
    $user = createTenantAdmin($tenant);

    TenantEfiCredentials::create([
        'tenant_id' => $tenant->id,
        'client_id_encrypted' => app(EncryptedCredentialService::class)->encrypt('Client_Id_old'),
        'client_secret_encrypted' => app(EncryptedCredentialService::class)->encrypt('Client_Secret_old'),
        'pix_key_encrypted' => app(EncryptedCredentialService::class)->encrypt('pix@old.com'),
        'account_type' => 'sandbox',
        'certificate_content_encrypted' => app(EncryptedCredentialService::class)->encrypt('CERT-P12-CONTENT'),
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(EfiCredentialsManager::class)
        ->set('client_id', 'Client_Id_new')
        ->set('client_secret', 'Client_Secret_new')
        ->set('pix_key', 'pix@new.com')
        ->set('account_type', 'production')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    $creds = TenantEfiCredentials::where('tenant_id', $tenant->id)->first();
    expect($creds->decryptCertificateContent())->toBe('CERT-P12-CONTENT')
        ->and($creds->decryptClientId())->toBe('Client_Id_new');
});
