<?php

declare(strict_types=1);

use App\Livewire\Admin\PlatformSupport;
use App\Livewire\Admin\SidebarCounts;
use App\Livewire\Client\SupportPage;
use App\Livewire\Superadmin\PlatformSupportManager;
use App\Livewire\Superadmin\SupportSidebarCounts;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

it('preserva audience tenant como padrao para tickets existentes', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);

    $ticket = SupportTicket::create([
        'tenant_id' => $tenant->id,
        'user_id' => $admin->id,
        'subject' => 'Ticket legado',
        'category' => 'pedido',
        'priority' => 'media',
        'status' => 'aberto',
    ]);

    expect($ticket->audience)->toBe(SupportTicket::AUDIENCE_TENANT);
});

it('tenant admin ve apenas tickets platform do proprio tenant no canal com a plataforma', function () {
    $tenantA = createTenant(['name' => 'Tenant A']);
    $tenantB = createTenant(['name' => 'Tenant B']);
    $adminA = createTenantAdmin($tenantA);

    SupportTicket::create([
        'tenant_id' => $tenantA->id, 'user_id' => $adminA->id, 'subject' => 'Platform A',
        'category' => 'conta', 'priority' => 'alta', 'status' => 'aberto',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);
    SupportTicket::create([
        'tenant_id' => $tenantB->id, 'user_id' => createTenantAdmin($tenantB)->id,
        'subject' => 'Platform B', 'category' => 'conta', 'priority' => 'media', 'status' => 'aberto',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);
    SupportTicket::create([
        'tenant_id' => $tenantA->id, 'user_id' => $adminA->id, 'subject' => 'Cliente A',
        'category' => 'pedido', 'priority' => 'baixa', 'status' => 'aberto',
    ]);

    $component = Livewire::actingAs($adminA)->test(PlatformSupport::class)->instance();

    expect($component->tickets)->toHaveCount(1)
        ->and($component->tickets->first()->tenant_id)->toBe($tenantA->id)
        ->and($component->tickets->first()->audience)->toBe(SupportTicket::AUDIENCE_PLATFORM);
});

it('tenant admin nao acessa ticket platform de outro tenant pelo id', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();
    $adminB = createTenantAdmin($tenantB);

    $ticketB = SupportTicket::create([
        'tenant_id' => $tenantB->id, 'user_id' => $adminB->id, 'subject' => 'Platform B',
        'category' => 'conta', 'priority' => 'media', 'status' => 'aberto',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);

    expect(fn () => Livewire::actingAs(createTenantAdmin($tenantA))
        ->test(PlatformSupport::class)
        ->call('viewTicket', $ticketB->id))
        ->toThrow(ModelNotFoundException::class);
});

it('abre chamado platform via admin e expoe a mensagem inicial', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant, ['name' => 'Admin ABC']);

    Livewire::actingAs($admin)
        ->test(PlatformSupport::class)
        ->set('showCreateForm', true)
        ->set('newSubject', 'Preciso de ajuda com a cobrança')
        ->set('newCategory', 'conta')
        ->set('newPriority', 'alta')
        ->set('newBody', 'Estou com um problema na minha fatura deste mês.')
        ->call('openTicket')
        ->assertDispatched('notify');

    $ticket = SupportTicket::where('audience', SupportTicket::AUDIENCE_PLATFORM)->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->tenant_id)->toBe($tenant->id)
        ->and($ticket->status)->toBe('aberto');

    $message = SupportTicketMessage::where('ticket_id', $ticket->id)->first();

    expect($message->author_role)->toBe('admin')
        ->and($message->author_name)->toBe('Admin ABC')
        ->and($message->body)->toContain('minha fatura');
});

it('superadmin ve tickets platform de todos os tenants mas nao tickets tenant', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);
    $adminA = createTenantAdmin($tenantA);
    $adminB = createTenantAdmin($tenantB);

    SupportTicket::create([
        'tenant_id' => $tenantA->id, 'user_id' => $adminA->id, 'subject' => 'Plat A',
        'category' => 'conta', 'priority' => 'alta', 'status' => 'aberto',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);
    SupportTicket::create([
        'tenant_id' => $tenantB->id, 'user_id' => $adminB->id, 'subject' => 'Plat B',
        'category' => 'conta', 'priority' => 'media', 'status' => 'aguardando_cliente',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);
    SupportTicket::create([
        'tenant_id' => $tenantA->id, 'user_id' => $adminA->id, 'subject' => 'Tenant ticket',
        'category' => 'pedido', 'priority' => 'baixa', 'status' => 'aberto',
    ]);

    $component = Livewire::actingAs(createSuperAdmin())
        ->test(PlatformSupportManager::class)
        ->instance();

    expect($component->tickets)->toHaveCount(2)
        ->and($component->tickets->pluck('subject'))->toContain('Plat A', 'Plat B')
        ->and($component->tickets->pluck('subject'))->not->toContain('Tenant ticket');
});

it('plataforma responde ao chamado e marca autor como platform', function () {
    $tenant = createTenant(['name' => 'Zeus Lanches']);
    $admin = createTenantAdmin($tenant);

    $ticket = SupportTicket::create([
        'tenant_id' => $tenant->id, 'user_id' => $admin->id, 'subject' => 'Problema no pix',
        'category' => 'pagamento', 'priority' => 'alta', 'status' => 'aberto',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);

    Livewire::actingAs(createSuperAdmin())
        ->test(PlatformSupportManager::class)
        ->set('viewingTicketId', $ticket->id)
        ->set('replyBody', 'Identificamos o problema e ja corrigimos.')
        ->call('sendReply')
        ->assertDispatched('notify');

    expect($ticket->messages)->toHaveCount(1);

    $reply = $ticket->messages->sortBy('id')->last();

    expect($reply->author_role)->toBe('platform')
        ->and($reply->body)->toContain('corrigimos')
        ->and($ticket->fresh()->status)->toBe('em_atendimento');
});

it('sidebar do superadmin conta apenas tickets platform abertos', function () {
    $tenantA = createTenant(['name' => 'A']);
    $tenantB = createTenant(['name' => 'B']);
    $adminA = createTenantAdmin($tenantA);

    SupportTicket::create([
        'tenant_id' => $tenantA->id, 'user_id' => $adminA->id, 'subject' => 'Plat aberto',
        'category' => 'conta', 'priority' => 'media', 'status' => 'aberto',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);
    SupportTicket::create([
        'tenant_id' => $tenantB->id, 'user_id' => createTenantAdmin($tenantB)->id,
        'subject' => 'Plat resolvido', 'category' => 'conta', 'priority' => 'media',
        'status' => 'resolvido', 'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);
    SupportTicket::create([
        'tenant_id' => $tenantA->id, 'user_id' => $adminA->id, 'subject' => 'Cliente aberto',
        'category' => 'pedido', 'priority' => 'baixa', 'status' => 'aberto',
    ]);

    $component = Livewire::actingAs(createSuperAdmin())
        ->test(SupportSidebarCounts::class)
        ->instance();

    expect($component->openPlatformTicketsCount)->toBe(1);
});

it('sidebar admin separa tickets tenant dos tickets platform', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);

    SupportTicket::create([
        'tenant_id' => $tenant->id, 'user_id' => $admin->id, 'subject' => 'Cliente aberto',
        'category' => 'pedido', 'priority' => 'baixa', 'status' => 'aberto',
    ]);
    SupportTicket::create([
        'tenant_id' => $tenant->id, 'user_id' => $admin->id, 'subject' => 'Plat aberto',
        'category' => 'conta', 'priority' => 'media', 'status' => 'aberto',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);
    SupportTicket::create([
        'tenant_id' => $tenant->id, 'user_id' => $admin->id, 'subject' => 'Plat resolvido',
        'category' => 'conta', 'priority' => 'media', 'status' => 'resolvido',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);

    $component = Livewire::actingAs($admin)->test(SidebarCounts::class)->instance();

    expect($component->openTicketsCount)->toBe(1)
        ->and($component->openPlatformTicketsCount)->toBe(1);
});

it('empresa nao envia mensagem em chamado encerrado (fechado)', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);

    $ticket = SupportTicket::create([
        'tenant_id' => $tenant->id, 'user_id' => $admin->id, 'subject' => 'Encerrado',
        'category' => 'conta', 'priority' => 'media', 'status' => 'fechado',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);

    Livewire::actingAs($admin)
        ->test(PlatformSupport::class)
        ->set('viewingTicketId', $ticket->id)
        ->set('replyBody', 'Quero continuar falando...')
        ->call('sendReply')
        ->assertDispatched('notify');

    expect(SupportTicketMessage::where('ticket_id', $ticket->id)->count())->toBe(0);
});

it('empresa nao envia mensagem em chamado resolvido sem reabrir', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);

    $ticket = SupportTicket::create([
        'tenant_id' => $tenant->id, 'user_id' => $admin->id, 'subject' => 'Resolvido',
        'category' => 'conta', 'priority' => 'media', 'status' => 'resolvido',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);

    Livewire::actingAs($admin)
        ->test(PlatformSupport::class)
        ->set('viewingTicketId', $ticket->id)
        ->set('replyBody', 'Nao ficou bom...')
        ->call('sendReply');

    expect(SupportTicketMessage::where('ticket_id', $ticket->id)->count())->toBe(0)
        ->and($ticket->fresh()->status)->toBe('resolvido');
});

it('empresa reabre chamado fechado e consegue responder', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);

    $ticket = SupportTicket::create([
        'tenant_id' => $tenant->id, 'user_id' => $admin->id, 'subject' => 'Encerrado',
        'category' => 'conta', 'priority' => 'media', 'status' => 'fechado',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);

    Livewire::actingAs($admin)
        ->test(PlatformSupport::class)
        ->set('viewingTicketId', $ticket->id)
        ->call('updateStatus', $ticket->id, 'aberto');

    Livewire::actingAs($admin)
        ->test(PlatformSupport::class)
        ->set('viewingTicketId', $ticket->id)
        ->set('replyBody', 'Problema voltou!')
        ->call('sendReply')
        ->assertDispatched('notify');

    expect(SupportTicketMessage::where('ticket_id', $ticket->id)->count())->toBe(1)
        ->and($ticket->fresh()->status)->toBe('em_atendimento');
});

it('plataforma nao responde chamado fechado, mas pode responder resolvido', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);

    $closed = SupportTicket::create([
        'tenant_id' => $tenant->id, 'user_id' => $admin->id, 'subject' => 'Fechado',
        'category' => 'conta', 'priority' => 'media', 'status' => 'fechado',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);
    $resolved = SupportTicket::create([
        'tenant_id' => $tenant->id, 'user_id' => $admin->id, 'subject' => 'Resolvido',
        'category' => 'conta', 'priority' => 'media', 'status' => 'resolvido',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);
    $super = createSuperAdmin();

    Livewire::actingAs($super)
        ->test(PlatformSupportManager::class)
        ->set('viewingTicketId', $closed->id)
        ->set('replyBody', 'tentativa em fechado')
        ->call('sendReply')
        ->assertDispatched('notify');

    expect(SupportTicketMessage::where('ticket_id', $closed->id)->count())->toBe(0)
        ->and($closed->fresh()->status)->toBe('fechado');

    Livewire::actingAs($super)
        ->test(PlatformSupportManager::class)
        ->set('viewingTicketId', $resolved->id)
        ->set('replyBody', 'Confirmo o ajuste, qualquer coisa falamos.')
        ->call('sendReply')
        ->assertDispatched('notify');

    expect(SupportTicketMessage::where('ticket_id', $resolved->id)->count())->toBe(1)
        ->and($resolved->fresh()->status)->toBe('em_atendimento');
});

it('cliente nao responde ticket fechado', function () {
    $tenant = createTenant();
    $client = createTenantAdmin($tenant, ['role' => 'cliente']);

    $ticket = SupportTicket::create([
        'tenant_id' => $tenant->id, 'user_id' => $client->id, 'subject' => 'Fechado',
        'category' => 'pedido', 'priority' => 'media', 'status' => 'fechado',
    ]);

    Livewire::actingAs($client)
        ->test(SupportPage::class)
        ->set('viewingTicketId', $ticket->id)
        ->set('replyBody', 'Tem um problema ainda...')
        ->call('sendReply')
        ->assertDispatched('notify');

    expect(SupportTicketMessage::where('ticket_id', $ticket->id)->count())->toBe(0);
});

it('limita a abertura de chamados por rate limit (anti duplo envio)', function () {
    $tenant = createTenant();
    $admin = createTenantAdmin($tenant);

    $component = Livewire::actingAs($admin)->test(PlatformSupport::class);

    foreach (range(1, 10) as $i) {
        $component->set('newSubject', "Chamado {$i}")
            ->set('newCategory', 'conta')
            ->set('newPriority', 'media')
            ->set('newBody', 'Preciso de ajuda com minha fatura mensal.')
            ->call('openTicket');
    }

    $component->set('newSubject', 'Chamado 11')
        ->set('newCategory', 'conta')
        ->set('newPriority', 'media')
        ->set('newBody', 'Preciso de ajuda com minha fatura mensal.')
        ->call('openTicket')
        ->assertDispatched('notify');

    expect(SupportTicket::where('audience', SupportTicket::AUDIENCE_PLATFORM)->count())->toBe(10);
});
