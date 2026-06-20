<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderPayment extends Model
{
    use HasUuids, BelongsToTenant;

    protected $fillable = [
        'order_id',
        'tenant_id',
        'amount_cents',
        'method',
        'efi_charge_id',
        'efi_pix_txid',
        'status',
        'qrcode',
        'qrcode_image',
        'barcode',
        'payment_url',
        'expires_at',
        'paid_at',
        'webhook_received_at',
        'idempotency_key',
        'efi_response_raw',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'webhook_received_at' => 'datetime',
            'efi_response_raw' => 'array',
        ];
    }

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }
}
