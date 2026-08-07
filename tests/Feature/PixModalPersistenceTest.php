<?php

declare(strict_types=1);

use App\Livewire\Public\Menu;
use App\Models\Order;
use App\Services\EfiBank\TenantEfiBankService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('PIX (Menu): modal permanece aberto com QR apos gerar e verificar', function () {
    $tenant = createP0Tenant();
    $user = createTenantAdmin($tenant, ['role' => 'cliente', 'phone' => '(11) 99999-9999', 'is_staff' => false]);
    $product = createP0Product($tenant);

    $order = Order::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'customer_name' => 'Cliente Teste',
        'customer_phone' => '(11) 99999-9999',
        'total' => $product->price,
        'payment_method' => 'pix',
        'payment_change' => 0,
        'status' => 'novo',
        'type' => 'entrega',
    ]);

    $efi = Mockery::mock(TenantEfiBankService::class, function ($mock) {
        $mock->shouldReceive('generatePixChargeData')
            ->once()
            ->andReturn([
                'pixCopiaECola' => '00020126PIX',
                'qrcode' => base64_encode('png-qr'),
                'txid' => 'ped1234567890123456789012',
            ]);
    });
    app()->instance(TenantEfiBankService::class, $efi);

    Http::fake([
        '*/cob/ped1234567890123456789012' => Http::response(['status' => 'ATIVA'], 200),
    ]);

    $test = Livewire::actingAs($user)
        ->test(Menu::class, ['tenant' => $tenant])
        ->call('generateOrderPix', $order->id);

    expect($test->get('showPixModal'))->toBeTrue();
    expect($test->get('pixQrCode'))->not->toBeNull();
    expect($test->get('pixCopiaECola'))->toBe('00020126PIX');

    $test->call('verifyPixPayment');
    expect($test->get('showPixModal'))->toBeTrue();
    expect($test->get('pixQrCode'))->not->toBeNull();

    $test->call('verifyPixPayment');
    expect($test->get('showPixModal'))->toBeTrue();
    expect($test->get('pixQrCode'))->not->toBeNull();
});
