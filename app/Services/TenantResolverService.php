<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class TenantResolverService
{
    private const CACHE_TTL_SECONDS = 60;

    public function resolveBySubdomain(string $subdomain): ?Tenant
    {
        return Cache::remember(
            "tenant_subdomain:{$subdomain}",
            self::CACHE_TTL_SECONDS,
            function () use ($subdomain) {
                return Tenant::where('slug', $subdomain)->first();
            }
        );
    }

    public function resolveByDomain(string $domain): ?Tenant
    {
        return Cache::remember(
            "tenant_domain:{$domain}",
            self::CACHE_TTL_SECONDS,
            function () use ($domain) {
                return Tenant::where('domain', $domain)->first();
            }
        );
    }

    public function clearCache(Tenant $tenant): void
    {
        Cache::forget("tenant_subdomain:{$tenant->slug}");
        if ($tenant->domain) {
            Cache::forget("tenant_domain:{$tenant->domain}");
        }
    }
}
