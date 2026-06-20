<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SaasPlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SaasPlan extends Model
{
    /** @use HasFactory<SaasPlanFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public const DISCOUNT_TIERS = [
        1 => 0,
        3 => 15,
        6 => 23,
        12 => 32,
    ];

    protected $fillable = [
        'name',
        'slug',
        'price_cents',
        'interval',
        'features_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'features_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    public function subscriptions()
    {
        return $this->hasMany(SaasSubscription::class);
    }

    public static function getDiscountPercent(int $months): int
    {
        return self::DISCOUNT_TIERS[$months] ?? 0;
    }

    public function getTotalForMonths(int $months): int
    {
        $discount = self::getDiscountPercent($months);
        $total = $this->price_cents * $months;
        return (int) round($total * (100 - $discount) / 100);
    }
}
