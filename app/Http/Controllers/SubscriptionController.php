<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'plan' => ['required', 'in:free,paid'],
        ]);

        $tenant = Auth::user()->tenant;

        if ($request->plan === Tenant::PLAN_PAID) {
            $tenant->update([
                'plan' => Tenant::PLAN_PAID,
                'max_tables' => Tenant::PLAN_MAX_TABLES[Tenant::PLAN_PAID],
                'status' => 'active',
                'subscription_id' => 'sub_' . strtolower(str()->random(16)),
                'subscription_ends_at' => now()->addMonth(),
                'trial_ends_at' => null,
            ]);

            return redirect('/dashboard')->with('success', 'Assinatura Premium ativada com sucesso!');
        }

        return redirect('/dashboard')->with('info', 'Voce ja esta no plano Gratuito.');
    }

    public function cancel(): RedirectResponse
    {
        $tenant = Auth::user()->tenant;

        $tenant->update([
            'plan' => Tenant::PLAN_FREE,
            'max_tables' => Tenant::PLAN_MAX_TABLES[Tenant::PLAN_FREE],
            'subscription_id' => null,
            'subscription_ends_at' => null,
        ]);

        return redirect('/dashboard')->with('info', 'Assinatura cancelada. Voce voltou ao plano Gratuito.');
    }
}
