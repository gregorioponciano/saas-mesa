<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Webhook\SaasWebhookController;
use App\Http\Controllers\Webhook\TenantWebhookController;
use App\Livewire\Admin\CouponManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DeliveryPeopleManager;
use App\Livewire\Admin\MenuManager;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\SubscriptionCheckout;
use App\Livewire\Admin\TablesPage;
use App\Livewire\Admin\UserManager;
use App\Livewire\Client\ClientDashboard;
use App\Livewire\Waiter\WaiterDashboard;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * --------------------------------------------------------------------------
 * Rotas Públicas / Iniciais
 * --------------------------------------------------------------------------
 */

// Rota da página inicial (Landing page ou Boas-vindas do sistema)
// Como usar: Acessar a URL raiz do projeto '/' via GET.
Route::get('/', function () {
    return view('welcome');
});

/**
 * --------------------------------------------------------------------------
 * Rotas de Autenticação com Rate Limit (Throttle)
 * Limita o usuário a 10 requisições por minuto para evitar ataques de força bruta.
 * --------------------------------------------------------------------------
 */
Route::middleware(['throttle:10,1'])->group(function () {
    
    // Exibe o formulário de login para administradores/estabelecimentos
    // Como usar: GET para '/login' ou route('login')
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    
    // Processa o envio dos dados de login do administrador
    // Como usar: POST para '/login' enviando 'email' e 'password'
    Route::post('/login', [AuthController::class, 'login']);
    
    // Exibe o formulário de cadastro de uma nova empresa (Tenant/Inquilino)
    // Como usar: GET para '/register' ou route('register.tenant')
    Route::get('/register', [AuthController::class, 'registerTenantForm'])->name('register.tenant');
    
    // Processa o cadastro da nova empresa e do usuário administrador
    // Como usar: POST para '/register' com os dados da empresa e do admin
    Route::post('/register', [AuthController::class, 'registerTenant'])->name('register.tenant.store');
});

// Desconecta o usuário logado do sistema
// Como usar: POST para '/logout' (obrigatório ser POST por segurança contra CSRF) ou route('logout')
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


/**
 * --------------------------------------------------------------------------
 * Painel Administrativo (Exclusivo para Admins do Tenant)
 * Protegido por: Autenticação, Escopo do Tenant, Checagem de Inscrição e Regra de Admin.
 * --------------------------------------------------------------------------
 */
Route::middleware(['auth', 'tenant.scope', 'check.subscription', 'check.admin'])->group(function () {
    
    // Tela principal do Painel de Controle (Dashboard) usando Livewire
    // Como usar: GET para '/dashboard' ou route('dashboard')
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    
    // Gerenciamento de mesas do restaurante
    // Como usar: GET para '/dashboard/tables' ou route('dashboard.tables')
    Route::get('/dashboard/tables', TablesPage::class)->name('dashboard.tables');
    
    // Gerenciamento de itens do cardápio (produtos, categorias, etc.)
    // Como usar: GET para '/dashboard/menu' ou route('dashboard.menu')
    Route::get('/dashboard/menu', MenuManager::class)->name('dashboard.menu');
    
    // Gerenciamento de usuários/funcionários vinculados à empresa
    // Como usar: GET para '/dashboard/users' ou route('dashboard.users')
    Route::get('/dashboard/users', UserManager::class)->name('dashboard.users');
    
    // Gerenciamento de cupons de desconto
    // Como usar: GET para '/dashboard/cupons' ou route('dashboard.cupons')
    Route::get('/dashboard/cupons', CouponManager::class)->name('dashboard.cupons');
    
    // Gerenciamento de entregadores cadastrados
    // Como usar: GET para '/dashboard/entregadores' ou route('dashboard.delivery-people')
    Route::get('/dashboard/entregadores', DeliveryPeopleManager::class)->name('dashboard.delivery-people');


    // Configurações gerais da empresa (nome, horários, endereço, etc.)
    // Como usar: GET para '/dashboard/configuracoes' ou route('dashboard.settings')
    Route::get('/dashboard/configuracoes', Settings::class)->name('dashboard.settings');

// Credenciais EfiBank do tenant (receber pagamentos dos clientes)
// Como usar: GET para '/dashboard/efi-credentials' ou route('dashboard.efi-credentials')
Route::get('/dashboard/efi-credentials', \App\Livewire\Admin\EfiCredentialsManager::class)
    ->name('dashboard.efi-credentials');

// Configuração de Email SMTP do tenant
// Como usar: GET para '/dashboard/configurar-email' ou route('dashboard.smtp-settings')
Route::get('/dashboard/configurar-email', \App\Livewire\Admin\SmtpSettings::class)
    ->name('dashboard.smtp-settings');

    // Tela de checkout de assinatura do plano da plataforma
    // Como usar: GET para '/subscription' ou route('subscription.checkout')
    Route::get('/subscription', [\App\Http\Controllers\SubscriptionController::class, 'checkout'])->name('subscription.checkout');

    // Processa o pagamento/ativação da assinatura da empresa
    // Como usar: POST para '/subscription' enviando os dados de pagamento
    Route::post('/subscription', [SubscriptionController::class, 'store'])->name('subscription.checkout.store');
    
    // Cancela a assinatura ativa da empresa na plataforma
    // Como usar: POST para '/subscription/cancel' ou route('subscription.cancel')
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
});


/**
 * --------------------------------------------------------------------------
 * Módulo de Cardápio e Autenticação de Garçons (Público/Clientes)
 * Prefixo: /cardapio
 * --------------------------------------------------------------------------
 */
Route::prefix('/cardapio')->group(function () {
    
    // Exibe o cardápio público de um restaurante específico através do 'slug'
    // Como usar: GET para '/cardapio/nome-do-restaurante' ou route('menu.show', ['slug' => 'slug-da-empresa'])
    Route::get('/{slug:slug}', function (Tenant $slug) {
        if (Auth::check() && Auth::user()->tenant_id !== $slug->id) {
            abort(403);
        }
        return view('menu-page', ['tenant' => $slug]);
    })->name('menu.show');

    // Exibe a tela de login exclusiva para Garçons/Staff do restaurante informando o slug
    // Como usar: GET para '/cardapio/nome-do-restaurante/acesso'
    Route::get('/{slug:slug}/acesso', [AuthController::class, 'waiterLoginForm'])->name('waiter.login.form');
    
    // Processa o login do garçom
    // Como usar: POST para '/cardapio/nome-do-restaurante/acesso' com as credenciais do garçom
    Route::post('/{slug:slug}/acesso', [AuthController::class, 'waiterLogin'])->name('waiter.login');

    // Exibe o formulário de cadastro/auto-registro para novos garçons
    // Como usar: GET para '/cardapio/nome-do-restaurante/cadastro'
    Route::get('/{slug:slug}/cadastro', [AuthController::class, 'waiterRegisterForm'])->name('waiter.register.form');
    
    // Processa o registro do novo garçom
    // Como usar: POST para '/cardapio/nome-do-restaurante/cadastro' com dados do funcionário
    Route::post('/{slug:slug}/cadastro', [AuthController::class, 'waiterRegister'])->name('waiter.register');

    // Exibe formulário de recuperação de senha
    Route::get('/{slug:slug}/recuperar-senha', [AuthController::class, 'forgotPasswordForm'])->name('waiter.forgot.form');

    // Processa solicitação de recuperação de senha
    Route::post('/{slug:slug}/recuperar-senha', [AuthController::class, 'sendResetLink'])->name('waiter.forgot.send');

    // Exibe formulário de redefinição de senha com token
    Route::get('/{slug:slug}/redefinir-senha/{token}', [AuthController::class, 'resetPasswordForm'])->name('waiter.reset.form');

    // Processa redefinição de senha
    Route::post('/{slug:slug}/redefinir-senha/{token}', [AuthController::class, 'resetPassword'])->name('waiter.reset');
});


/**
 * --------------------------------------------------------------------------
 * Painel da Equipe / Garçons (Staff)
 * Prefixo: /painel | Protegido por: Autenticação, Escopo e Regra de Staff.
 * --------------------------------------------------------------------------
 */
Route::middleware(['auth', 'tenant.scope', 'check.staff'])->prefix('/painel')->group(function () {
    
    // Exibe a tela principal do painel do garçom para gerenciar pedidos das mesas
    // Possui uma trava de segurança extra confirmando se o garçom pertence àquele Tenant (empresa)
    // Como usar: GET para '/painel/nome-do-restaurante' ou route('waiter.panel', ['slug' => 'slug-da-empresa'])
    Route::get('/{slug:slug}', function (Tenant $slug) {
        $user = Auth::user();

            if ($slug->isFree()) {
            abort(403, 'Acesso restrito. Plano Premium requerido.');
        }

        if ($user->tenant_id !== $slug->id) {
            abort(403);
        }

        return view('waiter-panel', ['tenant' => $slug]);
    })->name('waiter.panel');

    // Exibe as configurações do perfil do garçom ou do painel dele
    // Como usar: GET para '/painel/nome-do-restaurante/configuracoes'
    Route::get('/{slug:slug}/configuracoes', function (Tenant $slug) {
        $user = Auth::user();
        if ($user->tenant_id !== $slug->id) {
            abort(403);
        }
        return view('waiter-panel', ['tenant' => $slug, 'tab' => 'settings']);
    })->name('waiter.panel.settings');
});


/**
 * --------------------------------------------------------------------------
 * Área do Cliente Final (Minha Conta / Pedidos do Cliente)
 * Prefixo: /conta | Protegido por: Autenticação e Escopo do Tenant.
 * --------------------------------------------------------------------------
 */
Route::middleware(['auth', 'tenant.scope'])->prefix('/conta')->group(function () {
    
    // Exibe o painel do cliente final para ele acompanhar seus pedidos ou histórico naquele restaurante
    // Valida se o cliente está logado no escopo correto do restaurante atual
    // Como usar: GET para '/conta/nome-do-restaurante' ou route('client.panel', ['slug' => 'slug-da-empresa'])
    Route::get('/{slug:slug}', function (Tenant $slug) {
        $user = Auth::user();
        if ($user->tenant_id !== $slug->id) {
            abort(403); // Impede que um cliente veja dados de outra loja
        }
        return view('client-panel', ['tenant' => $slug]);
    })->name('client.panel');
});

/**
 * --------------------------------------------------------------------------
 * Webhooks EfiBank (sem CSRF, com validação HMAC)
 * --------------------------------------------------------------------------
 */
Route::prefix('webhook/efi')
    ->middleware(['validate.webhook.signature'])
    ->group(function () {
        Route::post('/saas', [SaasWebhookController::class, 'handle']);
        Route::post('/tenant/{tenantId}', [TenantWebhookController::class, 'handle']);
    });