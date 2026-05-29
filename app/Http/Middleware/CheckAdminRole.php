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
        if (Auth::check() && !Auth::user()->isAdmin()) {
            if (Auth::user()->isStaff()) {
                return redirect()->route('waiter.panel', Auth::user()->load('tenant')->tenant->slug)
                    ->with('error', ' 403 Acesso restrito a administradores.');
            }
            return redirect()->route('menu.show', Auth::user()->load('tenant')->tenant->slug)
                ->with('error', 'Acesso restrito a administradores.');
        }

        return $next($request);
    }
}
