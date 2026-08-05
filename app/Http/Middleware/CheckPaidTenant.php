<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPaidTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('slug');

        if (! $tenant instanceof Tenant) {
            $tenant = is_string($tenant) ? Tenant::query()->where('slug', $tenant)->first() : null;
        }

        if ($tenant && $tenant->isFree()) {
            abort(403, 'Acesso restrito. Plano Premium requerido.');
        }

        return $next($request);
    }
}
