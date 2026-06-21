<?php

namespace App\Livewire\Public;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Table;
use App\Models\UserAddress;
use App\Services\EfiBank\TenantEfiBankService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Menu extends Component
{
    public $tenant;
    public ?int $selectedProductId = null;
    public ?string $token = null;

    public string $clientTab = 'menu';
    public string $orderHistoryFilter = 'all';
    public string $historyPeriod = 'all';
    public string $historySearch = '';
    public string $profileName = '';
    public string $profileEmail = '';
    public string $profilePassword = '';
    public string $profilePasswordConfirmation = '';

    public ?int $selectedTableId = null;
    public ?string $selectedTableNumber = null;
    public ?string $selectedTableToken = null;
    public bool $showQrModal = false;
    public bool $showTablePicker = false;
    public ?int $pickingTableId = null;

    protected $listeners = [
        'productSelected' => 'showProduct',
        'orderUpdated' => '$refresh',
        'notifyNewOrder' => '$refresh',
        'tableSelected' => 'onTableSelectedFromCart',
        'cartUpdated' => '$refresh',
        'tableFreed' => 'clearTableSession',
        'goToMyOrders' => 'goToMyOrders',
    ];

    public function goToMyOrders(): void
    {
        $this->switchClientTab('orders');
    }

    public function mount($tenant): void
    {
        $this->tenant = $tenant;

        if (Auth::check()) {
            $this->profileName = Auth::user()->name;
        }

        $this->restoreTableFromSession();
        $this->restoreTableFromToken();
        $this->verifyTableAccess();
    }

    protected function restoreTableFromSession(): void
    {
        $token = Session::get("table_token_{$this->tenant->id}");
        if ($token) {
            $table = Table::where('tenant_id', $this->tenant->id)
                ->where('token', $token)
                ->first();
            if ($table) {
                $this->selectedTableId = $table->id;
                $this->selectedTableNumber = $table->number;
                $this->selectedTableToken = $token;
            }
        }
    }

    protected function restoreTableFromToken(): void
    {
        if ($this->token && !$this->selectedTableToken) {
            $table = Table::where('tenant_id', $this->tenant->id)
                ->where('token', $this->token)
                ->first();
            if ($table) {
                $this->selectedTableId = $table->id;
                $this->selectedTableNumber = $table->number;
                $this->selectedTableToken = $table->token;
                Session::put("table_token_{$this->tenant->id}", $table->token);
            }
        }
    }

    public function clearTableSession(): void
    {
        $this->selectedTableId = null;
        $this->selectedTableNumber = null;
        $this->selectedTableToken = null;
        $this->showQrModal = false;
        $this->showCloseTablePix = false;
        $this->closeTablePixQr = null;
        $this->closeTablePixCopia = null;
        Session::forget("table_token_{$this->tenant->id}");
        $this->dispatch('tableFreed')->to('public.cart');
    }

    public function verifyTableAccess(): void
    {
        if (!$this->selectedTableId || !Auth::check()) return;

        $table = Table::where('tenant_id', $this->tenant->id)->find($this->selectedTableId);

        if ($table && $table->status === 'free') {
            $tableEverHadOrders = Order::where('table_id', $table->id)->exists();

            if ($tableEverHadOrders && !$table->hasOpenBillableOrders()) {
                $this->clearTableSession();
                $this->dispatch('notify', message: 'Voce foi liberado da mesa.');
            }
        }
    }

    #[Computed]
    public function categories()
    {
        return Category::where('tenant_id', $this->tenant->id)
            ->with(['products' => function ($q) {
                $q->active()->with('attributes.options.ingredient');
            }])
            ->orderBy('position')
            ->get();
    }

    #[Computed]
    public function selectedProduct()
    {
        if (!$this->selectedProductId) {
            return null;
        }
        return Product::with('attributes.options.ingredient')
            ->where('tenant_id', $this->tenant->id)
            ->findOrFail($this->selectedProductId);
    }

    public function showProduct($productId): void
    {
        $this->selectedProductId = $productId;
        $this->dispatch('product-selected');
    }

    public function closeProduct(): void
    {
        $this->selectedProductId = null;
    }

    public function switchClientTab(string $tab): void
    {
        $this->clientTab = $tab;
    }

    public function saveProfile(): void
    {
        if (!Auth::check()) {
            return;
        }

        $this->validate([
            'profileName' => 'required|string|max:255',
            'profileEmail' => 'required|email|max:255',
            'profilePassword' => 'nullable|string|min:6',
            'profilePasswordConfirmation' => 'nullable|same:profilePassword',
        ]);

        $user = Auth::user();
        $data = ['name' => $this->profileName, 'email' => $this->profileEmail];
        if ($this->profilePassword) {
            $data['password'] = $this->profilePassword;
        }
        $user->update($data);
        $this->profilePassword = '';
        $this->profilePasswordConfirmation = '';
        $this->dispatch('notify', message: 'Perfil atualizado com sucesso!');
    }

    #[Computed]
    public function myOrders()
    {
        if (!Auth::check()) {
            return collect();
        }
        return Order::where('user_id', Auth::id())
            ->whereIn('status', ['fechado', 'entregue'])
            ->with('items', 'table', 'payments')
            ->latest()
            ->take(50)
            ->get();
    }

    #[Computed]
    public function myOrdersCount(): int
    {
        if (!Auth::check()) {
            return 0;
        }
        return Order::where('user_id', Auth::id())
            ->whereIn('status', ['fechado', 'entregue'])
            ->count();
    }

    #[Computed]
    public function myActiveOrders()
    {
        if (!Auth::check()) {
            return collect();
        }
        return Order::where('user_id', Auth::id())
            ->whereNotIn('status', ['fechado', 'cancelado', 'entregue'])
            ->with('items', 'table', 'payments')
            ->latest()
            ->get();
    }

    public string $ordersFilter = 'mesa';

    public bool $showPixModal = false;
    public ?string $pixQrCode = null;
    public ?string $pixCopiaECola = null;
    public bool $generatingPix = false;
    public ?int $pixOrderId = null;
    public ?string $pixTxid = null;
    public bool $pixPaymentConfirmed = false;
    public bool $pixPaymentError = false;
    public string $pixPaymentErrorMsg = '';
    public int $pixExpiresIn = 3600;

    #[Computed]
    public function myUnpaidOrders()
    {
        if (!Auth::check()) {
            return collect();
        }
        $userId = Auth::id();
        $orders = Order::where('user_id', $userId)
            ->whereNotIn('status', ['entregue', 'cancelado', 'fechado'])
            ->with('items', 'table', 'payments')
            ->latest()
            ->get();

        return $orders->filter(function ($order) {
            $paid = $order->payments->where('status', 'paid')->sum('amount');
            return $paid < (float) $order->total;
        })->values();
    }

    public function generateOrderPix(int $orderId): void
    {
        $order = Order::where('user_id', Auth::id())->findOrFail($orderId);

        $this->generatingPix = true;
        $this->pixQrCode = null;
        $this->pixCopiaECola = null;
        $this->pixOrderId = $order->id;
        $this->pixTxid = null;
        $this->pixPaymentConfirmed = false;
        $this->pixPaymentError = false;
        $this->pixPaymentErrorMsg = '';

        try {
            $txid = 'ord' . $order->id . now()->format('YmdHis') . rand(100, 999);
            $charge = app(TenantEfiBankService::class)->generatePixChargeData(
                $this->tenant, $order->total, $txid, $order->customer_name ?: Auth::user()->name
            );
            $this->pixCopiaECola = $charge['pixCopiaECola'] ?? null;
            $this->pixTxid = $charge['txid'] ?? $txid;
            $this->pixQrCode = $charge['qrcode'] ?? null;
            if ($this->pixCopiaECola) {
                $this->showPixModal = true;
            }
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Erro ao gerar PIX: ' . $e->getMessage());
        }

        $this->generatingPix = false;
    }

    public function closePixModal(): void
    {
        $this->showPixModal = false;
        $this->pixQrCode = null;
        $this->pixCopiaECola = null;
        $this->pixOrderId = null;
        $this->pixTxid = null;
        $this->pixPaymentConfirmed = false;
        $this->pixPaymentError = false;
    }

    public function verifyPixPayment(): void
    {
        if (!$this->pixTxid || !$this->pixOrderId || $this->pixPaymentConfirmed) {
            return;
        }

        try {
            $client = \App\Services\EfiBank\EfiBankClient::forTenant($this->tenant);
            $charge = $client->pixGetCharge($this->pixTxid);

            if (($charge['status'] ?? '') === 'CONCLUIDA') {
                $this->pixPaymentConfirmed = true;
                $this->pixPaymentError = false;

                $order = Order::find($this->pixOrderId);
                if ($order && !$order->payments->where('status', 'paid')->count()) {
                    Payment::create([
                        'order_id' => $order->id,
                        'tenant_id' => $order->tenant_id,
                        'amount' => $order->total,
                        'payment_method' => 'pix',
                        'status' => 'paid',
                        'paid_at' => now(),
                        'notes' => 'Pagamento PIX confirmado via API',
                    ]);
                }

                if ($order && $order->table_id) {
                    Table::tryFreeTable($order->table_id);
                    $this->clearTableSession();
                }

                $this->showPixModal = false;
                $this->dispatch('notify', message: 'Pagamento PIX confirmado!');
            }
        } catch (\Throwable $e) {
            $this->pixPaymentError = true;
            $this->pixPaymentErrorMsg = $e->getMessage();
        }
    }

    public function getCanceledOrders()
    {
        if (!Auth::check()) {
            return collect();
        }
        return Order::where('user_id', Auth::id())
            ->where('status', 'cancelado')
            ->with('items', 'table', 'payments')
            ->latest()
            ->take(20)
            ->get();
    }

    public bool $showAddressModal = false;
    public ?int $editingAddressId = null;
    public string $addrLabel = 'Casa';
    public string $addrAddress = '';
    public string $addrNumber = '';
    public string $addrComplement = '';
    public string $addrNeighborhood = '';
    public string $addrCity = '';
    public string $addrState = '';
    public string $addrZipcode = '';
    public string $addrReference = '';
    public bool $addrIsDefault = false;
    public ?int $confirmDeleteAddressId = null;
    public bool $showDeleteAccountConfirm = false;
    public string $deleteConfirmation = '';

    public bool $showCloseTablePix = false;
    public ?string $closeTablePixQr = null;
    public ?string $closeTablePixCopia = null;
    public bool $generatingTablePix = false;
    public float $tableBillTotal = 0;

    public function closeMyTableBill(): void
    {
        if (!Auth::check()) return;

        if ($this->closeTablePixCopia && $this->closeTablePixQr) {
            $this->showCloseTablePix = true;
            return;
        }

        $orders = Order::where('user_id', Auth::id())
            ->whereNotNull('table_id')
            ->whereNotIn('status', ['fechado', 'cancelado'])
            ->with('payments')
            ->get();

        if ($orders->isEmpty()) {
            $this->dispatch('notify', message: 'Nenhuma conta de mesa ativa.');
            return;
        }

        $tableId = $orders->first()->table_id;
        $total = $orders->sum(fn($o) => (float) $o->total);
        $paid = $orders->sum(fn($o) => (float) $o->payments->where('status', 'paid')->sum('amount'));
        $pending = max(0, $total - $paid);

        if ($pending <= 0) {
            $this->dispatch('notify', message: 'Conta ja esta paga.');
            return;
        }

        $this->tableBillTotal = $pending;
        $this->generatingTablePix = true;
        $this->closeTablePixQr = null;
        $this->closeTablePixCopia = null;

        try {
            $txid = 'clt' . $tableId . now()->format('YmdHis') . rand(100, 999);
            $charge = app(TenantEfiBankService::class)->generatePixChargeData(
                $this->tenant, $pending, $txid, Auth::user()->name
            );
            $this->closeTablePixCopia = $charge['pixCopiaECola'] ?? null;
            $this->closeTablePixQr = $charge['qrcode'] ?? null;
            if ($this->closeTablePixCopia) {
                $this->showCloseTablePix = true;
            }
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Erro ao gerar PIX: ' . $e->getMessage());
        }

        $this->generatingTablePix = false;
    }

    public function confirmTablePixPayment(): void
    {
        if (!Auth::check()) return;

        $orders = Order::where('user_id', Auth::id())
            ->whereNotNull('table_id')
            ->whereNotIn('status', ['fechado', 'cancelado'])
            ->with('payments')
            ->get();

        if ($orders->isEmpty()) return;

        $tableId = $orders->first()->table_id;
        DB::transaction(function () use ($orders, $tableId) {
            foreach ($orders as $order) {
                $pending = $order->pendingPaymentAmount();
                if ($pending > 0) {
                    Payment::create([
                        'order_id' => $order->id,
                        'tenant_id' => $order->tenant_id,
                        'amount' => $pending,
                        'payment_method' => 'pix',
                        'status' => 'paid',
                        'paid_at' => now(),
                        'notes' => 'Pagamento via PIX pelo cliente',
                    ]);
                }
                if ($order->pendingPaymentAmount() <= 0) {
                    $order->update(['status' => 'fechado']);
                }
            }
            Table::tryFreeTable($tableId);
        });

        $this->showCloseTablePix = false;
        $this->closeTablePixQr = null;
        $this->closeTablePixCopia = null;
        $this->clearTableSession();
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Mesa liberada e pagamento confirmado!');
    }

    public function cancelTablePixPayment(): void
    {
        $this->showCloseTablePix = false;
    }

    public function openAddressModal(?int $addressId = null): void
    {
        $this->resetAddressForm();
        $this->editingAddressId = $addressId;

        if ($addressId) {
            $addr = UserAddress::where('user_id', Auth::id())->findOrFail($addressId);
            $this->addrLabel = $addr->label;
            $this->addrAddress = $addr->address;
            $this->addrNumber = $addr->number ?? '';
            $this->addrComplement = $addr->complement ?? '';
            $this->addrNeighborhood = $addr->neighborhood ?? '';
            $this->addrCity = $addr->city;
            $this->addrState = $addr->state;
            $this->addrZipcode = $addr->zipcode ?? '';
            $this->addrReference = $addr->reference ?? '';
            $this->addrIsDefault = $addr->is_default;
        }

        $this->showAddressModal = true;
    }

    public function closeAddressModal(): void
    {
        $this->showAddressModal = false;
    }

    public function lookupCep(string $cep): void
    {
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) !== 8) return;

        try {
            $response = file_get_contents("https://viacep.com.br/ws/{$cep}/json/");
            $data = json_decode($response, true);
            if ($data && !isset($data['erro'])) {
                $this->addrAddress = $data['logradouro'] ?? '';
                $this->addrNeighborhood = $data['bairro'] ?? '';
                $this->addrCity = $data['localidade'] ?? '';
                $this->addrState = $data['uf'] ?? '';
                $this->addrNumber = '';
                $this->addrComplement = '';
            }
        } catch (\Throwable $e) {}
    }

    public function resetAddressForm(): void
    {
        $this->editingAddressId = null;
        $this->addrLabel = 'Casa';
        $this->addrAddress = '';
        $this->addrNumber = '';
        $this->addrComplement = '';
        $this->addrNeighborhood = '';
        $this->addrCity = '';
        $this->addrState = '';
        $this->addrZipcode = '';
        $this->addrReference = '';
        $this->addrIsDefault = false;
    }

    public function saveAddress(): void
    {
        $userId = Auth::id();

        $this->validate([
            'addrLabel' => 'required|string|max:50',
            'addrAddress' => 'required|string|max:255',
            'addrNumber' => 'nullable|string|max:20',
            'addrComplement' => 'nullable|string|max:100',
            'addrNeighborhood' => 'nullable|string|max:100',
            'addrCity' => 'required|string|max:100',
            'addrState' => 'required|string|max:50',
            'addrZipcode' => 'nullable|string|max:20',
            'addrReference' => 'nullable|string|max:255',
        ]);

        if (!$this->editingAddressId) {
            $existingCount = UserAddress::where('user_id', $userId)->count();
            if ($existingCount >= 5) {
                $this->dispatch('notify', message: 'Limite maximo de 5 enderecos atingido.');
                return;
            }
        }

        if ($this->addrIsDefault) {
            UserAddress::where('user_id', $userId)->update(['is_default' => false]);
        }

        $data = [
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => $userId,
            'label' => $this->addrLabel,
            'address' => $this->addrAddress,
            'number' => $this->addrNumber ?: null,
            'complement' => $this->addrComplement ?: null,
            'neighborhood' => $this->addrNeighborhood ?: null,
            'city' => $this->addrCity,
            'state' => $this->addrState,
            'zipcode' => $this->addrZipcode ?: null,
            'reference' => $this->addrReference ?: null,
            'is_default' => $this->addrIsDefault,
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
        $addr = UserAddress::where('user_id', Auth::id())->find($this->confirmDeleteAddressId);
        if ($addr) {
            $addr->delete();
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

    public function getMyAddresses()
    {
        return UserAddress::where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function cartItemsCount(): int
    {
        return collect($this->cartItems)->sum('quantity');
    }

    #[Computed]
    public function cartItems(): array
    {
        return \Illuminate\Support\Facades\Session::get("cart_{$this->tenant->id}", []);
    }

    #[Computed]
    public function availableTables()
    {
        return $this->tenant->manageableTables()
            ->where('status', 'free')
            ->orderByRaw("CAST(number AS UNSIGNED), number")
            ->get();
    }

    public function userHasPendingPaymentOnTable(): bool
    {
        if (!Auth::check() || !$this->selectedTableId) return false;

        return Order::where('user_id', Auth::id())
            ->where('table_id', $this->selectedTableId)
            ->whereNotIn('status', ['fechado', 'cancelado'])
            ->exists();
    }

    public function selectTable(int $tableId): void
    {
        if ($this->selectedTableId && $this->selectedTableId !== $tableId) {
            if ($this->userHasPendingPaymentOnTable()) {
                $this->dispatch('notify', message: "Voce ja esta na Mesa {$this->selectedTableNumber}. Finalize os pedidos pendentes antes de trocar.");
                return;
            }
            $this->clearTableSession();
        }

        $table = Table::where('tenant_id', $this->tenant->id)->find($tableId);
        if (!$table) {
            return;
        }

        $this->selectedTableId = $table->id;
        $this->selectedTableNumber = $table->number;
        $this->selectedTableToken = $table->token;
        $this->showTablePicker = false;
        $this->showQrModal = true;

        if ($table->status === 'free') {
            $table->update(['status' => 'occupied']);
        }

        Session::put("table_token_{$this->tenant->id}", $table->token);

        $this->dispatch('tableSelected', tableId: $table->id)->to('public.cart');
        $this->dispatch('notify', message: "Mesa {$table->number} selecionada!");
    }

    public function confirmTable(): void
    {
        $this->showQrModal = false;
        $this->dispatch('notify', message: "Voce esta na Mesa {$this->selectedTableNumber}!");
    }

    public function leaveTable(): void
    {
        if (!Auth::check()) return;

        if ($this->userHasPendingPaymentOnTable()) {
            $this->dispatch('notify', message: 'Existem pedidos pendentes de pagamento nesta mesa. Quite-os antes de sair ou solicite ao atendente.');
            return;
        }

        $tableId = $this->selectedTableId;
        $this->clearTableSession();

        if ($tableId) {
            Table::tryFreeTable($tableId);
        }

        $this->dispatch('notify', message: 'Voce saiu da mesa.');
    }

    public function showQrCode(): void
    {
        $this->showQrModal = true;
    }

    public function onTableSelectedFromCart(int $tableId): void
    {
        if ($this->selectedTableId && $this->selectedTableId !== $tableId) {
            return;
        }

        $table = Table::find($tableId);
        if ($table) {
            $this->selectedTableId = $table->id;
            $this->selectedTableNumber = $table->number;
            $this->selectedTableToken = $table->token;
            Session::put("table_token_{$this->tenant->id}", $table->token);
        }
    }

    public function getQrCodeUrl(): string
    {
        if (!$this->selectedTableToken) {
            return '';
        }
        $url = route('menu.show', ['slug' => $this->tenant->slug]) . '?token=' . $this->selectedTableToken;
        return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);
    }

    public function getTableEntryUrl(): string
    {
        if (!$this->selectedTableToken) {
            return '';
        }
        return route('menu.show', ['slug' => $this->tenant->slug]) . '?token=' . $this->selectedTableToken;
    }

    public function render()
    {
        return view('livewire.public.menu', [
            'categories' => $this->categories,
            'selectedProduct' => $this->selectedProduct,
            'myOrders' => $this->myOrders,
            'myActiveOrders' => $this->myActiveOrders,
            'myUnpaidOrders' => $this->myUnpaidOrders,
            'availableTables' => $this->availableTables,
            'qrCodeUrl' => $this->getQrCodeUrl(),
            'tableEntryUrl' => $this->getTableEntryUrl(),
            'cartItemsCount' => $this->cartItemsCount,
            'cartItems' => $this->cartItems,
            'myOrdersCount' => $this->myOrdersCount,
            'myAddresses' => Auth::check() ? $this->getMyAddresses() : collect(),
        ]);
    }
}
