<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SaasPaymentHistory extends Model
{
    use HasUuids;

    protected $table = 'saas_payment_history';

    protected $fillable = [
        'subscription_id',
        'tenant_id',
        'amount_cents',
        'status',
        'efi_charge_id',
        'method',
        'paid_at',
        'receipt_url',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(SaasSubscription::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
