<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'refunded_at',
        'refunded_by',
        'refund_note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
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

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Estorna/ressarce um pagamento recebido, preservando o historico
     * (o registro nao e apagado, apenas marcado como reembolsado).
     */
    public function markRefunded(int $byUserId, ?string $reason = null): bool
    {
        if ($this->status === 'refunded') {
            return false;
        }

        $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'refunded_by' => $byUserId,
            'refund_note' => $reason ? substr($reason, 0, 255) : null,
        ]);

        return true;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
