<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckStaffRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->isStaff()) {
            $tenantSlug = Auth::user()->load('tenant')->tenant?->slug;

            if ($tenantSlug) {
                return redirect()->route('menu.show', $tenantSlug)
                    ->with('error', 'Acesso restrito a equipe do restaurante.');
            }

            return redirect()->route('login')
                ->with('error', 'Acesso restrito a equipe do restaurante.');
        }

        return $next($request);
    }
}
