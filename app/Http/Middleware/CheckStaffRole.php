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
        if (Auth::check() && !Auth::user()->isStaff()) {
            return redirect()->route('menu.show', Auth::user()->load('tenant')->tenant->slug)
                ->with('error', 'Acesso restrito a equipe do restaurante.');
        }

        return $next($request);
    }
}
