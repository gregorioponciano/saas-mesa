<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubscriptionController;
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

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['throttle:10,1'])->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'registerTenantForm'])->name('register.tenant');
    Route::post('/register', [AuthController::class, 'registerTenant'])->name('register.tenant.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'tenant.scope', 'check.subscription', 'check.admin'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/dashboard/tables', TablesPage::class)->name('dashboard.tables');
    Route::get('/dashboard/menu', MenuManager::class)->name('dashboard.menu');
    Route::get('/dashboard/users', UserManager::class)->name('dashboard.users');
    Route::get('/dashboard/cupons', CouponManager::class)->name('dashboard.cupons');
    Route::get('/dashboard/entregadores', DeliveryPeopleManager::class)->name('dashboard.delivery-people');
    Route::get('/dashboard/configuracoes', Settings::class)->name('dashboard.settings');

    Route::get('/subscription', SubscriptionCheckout::class)->name('subscription.checkout');

    Route::post('/subscription', [SubscriptionController::class, 'store'])->name('subscription.checkout.store');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
});

Route::prefix('/cardapio')->group(function () {
    Route::get('/{slug:slug}', function (Tenant $slug) {
        return view('menu-page', ['tenant' => $slug]);
    })->name('menu.show');

    Route::get('/{slug:slug}/acesso', [AuthController::class, 'waiterLoginForm'])->name('waiter.login.form');
    Route::post('/{slug:slug}/acesso', [AuthController::class, 'waiterLogin'])->name('waiter.login');

    Route::get('/{slug:slug}/cadastro', [AuthController::class, 'waiterRegisterForm'])->name('waiter.register.form');
    Route::post('/{slug:slug}/cadastro', [AuthController::class, 'waiterRegister'])->name('waiter.register');
});

Route::middleware(['auth', 'tenant.scope', 'check.staff'])->prefix('/painel')->group(function () {
    Route::get('/{slug:slug}', function (Tenant $slug) {
        $user = Auth::user();
        if ($user->tenant_id !== $slug->id) {
            abort(403);
        }
        return view('waiter-panel', ['tenant' => $slug]);
    })->name('waiter.panel');

    Route::get('/{slug:slug}/configuracoes', function (Tenant $slug) {
        $user = Auth::user();
        if ($user->tenant_id !== $slug->id) {
            abort(403);
        }
        return view('waiter-panel', ['tenant' => $slug, 'tab' => 'settings']);
    })->name('waiter.panel.settings');
});

Route::middleware(['auth', 'tenant.scope'])->prefix('/conta')->group(function () {
    Route::get('/{slug:slug}', function (Tenant $slug) {
        $user = Auth::user();
        if ($user->tenant_id !== $slug->id) {
            abort(403);
        }
        return view('client-panel', ['tenant' => $slug]);
    })->name('client.panel');
});
