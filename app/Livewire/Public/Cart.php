<?php

namespace App\Livewire\Public;

use App\Livewire\Concerns\HasCart;
use App\Models\Coupon;
use App\Models\CustomerPoint;
use App\Models\LoyaltyConfig;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Table;
use App\Models\UserAddress;
use App\Services\DeliveryNotificationService;
use App\Services\EfiBank\TenantEfiBankService;
use App\Services\PointsService;
use App\Services\StockService;
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

    public string $paymentMethod = 'pix';
    public ?float $cashAmount = null;

    public string $deliveryAddress = '';

    public string $deliveryReference = '';

    public string $couponCode = '';

    public ?array $appliedCoupon = null;

    public float $discount = 0;

    public bool $usePoints = false;

    public float $pointsDiscount = 0;

    public bool $couponsEnabled = true;

    public array $userAddresses = [];
    public ?int $selectedAddressId = null;

    public bool $showAddressModal = false;

    public bool $showPixCheckoutModal = false;
    public ?string $pixQrCode = null;
    public ?string $pixCopiaECola = null;
    public bool $generatingPix = false;
    public ?int $pixOrderId = null;
    public ?string $pixTxid = null;
    public bool $pixPaymentConfirmed = false;
    public bool $pixPaymentError = false;
    public string $pixPaymentErrorMsg = '';

    public string $newAddressLabel = '';
    public string $newAddressStreet = '';
    public string $newAddressNumber = '';
    public string $newAddressComplement = '';
    public string $newAddressNeighborhood = '';
    public string $newAddressCity = '';
    public string $newAddressState = '';
    public string $newAddressZipcode = '';
    public string $newAddressReference = '';

    protected $listeners = ['addToCart', 'addRedeemedPointsItem', 'tableSelected' => 'onTableSelected', 'tableFreed' => 'clearTable'];

    public function mount($tenant, ?string $token = null): void
    {
        $this->tenant = $tenant;
        $this->couponsEnabled = $tenant->coupons_enabled ?? true;

        $this->restoreCartFromSession();

        if (Auth::check()) {
            $this->customerName = Auth::user()->name;
            $this->customerPhone = Auth::user()->phone ?? '';
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
        $this->syncTableWithSession();
        $this->restorePendingCartItem();

        $this->previousTableNumber = $this->tableNumber;
        $this->verifyTableAccess();
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
            $this->deliveryAddress = $address->full_address;
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
            $order = Order::with('items', 'table')->find($orderId);
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
        $this->dispatchBrowserCartEvent();
    }

    public function addRedeemedPointsItem($productId, $productName, $pointsPrice, $quantity = 1): void
    {
        if (!Auth::check()) {
            $this->redirect(route('waiter.login.form', $this->tenant->slug) . '?redirect=' . urlencode(route('menu.show', $this->tenant->slug)));
            return;
        }

        $this->addCartItem($productId, $productName, 0, [], $quantity);

        $key = $productId . '-' . md5(json_encode([]));
        if (isset($this->cartItems[$key])) {
            $this->cartItems[$key]['is_points_item'] = true;
            $this->cartItems[$key]['points_cost'] = (int) $pointsPrice;
            $this->persistCartToSession();
        }

        $this->showCart = true;
        $this->dispatch('cartUpdated');
        $this->dispatchBrowserCartEvent();
    }

    public function removeItem($key): void
    {
        $this->removeCartItem($key);
        if (empty($this->cartItems)) {
            $this->showCart = false;
        }
        $this->dispatch('cartUpdated');
        $this->dispatchBrowserCartEvent();
    }

    public function updateQuantity($key, $delta): void
    {
        $this->adjustCartQuantity($key, $delta);
        $this->dispatch('cartUpdated');
        $this->dispatchBrowserCartEvent();
    }

    #[Computed]
    public function total()
    {
        $cartTotal = $this->calcCartTotal();
        $deliveryCost = $this->orderType === 'entrega' ? (float) ($this->tenant->delivery_cost_per_order ?? 0) : 0;
        $subtotal = max(0, $cartTotal - $this->discount + $deliveryCost);
        $pointsDiscount = $this->usePoints ? $this->pointsDiscount : 0;
        return max(0, $subtotal - $pointsDiscount);
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

        if (!$this->couponsEnabled) {
            $this->dispatch('notify', message: 'Cupons estao desativados.');
            return;
        }

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

    #[Computed]
    public function pointsBalance(): int
    {
        if (!Auth::check()) return 0;
        return app(PointsService::class)->getCustomerBalance($this->tenant, Auth::user());
    }

    #[Computed]
    public function pointsActive(): bool
    {
        return app(PointsService::class)->arePointsVisibleForCustomer($this->tenant);
    }

    #[Computed]
    public function pointsMonetaryValue(): float
    {
        return app(PointsService::class)->pointsToMoney($this->pointsBalance);
    }

    #[Computed]
    public function maxSpendablePoints(): int
    {
        if (!Auth::check() || !$this->pointsActive) return 0;
        return app(PointsService::class)->getMaxSpendablePoints(
            $this->tenant, Auth::user(), $this->calcCartTotal()
        );
    }

    public function togglePoints(): void
    {
        $this->usePoints = !$this->usePoints;

        if ($this->usePoints) {
            $cartTotal = $this->calcCartTotal();
            $deliveryCost = $this->orderType === 'entrega' ? (float) ($this->tenant->delivery_cost_per_order ?? 0) : 0;
            $totalBeforePoints = max(0, $cartTotal - $this->discount + $deliveryCost);
            $minOrderValue = (float) (LoyaltyConfig::forTenant($this->tenant)->min_points_order_value ?? 10.00);

            if ($totalBeforePoints < $minOrderValue) {
                $this->usePoints = false;
                $this->dispatch('notify', message: 'Valor minimo do pedido para usar pontos: R$ ' . number_format($minOrderValue, 2, ',', '.'));
                return;
            }

            $maxPoints = $this->maxSpendablePoints;
            if ($maxPoints <= 0) {
                $this->usePoints = false;
                $this->dispatch('notify', message: 'Voce nao tem pontos suficientes.');
                return;
            }
            $this->pointsDiscount = app(PointsService::class)->pointsToMoney($maxPoints);
        } else {
            $this->pointsDiscount = 0;
        }
    }

    public function removePoints(): void
    {
        $this->reset('usePoints', 'pointsDiscount');
        $this->dispatch('notify', message: 'Desconto por pontos removido.');
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

    public function lookupCep(string $cep): void
    {
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) !== 8) return;

        try {
            $response = file_get_contents("https://viacep.com.br/ws/{$cep}/json/");
            $data = json_decode($response, true);
            if ($data && !isset($data['erro'])) {
                $this->newAddressStreet = $data['logradouro'] ?? '';
                $this->newAddressNeighborhood = $data['bairro'] ?? '';
                $this->newAddressCity = $data['localidade'] ?? '';
                $this->newAddressState = $data['uf'] ?? '';
                $this->newAddressNumber = '';
                $this->newAddressComplement = '';
            }
        } catch (\Throwable $e) {}
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

        $rules = [
            'customerName' => 'required|string|max:255',
        ];

        if ($this->orderType === 'mesa' && !$this->tableNumber) {
            $this->dispatch('notify', message: 'Selecione uma mesa.');
            return;
        }

        if ($this->orderType === 'entrega') {
            $rules['customerPhone'] = 'required|string|max:20';
            $rules['deliveryAddress'] = 'required|string';
            $rules['paymentMethod'] = 'required|in:pix,credit_card,cash';
            if ($this->paymentMethod === 'cash') {
                $rules['cashAmount'] = 'required|numeric|min:' . ($this->calcCartTotal() - $this->discount + ($this->orderType === 'entrega' ? (float) ($this->tenant->delivery_cost_per_order ?? 0) : 0) + 0.01);
            }
        }

        $this->validate($rules);

        if (empty($this->cartItems)) {
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

        $stockErrors = app(StockService::class)->validateStockForCartItems($this->cartItems, $this->tenant->id);
        if (!empty($stockErrors)) {
            foreach ($stockErrors as $error) {
                $this->dispatch('notify', message: $error);
            }
            return;
        }

        $orderId = null;
        $order = null;
        $pointsSpent = 0;
        $redeemPointsSpent = 0;

        DB::transaction(function () use ($tableId, &$orderId, &$order, &$pointsSpent, &$redeemPointsSpent) {
            $addressData = null;
            if ($this->orderType === 'entrega' && $this->deliveryAddress) {
                $addressData = [
                    'address' => $this->deliveryAddress,
                    'reference' => $this->deliveryReference,
                    'street' => $this->newAddressStreet ?: null,
                    'number' => $this->newAddressNumber ?: null,
                    'complement' => $this->newAddressComplement ?: null,
                    'neighborhood' => $this->newAddressNeighborhood ?: null,
                    'city' => $this->newAddressCity ?: '',
                    'state' => $this->newAddressState ?: '',
                    'zipcode' => $this->newAddressZipcode ?: null,
                ];
            }

            $deliveryCost = $this->orderType === 'entrega' ? (float) ($this->tenant->delivery_cost_per_order ?? 0) : 0;
            $cartTotal = $this->calcCartTotal();
            $totalBeforePoints = max(0, $cartTotal - $this->discount + $deliveryCost);
            $pointsDiscount = $this->usePoints ? $this->pointsDiscount : 0;

            $redeemPointsSpent = collect($this->cartItems)->sum(fn($i) => $i['points_cost'] ?? 0);
            $hasRedeemedItems = $redeemPointsSpent > 0;

            if ($hasRedeemedItems) {
                $balance = app(PointsService::class)->getCustomerBalance($this->tenant, Auth::user());
                $totalNeeded = $redeemPointsSpent;
                if ($this->usePoints && $pointsDiscount > 0) {
                    $totalNeeded += app(PointsService::class)->moneyToPoints($pointsDiscount);
                }
                if ($balance < $totalNeeded) {
                    throw new \RuntimeException('Saldo de pontos insuficiente para itens resgatados.');
                }
            }

            if ($this->usePoints && $pointsDiscount > 0) {
                $minOrderValue = (float) (LoyaltyConfig::forTenant($this->tenant)->min_points_order_value ?? 10.00);
                if ($totalBeforePoints < $minOrderValue) {
                    throw new \RuntimeException('Valor minimo do pedido para usar pontos: R$ ' . number_format($minOrderValue, 2, ',', '.'));
                }
            }

            $pointsSpent = $this->usePoints && $pointsDiscount > 0
                ? app(PointsService::class)->moneyToPoints($pointsDiscount)
                : 0;

            $totalPointsSpent = $pointsSpent + $redeemPointsSpent;
            $orderTotal = max(0, $totalBeforePoints - $pointsDiscount);

            $order = Order::create([
                'tenant_id' => $this->tenant->id,
                'user_id' => Auth::id(),
                'table_id' => $tableId,
                'customer_name' => $this->customerName,
                'customer_phone' => $this->customerPhone,
                'total' => $orderTotal,
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
                'points_used' => $totalPointsSpent > 0,
                'points_spent' => $totalPointsSpent,
                'points_discount' => ($this->usePoints ? $this->pointsDiscount : 0) + app(PointsService::class)->pointsToMoney($redeemPointsSpent),
            ]);

            foreach ($this->cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['unit_price'],
                    'selected_options_json' => $item['options'],
                    'is_points_item' => $item['is_points_item'] ?? false,
                    'points_cost' => $item['points_cost'] ?? null,
                ]);
            }

            app(StockService::class)->deductOrderStock($order, Auth::id());

            if ($totalPointsSpent > 0) {
                $result = app(PointsService::class)->spendPoints(
                    $this->tenant,
                    Auth::user(),
                    $totalPointsSpent,
                    $order,
                    "Resgate de {$totalPointsSpent} pontos no Pedido #{$order->id}"
                );

                if (!$result['success']) {
                    throw new \RuntimeException($result['message'] ?? 'Erro ao resgatar pontos.');
                }
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

        // Save phone to user profile before clearing it
        if ($this->orderType === 'entrega') {
            $this->autoSaveDeliveryAddress();
        }
        if (Auth::check() && $this->customerPhone && Auth::user()->phone !== $this->customerPhone) {
            Auth::user()->update(['phone' => $this->customerPhone]);
        }

        $this->loadOrderTracking();
        $this->customerName = Auth::check() ? Auth::user()->name : '';
        $this->customerPhone = '';
        $this->notes = '';
        $this->showCart = false;

        $willGeneratePix = $this->orderType === 'entrega' && $this->paymentMethod === 'pix' && $order;

        if (!$willGeneratePix) {
            $this->dispatch('goToMyOrders')->to('public.menu');
        }

        if ($this->orderType === 'entrega' && $order) {
            app(DeliveryNotificationService::class)->newOrderAvailable($order);
        }

        if ($willGeneratePix) {
            $this->generateCheckoutPix($order);
        }

        $this->cashAmount = null;
        $this->resetCart();
        $this->dispatchBrowserCartEvent();

        if (!$this->qrTableNumber) {
            $this->tableNumber = '';
        }

        $this->reset('appliedCoupon', 'discount', 'couponCode', 'usePoints', 'pointsDiscount');

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

        if (!$willGeneratePix) {
            $this->dispatch('notify', message: "Pedido enviado com sucesso{$orderTypeLabel}! Acompanhe o status.");
        }
    }

    protected function generateCheckoutPix(Order $order): void
    {
        $this->generatingPix = true;
        $this->pixQrCode = null;
        $this->pixCopiaECola = null;
        $this->pixOrderId = $order->id;
        $this->pixTxid = null;
        $this->pixPaymentConfirmed = false;
        $this->pixPaymentError = false;
        $this->pixPaymentErrorMsg = '';

        try {
            $txid = 'ped' . $order->id . now()->format('YmdHis') . rand(100, 999);
            $charge = app(TenantEfiBankService::class)->generatePixChargeData(
                $this->tenant, $order->total, $txid, $order->customer_name
            );
            $this->pixCopiaECola = $charge['pixCopiaECola'] ?? null;
            $this->pixTxid = $charge['txid'] ?? $txid;
            $this->pixQrCode = $charge['qrcode'] ?? null;
            if ($this->pixCopiaECola) {
                $this->showPixCheckoutModal = true;
            }
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'Pedido criado! Erro ao gerar PIX: ' . $e->getMessage());
        }

        $this->generatingPix = false;
    }

    public function closePixCheckoutModal(): void
    {
        $this->showPixCheckoutModal = false;
        $this->pixQrCode = null;
        $this->pixCopiaECola = null;
    }

    public function verifyCheckoutPixPayment(): void
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

                $order = Order::where('user_id', Auth::id())->find($this->pixOrderId);
                if ($order && !$order->hasPayment()) {
                    Payment::create([
                        'order_id' => $order->id,
                        'tenant_id' => $order->tenant_id,
                        'amount' => $order->total,
                        'payment_method' => 'pix',
                        'status' => 'paid',
                        'paid_at' => now(),
                        'notes' => 'Pagamento PIX confirmado via API',
                    ]);
                    app(PointsService::class)->grantPointsForOrder($order->fresh());
                }

                $this->showPixCheckoutModal = false;
                $this->dispatch('notify', message: 'Pagamento PIX confirmado! Pedido enviado.');
            }
        } catch (\Throwable $e) {
            $this->pixPaymentError = true;
            $this->pixPaymentErrorMsg = $e->getMessage();
        }
    }

    protected function autoSaveDeliveryAddress(): void
    {
        if (!$this->deliveryAddress) {
            return;
        }

        $userId = Auth::id();

        if ($this->selectedAddressId) {
            return;
        }

        $existingCount = UserAddress::where('user_id', $userId)->count();
        if ($existingCount >= 5) {
            return;
        }

        $alreadySaved = UserAddress::where('user_id', $userId)
            ->where('address', $this->newAddressStreet ?: $this->deliveryAddress)
            ->exists();

        if ($alreadySaved) {
            return;
        }

        UserAddress::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $userId,
            'label' => 'Entrega',
            'address' => $this->newAddressStreet ?: $this->deliveryAddress,
            'number' => $this->newAddressNumber ?: null,
            'complement' => $this->newAddressComplement ?: null,
            'neighborhood' => $this->newAddressNeighborhood ?: null,
            'city' => $this->newAddressCity ?: '',
            'state' => $this->newAddressState ?: '',
            'zipcode' => $this->newAddressZipcode ?: null,
            'reference' => $this->deliveryReference ?: null,
            'is_default' => $existingCount === 0,
        ]);
    }

    protected function dispatchBrowserCartEvent(): void
    {
        $this->dispatch('cart-badge-update', count: $this->calcCartItemsCount());
    }

    protected function syncTableWithSession(): void
    {
        $sessionToken = Session::get("table_token_{$this->tenant->id}");
        if ($sessionToken) {
            $table = Table::where('tenant_id', $this->tenant->id)
                ->where('token', $sessionToken)
                ->first();
            if ($table) {
                $this->tableNumber = $table->number;
                $this->qrTableNumber = $table->number;
                $this->qrTableToken = $table->token;
                return;
            }
            Session::forget("table_token_{$this->tenant->id}");
        }
        $this->tableNumber = '';
        $this->qrTableNumber = null;
        $this->qrTableToken = null;
    }

    public function loadOrderTracking(): void
    {
        $this->verifyTableAccess();

        if (!$this->lastOrderId) {
            $this->orderTracking = null;
            return;
        }

        $order = Order::with('items', 'table', 'payments')->find($this->lastOrderId);

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
                'points_discount' => (float) ($order->points_discount ?? 0),
                'points_used' => (bool) ($order->points_used ?? false),
                'points_spent' => (int) ($order->points_spent ?? 0),
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

    public function clearTable(): void
    {
        $this->tableNumber = '';
        $this->previousTableNumber = '';
        $this->qrTableNumber = null;
        $this->qrTableToken = null;
        $this->showQrModal = false;
        $this->showPixCheckoutModal = false;
        $this->pixQrCode = null;
        $this->pixCopiaECola = null;
        $this->pixPaymentConfirmed = false;
    }

    public function verifyTableAccess(): void
    {
        if (!Auth::check()) return;

        $sessionToken = Session::get("table_token_{$this->tenant->id}");

        if (!$sessionToken) {
            if ($this->tableNumber !== '' && $this->tableNumber !== null) {
                $this->clearTable();
            }
            return;
        }

        $table = Table::where('tenant_id', $this->tenant->id)
            ->where('token', $sessionToken)
            ->first();

        if (!$table) {
            $this->clearTable();
            Session::forget("table_token_{$this->tenant->id}");
            return;
        }

        if ($table->status === 'free') {
            $tableEverHadOrders = Order::where('table_id', $table->id)->exists();

            if ($tableEverHadOrders && !$table->hasOpenBillableOrders()) {
                $this->clearTable();
                Session::forget("table_token_{$this->tenant->id}");
                $this->dispatch('tableFreed')->to('public.menu');
            }
        }
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

    public function updatedOrderType($value): void
    {
        if ($value === 'entrega' && !$this->selectedAddressId && !empty($this->userAddresses)) {
            $default = collect($this->userAddresses)->firstWhere('is_default', true) ?? $this->userAddresses[0];
            if ($default) {
                $this->selectAddress($default['id']);
            }
        }
    }

    public function updatedTableNumber($value): void
    {
        if (!Auth::check()) return;

        if ($this->previousTableNumber && $value !== $this->previousTableNumber) {
            $this->tableNumber = $this->previousTableNumber;
            $this->dispatch('notify', message: 'Mesa fixa. A mesa so pode ser alterada no painel administrativo.');
            return;
        }

        if ($value) {
            $table = Table::where('tenant_id', $this->tenant->id)
                ->where('number', $value)
                ->first();
            if ($table) {
                $this->tableNumber = $table->number;
                $this->qrTableNumber = $table->number;
                $this->qrTableToken = $table->token;
                $this->previousTableNumber = $table->number;
                $this->orderType = 'mesa';
                $this->dispatch('tableSelected', tableId: $table->id)->to('public.menu');
            }
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
            $this->previousTableNumber = $table->number;
            $this->orderType = 'mesa';
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
            'pointsBalance' => $this->pointsBalance,
            'pointsActive' => $this->pointsActive,
            'maxSpendablePoints' => $this->maxSpendablePoints,
        ]);
    }
}
