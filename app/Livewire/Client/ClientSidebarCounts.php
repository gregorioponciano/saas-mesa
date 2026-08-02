<?php

namespace App\Livewire\Client;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Propriedades computadas ([Computed]) reconhecidas pelo PHPStan.
 *
 * @property int $myActiveOrdersCount
 */
class ClientSidebarCounts extends Component
{
    #[Computed]
    public function myActiveOrdersCount(): int
    {
        return Order::where('user_id', Auth::id())
            ->whereIn('status', ['novo', 'em_preparo', 'saiu_entrega'])
            ->count();
    }

    public function render()
    {
        return view('livewire.client.client-sidebar-counts', [
            'myActiveOrdersCount' => $this->myActiveOrdersCount,
        ]);
    }
}
