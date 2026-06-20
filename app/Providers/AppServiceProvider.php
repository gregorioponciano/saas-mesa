<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\SaasSubscription;
use App\Observers\SaasSubscriptionObserver;
use App\Services\EfiBank\SaasEfiBankService;
use App\Services\EfiBank\TenantEfiBankService;
use App\Services\EfiBank\WebhookValidatorService;
use App\Services\EncryptedCredentialService;
use App\Services\SubscriptionService;
use App\Services\TenantResolverService;
use Illuminate\Database\Eloquent\Model;
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

        $this->app->singleton(SubscriptionService::class, function ($app) {
            return new SubscriptionService(
                $app->make(SaasEfiBankService::class),
                $app->make(TenantResolverService::class)
            );
        });
    }

    public function boot(): void
    {
        Model::shouldBeStrict(!$this->app->isProduction());

        SaasSubscription::observe(SaasSubscriptionObserver::class);
    }
}
