<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantResolverService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        private readonly TenantResolverService $tenantResolver
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;
        $host = $request->getHost();
        $mainDomain = config('tenancy.main_domain', 'saasmesa.com.br');

        if (str_contains($host, '.')) {
            $parts = explode('.', $host);
            $subdomain = $parts[0];

            if ($subdomain !== 'www' && count($parts) >= 2) {
                $tenant = $this->tenantResolver->resolveBySubdomain($subdomain);
            }
        }

        if (!$tenant && Auth::check() && Auth::user()->tenant_id) {
            $tenant = Tenant::find(Auth::user()->tenant_id);
        }

        if ($request->header('X-Tenant-Id')) {
            $tenantByHeader = Tenant::find($request->header('X-Tenant-Id'));
            if ($tenantByHeader) {
                $tenant = $tenantByHeader;
            }
        }

        if ($tenant) {
            $request->merge(['current_tenant' => $tenant]);
            $request->setUserResolver(function () use ($tenant) {
                $user = Auth::user();
                if ($user) {
                    $user->setRelation('tenant', $tenant);
                }
                return $user;
            });
        }

        return $next($request);
    }
}
