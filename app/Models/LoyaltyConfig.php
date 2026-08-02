<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyConfig extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_loyalty_configs';

    protected $fillable = [
        'tenant_id',
        'points_enabled',
        'points_percentage',
        'points_to_money_rate',
        'min_points_order_value',
    ];

    protected function casts(): array
    {
        return [
            'points_enabled' => 'boolean',
            'points_to_money_rate' => 'decimal:4',
            'points_percentage' => 'integer',
            'min_points_order_value' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function forTenant(Tenant $tenant): self
    {
        return static::firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['points_enabled' => false, 'points_percentage' => 10, 'min_points_order_value' => 10.00]
        );
    }

    public function canEnable(): bool
    {
        return $this->tenant->isPaid();
    }
}
