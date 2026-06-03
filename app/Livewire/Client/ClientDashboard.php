<?php

namespace App\Livewire\Client;

use App\Models\Order;
use App\Models\Table;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ClientDashboard extends Component
{
    public $tenant;

    public string $tab = 'orders';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public string $historyPeriod = 'all';

    public bool $showAddressModal = false;
    public bool $showDeleteAccountConfirm = false;
    public string $deleteConfirmation = '';

    public ?int $editingAddressId = null;

    public string $addressLabel = 'Casa';

    public string $addressAddress = '';

    public string $addressNumber = '';

    public string $addressComplement = '';

    public string $addressNeighborhood = '';

    public string $addressCity = '';

    public string $addressState = '';

    public string $addressZipcode = '';

    public string $addressReference = '';

    public bool $addressIsDefault = false;

    public ?int $confirmDeleteAddressId = null;

    protected $listeners = ['orderUpdated' => '$refresh', 'notifyNewOrder' => '$refresh'];

    public function mount(): void
    {
        $this->tab = request()->query('tab', 'orders');
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function saveProfile(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'nullable|string|min:6',
            'passwordConfirmation' => 'nullable|same:password',
        ]);

        $user = Auth::user();
        $data = ['name' => $this->name, 'email' => $this->email];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        $user->update($data);
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->dispatch('notify', message: 'Perfil atualizado com sucesso!');
    }

    public function openAddressModal(?int $addressId = null): void
    {
        $this->resetAddressForm();
        $this->editingAddressId = $addressId;

        if ($addressId) {
            $address = UserAddress::where('user_id', Auth::id())->findOrFail($addressId);
            $this->addressLabel = $address->label;
            $this->addressAddress = $address->address;
            $this->addressNumber = $address->number ?? '';
            $this->addressComplement = $address->complement ?? '';
            $this->addressNeighborhood = $address->neighborhood ?? '';
            $this->addressCity = $address->city;
            $this->addressState = $address->state;
            $this->addressZipcode = $address->zipcode ?? '';
            $this->addressReference = $address->reference ?? '';
            $this->addressIsDefault = $address->is_default;
        }

        $this->showAddressModal = true;
    }

    public function closeAddressModal(): void
    {
        $this->showAddressModal = false;
        $this->resetAddressForm();
    }

    public function resetAddressForm(): void
    {
        $this->editingAddressId = null;
        $this->addressLabel = 'Casa';
        $this->addressAddress = '';
        $this->addressNumber = '';
        $this->addressComplement = '';
        $this->addressNeighborhood = '';
        $this->addressCity = '';
        $this->addressState = '';
        $this->addressZipcode = '';
        $this->addressReference = '';
        $this->addressIsDefault = false;
    }

    public function saveAddress(): void
    {
        $userId = Auth::id();

        $this->validate([
            'addressLabel' => 'required|string|max:50',
            'addressAddress' => 'required|string|max:255',
            'addressNumber' => 'nullable|string|max:20',
            'addressComplement' => 'nullable|string|max:100',
            'addressNeighborhood' => 'nullable|string|max:100',
            'addressCity' => 'required|string|max:100',
            'addressState' => 'required|string|max:50',
            'addressZipcode' => 'nullable|string|max:20',
            'addressReference' => 'nullable|string|max:255',
        ]);

        if (!$this->editingAddressId) {
            $existingCount = UserAddress::where('user_id', $userId)->count();
            if ($existingCount >= 5) {
                $this->dispatch('notify', message: 'Limite maximo de 5 enderecos atingido.');
                return;
            }
        }

        if ($this->addressIsDefault) {
            UserAddress::where('user_id', $userId)->update(['is_default' => false]);
        }

        $data = [
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => $userId,
            'label' => $this->addressLabel,
            'address' => $this->addressAddress,
            'number' => $this->addressNumber ?: null,
            'complement' => $this->addressComplement ?: null,
            'neighborhood' => $this->addressNeighborhood ?: null,
            'city' => $this->addressCity,
            'state' => $this->addressState,
            'zipcode' => $this->addressZipcode ?: null,
            'reference' => $this->addressReference ?: null,
            'is_default' => $this->addressIsDefault,
        ];

        if ($this->editingAddressId) {
            UserAddress::where('user_id', $userId)->where('id', $this->editingAddressId)->update($data);
            $this->dispatch('notify', message: 'Endereco atualizado com sucesso!');
        } else {
            UserAddress::create($data);
            $this->dispatch('notify', message: 'Endereco salvo com sucesso!');
        }

        $this->closeAddressModal();
    }

    public function confirmDeleteAddress(int $id): void
    {
        $this->confirmDeleteAddressId = $id;
    }

    public function cancelDeleteAddress(): void
    {
        $this->confirmDeleteAddressId = null;
    }

    public function deleteAddress(): void
    {
        $address = UserAddress::where('user_id', Auth::id())->find($this->confirmDeleteAddressId);
        if ($address) {
            $address->delete();
            $this->dispatch('notify', message: 'Endereco removido.');
        }
        $this->confirmDeleteAddressId = null;
    }

    public function setDefaultAddress(int $id): void
    {
        $userId = Auth::id();
        UserAddress::where('user_id', $userId)->update(['is_default' => false]);
        UserAddress::where('user_id', $userId)->where('id', $id)->update(['is_default' => true]);
        $this->dispatch('notify', message: 'Endereco padrao atualizado.');
    }

    public function exportData()
    {
        $user = Auth::user();
        $tenant = $this->tenant;

        $data = [
            'exportado_em' => now()->toIso8601String(),
            'usuario' => [
                'nome' => $user->name,
                'email' => $user->email,
                'funcao' => $user->roleLabel(),
                'membro_desde' => $user->created_at->toIso8601String(),
            ],
            'restaurante' => [
                'nome' => $tenant->name,
                'email' => $tenant->email,
                'slug' => $tenant->slug,
            ],
            'total_pedidos' => $tenant->orders()->where('user_id', $user->id)->count(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $path = tempnam(sys_get_temp_dir(), 'lgpd-');
        file_put_contents($path, $json);
        $filename = 'dados-lgpd-' . $tenant->slug . '-' . now()->format('Y-m-d') . '.json';

        return response()->download($path, $filename, ['Content-Type' => 'application/json'])->deleteFileAfterSend();
    }

    public function confirmDeleteAccount(): void
    {
        $this->deleteConfirmation = '';
        $this->showDeleteAccountConfirm = true;
    }

    public function cancelDeleteAccount(): void
    {
        $this->deleteConfirmation = '';
        $this->showDeleteAccountConfirm = false;
    }

    public function deleteMyAccount(): void
    {
        $this->validate(['deleteConfirmation' => 'required|in:EXCLUIR']);

        $user = Auth::user();

        DB::transaction(function () use ($user) {
            $user->orders()->update(['user_id' => null]);
            $user->delete();
        });

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect(route('menu.show', $this->tenant->slug), navigate: false);
    }

    public function freeMyTable(): void
    {
        $orders = Order::where('user_id', Auth::id())
            ->whereNotNull('table_id')
            ->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
            ->get();

        if ($orders->isEmpty()) {
            $this->dispatch('notify', message: 'Nenhuma mesa ativa para liberar.');
            return;
        }

        $tableId = $orders->first()->table_id;
        foreach ($orders as $order) {
            $order->update([
                'status' => 'fechado',
                'bill_closed_at' => now(),
            ]);
        }

        Table::tryFreeTable($tableId);
        session()->forget("table_token_{$this->tenant->id}");
        $this->dispatch('tableFreed')->to('public.menu');
        $this->dispatch('tableFreed')->to('public.cart');
        $this->dispatch('notify', message: 'Mesa liberada com sucesso!');
    }

    #[Computed]
    public function myOrders()
    {
        return Order::where('user_id', Auth::id())
            ->with('items', 'table')
            ->latest()
            ->take(30)
            ->get();
    }

    #[Computed]
    public function myActiveOrders()
    {
        return Order::where('user_id', Auth::id())
            ->whereIn('status', ['novo', 'em_preparo', 'saiu_entrega'])
            ->with('items', 'table')
            ->latest()
            ->get();
    }

    #[Computed]
    public function orderHistory()
    {
        $query = Order::where('user_id', Auth::id())
            ->with('items', 'table')
            ->whereIn('status', ['entregue', 'cancelado', 'fechado']);

        if ($this->historyPeriod === 'today') {
            $query->whereDate('created_at', now()->today());
        } elseif ($this->historyPeriod === 'week') {
            $query->whereDate('created_at', '>=', now()->subDays(7));
        } elseif ($this->historyPeriod === 'month') {
            $query->whereDate('created_at', '>=', now()->subDays(30));
        }

        return $query->latest()->take(30)->get();
    }

    #[Computed]
    public function myAddresses()
    {
        return UserAddress::where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function render()
    {
        return view('livewire.client.client-dashboard', [
            'myOrders' => $this->myOrders,
            'myActiveOrders' => $this->myActiveOrders,
            'orderHistory' => $this->orderHistory,
            'myAddresses' => $this->myAddresses,
            'tenant' => $this->tenant,
        ])->extends('layouts.client');
    }
}
