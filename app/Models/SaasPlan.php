<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SaasPlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public const FEATURE_LABELS = [
        'max_tables' => 'Mesas máximas',
        'max_products' => 'Produtos máximos',
        'max_users' => 'Usuários máximos',
    ];

    public const DESCRIPTION_FEATURES = [
        'cardapio_ilimitado' => 'Cardápio digital ilimitado',
        'pedidos_ilimitados' => 'Pedidos ilimitados',
        'cupons_desconto' => 'Cupons de desconto',
        'delivery_entregadores' => 'Delivery com entregadores',
        'programa_fidelidade' => 'Programa de fidelidade (pontos)',
        'relatorios_avancados' => 'Relatórios avançados',
        'suporte_prioritario' => 'Suporte prioritário',
        'multi_usuarios' => 'Múltiplos usuários',
    ];

    protected $fillable = [
        'name',
        'slug',
        'price_cents',
        'interval',
        'features_json',
        'border_color',
        'background_color',
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

    public function subscriptions(): HasMany
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

    /**
     * Features visíveis (numéricas e descrições), com label em português.
     * Fonte única usada pelo painel superadmin e pela página de planos do tenant.
     *
     * @return array<int, array{key: string, label: string, value: bool|int|null}>
     */
    public function visibleFeatures(): array
    {
        $items = [];

        foreach (array_keys(self::FEATURE_LABELS + self::DESCRIPTION_FEATURES) as $key) {
            $value = ($this->features_json ?? [])[$key] ?? null;

            if ($value === null) {
                continue;
            }

            $items[] = [
                'key' => $key,
                'label' => self::FEATURE_LABELS[$key] ?? self::DESCRIPTION_FEATURES[$key],
                'value' => $value,
            ];
        }

        return $items;
    }
}
