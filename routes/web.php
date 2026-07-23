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
use App\Livewire\Admin\SupportManager;
use App\Livewire\Admin\UserManager;
use App\Livewire\Client\ClientDashboard;
use App\Livewire\Client\SupportPage;
use App\Livewire\Waiter\WaiterDashboard;
use App\Livewire\Waiter\WaiterSupport;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;

Route::get('/', function () { return view('welcome'); });

Route::get('/termos-de-uso', function () {
    $path = base_path('docs/termos-de-uso-saas-mesa.md');
    if (!file_exists($path)) abort(404);
    $markdown = file_get_contents($path);
    $converter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false, 'max_nesting_level' => 6]);
    $converter->getEnvironment()->addExtension(new TableExtension());
    $content = $converter->convert($markdown)->getContent();
    return view('terms', compact('content'));
})->name('terms');

Route::get('/politica-de-privacidade', function () {
    $path = base_path('docs/termos-clientes-finais.md');
    if (!file_exists($path)) abort(404);
    $markdown = file_get_contents($path);
    $converter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false, 'max_nesting_level' => 6]);
    $converter->getEnvironment()->addExtension(new TableExtension());
    $content = $converter->convert($markdown)->getContent();
    return view('terms', compact('content'));
})->name('privacy');

Route::middleware(['throttle:10,1'])->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'registerTenantForm'])->name('register.tenant');
    Route::post('/register', [AuthController::class, 'registerTenant'])->name('register.tenant.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('throttle:5,1')->group(function () {
    Route::get('/login/recuperar-senha', [AuthController::class, 'adminForgotPasswordForm'])->name('admin.forgot.form');
    Route::post('/login/recuperar-senha', [AuthController::class, 'adminSendResetLink'])->name('admin.forgot.send');
    Route::get('/login/redefinir-senha/{token}', [AuthController::class, 'adminResetPasswordForm'])->name('admin.reset.form');
    Route::post('/login/redefinir-senha/{token}', [AuthController::class, 'adminResetPassword'])->name('admin.reset');
});

// Client Order Tracking (public, no auth)
Route::get('/pedido/{id}/rastreio', [\App\Http\Controllers\Web\DeliveryWebController::class, 'trackOrder'])
    ->name('order.tracking')
    ->whereNumber('id');

// Delivery Web Panel
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/entregador/login', [\App\Http\Controllers\Web\DeliveryWebController::class, 'loginForm'])->name('delivery.login');
    Route::post('/entregador/login', [\App\Http\Controllers\Web\DeliveryWebController::class, 'login']);
    Route::get('/entregador/recuperar-senha', [\App\Http\Controllers\Web\DeliveryWebController::class, 'forgotPasswordForm'])->name('delivery.forgot.form');
    Route::post('/entregador/recuperar-senha', [\App\Http\Controllers\Web\DeliveryWebController::class, 'sendResetLink'])->name('delivery.forgot.send');
    Route::get('/entregador/redefinir-senha/{token}', [\App\Http\Controllers\Web\DeliveryWebController::class, 'resetPasswordForm'])->name('delivery.reset.form');
    Route::post('/entregador/redefinir-senha/{token}', [\App\Http\Controllers\Web\DeliveryWebController::class, 'resetPassword'])->name('delivery.reset');
});

Route::middleware('auth:delivery-web')->prefix('entregador')->name('delivery.')->group(function () {
    Route::get('/painel', [\App\Http\Controllers\Web\DeliveryWebController::class, 'dashboard'])->name('dashboard');
    Route::get('/notificacoes', [\App\Http\Controllers\Web\DeliveryWebController::class, 'getNotifications'])->name('notifications.json');
    Route::post('/notificacoes/{notification}/ler', [\App\Http\Controllers\Web\DeliveryWebController::class, 'markNotificationRead'])->name('notification.read');
    Route::post('/pedidos/{order}/aceitar', [\App\Http\Controllers\Web\DeliveryWebController::class, 'acceptOrder'])->name('order.accept');
    Route::post('/pedidos/{order}/coletar', [\App\Http\Controllers\Web\DeliveryWebController::class, 'pickupOrder'])->name('order.pickup');
    Route::post('/pedidos/{order}/entregar', [\App\Http\Controllers\Web\DeliveryWebController::class, 'deliverOrder'])->name('order.deliver');
    Route::post('/toggle-disponibilidade', [\App\Http\Controllers\Web\DeliveryWebController::class, 'toggleAvailability'])->name('toggle.availability');
    Route::post('/configuracoes', [\App\Http\Controllers\Web\DeliveryWebController::class, 'updateSettings'])->name('settings.update');
    Route::post('/logout', [\App\Http\Controllers\Web\DeliveryWebController::class, 'logout'])->name('logout');
});

// Delivery Invite
Route::prefix('convidar/entregador')->name('delivery.invite.')->middleware('throttle:10,1')->group(function () {
    Route::get('/sucesso', [\App\Http\Controllers\Web\DeliveryInviteController::class, 'success'])->name('success');
    Route::get('/{token}', [\App\Http\Controllers\Web\DeliveryInviteController::class, 'show'])->name('show');
    Route::post('/{token}', [\App\Http\Controllers\Web\DeliveryInviteController::class, 'accept'])->name('accept');
});

// Admin Panel
Route::middleware(['auth', 'tenant.scope', 'check.subscription', 'check.admin'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/dashboard/tables', TablesPage::class)->name('dashboard.tables');
    Route::get('/dashboard/menu', MenuManager::class)->name('dashboard.menu');
    Route::get('/dashboard/users', UserManager::class)->name('dashboard.users');
    Route::get('/dashboard/cupons', CouponManager::class)->name('dashboard.cupons');
    Route::get('/dashboard/entregadores', DeliveryPeopleManager::class)->name('dashboard.delivery-people');
    Route::get('/dashboard/pontos', \App\Livewire\Admin\LoyaltyManager::class)->name('dashboard.loyalty');
    Route::get('/dashboard/configuracoes', Settings::class)->name('dashboard.settings');
    Route::get('/subscription', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/subscription', [SubscriptionController::class, 'store'])->name('subscription.checkout.store');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::get('/dashboard/efi-credentials', \App\Livewire\Admin\EfiCredentialsManager::class)->name('dashboard.efi-credentials');
    Route::get('/dashboard/configurar-email', \App\Livewire\Admin\SmtpSettings::class)->name('dashboard.smtp-settings');
    Route::get('/dashboard/suporte', SupportManager::class)->name('dashboard.support');
});

// Public Menu
Route::prefix('/cardapio')->group(function () {
    Route::get('/{slug:slug}', function (Tenant $slug) {
        if (Auth::check() && Auth::user()->tenant_id !== $slug->id) abort(403);
        return view('menu-page', ['tenant' => $slug]);
    })->name('menu.show');

    Route::get('/{slug:slug}/acesso', [AuthController::class, 'waiterLoginForm'])
        ->middleware('throttle:10,1')->name('waiter.login.form');
    Route::post('/{slug:slug}/acesso', [AuthController::class, 'waiterLogin'])
        ->middleware('throttle:10,1')->name('waiter.login');
    Route::get('/{slug:slug}/cadastro', [AuthController::class, 'waiterRegisterForm'])
        ->middleware('throttle:10,1')->name('waiter.register.form');
    Route::post('/{slug:slug}/cadastro', [AuthController::class, 'waiterRegister'])
        ->middleware('throttle:10,1')->name('waiter.register');
    Route::get('/{slug:slug}/recuperar-senha', [AuthController::class, 'forgotPasswordForm'])
        ->middleware('throttle:5,1')->name('waiter.forgot.form');
    Route::post('/{slug:slug}/recuperar-senha', [AuthController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')->name('waiter.forgot.send');
    Route::get('/{slug:slug}/redefinir-senha/{token}', [AuthController::class, 'resetPasswordForm'])
        ->middleware('throttle:5,1')->name('waiter.reset.form');
    Route::post('/{slug:slug}/redefinir-senha/{token}', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1')->name('waiter.reset');
});

// Waiter Panel
Route::middleware(['auth', 'tenant.scope', 'check.staff'])->prefix('/painel')->group(function () {
    Route::get('/{slug:slug}', function (Tenant $slug) {
        $user = Auth::user();
        if ($user->tenant_id !== $slug->id) abort(403);
        if ($slug->isFree()) abort(403, 'Acesso restrito. Plano Premium requerido.');
        return view('waiter-panel', ['tenant' => $slug]);
    })->name('waiter.panel');

    Route::get('/{slug:slug}/configuracoes', function (Tenant $slug) {
        $user = Auth::user();
        if ($user->tenant_id !== $slug->id) abort(403);
        return view('waiter-panel', ['tenant' => $slug, 'tab' => 'settings']);
    })->name('waiter.panel.settings');

    Route::get('/{slug:slug}/suporte', WaiterSupport::class)->name('waiter.support');
});

// Client Area
Route::middleware(['auth', 'tenant.scope'])->prefix('/conta')->group(function () {
    Route::get('/{slug:slug}', function (Tenant $slug) {
        $user = Auth::user();
        if ($user->tenant_id !== $slug->id) abort(403);
        return view('client-panel', ['tenant' => $slug]);
    })->name('client.panel');

    Route::get('/{slug:slug}/suporte', SupportPage::class)->name('client.support');
});

// Webhooks
Route::prefix('webhook/efi')
    ->middleware(['validate.webhook.signature'])
    ->group(function () {
        Route::post('/saas', [SaasWebhookController::class, 'handle']);
        Route::post('/tenant/{tenantId}', [TenantWebhookController::class, 'handle']);
    });
