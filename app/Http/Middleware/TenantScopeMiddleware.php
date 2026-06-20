<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantScopeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $request->merge(['current_tenant_id' => Auth::user()->tenant_id]);

            if (Auth::user()->tenant) {
                $request->merge(['current_tenant' => Auth::user()->tenant]);
            }
        }

        $tenantFromRequest = $request->get('current_tenant');

        if ($tenantFromRequest && $tenantFromRequest->id) {
            $request->merge(['current_tenant_id' => $tenantFromRequest->id]);
        }

        return $next($request);
    }
}
