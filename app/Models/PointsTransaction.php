<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsTransaction extends Model
{
    use BelongsToTenant;

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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
