<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->isAdmin()) {
            $tenantSlug = Auth::user()->load('tenant')->tenant?->slug;

            if (! $tenantSlug) {
                return redirect()->route('login')
                    ->with('error', 'Acesso restrito a administradores.');
            }

            if (Auth::user()->isStaff()) {
                return redirect()->route('waiter.panel', $tenantSlug)
                    ->with('error', 'Acesso restrito a administradores.');
            }

            return redirect()->route('menu.show', $tenantSlug)
                ->with('error', 'Acesso restrito a administradores.');
        }

        return $next($request);
    }
}
