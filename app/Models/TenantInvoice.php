<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TenantInvoice extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'period_start',
        'period_end',
        'amount_cents',
        'status',
        'efi_charge_id',
        'paid_at',
        'items_json',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'amount_cents' => 'integer',
            'paid_at' => 'datetime',
            'items_json' => 'array',
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
}
