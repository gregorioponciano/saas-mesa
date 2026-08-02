<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'selected_options_json',
        'change_requested',
        'change_requested_at',
        'change_note',
        'cancelled_at',
        'cancelled_by',
        'is_points_item',
        'points_cost',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'selected_options_json' => 'array',
            'change_requested' => 'boolean',
            'change_requested_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_points_item' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function canRequestChange(): bool
    {
        if ($this->change_requested) {
            return false;
        }

        return $this->order?->canRequestChange() ?? false;
    }
}
