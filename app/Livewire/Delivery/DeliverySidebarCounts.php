<?php

namespace App\Livewire\Delivery;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Propriedades computadas ([Computed]) reconhecidas pelo PHPStan.
 *
 * @property int $availableOrdersCount
 * @property int $activeOrdersCount
 */
class DeliverySidebarCounts extends Component
{
    public function mount(): void
    {
        $delivery = Auth::guard('delivery-web')->user();
    }

    #[Computed]
    public function availableOrdersCount(): int
    {
        $delivery = Auth::guard('delivery-web')->user();
        if (! $delivery) {
            return 0;
        }

        return Order::where('tenant_id', $delivery->tenant_id)
            ->where('type', 'entrega')
            ->whereIn('status', ['novo', 'em_preparo'])
            ->whereNull('delivery_person_id')
            ->count();
    }

    #[Computed]
    public function activeOrdersCount(): int
    {
        $delivery = Auth::guard('delivery-web')->user();
        if (! $delivery) {
            return 0;
        }

        return Order::where('tenant_id', $delivery->tenant_id)
            ->where('delivery_person_id', $delivery->id)
            ->whereIn('status', ['coletado', 'saiu_entrega'])
            ->count();
    }

    public function render()
    {
        $delivery = Auth::guard('delivery-web')->user();
        $tenant = $delivery?->tenant;
        $currentTab = request('tab', 'disponiveis');

        $tabs = [
            'disponiveis' => [
                'label' => 'Disponíveis',
                'icon' => 'search',
                'route' => route('delivery.dashboard', ['tab' => 'disponiveis']),
                'badge' => $this->availableOrdersCount,
            ],
            'ativos' => [
                'label' => 'Ativos',
                'icon' => 'clipboard',
                'route' => route('delivery.dashboard', ['tab' => 'ativos']),
                'badge' => $this->activeOrdersCount,
            ],
            'historico' => [
                'label' => 'Histórico',
                'icon' => 'clock',
                'route' => route('delivery.dashboard', ['tab' => 'historico']),
            ],
            'perfil' => [
                'label' => 'Perfil',
                'icon' => 'user',
                'route' => route('delivery.dashboard', ['tab' => 'perfil']),
            ],
            'configuracoes' => [
                'label' => 'Configurações',
                'icon' => 'settings',
                'route' => route('delivery.dashboard', ['tab' => 'configuracoes']),
            ],
        ];

        return view('livewire.delivery.delivery-sidebar-counts', [
            'delivery' => $delivery,
            'tenant' => $tenant,
            'tabs' => $tabs,
            'currentTab' => $currentTab,
        ]);
    }
}
