<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request->route('slug'));

        if (! $tenant) {
            abort(404);
        }

        if (! Auth::check() || Auth::user()->tenant_id !== $tenant->id) {
            abort(403);
        }

        return $next($request);
    }

    private function resolveTenant(mixed $slug): ?Tenant
    {
        if ($slug instanceof Tenant) {
            return $slug;
        }

        if (is_string($slug)) {
            return Tenant::query()->where('slug', $slug)->first();
        }

        return null;
    }
}
