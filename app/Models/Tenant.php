<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    public const PLAN_FREE = 'free';
    public const PLAN_PAID = 'paid';

    public const PLAN_LABELS = [
        self::PLAN_FREE => 'Gratuito',
        self::PLAN_PAID => 'Premium',
    ];

    public const PLAN_PRICES = [
        self::PLAN_FREE => 0,
        self::PLAN_PAID => 97.90,
    ];

    public const PLAN_MAX_TABLES = [
        self::PLAN_FREE => 2,
        self::PLAN_PAID => 50,
    ];

    protected $fillable = [
        'name',
        'email',
        'slug',
        'domain',
        'whatsapp',
        'logo',
        'logo_width',
        'logo_height',
        'opening_time',
        'closing_time',
        'plan',
        'max_tables',
        'status',
        'subscription_id',
        'trial_ends_at',
        'subscription_ends_at',
        'delivery_cost_per_order',
        'delivery_cost_type',
        'delivery_cost_per_km',
        'delivery_cost_enabled',
        'address',
        'number',
        'neighborhood',
        'city',
        'state',
        'zipcode',
        'latitude',
        'longitude',
        'delivery_radius',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'coupons_enabled',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'max_tables' => 'integer',
            'coupons_enabled' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'delivery_radius' => 'decimal:1',
            'delivery_cost_per_km' => 'decimal:2',
            'delivery_cost_enabled' => 'boolean',
        ];
    }

    public function deliveryCostForDistance(?float $distanceKm = null): float
    {
        if (!($this->delivery_cost_enabled ?? true)) {
            return 0.0;
        }

        $fixed = (float) ($this->delivery_cost_per_order ?? 0);
        $perKm = (float) ($this->delivery_cost_per_km ?? 0);
        $distance = $distanceKm !== null ? (float) $distanceKm : 0.0;

        return round($fixed + $perKm * $distance, 2);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function tables()
    {
        return $this->hasMany(Table::class);
    }

    public function deliveryPeople()
    {
        return $this->hasMany(DeliveryPerson::class);
    }

    public function ingredients()
    {
        return $this->hasMany(Ingredient::class);
    }

    public function backups()
    {
        return $this->hasMany(\App\Models\TenantBackup::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']);
    }

    public function isFree(): bool
    {
        return $this->plan === self::PLAN_FREE;
    }

    public function isPaid(): bool
    {
        return $this->plan === self::PLAN_PAID;
    }

    public function canAddTable(): bool
    {
        return $this->tables()->count() < $this->max_tables;
    }

    public function logoUrl(): ?string
    {
        if (!$this->logo) {
            return null;
        }
        return Storage::url($this->logo);
    }

    public function maxTablesAllowed(): int
    {
        return self::PLAN_MAX_TABLES[$this->plan] ?? self::PLAN_MAX_TABLES[self::PLAN_FREE];
    }

    public function hasHiddenTables(): bool
    {
        return $this->isFree() && $this->tables()->count() > $this->maxTablesAllowed();
    }

    public function hiddenTablesCount(): int
    {
        return max(0, $this->tables()->count() - $this->maxTablesAllowed());
    }

    public function manageableTables()
    {
        $query = $this->tables()->orderByRaw("CAST(number AS UNSIGNED), number");
        if ($this->isFree()) {
            $ids = (clone $query)->take($this->maxTablesAllowed())->pluck('id');
            return $query->whereIn('id', $ids);
        }
        return $query;
    }

    public function planLabel(): string
    {
        return self::PLAN_LABELS[$this->plan] ?? 'Gratuito';
    }

    public function activeSubscription()
    {
        return $this->hasOne(\App\Models\SaasSubscription::class)
            ->whereIn('status', ['active', 'trialing']);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function efiCredentials()
    {
        return $this->hasOne(TenantEfiCredentials::class);
    }

    public function loyaltyConfig()
    {
        return $this->hasOne(\App\Models\LoyaltyConfig::class, 'tenant_id');
    }

    public function isOpen(): bool
    {
        if (!$this->opening_time || !$this->closing_time) {
            return false;
        }

        \Carbon\Carbon::setLocale('pt_BR');
        $now = \Carbon\Carbon::now('America/Sao_Paulo');
        $opening = \Carbon\Carbon::createFromTimeString($this->opening_time, 'America/Sao_Paulo');
        $closing = \Carbon\Carbon::createFromTimeString($this->closing_time, 'America/Sao_Paulo');

        // Handle overnight shifts (e.g., opens at 22:00 and closes at 02:00)
        if ($closing->lessThan($opening)) {
            return $now->greaterThanOrEqualTo($opening) || $now->lessThan($closing);
        }

        return $now->between($opening, $closing);
    }
}
