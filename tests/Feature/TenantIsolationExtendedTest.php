<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\DeliveryEarning;
use App\Models\DeliveryPerson;
use App\Models\Ingredient;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\TenantBillingConfig;
use App\Models\TenantEfiCredentials;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\EncryptedCredentialService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

function tenantIsolationCredentials(Tenant $tenant): TenantEfiCredentials
{
    $encryptor = app(EncryptedCredentialService::class);

    return TenantEfiCredentials::create([
        'tenant_id' => $tenant->id,
        'client_id_encrypted' => $encryptor->encrypt("client_{$tenant->id}"),
        'client_secret_encrypted' => $encryptor->encrypt("secret_{$tenant->id}"),
        'pix_key_encrypted' => $encryptor->encrypt("pix_{$tenant->id}@test.com"),
        'account_type' => 'sandbox',
        'webhook_secret_encrypted' => $encryptor->encrypt("webhook_secret_{$tenant->id}"),
        'is_active' => true,
    ]);
}

test('tenant A nao le nem altera pagamentos do tenant B', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    $orderA = Order::factory()->create(['tenant_id' => $tenantA->id]);
    $orderB = Order::factory()->create(['tenant_id' => $tenantB->id]);

    Payment::create([
        'order_id' => $orderA->id, 'tenant_id' => $tenantA->id,
        'amount' => 10.00, 'payment_method' => 'pix', 'status' => 'pending',
    ]);
    $paymentB = Payment::create([
        'order_id' => $orderB->id, 'tenant_id' => $tenantB->id,
        'amount' => 99.00, 'payment_method' => 'pix', 'status' => 'pending',
    ]);

    $this->actingAs(createTenantAdmin($tenantA));

    expect(Payment::count())->toBe(1);
    expect(Payment::find($paymentB->id))->toBeNull();
    expect(fn () => Payment::findOrFail($paymentB->id))->toThrow(ModelNotFoundException::class);

    expect(Payment::where('id', $paymentB->id)->update(['status' => 'paid']))->toBe(0);
    expect($paymentB->fresh()->status)->toBe('pending');
});

test('tenant A nao le nem altera entregadores do tenant B', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    DeliveryPerson::create(['tenant_id' => $tenantA->id, 'name' => 'Entregador A', 'phone' => '11999990001']);
    $personB = DeliveryPerson::create(['tenant_id' => $tenantB->id, 'name' => 'Entregador B', 'phone' => '11999990002']);

    $this->actingAs(createTenantAdmin($tenantA));

    expect(DeliveryPerson::count())->toBe(1);
    expect(DeliveryPerson::find($personB->id))->toBeNull();
    expect(fn () => DeliveryPerson::findOrFail($personB->id))->toThrow(ModelNotFoundException::class);

    expect(DeliveryPerson::where('id', $personB->id)->update(['status' => 'inactive']))->toBe(0);
    expect($personB->fresh()->status)->toBe('active');
});

test('tenant A nao le nem altera ingredientes do tenant B', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    Ingredient::create(['tenant_id' => $tenantA->id, 'name' => 'Queijo A']);
    $ingredientB = Ingredient::create(['tenant_id' => $tenantB->id, 'name' => 'Queijo B']);

    $this->actingAs(createTenantAdmin($tenantA));

    expect(Ingredient::count())->toBe(1);
    expect(Ingredient::find($ingredientB->id))->toBeNull();
    expect(fn () => Ingredient::findOrFail($ingredientB->id))->toThrow(ModelNotFoundException::class);

    expect(Ingredient::where('id', $ingredientB->id)->update(['is_active' => false]))->toBe(0);
    expect($ingredientB->fresh()->is_active)->toBeTrue();
});

test('tenant A nao le nem altera notificacoes do tenant B', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    $userA = createTenantAdmin($tenantA);
    $userB = createTenantAdmin($tenantB);

    Notification::create([
        'tenant_id' => $tenantA->id, 'notifiable_type' => User::class, 'notifiable_id' => $userA->id,
        'type' => 'order_created', 'data' => ['order_id' => 1],
    ]);
    $notificationB = Notification::create([
        'tenant_id' => $tenantB->id, 'notifiable_type' => User::class, 'notifiable_id' => $userB->id,
        'type' => 'order_created', 'data' => ['order_id' => 2],
    ]);

    $this->actingAs($userA);

    expect(Notification::count())->toBe(1);
    expect(Notification::find($notificationB->id))->toBeNull();
    expect(fn () => Notification::findOrFail($notificationB->id))->toThrow(ModelNotFoundException::class);

    expect(Notification::where('id', $notificationB->id)->update(['read_at' => now()]))->toBe(0);
    expect($notificationB->fresh()->read_at)->toBeNull();
});

test('tenant A nao le nem altera enderecos do tenant B', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    $userA = createTenantAdmin($tenantA);
    $userB = createTenantAdmin($tenantB);

    UserAddress::create([
        'tenant_id' => $tenantA->id, 'user_id' => $userA->id,
        'label' => 'Casa', 'address' => 'Rua A', 'city' => 'Sao Paulo', 'state' => 'SP',
    ]);
    $addressB = UserAddress::create([
        'tenant_id' => $tenantB->id, 'user_id' => $userB->id,
        'label' => 'Trabalho', 'address' => 'Rua B', 'city' => 'Rio de Janeiro', 'state' => 'RJ',
    ]);

    $this->actingAs($userA);

    expect(UserAddress::count())->toBe(1);
    expect(UserAddress::find($addressB->id))->toBeNull();
    expect(fn () => UserAddress::findOrFail($addressB->id))->toThrow(ModelNotFoundException::class);

    expect(UserAddress::where('id', $addressB->id)->update(['is_default' => true]))->toBe(0);
    expect($addressB->fresh()->is_default)->toBeFalse();
});

test('tenant A nao le nem altera ganhos de entrega do tenant B', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    $orderA = Order::factory()->create(['tenant_id' => $tenantA->id]);
    $orderB = Order::factory()->create(['tenant_id' => $tenantB->id]);
    $personA = DeliveryPerson::create(['tenant_id' => $tenantA->id, 'name' => 'Entregador A', 'phone' => '11999990001']);
    $personB = DeliveryPerson::create(['tenant_id' => $tenantB->id, 'name' => 'Entregador B', 'phone' => '11999990002']);

    DeliveryEarning::create([
        'tenant_id' => $tenantA->id, 'delivery_person_id' => $personA->id,
        'order_id' => $orderA->id, 'amount' => 5.00, 'status' => 'pending',
    ]);
    $earningB = DeliveryEarning::create([
        'tenant_id' => $tenantB->id, 'delivery_person_id' => $personB->id,
        'order_id' => $orderB->id, 'amount' => 15.00, 'status' => 'pending',
    ]);

    $this->actingAs(createTenantAdmin($tenantA));

    expect(DeliveryEarning::count())->toBe(1);
    expect(DeliveryEarning::find($earningB->id))->toBeNull();
    expect(fn () => DeliveryEarning::findOrFail($earningB->id))->toThrow(ModelNotFoundException::class);

    expect(DeliveryEarning::where('id', $earningB->id)->update(['status' => 'paid']))->toBe(0);
    expect($earningB->fresh()->status)->toBe('pending');
});

test('tenant A nao le nem altera atributos de produto do tenant B', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    $categoryA = Category::factory()->create(['tenant_id' => $tenantA->id]);
    $categoryB = Category::factory()->create(['tenant_id' => $tenantB->id]);
    $productA = Product::factory()->create(['tenant_id' => $tenantA->id, 'category_id' => $categoryA->id]);
    $productB = Product::factory()->create(['tenant_id' => $tenantB->id, 'category_id' => $categoryB->id]);

    ProductAttribute::create([
        'tenant_id' => $tenantA->id, 'product_id' => $productA->id, 'name' => 'Tamanho A', 'type' => 'single',
    ]);
    $attributeB = ProductAttribute::create([
        'tenant_id' => $tenantB->id, 'product_id' => $productB->id, 'name' => 'Tamanho B', 'type' => 'single',
    ]);

    $this->actingAs(createTenantAdmin($tenantA));

    expect(ProductAttribute::count())->toBe(1);
    expect(ProductAttribute::find($attributeB->id))->toBeNull();
    expect(fn () => ProductAttribute::findOrFail($attributeB->id))->toThrow(ModelNotFoundException::class);

    expect(ProductAttribute::where('id', $attributeB->id)->update(['price' => 2.00]))->toBe(0);
    expect($attributeB->fresh()->price)->toBe('0.00');
});

test('tenant A nao le nem altera tickets de suporte do tenant B', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    $userA = createTenantAdmin($tenantA);
    $userB = createTenantAdmin($tenantB);

    SupportTicket::create([
        'tenant_id' => $tenantA->id, 'user_id' => $userA->id,
        'subject' => 'Ticket A', 'category' => 'pedido', 'priority' => 'alta', 'status' => 'aberto',
    ]);
    $ticketB = SupportTicket::create([
        'tenant_id' => $tenantB->id, 'user_id' => $userB->id,
        'subject' => 'Ticket B', 'category' => 'pagamento', 'priority' => 'baixa', 'status' => 'aberto',
    ]);

    $this->actingAs($userA);

    expect(SupportTicket::count())->toBe(1);
    expect(SupportTicket::find($ticketB->id))->toBeNull();
    expect(fn () => SupportTicket::findOrFail($ticketB->id))->toThrow(ModelNotFoundException::class);

    expect(SupportTicket::where('id', $ticketB->id)->update(['status' => 'resolvido']))->toBe(0);
    expect($ticketB->fresh()->status)->toBe('aberto');
});

test('tenant A nao le nem altera pagamentos de pedido do tenant B', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    $orderA = Order::factory()->create(['tenant_id' => $tenantA->id]);
    $orderB = Order::factory()->create(['tenant_id' => $tenantB->id]);

    OrderPayment::create([
        'order_id' => $orderA->id, 'tenant_id' => $tenantA->id,
        'amount_cents' => 1000, 'method' => 'pix', 'status' => 'pending',
        'idempotency_key' => Str::uuid()->toString(),
    ]);
    $paymentB = OrderPayment::create([
        'order_id' => $orderB->id, 'tenant_id' => $tenantB->id,
        'amount_cents' => 9900, 'method' => 'pix', 'status' => 'pending',
        'idempotency_key' => Str::uuid()->toString(),
    ]);

    $this->actingAs(createTenantAdmin($tenantA));

    expect(OrderPayment::count())->toBe(1);
    expect(OrderPayment::find($paymentB->id))->toBeNull();
    expect(fn () => OrderPayment::findOrFail($paymentB->id))->toThrow(ModelNotFoundException::class);

    expect(OrderPayment::where('id', $paymentB->id)->update(['status' => 'paid']))->toBe(0);
    expect($paymentB->fresh()->status)->toBe('pending');
});

test('tenant A nao le nem altera credenciais EfiBank do tenant B', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    tenantIsolationCredentials($tenantA);
    $credentialsB = tenantIsolationCredentials($tenantB);

    $this->actingAs(createTenantAdmin($tenantA));

    expect(TenantEfiCredentials::count())->toBe(1);
    expect(TenantEfiCredentials::find($credentialsB->id))->toBeNull();
    expect(fn () => TenantEfiCredentials::findOrFail($credentialsB->id))->toThrow(ModelNotFoundException::class);

    expect(TenantEfiCredentials::where('id', $credentialsB->id)->update(['is_active' => false]))->toBe(0);
    expect($credentialsB->fresh()->is_active)->toBeTrue();
});

test('tenant A nao le nem altera configuracao de billing do tenant B', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    TenantBillingConfig::create([
        'tenant_id' => $tenantA->id, 'billing_type' => 'fixed', 'monthly_fee_cents' => 1000, 'is_active' => true,
    ]);
    $configB = TenantBillingConfig::create([
        'tenant_id' => $tenantB->id, 'billing_type' => 'fixed', 'monthly_fee_cents' => 9000, 'is_active' => true,
    ]);

    $this->actingAs(createTenantAdmin($tenantA));

    expect(TenantBillingConfig::count())->toBe(1);
    expect(TenantBillingConfig::find($configB->id))->toBeNull();
    expect(fn () => TenantBillingConfig::findOrFail($configB->id))->toThrow(ModelNotFoundException::class);

    expect(TenantBillingConfig::where('id', $configB->id)->update(['is_active' => false]))->toBe(0);
    expect($configB->fresh()->is_active)->toBeTrue();
});

test('trait BelongsToTenant bloqueia troca de tenant_id em recurso existente', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    $config = TenantBillingConfig::create([
        'tenant_id' => $tenantA->id, 'billing_type' => 'fixed', 'monthly_fee_cents' => 1000,
    ]);

    $this->actingAs(createTenantAdmin($tenantA));

    expect(fn () => $config->update(['tenant_id' => $tenantB->id]))
        ->toThrow(RuntimeException::class, 'Cannot change tenant_id of an existing resource.');
});

test('tenant A nao acessa pedido do tenant B por ID direto na URL', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);

    $orderB = Order::factory()->create(['tenant_id' => $tenantB->id]);

    $this->actingAs(createTenantAdmin($tenantA));

    $this->getJson("/api/orders/{$orderB->id}/payment/status")
        ->assertStatus(404);

    $this->getJson("/api/orders/{$orderB->id}/payment/qrcode")
        ->assertStatus(404);
});
