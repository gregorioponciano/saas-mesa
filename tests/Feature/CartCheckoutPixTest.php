<?php

declare(strict_types=1);

use App\Livewire\Public\Cart;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\TenantEfiCredentials;
use App\Models\UserAddress;
use App\Services\EfiBank\TenantEfiBankService;
use App\Services\EncryptedCredentialService;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function saveFakeEfiCredentials(Tenant $tenant): void
{
    $enc = app(EncryptedCredentialService::class);
    TenantEfiCredentials::create([
        'tenant_id' => $tenant->id,
        'client_id_encrypted' => $enc->encrypt('client-id-teste'),
        'client_secret_encrypted' => $enc->encrypt('client-secret-teste'),
        'pix_key_encrypted' => $enc->encrypt('pix@teste.com.br'),
        'account_type' => 'sandbox',
        'is_active' => true,
    ]);
}

function p0Tenant(): Tenant
{
    return createTenant([
        'opening_time' => '00:00',
        'closing_time' => '23:59',
        'address' => 'Rua Teste 123',
        'city' => 'Sao Paulo',
        'state' => 'SP',
        'latitude' => -21.6869,
        'longitude' => -49.7989,
        'delivery_radius' => 50,
        'delivery_cost_enabled' => true,
        'delivery_cost_per_order' => 4,
        'delivery_cost_per_km' => 2,
    ]);
}

function p0Product(Tenant $tenant, float $price = 50.0): Product
{
    return Product::factory()->create([
        'tenant_id' => $tenant->id,
        'price' => $price,
    ]);
}

test('cart: endereco padrao vem pre-selecionado e nao duplica ao enviar pedido (inclusive modal PIX)', function () {
    $tenant = p0Tenant();
    saveFakeEfiCredentials($tenant);
    $user = createTenantAdmin($tenant, ['role' => 'cliente', 'phone' => '(11) 99999-9999', 'is_staff' => false]);
    $product = p0Product($tenant);

    $addr = UserAddress::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'label' => 'casa',
        'address' => 'Rua Sete de Setembro',
        'number' => '222',
        'neighborhood' => 'Centro',
        'city' => 'Lins',
        'state' => 'SP',
        'zipcode' => '16400025',
        'is_default' => true,
    ]);

    $efi = Mockery::mock(TenantEfiBankService::class, function ($mock) {
        $mock->shouldReceive('generatePixChargeData')
            ->andReturn([
                'pixCopiaECola' => '00020101021226830014BR.GOV.BCB',
                'qrcode' => base64_encode('png-qr'),
                'txid' => 'ped1234567890123456789012',
            ]);
    });
    app()->instance(TenantEfiBankService::class, $efi);

    app()->instance(GeocodingService::class, Mockery::mock(GeocodingService::class, function ($mock) {
        $mock->shouldReceive('geocode')->andReturn(['lat' => -21.7769, 'lng' => -49.7989, 'display_name' => 'Teste']);
    }));

    Http::fake([
        '*/oauth/*' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600], 200),
        '*cob/ped1234567890123456789012*' => Http::response(['status' => 'ATIVA'], 200),
    ]);

    $comp = Livewire::actingAs($user)
        ->test(Cart::class, ['tenant' => $tenant])
        ->call('addToCart', $product->id, $product->name, $product->price, [], 1);

    // endereco padrao deve vir marcado automaticamente
    expect($comp->get('selectedAddressId'))->toBe($addr->id);
    expect($comp->get('deliveryAddress'))->toContain('Rua Sete de Setembro');

    $comp->set('orderType', 'entrega')
        ->set('paymentMethod', 'pix')
        ->set('customerPhone', '11999999999')
        ->call('checkout');

    // modal PIX aberto com QR
    expect($comp->get('showPixCheckoutModal'))->toBeTrue();
    expect($comp->get('pixQrCode'))->not->toBeNull();

    // poll: cobranca ATIVA nao deve fechar o modal
    $comp->call('verifyCheckoutPixPayment');
    expect($comp->get('showPixCheckoutModal'))->toBeTrue();
    expect($comp->get('pixPaymentError'))->toBeFalse();
    expect($comp->get('pixPaymentErrorMsg'))->toBe('');

    $comp->call('verifyCheckoutPixPayment');
    expect($comp->get('showPixCheckoutModal'))->toBeTrue();

    // nao deve duplicar o endereco ao enviar pedido
    expect(UserAddress::where('user_id', $user->id)->count())->toBe(1);
});

test('checkout entrega sem endereco selecionado deduplica e nunca gera endereco duplicado', function () {
    $tenant = p0Tenant();
    $user = createTenantAdmin($tenant, ['role' => 'cliente', 'phone' => '(11) 99999-9999', 'is_staff' => false]);
    $product = p0Product($tenant);

    $efi = Mockery::mock(TenantEfiBankService::class, function ($mock) {
        $mock->shouldReceive('generatePixChargeData')
            ->andReturn([
                'pixCopiaECola' => '00020101021226830014BR.GOV.BCB',
                'qrcode' => base64_encode('png-qr'),
                'txid' => 'dedupe1234567891234567890',
            ]);
    });
    app()->instance(TenantEfiBankService::class, $efi);

    app()->instance(GeocodingService::class, Mockery::mock(GeocodingService::class, function ($mock) {
        $mock->shouldReceive('geocode')->andReturn(['lat' => -21.7769, 'lng' => -49.7989, 'display_name' => 'Teste']);
    }));

    Http::fake(['*' => Http::response(['status' => 'ATIVA'], 200)]);

    $comp = Livewire::actingAs($user)
        ->test(Cart::class, ['tenant' => $tenant])
        ->call('addToCart', $product->id, $product->name, $product->price, [], 1)
        ->set('orderType', 'entrega')
        ->set('paymentMethod', 'pix')
        ->set('customerPhone', '11999999999')
        ->set('deliveryAddress', 'Rua A, 10, Centro')
        ->call('checkout');

    // segundo envio com o mesmo endereco
    $comp->call('addToCart', $product->id, $product->name, $product->price, [], 1)
        ->set('orderType', 'entrega')
        ->set('paymentMethod', 'pix')
        ->set('customerPhone', '11999999999')
        ->set('deliveryAddress', 'Rua A, 10, Centro')
        ->call('checkout');

    expect(UserAddress::where('user_id', $user->id)->count())->toBeLessThanOrEqual(1);
});
