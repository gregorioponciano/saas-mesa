<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'selected_options_json' => 'array',
            'change_requested' => 'boolean',
            'change_requested_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function canRequestChange(): bool
    {
        if ($this->change_requested) {
            return false;
        }
        return $this->order?->canRequestChange() ?? false;
    }
}
