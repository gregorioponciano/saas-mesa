<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([TenantScope::class])]
class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'tenant_id',
        'amount',
        'payment_method',
        'status',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public const array PAYMENT_METHODS = [
        'pix' => 'PIX',
        'credit_card' => 'Cartão de Crédito',
        'debit_card' => 'Cartão de Débito',
        'cash' => 'Dinheiro',
    ];

    public const array STATUS_LABELS = [
        'pending' => 'Pendente',
        'paid' => 'Pago',
        'cancelled' => 'Cancelado',
        'refunded' => 'Reembolsado',
    ];

    public const array STATUS_CLASSES = [
        'pending' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        'paid' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'cancelled' => 'bg-neutral-500/10 text-neutral-400 border-neutral-500/20',
        'refunded' => 'bg-red-500/10 text-red-400 border-red-500/20',
    ];

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function statusClasses(): string
    {
        return self::STATUS_CLASSES[$this->status] ?? 'bg-neutral-500/10 text-neutral-400 border-neutral-500/20';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
