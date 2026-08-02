<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->tenant) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if ($tenant->status === 'suspended') {
            return redirect()->route('login')
                ->with('error', 'Sua conta está suspensa. Entre em contato com o suporte.');
        }

        if ($tenant->status === 'cancelled') {
            return redirect()->route('login')
                ->with('error', 'Sua conta foi cancelada.');
        }

        return $next($request);
    }
}
