<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'tenant_id',
        'product_id',
        'order_id',
        'user_id',
        'quantity',
        'stock_before',
        'stock_after',
        'type',
        'description',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    public const array TYPES = [
        'sale' => 'Venda',
        'cancellation' => 'Cancelamento',
        'manual_adjustment' => 'Ajuste Manual',
        'entry' => 'Entrada',
        'exit' => 'Saída',
        'return' => 'Devolução',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
