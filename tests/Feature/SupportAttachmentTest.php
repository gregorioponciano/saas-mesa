<?php

declare(strict_types=1);

use App\Livewire\Client\SupportPage;
use App\Livewire\Superadmin\PlatformSupportManager;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('rejeita anexo de tipo invalido ao abrir ticket', function () {
    $tenant = createTenant();
    $client = createTenantAdmin($tenant, ['role' => 'cliente']);

    Livewire::actingAs($client)
        ->test(SupportPage::class)
        ->set('newSubject', 'Pedido atrasado')
        ->set('newCategory', 'pedido')
        ->set('newPriority', 'alta')
        ->set('newBody', 'Meu pedido chegou atrasado e quero reclamar.')
        ->set('attachment', UploadedFile::fake()->create('nota.txt', 10))
        ->call('openTicket')
        ->assertHasErrors(['attachment']);

    expect(SupportTicket::count())->toBe(0);
});

it('rejeita anexo acima do limite de 2MB ao abrir ticket', function () {
    Storage::fake('public');

    $tenant = createTenant();
    $client = createTenantAdmin($tenant, ['role' => 'cliente']);

    Livewire::actingAs($client)
        ->test(SupportPage::class)
        ->set('newSubject', 'Pedido atrasado')
        ->set('newCategory', 'pedido')
        ->set('newPriority', 'alta')
        ->set('newBody', 'Meu pedido chegou atrasado e quentei.')
        ->set('attachment', UploadedFile::fake()->create('foto.png', 4096))
        ->call('openTicket')
        ->assertHasErrors(['attachment']);

    expect(SupportTicket::count())->toBe(0);
});

it('salva anexo valido ao abrir ticket e registra caminho e mime', function () {
    Storage::fake('public');

    $tenant = createTenant();
    $client = createTenantAdmin($tenant, ['role' => 'cliente']);

    Livewire::actingAs($client)
        ->test(SupportPage::class)
        ->set('newSubject', 'Pedido atrasado')
        ->set('newCategory', 'pedido')
        ->set('newPriority', 'alta')
        ->set('newBody', 'Meu pedido chegou atrasado e quentei.')
        ->set('attachment', UploadedFile::fake()->image('print_pedido.png'))
        ->call('openTicket')
        ->assertDispatched('notify');

    $message = SupportTicketMessage::first();

    expect($message)->not->toBeNull()
        ->and($message->attachment_path)->not->toBeNull()
        ->and($message->attachment_original_name)->toBe('print_pedido.png')
        ->and($message->attachment_mime)->toBe('image/png');

    Storage::disk('public')->assertExists($message->attachment_path);
});

it('expoe a url publica do anexo na thread do ticket', function () {
    Storage::fake('public');

    $tenant = createTenant();
    $client = createTenantAdmin($tenant, ['role' => 'cliente']);

    Livewire::actingAs($client)
        ->test(SupportPage::class)
        ->set('newSubject', 'Falta item')
        ->set('newCategory', 'pedido')
        ->set('newPriority', 'media')
        ->set('newBody', 'Faltou a bebida no meu pedido.')
        ->set('attachment', UploadedFile::fake()->image('pedido_faltando.png'))
        ->call('openTicket');

    $ticket = SupportTicket::first();
    $message = SupportTicketMessage::first();

    expect(str_starts_with($message->attachmentUrl(), '/storage/'))->toBeTrue();

    $component = Livewire::actingAs($client)
        ->test(SupportPage::class)
        ->call('viewTicket', $ticket->id)
        ->instance();

    $firstMessage = $component->viewingTicket['messages'][0];

    expect($firstMessage['attachment_name'])->toBe('pedido_faltando.png')
        ->and($firstMessage['attachment_url'])->toBe($message->attachmentUrl());
});

it('plataforma pode responder anexando pdf e o arquivo fica no storage', function () {
    Storage::fake('public');

    $tenant = createTenant(['name' => 'Zeus Lanches']);
    $admin = createTenantAdmin($tenant);

    $ticket = SupportTicket::create([
        'tenant_id' => $tenant->id, 'user_id' => $admin->id, 'subject' => 'NF nao emitida',
        'category' => 'conta', 'priority' => 'alta', 'status' => 'aberto',
        'audience' => SupportTicket::AUDIENCE_PLATFORM,
    ]);

    Livewire::actingAs(createSuperAdmin())
        ->test(PlatformSupportManager::class)
        ->set('viewingTicketId', $ticket->id)
        ->set('replyBody', 'Segue a nota fiscal corrigida.')
        ->set('attachment', UploadedFile::fake()->create('nf_corrigida.pdf', 10, 'application/pdf'))
        ->call('sendReply')
        ->assertDispatched('notify');

    $reply = SupportTicketMessage::where('ticket_id', $ticket->id)->get()->sortBy('id')->last();

    expect($reply->attachment_original_name)->toBe('nf_corrigida.pdf')
        ->and($reply->attachment_mime)->toBe('application/pdf');

    Storage::disk('public')->assertExists($reply->attachment_path);
});
