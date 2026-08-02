<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SaasPaymentHistory;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Services\EfiBank\SaasEfiBankService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly SaasEfiBankService $efiBankService
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'string'],
            'months' => ['sometimes', 'integer', 'in:1,3,6,12'],
            'payment_method' => ['sometimes', 'string', 'in:pix,credit_card,billet'],
        ]);

        $tenant = Auth::user()->tenant;
        $planSlug = $validated['plan'];
        $months = (int) ($validated['months'] ?? 1);

        if (in_array($planSlug, ['free', 'gratuito'], true)) {
            return $this->activateFreePlan($tenant);
        }

        $plan = SaasPlan::where('slug', $planSlug)
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            return redirect()->route('subscription.checkout')
                ->with('error', 'Plano não encontrado.');
        }

        $existingSubscription = SaasSubscription::where('tenant_id', $tenant->id)->first();

        // Se já tem pagamento pendente válido, reexibe
        if ($existingSubscription && $existingSubscription->status === 'pending') {
            $details = $this->efiBankService->pixDetails($existingSubscription);
            if (! empty($details['expired'])) {
                // PIX expirado — vai gerar novo
                $this->logExpiredCharge($existingSubscription);
            } else {
                return redirect()->route('subscription.checkout')
                    ->with('payment_pending', $existingSubscription->id);
            }
        }

        // Cria cobrança PIX via EfiBank (sua conta)
        try {
            DB::transaction(function () use ($tenant, $plan, $existingSubscription, $months) {
                if ($existingSubscription) {
                    $existingSubscription->update([
                        'plan_id' => $plan->id,
                        'status' => 'pending',
                        'payment_method' => 'pix',
                        'efi_charge_id' => null,
                        'metadata' => null,
                    ]);
                    $this->efiBankService->chargeSubscription($existingSubscription, $tenant, $plan, $months);
                    session()->flash('payment_pending', $existingSubscription->id);
                } else {
                    $subscription = $this->efiBankService->createSubscription($tenant, $plan, ['months' => $months]);
                    if ($subscription->status === 'payment_error') {
                        throw new \RuntimeException('Falha ao gerar cobrança PIX');
                    }
                    session()->flash('payment_pending', $subscription->id);
                }
            });

            return redirect()->route('subscription.checkout');

        } catch (\Throwable $e) {
            Log::error('Subscription charge failed', [
                'tenant_id' => $tenant->id,
                'plan' => $planSlug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('subscription.checkout')
                ->with('error', 'Erro ao gerar cobrança: '.$e->getMessage());
        }
    }

    private function activateFreePlan(Tenant $tenant): RedirectResponse
    {
        $freePlan = SaasPlan::whereIn('slug', ['free', 'gratuito'])->first();

        DB::transaction(function () use ($tenant, $freePlan) {
            $subscription = SaasSubscription::where('tenant_id', $tenant->id)->first();

            if ($subscription) {
                $subscription->update([
                    'plan_id' => $freePlan?->id,
                    'status' => 'active',
                ]);
            } elseif ($freePlan) {
                SaasSubscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $freePlan->id,
                    'status' => 'active',
                    'current_period_start' => now(),
                    'current_period_end' => now()->addMonth(),
                ]);
            }

            $tenant->update([
                'plan' => Tenant::PLAN_FREE,
                'max_tables' => Tenant::PLAN_MAX_TABLES[Tenant::PLAN_FREE],
                'status' => 'active',
            ]);
        });

        return redirect('/dashboard')->with('success', 'Plano Gratuito ativado com sucesso!');
    }

    public function cancel(): RedirectResponse
    {
        $tenant = Auth::user()->tenant;

        if ($tenant->isPaid()) {
            return redirect()->route('subscription.checkout')
                ->with('error', 'Não é possível cancelar enquanto o Premium estiver ativo. Deixe o plano expirar.');
        }

        $freePlan = SaasPlan::where('slug', 'free')->first();

        DB::transaction(function () use ($tenant, $freePlan) {
            $subscription = SaasSubscription::where('tenant_id', $tenant->id)->first();

            if ($subscription) {
                $subscription->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
            }

            if ($freePlan) {
                $newSubscription = SaasSubscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $freePlan->id,
                    'status' => 'active',
                    'current_period_start' => now(),
                    'current_period_end' => now()->addMonth(),
                ]);

                $tenant->update([
                    'subscription_id' => $newSubscription->id,
                ]);
            }

            $tenant->update([
                'plan' => Tenant::PLAN_FREE,
                'max_tables' => Tenant::PLAN_MAX_TABLES[Tenant::PLAN_FREE],
                'subscription_ends_at' => null,
            ]);
        });

        return redirect('/dashboard')->with('info', 'Assinatura cancelada. Você voltou ao plano Gratuito.');
    }

    private function logExpiredCharge(SaasSubscription $subscription): void
    {
        $metadata = $subscription->metadata ?? [];
        $expiresAt = ! empty($metadata['expires_at'])
            ? Carbon::parse($metadata['expires_at'])
            : null;

        SaasPaymentHistory::updateOrCreate(
            ['efi_charge_id' => $subscription->efi_charge_id],
            [
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
                'amount_cents' => $subscription->plan?->getTotalForMonths($metadata['months'] ?? 1) ?? 0,
                'status' => 'expired',
                'method' => 'pix',
                'paid_at' => $expiresAt,
            ]
        );
    }

    public function checkout(): View
    {
        $plans = SaasPlan::where('is_active', true)
            ->orderBy('price_cents')
            ->get();
        $tenant = Auth::user()->tenant;
        $currentSubscription = SaasSubscription::where('tenant_id', $tenant->id)->first();
        $paymentHistory = SaasPaymentHistory::where('tenant_id', $tenant->id)
            ->with('subscription.plan')
            ->latest('paid_at')
            ->get();

        $pendingPayment = null;
        if (session('payment_pending')) {
            $pendingSubscription = SaasSubscription::find(session('payment_pending'));
            if ($pendingSubscription && $pendingSubscription->tenant_id === $tenant->id) {
                $pendingPayment = $this->efiBankService->pixDetails($pendingSubscription);
                if (! empty($pendingPayment['expired'])) {
                    $this->logExpiredCharge($pendingSubscription);
                }
                $pendingPayment['subscription_id'] = $pendingSubscription->id;
                $pendingPayment['plan'] = $pendingSubscription->plan;
                $pendingPayment['created_at'] = $pendingSubscription->created_at;
            }
        }

        return view('subscription.checkout', [
            'plans' => $plans,
            'tenant' => $tenant,
            'currentSubscription' => $currentSubscription,
            'pendingPayment' => $pendingPayment,
            'paymentHistory' => $paymentHistory,
        ]);
    }
}
