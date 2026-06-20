<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SaasSubscriptionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SaasSubscription extends Model
{
    /** @use HasFactory<SaasSubscriptionFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'efi_subscription_id',
        'efi_charge_id',
        'status',
        'payment_method',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'next_billing_date',
        'suspended_at',
        'cancelled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'next_billing_date' => 'datetime',
            'suspended_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan()
    {
        return $this->belongsTo(SaasPlan::class);
    }

    public function paymentHistory()
    {
        return $this->hasMany(SaasPaymentHistory::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
