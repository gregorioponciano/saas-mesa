<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SaasPixCharge extends Model
{
    use HasUuids;

    protected $table = 'saas_pix_charges';

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'plan_id',
        'txid',
        'loc_id',
        'amount_cents',
        'months',
        'status',
        'qrcode',
        'copy_paste',
        'expires_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'months' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(SaasSubscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SaasPlan::class);
    }

    public function isExpired(): bool
    {
        if ($this->status === 'paid') {
            return false;
        }

        return $this->expires_at && now()->isAfter($this->expires_at);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending'], true);
    }

    public function resolveStatus(): string
    {
        if ($this->status === 'paid') {
            return 'paid';
        }

        return $this->isExpired() ? 'expired' : 'pending';
    }
}
