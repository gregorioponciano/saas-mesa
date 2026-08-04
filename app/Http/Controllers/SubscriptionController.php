<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SaasPaymentHistory;
use App\Models\SaasPixCharge;
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

        $subscriptions = SaasSubscription::with('plan')
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        // Assinatura ativa do tenant (a que dirige o plano pago atual)
        $currentSubscription = ! empty($tenant->subscription_id)
            ? $subscriptions->firstWhere('id', $tenant->subscription_id)
            : $subscriptions->firstWhere('status', 'active');

        // Plano pago ativo no momento (impede downgrade e volta ao gratuito)
        $activePaidPlan = null;
        $hasPaidPlan = false;

        if ($currentSubscription && $currentSubscription->plan) {
            $isPaidPlan = $currentSubscription->plan->price_cents > 0;
            $hasPaidPlan = $isPaidPlan && in_array($currentSubscription->status, ['active', 'trial', 'pending'], true);

            if ($isPaidPlan && in_array($currentSubscription->status, ['active', 'trial'], true)) {
                $activePaidPlan = $currentSubscription->plan;
            }
        }

        // Assinatura mais recente para decidir reuso/renovação do PIX pendente
        $existingSubscription = $subscriptions->first();

        if (in_array($planSlug, ['free', 'gratuito'], true)) {
            if ($hasPaidPlan) {
                return redirect()->route('subscription.checkout')
                    ->with('error', 'Você está em um plano pago. Não é possível voltar ao plano Gratuito — faça upgrade ou renove seu plano.');
            }

            return $this->activateFreePlan($tenant);
        }

        $plan = SaasPlan::where('slug', $planSlug)
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            return redirect()->route('subscription.checkout')
                ->with('error', 'Plano não encontrado.');
        }

        // Não permite trocar para um plano pago mais barato — só upgrade ou renovação
        if ($activePaidPlan && $plan->id !== $activePaidPlan->id && $plan->price_cents < $activePaidPlan->price_cents) {
            return redirect()->route('subscription.checkout')
                ->with('error', 'Não é possível trocar para um plano inferior. Você pode renovar seu plano atual ou fazer um upgrade pagando via PIX.');
        }

        // Só reutiliza o PIX pendente se for o MESMO plano + período.
        // Se mudou o valor/plano, gera um PIX novo (vira uma nova entrada no histórico).
        $reusePending = false;
        if ($existingSubscription && $existingSubscription->status === 'pending') {
            $sameSelection = $existingSubscription->plan_id === $plan->id
                && (int) ($existingSubscription->metadata['months'] ?? 1) === $months;

            $details = $this->efiBankService->pixDetails($existingSubscription);

            if ($sameSelection && empty($details['expired'])) {
                $reusePending = true;
            } elseif (! empty($details['expired'])) {
                // PIX antigo expirou — marca como expirado e segue gerando novo
                $this->logExpiredCharge($existingSubscription);
            }
        }

        if ($reusePending) {
            return redirect()->route('subscription.checkout')
                ->with('payment_pending', $existingSubscription->id);
        }

        // Cria cobrança PIX via EfiBank (sua conta)
        try {
            DB::transaction(function () use ($tenant, $plan, $currentSubscription, $existingSubscription, $months) {
                $samePlan = $currentSubscription && $currentSubscription->plan_id === $plan->id;

                // Renovação do MESMO plano: reutiliza a assinatura ATIVA atual, mantendo o
                // período vigente para que a confirmação ESTENDA o current_period_end.
                if ($currentSubscription && $samePlan) {
                    $currentSubscription->update([
                        'plan_id' => $plan->id,
                        'status' => 'pending',
                        'payment_method' => 'pix',
                        'efi_charge_id' => null,
                        'metadata' => null,
                    ]);
                    $this->efiBankService->chargeSubscription($currentSubscription, $tenant, $plan, $months);
                    session()->flash('payment_pending', $currentSubscription->id);

                    return;
                }

                // Upgrade (plano diferente): cria NOVA assinatura do zero.
                // Ao confirmar, o novo período começa de hoje — os dias restantes
                // do plano anterior NÃO são herdados (o usuário é avisado no checkout).
                if ($currentSubscription && $currentSubscription->status === 'active') {
                    $subscription = $this->efiBankService->createSubscription($tenant, $plan, ['months' => $months]);
                    if ($subscription->status === 'payment_error') {
                        throw new \RuntimeException('Falha ao gerar cobrança PIX');
                    }
                    session()->flash('payment_pending', $subscription->id);

                    return;
                }

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
                'max_tables' => null,
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
                'max_tables' => null,
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

        SaasPixCharge::where('txid', $subscription->efi_charge_id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);
    }

    public function checkout(): View
    {
        $plans = SaasPlan::where('is_active', true)
            ->orderBy('price_cents')
            ->get();
        $tenant = Auth::user()->tenant;
        $currentSubscription = ! empty($tenant->subscription_id)
            ? SaasSubscription::find($tenant->subscription_id)
            : SaasSubscription::where('tenant_id', $tenant->id)->first();
        $paymentHistory = SaasPaymentHistory::where('tenant_id', $tenant->id)
            ->with('subscription.plan')
            ->latest('paid_at')
            ->get();

        $pixCharges = SaasPixCharge::with('plan')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->get()
            ->map(function (SaasPixCharge $charge) {
                return [
                    'id' => $charge->id,
                    'plan_name' => $charge->plan?->name,
                    'plan_slug' => $charge->plan?->slug,
                    'amount_cents' => $charge->amount_cents,
                    'months' => $charge->months,
                    'txid' => $charge->txid,
                    'qrcode' => $charge->qrcode,
                    'copy_paste' => $charge->copy_paste,
                    'expires_at' => $charge->expires_at,
                    'paid_at' => $charge->paid_at,
                    'created_at' => $charge->created_at,
                    'status' => $charge->resolveStatus(),
                ];
            })
            ->values();

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
            'pixCharges' => $pixCharges,
        ]);
    }
}
