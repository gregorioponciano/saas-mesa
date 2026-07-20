<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointsTransaction extends Model
{
    protected $table = 'points_transactions';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'order_id',
        'points',
        'type',
        'description',
        'idempotency_key',
    ];

    public const TYPE_EARNED = 'earned';
    public const TYPE_SPENT = 'spent';
    public const TYPE_REVERSED = 'reversed';
    public const TYPE_EXPIRED = 'expired';
    public const TYPE_REFUNDED = 'refunded';

    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
