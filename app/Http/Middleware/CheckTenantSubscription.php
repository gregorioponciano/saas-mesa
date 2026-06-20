<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SaasSubscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->get('current_tenant');

        if (!$tenant && Auth::check() && Auth::user()->tenant_id) {
            $tenant = Auth::user()->tenant;
        }

        if ($tenant) {
            $subscription = SaasSubscription::where('tenant_id', $tenant->id)->first();

            if ($subscription && $subscription->status === 'pending') {
                if ($request->expectsJson() || $request->is('api/*') || $request->wantsJson()) {
                    return response()->json([
                        'error' => 'payment_pending',
                        'message' => 'Pagamento pendente. Conclua o pagamento para acessar o sistema.',
                        'subscription_status' => 'pending',
                    ], 402);
                }

                return redirect()->route('subscription.checkout')
                    ->with('error', 'Pagamento pendente. Conclua o pagamento para acessar o sistema.');
            }

            if ($tenant->status === 'suspended') {
                $subscription = SaasSubscription::where('tenant_id', $tenant->id)
                    ->where('status', 'suspended')
                    ->first();

                $message = 'Sua assinatura está pendente. Regularize o pagamento para continuar.';
                $daysOverdue = null;

                if ($subscription && $subscription->suspended_at) {
                    $daysOverdue = (int) $subscription->suspended_at->diffInDays(now());
                    $message = "Sua assinatura está pendente há {$daysOverdue} dias. Regularize o pagamento para continuar.";
                }

                if ($request->expectsJson() || $request->is('api/*') || $request->wantsJson()) {
                    return response()->json([
                        'error' => 'payment_required',
                        'message' => $message,
                        'days_overdue' => $daysOverdue,
                        'subscription_status' => 'suspended',
                    ], 402);
                }

                return redirect()->route('subscription.checkout')
                    ->with('error', $message);
            }

            if ($tenant->status === 'cancelled') {
                if ($request->expectsJson() || $request->is('api/*') || $request->wantsJson()) {
                    return response()->json([
                        'error' => 'subscription_cancelled',
                        'message' => 'Sua assinatura foi cancelada.',
                    ], 402);
                }

                return redirect()->route('subscription.checkout')
                    ->with('error', 'Sua assinatura foi cancelada.');
            }
        }

        return $next($request);
    }
}
