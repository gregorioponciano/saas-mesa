<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_pix_charges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->index()->constrained()->cascadeOnDelete();
            $table->uuid('subscription_id')->nullable();
            $table->uuid('plan_id')->nullable();
            $table->string('txid', 64)->nullable()->index();
            $table->string('loc_id', 64)->nullable();
            $table->unsignedBigInteger('amount_cents')->default(0);
            $table->unsignedInteger('months')->default(1);
            $table->string('status', 20)->default('pending')->index();
            $table->longText('qrcode')->nullable();
            $table->text('copy_paste')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('subscription_id')->references('id')->on('saas_subscriptions')->nullOnDelete();
            $table->foreign('plan_id')->references('id')->on('saas_plans')->nullOnDelete();
        });

        $this->backfillFromSubscriptions();
    }

    private function backfillFromSubscriptions(): void
    {
        $subscriptions = DB::table('saas_subscriptions')
            ->whereNotNull('efi_charge_id')
            ->get(['id', 'tenant_id', 'plan_id', 'efi_charge_id', 'status', 'metadata', 'created_at']);

        foreach ($subscriptions as $subscription) {
            $metadata = json_decode((string) $subscription->metadata, true) ?? [];
            $expiresAt = $metadata['expires_at'] ?? null;

            if (empty($metadata['pix_qrcode']) && empty($metadata['pix_copy_paste'])) {
                continue;
            }

            $plan = DB::table('saas_plans')->where('id', $subscription->plan_id)->first();
            $months = $metadata['months'] ?? 1;
            $amountCents = $plan && isset($plan->price_cents)
                ? $this->totalForMonths((int) $plan->price_cents, (int) $months)
                : 0;

            $expired = $expiresAt && now()->isAfter($expiresAt);

            DB::table('saas_pix_charges')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'txid' => $subscription->efi_charge_id,
                'loc_id' => $metadata['loc_id'] ?? null,
                'amount_cents' => $amountCents,
                'months' => $months,
                'status' => $subscription->status === 'active' ? 'paid' : ($expired ? 'expired' : 'pending'),
                'qrcode' => $metadata['pix_qrcode'] ?? null,
                'copy_paste' => $metadata['pix_copy_paste'] ?? null,
                'expires_at' => $expiresAt,
                'paid_at' => null,
                'created_at' => $subscription->created_at ?: now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function totalForMonths(int $priceCents, int $months): int
    {
        $intervals = [1 => 0, 3 => 15, 6 => 23, 12 => 32];
        $discountPct = $intervals[$months] ?? 0;

        return (int) round($priceCents * $months * (100 - $discountPct) / 100);
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_pix_charges');
    }
};