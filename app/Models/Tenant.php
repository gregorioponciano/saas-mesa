<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
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
        if (! ($this->delivery_cost_enabled ?? true)) {
            return 0.0;
        }

        $fixed = (float) ($this->delivery_cost_per_order ?? 0);
        $perKm = (float) ($this->delivery_cost_per_km ?? 0);
        $distance = $distanceKm !== null ? (float) $distanceKm : 0.0;

        return round($fixed + $perKm * $distance, 2);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(Table::class);
    }

    public function deliveryPeople(): HasMany
    {
        return $this->hasMany(DeliveryPerson::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(TenantBackup::class);
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

    private ?SaasPlan $resolvedPlan = null;

    /**
     * Plano ativo do tenant. Apenas o array de features fica em cache
     * (valores primitivos — serialização segura em qualquer store; objetos
     * Eloquent em cache file podem virar __PHP_Incomplete_Class). Invalidado
     * quando o superadmin salva o plano.
     */
    public function currentPlan(): ?SaasPlan
    {
        $plan = $this->resolvePlan();

        if (! $plan) {
            return null;
        }

        $features = Cache::remember(SaasPlan::planCacheKey($plan->slug), 3600, fn (): array => $plan->features_json ?? []);

        $slug = $plan->slug === 'gratuito' ? 'free' : $plan->slug;

        return (new SaasPlan)->forceFill([
            'slug' => $slug,
            'features_json' => $features,
            'name' => $plan->name,
        ]);
    }

    /**
     * Plano concreto do tenant: sempre o plano da assinatura ativa (qualquer
     * plano criado no painel — free, premium ou custom), com fallback para
     * premium (pagante sem assinatura) e free/slug legado 'gratuito'.
     */
    private function resolvePlan(): ?SaasPlan
    {
        if ($this->resolvedPlan !== null) {
            return $this->resolvedPlan;
        }

        $subscriptionPlan = $this->activeSubscription?->plan;

        if ($subscriptionPlan && $subscriptionPlan->is_active) {
            return $this->resolvedPlan = $subscriptionPlan;
        }

        if ($this->plan === self::PLAN_PAID) {
            return $this->resolvedPlan = SaasPlan::query()
                ->where('slug', 'premium')
                ->where('is_active', true)
                ->first();
        }

        return $this->resolvedPlan = SaasPlan::query()->where('slug', 'free')->where('is_active', true)->first()
            ?? SaasPlan::query()->where('slug', 'gratuito')->where('is_active', true)->first();
    }

    public function planFeature(string $key, mixed $default = null): mixed
    {
        return $this->currentPlan()?->features_json[$key] ?? $default;
    }

    /**
     * Feature booleana/numerica do plano (ex.: programa_fidelidade,
     * backup_retention_days). Fonte unica de verdade: SaasPlan.features_json.
     */
    public function hasFeature(string $key): bool
    {
        return (bool) ($this->planFeature($key, false) ?? false);
    }

    public function canAddTable(): bool
    {
        return $this->tables()->count() < $this->maxTablesAllowed();
    }

    public function canCreateProduct(): bool
    {
        return $this->products()->count() < $this->maxProductsAllowed();
    }

    public function canCreateUser(): bool
    {
        return $this->users()->count() < $this->maxUsersAllowed();
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        return Storage::url($this->logo);
    }

    /**
     * Limite de mesas: override individual (contrato especial) ou, na
     * ausencia dele, o limite dinamico do plano ativo.
     */
    public function maxTablesAllowed(): int
    {
        $planDefault = self::PLAN_MAX_TABLES[$this->plan] ?? self::PLAN_MAX_TABLES[self::PLAN_FREE];

        return (int) ($this->max_tables ?? $this->planFeature('max_tables', $planDefault));
    }

    public function maxProductsAllowed(): int
    {
        return (int) $this->planFeature('max_products', $this->isPaid() ? 999 : 20);
    }

    public function maxUsersAllowed(): int
    {
        return (int) $this->planFeature('max_users', $this->isPaid() ? 20 : 2);
    }

    /**
     * Mensagem de limite atingido: para o gratuito mantém o texto de
     * upgrade; para pagantes avisa que o limite é do plano contratado.
     */
    public function planLimitMessage(string $item, int $limit): string
    {
        $base = 'Seu plano permite apenas '.$limit.' '.$item.'.';

        return $this->isFree() ? $base.' Faça upgrade para Premium.' : $base;
    }

    public function hasHiddenTables(): bool
    {
        return $this->tables()->count() > $this->maxTablesAllowed();
    }

    public function hiddenTablesCount(): int
    {
        return max(0, $this->tables()->count() - $this->maxTablesAllowed());
    }

    public function hiddenProductsCount(): int
    {
        return max(0, $this->products()->count() - $this->maxProductsAllowed());
    }

    public function hiddenUsersCount(): int
    {
        return max(0, $this->users()->count() - $this->maxUsersAllowed());
    }

    public function manageableProductsIds(): array
    {
        return $this->products()->orderBy('id')
            ->take(max(0, $this->maxProductsAllowed()))
            ->pluck('id')
            ->all();
    }

    public function manageableUsersIds(): array
    {
        return $this->users()->orderBy('id')
            ->take(max(0, $this->maxUsersAllowed()))
            ->pluck('id')
            ->all();
    }

    public function manageableTables()
    {
        $query = $this->tables()->orderByRaw('CAST(number AS UNSIGNED), number');
        if ($this->hasHiddenTables()) {
            $ids = (clone $query)->take($this->maxTablesAllowed())->pluck('id');

            return $query->whereIn('id', $ids);
        }

        return $query;
    }

    public function planLabel(): string
    {
        return $this->currentPlan()?->name ?? (self::PLAN_LABELS[$this->plan] ?? 'Gratuito');
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(SaasSubscription::class)
            ->whereIn('status', ['active', 'trial']);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function efiCredentials(): HasOne
    {
        return $this->hasOne(TenantEfiCredentials::class);
    }

    public function loyaltyConfig(): HasOne
    {
        return $this->hasOne(LoyaltyConfig::class, 'tenant_id');
    }

    public function isOpen(): bool
    {
        if (! $this->opening_time || ! $this->closing_time) {
            return false;
        }

        Carbon::setLocale('pt_BR');
        $now = Carbon::now('America/Sao_Paulo');
        $opening = Carbon::createFromTimeString($this->opening_time, 'America/Sao_Paulo');
        $closing = Carbon::createFromTimeString($this->closing_time, 'America/Sao_Paulo');

        // Handle overnight shifts (e.g., opens at 22:00 and closes at 02:00)
        if ($closing->lessThan($opening)) {
            return $now->greaterThanOrEqualTo($opening) || $now->lessThan($closing);
        }

        return $now->between($opening, $closing);
    }
}
