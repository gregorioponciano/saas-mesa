<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\DeliveryPerson;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Models\TenantBackup;
use App\Models\User;
use Illuminate\Support\Str;

it('superadmin cria uma nova empresa com admin e assinatura trial', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->postJson('/api/superadmin/tenants', [
        'name' => 'Nova Lanchonete',
        'email' => 'nova@lanchonete.com',
        'whatsapp' => '(11) 99999-0000',
        'admin_name' => 'João Admin',
        'admin_password' => 'senha-segura-123',
    ])->assertCreated()
        ->assertJsonPath('tenant.name', 'Nova Lanchonete')
        ->assertJsonPath('tenant.plan', 'free')
        ->assertJsonPath('tenant.status', 'trial');

    $tenant = Tenant::where('slug', 'nova-lanchonete')->first();

    expect($tenant)->not->toBeNull()
        ->and($tenant->maxTablesAllowed())->toBe(2);

    $admin = User::where('tenant_id', $tenant->id)->where('role', 'admin')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->email)->toBe('nova@lanchonete.com');

    expect(SaasSubscription::where('tenant_id', $tenant->id)->where('status', 'trial')->exists())->toBeTrue();

    expect(AuditLog::where('action', 'tenant.create')->exists())->toBeTrue();
});

it('valida dados ao criar empresa', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->postJson('/api/superadmin/tenants', [
        'name' => '',
        'email' => 'invalido',
        'admin_name' => '',
        'admin_password' => 'curta',
    ])->assertStatus(422);

    expect(Tenant::count())->toBe(0);
});

it('exporta os dados da empresa para LGPD', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant(['name' => 'Empresa Exportada']);

    $this->getJson('/api/superadmin/tenants/'.$tenant->id.'/export')
        ->assertOk()
        ->assertJsonPath('purpose', 'solicitacao-lgpd-exportacao')
        ->assertJsonPath('tenant.name', 'Empresa Exportada')
        ->assertHeader('Content-Disposition');

    expect(AuditLog::where('action', 'tenant.export_data')->exists())->toBeTrue();
});

it('anonimiza e encerra a empresa removendo dados pessoais', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant(['name' => 'Empresa Sensível', 'whatsapp' => '(11) 12345-6789']);
    $admin = createTenantAdmin($tenant, ['email' => 'admin@sensivel.com']);
    $entregador = DeliveryPerson::create([
        'tenant_id' => $tenant->id,
        'name' => 'Carlos Entregador',
        'email' => 'carlos@delivery.com',
        'phone' => '(11) 88888-8888',
        'cpf' => '123.456.789-00',
        'password' => 'segredo',
    ]);
    $backup = TenantBackup::create([
        'tenant_id' => $tenant->id,
        'uuid' => (string) Str::uuid(),
        'filename' => 'backup.json',
        'disk' => 'backups',
        'size_bytes' => 10,
        'status' => 'ready',
        'type' => 'manual',
    ]);

    $this->deleteJson('/api/superadmin/tenants/'.$tenant->id)
        ->assertOk()
        ->assertJsonPath('message', 'Empresa anonimizada e encerrada com sucesso (LGPD).');

    $tenant->refresh();
    $admin->refresh();
    $entregador->refresh();

    expect($tenant->status)->toBe('cancelled')
        ->and($tenant->name)->toContain('Empresa Removida')
        ->and($tenant->whatsapp)->toBeNull()
        ->and($tenant->email)->toEndWith('@anonimo.invalid')
        ->and($admin->email)->toEndWith('@anonimo.invalid')
        ->and($admin->name)->toBe('Usuário Removido')
        ->and($entregador->email)->toBeNull()
        ->and($entregador->cpf)->toBeNull()
        ->and($entregador->name)->toBe('Entregador Removido');

    expect(TenantBackup::where('tenant_id', $tenant->id)->exists())->toBeFalse();
    expect(SaasSubscription::where('tenant_id', $tenant->id)->exists())->toBeFalse();
    expect(AuditLog::where('action', 'tenant.anonymize')->exists())->toBeTrue();
});

it('registra ações no log de auditoria com IP do operador', function () {
    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $tenant = createTenant(['name' => 'Auditável']);

    $this->postJson('/api/superadmin/tenants/'.$tenant->id.'/suspend')->assertOk();

    $log = AuditLog::where('action', 'tenant.suspend')->first();

    expect($log)->not->toBeNull()
        ->and($log->admin_user_id)->toBe($superadmin->id)
        ->and($log->tenant_id)->toBe($tenant->id)
        ->and($log->description)->toContain('Auditável')
        ->and($log->ip)->not->toBeNull();
});
