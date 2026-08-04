<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DESCRIPTION = [
        'pedidos_ilimitados' => 'Pedidos ilimitados',
        'cupons_desconto' => 'Cupons de desconto',
        'delivery_entregadores' => 'Delivery com entregadores',
        'programa_fidelidade' => 'Programa de fidelidade (pontos)',
        'relatorios_avancados' => 'Relatórios avançados',
        'suporte_prioritario' => 'Suporte prioritário',
    ];

    private const LIMITS = [
        'max_tables' => 'Mesas máximas',
        'max_products' => 'Produtos máximos',
        'max_users' => 'Usuários máximos',
    ];

    public function up(): void
    {
        Schema::table('saas_plans', function (Blueprint $table) {
            $table->string('badge')->nullable()->after('interval');
            $table->json('feature_items')->nullable()->after('features_json');
        });

        $plans = DB::table('saas_plans')->get();

        foreach ($plans as $plan) {
            $features = json_decode((string) $plan->features_json, true) ?? [];

            $items = [];
            foreach (self::LIMITS as $key => $label) {
                $value = $features[$key] ?? null;
                if ($value === null || $value === false || $value === '') {
                    continue;
                }
                $items[] = [
                    'label' => $label.': '.((int) $value >= 100000 ? 'Ilimitado' : (string) (int) $value),
                    'included' => true,
                ];
            }

            foreach (self::DESCRIPTION as $key => $label) {
                $value = $features[$key] ?? true;
                if ($value !== false) {
                    $items[] = ['label' => $label, 'included' => true];
                }
            }

            if (count($items) < 3) {
                foreach (self::DESCRIPTION as $label) {
                    $items[] = ['label' => $label, 'included' => true];
                }
            }

            DB::table('saas_plans')
                ->where('id', $plan->id)
                ->update([
                    'badge' => $plan->slug === 'premium' ? 'Popular' : null,
                    'feature_items' => json_encode($items, JSON_UNESCAPED_UNICODE),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('saas_plans', function (Blueprint $table) {
            $table->dropColumn(['badge', 'feature_items']);
        });
    }
};
