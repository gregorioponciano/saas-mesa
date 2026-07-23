<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Observers\SaasSubscriptionObserver;
use App\Observers\TenantObserver;
use App\Services\DeliveryService;
use App\Services\EfiBank\SaasEfiBankService;
use App\Services\EfiBank\TenantEfiBankService;
use App\Services\EfiBank\WebhookValidatorService;
use App\Services\EncryptedCredentialService;
use App\Services\PointsService;
use App\Services\SubscriptionService;
use App\Services\TenantResolverService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EncryptedCredentialService::class);
        $this->app->singleton(TenantResolverService::class);
        $this->app->singleton(WebhookValidatorService::class);
        $this->app->singleton(SaasEfiBankService::class);
        $this->app->singleton(TenantEfiBankService::class);
        $this->app->singleton(DeliveryService::class);

        $this->app->singleton(SubscriptionService::class, function ($app) {
            return new SubscriptionService(
                $app->make(SaasEfiBankService::class),
                $app->make(TenantResolverService::class)
            );
        });

        $this->app->singleton(PointsService::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(!$this->app->isProduction());

        SaasSubscription::observe(SaasSubscriptionObserver::class);
        Tenant::observe(TenantObserver::class);

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('delivery', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('webhook', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
