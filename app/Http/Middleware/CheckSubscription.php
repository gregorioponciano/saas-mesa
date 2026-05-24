<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->tenant()->exists()) {
            $tenant = Auth::user()->load('tenant')->tenant;

            $isSubscriptionRoute = $request->routeIs('subscription.checkout');

            if (!$isSubscriptionRoute) {
                if ($tenant->status === 'suspended') {
                    return redirect()->route('subscription.checkout')
                        ->with('error', 'Sua assinatura esta pendente. Regularize o pagamento para continuar.');
                }

                if ($tenant->status === 'cancelled') {
                    return redirect()->route('subscription.checkout')
                        ->with('error', 'Sua assinatura foi cancelada.');
                }

                if ($tenant->status === 'trial' && $tenant->trial_ends_at && $tenant->trial_ends_at->isPast()) {
                    $tenant->update(['status' => 'suspended']);
                    return redirect()->route('subscription.checkout')
                        ->with('error', 'Seu periodo de teste expirou. Escolha um plano para continuar.');
                }
            }
        }

        return $next($request);
    }
}
