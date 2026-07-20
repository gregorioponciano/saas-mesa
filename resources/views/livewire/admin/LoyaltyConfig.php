<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'points_enabled',
        'points_percentage',
        'points_to_money_rate',
        'min_points_order_value',
    ];

    public static function forTenant(Tenant $tenant): self
    {
        return self::firstOrCreate(['tenant_id' => $tenant->id]);
    }
}