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
        'badge',
        'features_json',
        'feature_items',
        'border_color',
        'background_color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'features_json' => 'array',
            'feature_items' => 'array',
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
     * Lista de recursos exibida no card do plano. Totalmente editável no
     * painel superadmin (label + incluído). Cria um padrão caso vazio.
     *
     * @return array<int, array{label: string, included: bool}>
     */
    public function featureItems(): array
    {
        $items = $this->feature_items ?? [];

        if (is_array($items) && $items !== []) {
            return collect($items)
                ->map(fn ($item) => [
                    'label' => $item['label'] ?? 'Recurso',
                    'included' => (bool) ($item['included'] ?? true),
                ])
                ->all();
        }

        return $this->defaultFeatureItems();
    }

    private function defaultFeatureItems(): array
    {
        $features = $this->features_json ?? [];

        $items = [];

        // Limites numéricos sempre à frente, com o valor configurado (ex.: "Mesas máximas: 2")
        foreach (self::FEATURE_LABELS as $key => $label) {
            $value = $features[$key] ?? null;
            if ($value === null || $value === false || $value === '') {
                continue;
            }
            $items[] = [
                'label' => $label.': '.self::formatFeatureCount($value),
                'included' => true,
            ];
        }

        // Recursos descritivos (descrição por chave), sem duplicar "Cardápio ilimitado"
        // e "Múltiplos usuários", que agora são representados pelos limites acima.
        foreach (self::DESCRIPTION_FEATURES as $key => $label) {
            if (in_array($key, ['cardapio_ilimitado', 'multi_usuarios'], true)) {
                continue;
            }
            $value = $features[$key] ?? true;
            if ($value !== false) {
                $items[] = ['label' => $label, 'included' => true];
            }
        }

        if (count($items) < 3) {
            foreach (self::DESCRIPTION_FEATURES as $label) {
                $items[] = ['label' => $label, 'included' => true];
            }
        }

        return $items;
    }

    /**
     * Formata um limite numérico para exibição ("Ilimitado" para valores muito altos).
     */
    public static function formatFeatureCount(mixed $value): string
    {
        return (int) $value >= 100000 ? 'Ilimitado' : (string) (int) $value;
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
