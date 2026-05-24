<?php

namespace App\Livewire\Public;

use App\Livewire\Concerns\HasCart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Cart extends Component
{
    use HasCart;

    public $tenant;

    public string $customerName = '';

    public string $customerPhone = '';

    public string $tableNumber = '';

    public string $previousTableNumber = '';

    public string $notes = '';

    public bool $showCart = false;

    public ?int $lastOrderId = null;

    public ?array $orderTracking = null;

    public ?string $previousOrderStatus = null;

    public ?string $token = null;

    public ?string $qrTableNumber = null;

    public ?string $qrTableToken = null;

    public bool $showQrModal = false;

    public string $orderType = 'mesa';

    public string $paymentMethod = '';

    public ?float $cashAmount = null;

    public string $deliveryAddress = '';

    public string $deliveryReference = '';

    public string $couponCode = '';

    public ?array $appliedCoupon = null;

    public float $discount = 0;

    public array $userAddresses = [];
    public ?int $selectedAddressId = null;

    public bool $showAddressModal = false;
    public string $newAddressLabel = '';
    public string $newAddressStreet = '';
    public string $newAddressNumber = '';
    public string $newAddressComplement = '';
    public string $newAddressNeighborhood = '';
    public string $newAddressCity = '';
    public string $newAddressState = '';
    public string $newAddressZipcode = '';
    public string $newAddressReference = '';

    protected $listeners = ['addToCart', 'cartUpdated' => '$refresh', 'tableSelected' => 'onTableSelected'];

    public function mount($tenant, ?string $token = null): void
    {
        $this->tenant = $tenant;

        $this->restoreCartFromSession();

        if (Auth::check()) {
            $this->customerName = Auth::user()->name;
            $this->deliveryAddress = Auth::user()->delivery_address ?? '';
            $this->deliveryReference = Auth::user()->delivery_reference ?? '';
            $this->loadUserAddresses();
        }

        $table = null;

        if ($token) {
            $table = Table::where('tenant_id', $tenant->id)
                ->where('token', $token)
                ->first();
        }

        if (!$table) {
            $sessionToken = Session::get("table_token_{$tenant->id}");
            if ($sessionToken) {
                $table = Table::where('tenant_id', $tenant->id)
                    ->where('token', $sessionToken)
                    ->first();
            }
        }

        if ($table) {
            $this->tableNumber = $table->number;
            $this->qrTableNumber = $table->number;
            $this->qrTableToken = $table->token;
            $this->orderType = 'mesa';
        }

        $this->restoreLastOrder();
        $this->restorePendingCartItem();

        $this->previousTableNumber = $this->tableNumber;
    }

    public function loadUserAddresses(): void
    {
        if (Auth::check()) {
            $this->userAddresses = UserAddress::where('user_id', Auth::id())
                ->orderBy('is_default', 'desc')
                ->get()
                ->toArray();
        }
    }

    public function selectAddress(int $addressId): void
    {
        $address = UserAddress::find($addressId);
        if ($address && $address->user_id === Auth::id()) {
            $this->selectedAddressId = $addressId;
            $this->deliveryAddress = $address->address;
            $this->deliveryReference = $address->reference ?? '';
        }
    }

    public function updatedSelectedAddressId($addressId): void
    {
        if ($addressId) {
            $this->selectAddress($addressId);
        } else {
            $this->deliveryAddress = '';
            $this->deliveryReference = '';
        }
    }

    protected function restorePendingCartItem(): void
    {
        $pending = Session::pull('pending_cart_item');
        if ($pending && isset($pending['productId'])) {
            $this->addCartItem(
                $pending['productId'],
                $pending['productName'] ?? '',
                $pending['price'] ?? 0,
                $pending['selectedOptions'] ?? [],
                $pending['quantity'] ?? 1
            );
            $this->showCart = true;
        }
    }

    protected function restoreLastOrder(): void
    {
        $orderId = Session::get("last_order_{$this->tenant->id}");
        if ($orderId) {
            $order = Order::with('items')->find($orderId);
            if ($order && $order->tenant_id === $this->tenant->id) {
                $this->lastOrderId = $order->id;
                $this->previousOrderStatus = $order->status;
                $this->loadOrderTracking();

                $this->orderType = $order->type ?? 'mesa';

                if ($order->table) {
                    $this->tableNumber = $order->table->number;
                    $this->qrTableNumber = $order->table->number;
                    $this->qrTableToken = $order->table->token;
                }

                if ($order->address_json) {
                    $address = $order->address_json;
                    $this->deliveryAddress = $address['address'] ?? '';
                    $this->deliveryReference = $address['reference'] ?? '';
                }
            } else {
                Session::forget("last_order_{$this->tenant->id}");
            }
        }
    }

    public function addToCart($productId, $productName, $price, $selectedOptions = [], $quantity = 1): void
    {
        if (!Auth::check()) {
            Session::put('pending_cart_item', [
                'productId' => $productId,
                'productName' => $productName,
                'price' => $price,
                'selectedOptions' => $selectedOptions,
                'quantity' => $quantity,
            ]);
            $this->redirect(route('waiter.login.form', $this->tenant->slug) . '?redirect=' . urlencode(route('menu.show', $this->tenant->slug)));
            return;
        }

        $this->addCartItem($productId, $productName, $price, $selectedOptions, $quantity);
        $this->showCart = true;
        $this->dispatch('cartUpdated');
    }

    public function removeItem($key): void
    {
        $this->removeCartItem($key);
        if (empty($this->cartItems)) {
            $this->showCart = false;
        }
        $this->dispatch('cartUpdated');
    }

    public function updateQuantity($key, $delta): void
    {
        $this->adjustCartQuantity($key, $delta);
        $this->dispatch('cartUpdated');
    }

    #[Computed]
    public function total()
    {
        $cartTotal = $this->calcCartTotal();
        return max(0, $cartTotal - $this->discount);
    }

    #[Computed]
    public function itemsCount()
    {
        return $this->calcCartItemsCount();
    }

    #[Computed]
    public function freeTables()
    {
        return $this->tenant->manageableTables()
            ->where('status', 'free')
            ->get();
    }

    public function applyCoupon(): void
    {
        $this->reset('appliedCoupon', 'discount');

        if (!$this->couponCode) {
            $this->dispatch('notify', message: 'Digite um codigo de cupom.');
            return;
        }

        $coupon = Coupon::where('tenant_id', $this->tenant->id)
            ->where('code', strtoupper($this->couponCode))
            ->first();

        if (!$coupon) {
            $this->dispatch('notify', message: 'Cupom nao encontrado.');
            return;
        }

        $cartTotal = $this->calcCartTotal();

        if (!$coupon->isValid($cartTotal)) {
            $this->dispatch('notify', message: 'Cupom invalido ou expirado.');
            return;
        }

        $this->discount = $coupon->calculateDiscount($cartTotal);
        $this->appliedCoupon = [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => (float) $coupon->discount_value,
        ];

        $this->dispatch('notify', message: "Cupom {$coupon->code} aplicado! Desconto de R$ " . number_format($this->discount, 2, ',', '.'));
    }

    public function removeCoupon(): void
    {
        $this->reset('appliedCoupon', 'discount', 'couponCode');
        $this->dispatch('notify', message: 'Cupom removido.');
    }

    public function openAddressModal(): void
    {
        $this->showAddressModal = true;
        $this->newAddressLabel = '';
        $this->newAddressStreet = '';
        $this->newAddressNumber = '';
        $this->newAddressComplement = '';
        $this->newAddressNeighborhood = '';
        $this->newAddressCity = '';
        $this->newAddressState = '';
        $this->newAddressZipcode = '';
        $this->newAddressReference = '';
    }

    public function closeAddressModal(): void
    {
        $this->showAddressModal = false;
    }

    public function saveNewAddress(): void
    {
        $this->validate([
            'newAddressLabel' => 'required|string|max:255',
            'newAddressStreet' => 'required|string|max:255',
            'newAddressNumber' => 'nullable|string|max:20',
            'newAddressComplement' => 'nullable|string|max:255',
            'newAddressNeighborhood' => 'nullable|string|max:255',
            'newAddressCity' => 'nullable|string|max:255',
            'newAddressState' => 'nullable|string|max:2',
            'newAddressZipcode' => 'nullable|string|max:10',
            'newAddressReference' => 'nullable|string|max:255',
        ]);

        $existingCount = UserAddress::where('user_id', Auth::id())->count();

        $address = UserAddress::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => Auth::id(),
            'label' => $this->newAddressLabel,
            'address' => $this->newAddressStreet,
            'number' => $this->newAddressNumber ?: null,
            'complement' => $this->newAddressComplement ?: null,
            'neighborhood' => $this->newAddressNeighborhood ?: null,
            'city' => $this->newAddressCity ?: '',
            'state' => $this->newAddressState ?: '',
            'zipcode' => $this->newAddressZipcode ?: null,
            'reference' => $this->newAddressReference ?: null,
            'is_default' => $existingCount === 0,
        ]);

        $this->selectedAddressId = $address->id;
        $this->deliveryAddress = $address->full_address;
        $this->deliveryReference = $address->reference ?? '';
        $this->showAddressModal = false;
        $this->loadUserAddresses();
        $this->dispatch('notify', message: 'Endereco salvo com sucesso!');
    }

    public function checkout(): void
    {
        if (!$this->tenant->isOpen()) {
            $this->dispatch('notify', message: 'O restaurante está fechado no momento. Não é possível fazer pedidos.');
            return;
        }

        $this->validate([
            'customerName' => 'required|string|max:255',
        ]);

        if (empty($this->cartItems)) {
            return;
        }

        if ($this->orderType === 'mesa' && !$this->tableNumber) {
            $this->dispatch('notify', message: 'Selecione uma mesa.');
            return;
        }

        if ($this->orderType === 'entrega' && !$this->deliveryAddress) {
            $this->dispatch('notify', message: 'Informe o endereco de entrega.');
            return;
        }

        if ($this->orderType === 'entrega' && !$this->paymentMethod) {
            $this->dispatch('notify', message: 'Selecione a forma de pagamento.');
            return;
        }

        if ($this->paymentMethod === 'cash' && (!$this->cashAmount || $this->cashAmount <= 0)) {
            $this->dispatch('notify', message: 'Informe o valor para calculo do troco.');
            return;
        }

        if ($this->paymentMethod === 'cash' && $this->cashAmount < $this->calcCartTotal() - $this->discount) {
            $this->dispatch('notify', message: 'O valor informado deve ser maior ou igual ao total do pedido.');
            return;
        }

        $tableId = null;
        if ($this->orderType === 'mesa' && $this->tableNumber) {
            $table = Table::where('tenant_id', $this->tenant->id)
                ->where('number', $this->tableNumber)
                ->first();

            if ($table) {
                $tableId = $table->id;
                if ($table->status !== 'occupied') {
                    $table->update(['status' => 'occupied']);
                }
            }
        }

        $orderId = null;

        DB::transaction(function () use ($tableId, &$orderId) {
            $addressData = null;
            if ($this->orderType === 'entrega' && $this->deliveryAddress) {
                $addressData = [
                    'address' => $this->deliveryAddress,
                    'reference' => $this->deliveryReference,
                ];
            }

            $deliveryCost = $this->orderType === 'entrega' ? (float) ($this->tenant->delivery_cost_per_order ?? 0) : 0;

            $order = Order::create([
                'tenant_id' => $this->tenant->id,
                'user_id' => Auth::id(),
                'table_id' => $tableId,
                'customer_name' => $this->customerName,
                'customer_phone' => $this->customerPhone,
                'total' => $this->calcCartTotal(),
                'discount' => $this->discount,
                'discount_type' => $this->appliedCoupon['discount_type'] ?? null,
                'coupon_id' => $this->appliedCoupon['id'] ?? null,
                'payment_method' => $this->orderType === 'entrega' ? $this->paymentMethod : null,
                'payment_change' => $this->paymentMethod === 'cash' ? $this->cashAmount : null,
                'delivery_cost' => $deliveryCost > 0 ? $deliveryCost : null,
                'status' => 'novo',
                'type' => $this->orderType,
                'address_json' => $addressData,
                'notes' => $this->notes,
            ]);

            foreach ($this->cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['unit_price'],
                    'selected_options_json' => $item['options'],
                ]);
            }

            $orderId = $order->id;
        });

        if ($this->appliedCoupon) {
            Coupon::where('id', $this->appliedCoupon['id'])->increment('used_count');
        }

        $this->lastOrderId = $orderId;
        Session::put("last_order_{$this->tenant->id}", $orderId);

        $order = Order::find($orderId);
        $this->previousOrderStatus = $order ? $order->status : null;

        $this->loadOrderTracking();
        $this->customerName = Auth::check() ? Auth::user()->name : '';
        $this->customerPhone = '';
        $this->notes = '';
        $this->cashAmount = null;
        $this->paymentMethod = '';
        $this->showCart = false;
        $this->resetCart();

        if (!$this->qrTableNumber) {
            $this->tableNumber = '';
        }

        $this->reset('appliedCoupon', 'discount', 'couponCode');

        if ($this->orderType === 'entrega' && Auth::check()) {
            $this->autoSaveDeliveryAddress();
        }

        $this->dispatch('cartCleared');
        $orderTypeLabel = match ($this->orderType) {
            'mesa' => " na Mesa {$this->tableNumber}",
            'entrega' => ' para entrega',
            'retirada' => ' para retirada',
            default => '',
        };
        $this->dispatch('notifyNewOrder');
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: "Pedido enviado com sucesso{$orderTypeLabel}! Acompanhe o status.");
    }

    protected function autoSaveDeliveryAddress(): void
    {
        if (!$this->deliveryAddress) {
            return;
        }

        $userId = Auth::id();

        $existingCount = UserAddress::where('user_id', $userId)->count();
        if ($existingCount >= 5) {
            return;
        }

        $alreadySaved = UserAddress::where('user_id', $userId)
            ->where('address', $this->deliveryAddress)
            ->where('reference', $this->deliveryReference)
            ->exists();

        if ($alreadySaved) {
            return;
        }

        UserAddress::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $userId,
            'label' => 'Entrega',
            'address' => $this->deliveryAddress,
            'number' => null,
            'complement' => null,
            'neighborhood' => null,
            'city' => '',
            'state' => '',
            'zipcode' => null,
            'reference' => $this->deliveryReference,
            'is_default' => $existingCount === 0,
        ]);
    }

    public function loadOrderTracking(): void
    {
        if (!$this->lastOrderId) {
            $this->orderTracking = null;
            return;
        }

        $order = Order::with('items')->find($this->lastOrderId);

        if ($order) {
            if ($this->previousOrderStatus && $order->status !== $this->previousOrderStatus) {
                $statusLabel = $order->statusLabel();
                $this->dispatch('notify', message: "Pedido #{$order->id}: {$statusLabel}");
            }
            $this->previousOrderStatus = $order->status;

            $this->orderTracking = [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'total' => $order->total,
                'discount' => (float) $order->discount,
                'status' => $order->status,
                'type' => $order->type,
                'statusLabel' => $order->statusLabel(),
                'statusColor' => $order->statusClasses(),
                'delivery_cost' => (float) ($order->delivery_cost ?? 0),
                'items' => $order->items->map(fn($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'change_requested' => $item->change_requested,
                    'change_note' => $item->change_note,
                ]),
                'address_json' => $order->address_json,
            ];
        } else {
            $this->lastOrderId = null;
            $this->orderTracking = null;
            $this->previousOrderStatus = null;
            Session::forget("last_order_{$this->tenant->id}");
        }
    }

    public function requestItemChange(int $itemId, string $note): void
    {
        if (!Auth::check() || !Auth::user()->isStaff()) {
            $this->dispatch('notify', message: 'Apenas atendentes podem solicitar trocas.');
            return;
        }

        $item = OrderItem::with('order')->find($itemId);
        if (!$item || $item->order_id !== $this->lastOrderId) {
            return;
        }
        if (!$item->canRequestChange()) {
            $this->dispatch('notify', message: 'Tempo para troca expirou (limite de 5 minutos).');
            return;
        }

        $item->update([
            'change_requested' => true,
            'change_requested_at' => now(),
            'change_note' => $note,
        ]);

        $this->loadOrderTracking();
        $this->dispatch('orderUpdated');
        $this->dispatch('notify', message: 'Troca solicitada ao atendente!');
    }

    public function newOrder(): void
    {
        $this->lastOrderId = null;
        $this->orderTracking = null;
        Session::forget("last_order_{$this->tenant->id}");
        if (!$this->qrTableNumber) {
            $this->tableNumber = '';
        }
    }

    public function hasTableLocked(): bool
    {
        return $this->tableNumber
            && Auth::check();
    }

    public function clearQrTable(): void
    {
        if ($this->hasTableLocked()) {
            $this->dispatch('notify', message: 'Mesa fixa. A mesa so pode ser alterada no painel administrativo.');
            return;
        }
        $this->qrTableNumber = null;
        $this->tableNumber = '';
    }

    public function updatedTableNumber($value): void
    {
        if (!Auth::check()) return;

        if ($this->previousTableNumber && $value !== $this->previousTableNumber) {
            $this->tableNumber = $this->previousTableNumber;
            $this->dispatch('notify', message: 'Mesa fixa. A mesa so pode ser alterada no painel administrativo.');
        }
    }

    public function onTableSelected(int $tableId): void
    {
        if ($this->hasTableLocked()) {
            return;
        }
        $table = Table::find($tableId);
        if ($table && $table->tenant_id === $this->tenant->id) {
            $this->tableNumber = $table->number;
            $this->qrTableNumber = $table->number;
            $this->qrTableToken = $table->token;
        }
    }

    public function showQrCode(): void
    {
        $this->showQrModal = true;
    }

    public function confirmQrModal(): void
    {
        $this->showQrModal = false;
    }

    public function getQrCodeUrl(): string
    {
        if (!$this->qrTableToken) {
            return '';
        }
        $url = route('menu.show', ['slug' => $this->tenant->slug]) . '?token=' . $this->qrTableToken;
        return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);
    }

    public function getTableEntryUrl(): string
    {
        if (!$this->qrTableToken) {
            return '';
        }
        return route('menu.show', ['slug' => $this->tenant->slug]) . '?token=' . $this->qrTableToken;
    }

    public function render()
    {
        return view('livewire.public.cart', [
            'total' => $this->total,
            'itemsCount' => $this->itemsCount,
            'freeTables' => $this->freeTables,
        ]);
    }
}
