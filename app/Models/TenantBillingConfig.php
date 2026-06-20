<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TenantBillingConfig extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'billing_type',
        'monthly_fee_cents',
        'per_transaction_fee_cents',
        'billing_day',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'monthly_fee_cents' => 'integer',
            'per_transaction_fee_cents' => 'integer',
            'billing_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
