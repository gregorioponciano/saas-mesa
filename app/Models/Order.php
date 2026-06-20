<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([TenantScope::class])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'table_id',
        'customer_name',
        'customer_phone',
        'total',
        'payment_method',
        'status',
        'type',
        'address_json',
        'notes',
        'bill_closed_at',
        'coupon_id',
        'discount',
        'discount_type',
        'payment_change',
        'delivery_person_id',
        'delivery_cost',
        'payment_status',
        'efi_charge_id',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'payment_change' => 'decimal:2',
            'address_json' => 'array',
            'bill_closed_at' => 'datetime',
        ];
    }

    public const array STATUS_LABELS = [
        'novo' => 'Novo',
        'em_preparo' => 'Em Preparo',
        'pronto' => 'Pronto',
        'saiu_entrega' => 'Saiu para Entrega',
        'entregue' => 'Entregue',
        'cancelado' => 'Cancelado',
        'fechado' => 'Fechado',
    ];

    public const array STATUS_CLASSES = [
        'novo' => 'bg-red-500/10 text-red-400 border-red-500/20',
        'em_preparo' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        'pronto' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
        'saiu_entrega' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        'entregue' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'cancelado' => 'bg-neutral-500/10 text-neutral-400 border-neutral-500/20',
        'fechado' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
    ];

    public const array STATUS_DOT_COLORS = [
        'novo' => 'bg-red-400',
        'em_preparo' => 'bg-amber-400',
        'pronto' => 'bg-sky-400',
        'saiu_entrega' => 'bg-blue-400',
        'entregue' => 'bg-emerald-400',
        'cancelado' => 'bg-neutral-400',
        'fechado' => 'bg-purple-400',
    ];

    public const array STATUS_ANIMATED = [
        'novo' => true,
        'em_preparo' => true,
        'pronto' => true,
        'saiu_entrega' => true,
        'entregue' => false,
        'cancelado' => false,
        'fechado' => false,
    ];

    public const array TYPE_LABELS = [
        'mesa' => 'Mesa',
        'entrega' => 'Entrega',
        'retirada' => 'Retirada',
    ];

    public const array TYPE_CLASSES = [
        'mesa' => 'bg-blue-500/20 text-blue-600',
        'entrega' => 'bg-green-500/20 text-green-600',
        'retirada' => 'bg-purple-500/20 text-purple-600',
    ];

    public const array STATUS_FINISHED = ['entregue', 'cancelado', 'fechado'];
    public const array STATUS_ACTIVE = ['novo', 'em_preparo', 'pronto', 'saiu_entrega'];

    public const int CHANGE_REQUEST_MINUTES = 5;

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function statusClasses(): string
    {
        return self::STATUS_CLASSES[$this->status] ?? 'bg-neutral-500/10 text-neutral-400 border-neutral-500/20';
    }

    public function statusDotColor(): string
    {
        return self::STATUS_DOT_COLORS[$this->status] ?? 'bg-neutral-400';
    }

    public function statusAnimated(): bool
    {
        return self::STATUS_ANIMATED[$this->status] ?? false;
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function typeClasses(): string
    {
        return self::TYPE_CLASSES[$this->type] ?? 'bg-neutral-500/20 text-neutral-600';
    }

    public function isMesa(): bool
    {
        return $this->type === 'mesa';
    }

    public function isEntrega(): bool
    {
        return $this->type === 'entrega';
    }

    public function isRetirada(): bool
    {
        return $this->type === 'retirada';
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['entregue', 'cancelado', 'fechado']);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['novo', 'em_preparo', 'pronto', 'saiu_entrega']);
    }

    public function isBillClosed(): bool
    {
        return $this->status === 'fechado' || $this->bill_closed_at !== null;
    }

    public function canRequestChange(): bool
    {
        if ($this->isFinished() || $this->isBillClosed()) {
            return false;
        }
        return $this->created_at->diffInMinutes(now()) < self::CHANGE_REQUEST_MINUTES;
    }

    public function nextStatus(): ?string
    {
        $flow = $this->statusFlow();
        $keys = array_keys($flow);
        $currentIndex = array_search($this->status, $keys);

        if ($currentIndex !== false && isset($keys[$currentIndex + 1])) {
            return $keys[$currentIndex + 1];
        }

        return null;
    }

    public function previousStatus(): ?string
    {
        $flow = $this->statusFlow();
        $keys = array_keys($flow);
        $currentIndex = array_search($this->status, $keys);

        if ($currentIndex !== false && isset($keys[$currentIndex - 1])) {
            return $keys[$currentIndex - 1];
        }

        return null;
    }

    public function statusFlow(): array
    {
        return match ($this->type) {
            'entrega' => [
                'novo' => 'em_preparo',
                'em_preparo' => 'saiu_entrega',
                'saiu_entrega' => 'entregue',
                'entregue' => 'fechado',
            ],
            default => [
                'novo' => 'em_preparo',
                'em_preparo' => 'pronto',
                'pronto' => 'entregue',
                'entregue' => 'fechado',
            ],
        };
    }

    public function statusFlowLabels(): array
    {
        return match ($this->type) {
            'entrega' => [
                'novo' => 'Iniciar Preparo',
                'em_preparo' => 'Saiu para Entrega',
                'saiu_entrega' => 'Confirmar Entrega',
                'entregue' => 'Fechar Pedido',
            ],
            default => [
                'novo' => 'Iniciar Preparo',
                'em_preparo' => 'Marcar como Pronto',
                'pronto' => 'Confirmar Entrega',
                'entregue' => 'Fechar Pedido',
            ],
        };
    }

    public function hasPayment(): bool
    {
        return $this->payments()->where('status', 'paid')->exists();
    }

    public function pendingPaymentAmount(): float
    {
        $paid = (float) $this->payments()->where('status', 'paid')->sum('amount');
        return max(0, (float) $this->total - $paid);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function deliveryPerson()
    {
        return $this->belongsTo(DeliveryPerson::class);
    }
}
