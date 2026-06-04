<div class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="closeDetail"></div>
    <div class="absolute right-0 top-0 bottom-0 w-full max-w-lg bg-neutral-950 border-l border-neutral-800 shadow-2xl shadow-black/50 overflow-y-auto">
        <div class="p-6">
            @php $selTable = $tables->firstWhere('id', $selectedTableId); @endphp
            <div class="flex items-start justify-between mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl font-black
                        {{ $selTable?->status === 'free' ? 'bg-emerald-500/10 text-emerald-400' : '' }}
                        {{ $selTable?->status === 'occupied' ? 'bg-red-500/10 text-red-400' : '' }}
                        {{ $selTable?->status === 'reserved' ? 'bg-blue-500/10 text-blue-400' : '' }}">
                        {{ $selTable?->number }}
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">Mesa {{ $selTable?->number }}</h2>
                        <p class="text-sm text-neutral-400">Capacidade: {{ $selTable?->capacity }} pessoas</p>
                        @if (is_array($orderDetail) && count($orderDetail) > 1)
                            <p class="text-xs text-amber-400 mt-1">{{ count($orderDetail) }} pedidos ativos</p>
                        @endif
                    </div>
                </div>
                <button wire:click="closeDetail" class="p-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            @if ($orderDetail)
                @foreach ((array) $orderDetail as $detail)
                    <div class="p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800 mb-4 {{ count((array) $orderDetail) > 1 ? 'border-l-4 border-l-amber-500/50' : '' }}">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="font-semibold text-lg">{{ $detail['customer_name'] ?? 'Cliente' }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-neutral-500">Pedido #{{ $detail['id'] }}</span>
                                    <span class="text-xs text-neutral-500">&middot;</span>
                                    <span class="text-xs text-neutral-500">{{ $detail['created_at'] }}</span>
                                    <span class="text-xs text-neutral-500">&middot;</span>
                                    <span class="text-xs font-semibold px-1.5 py-0.5 rounded-full {{ $detail['type'] === 'mesa' ? 'bg-blue-500/20 text-blue-400' : ($detail['type'] === 'entrega' ? 'bg-green-500/20 text-green-400' : 'bg-purple-500/20 text-purple-400') }}">
                                        {{ $detail['type'] === 'mesa' ? 'Mesa' : ($detail['type'] === 'entrega' ? 'Entrega' : 'Retirada') }}
                                    </span>
                                </div>
                            </div>
                            <span class="px-3 py-1.5 text-xs font-semibold rounded-full border {{ $detail['statusColor'] }}">{{ $detail['statusLabel'] }}</span>
                        </div>
                        @if ($detail['customer_phone'])
                            <div class="flex items-center gap-2 text-xs text-neutral-400 mb-4">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span>{{ $detail['customer_phone'] }}</span>
                            </div>
                        @endif
                        <div class="space-y-2 mb-4">
                            @foreach ($detail['items'] as $item)
                                <div class="flex items-center justify-between p-3 rounded-xl bg-neutral-800/50">
                                    <div class="flex items-center gap-3">
                                        <span class="w-6 h-6 rounded-lg bg-neutral-700 flex items-center justify-center text-xs font-bold text-neutral-300">{{ $item['quantity'] }}</span>
                                        <span class="text-sm">{{ $item['product_name'] }}</span>
                                    </div>
                                    <span class="text-sm text-neutral-300 font-medium">R$ {{ number_format($item['subtotal'], 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t border-neutral-800">
                            <span class="text-lg font-bold">Total</span>
                            <span class="text-xl font-bold text-amber-400">R$ {{ number_format($detail['total'], 2, ',', '.') }}</span>
                        </div>
                        @php
                            $nextStatus = $detail['nextStatus'] ?? null;
                            $nextStatusLabel = $detail['nextStatusLabel'] ?? 'Avancar';
                            $isFechado = in_array($detail['status'], ['fechado', 'cancelado']);
                            $hasPayment = $detail['has_payment'] ?? false;
                        @endphp
                        <div class="flex flex-wrap gap-2 mt-4">
                            @if ($nextStatus && !$isFechado)
                                <button wire:click="updateOrderStatus({{ $detail['id'] }}, '{{ $nextStatus }}')" wire:loading.attr="disabled"
                                                                        class="flex-1 min-w-[120px] py-3 px-4 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all hover:scale-[1.01] active:scale-[0.99] disabled:opacity-50">{{ $nextStatusLabel }}</button>
                            @endif
                            @if (in_array($detail['status'], ['novo', 'em_preparo', 'pronto']) && !$isFechado)
                                <button wire:click="updateOrderStatus({{ $detail['id'] }}, 'cancelado')" wire:loading.attr="disabled"
                                                                        class="flex-1 min-w-[120px] py-3 px-4 bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 font-semibold rounded-xl transition-all disabled:opacity-50">Cancelar Pedido</button>
                            @endif
                            @if (!$isFechado && in_array($detail['status'], ['entregue']) && ($detail['pending_payment'] ?? 0) > 0)
                                <button wire:click="openPaymentModal({{ $detail['id'] }})" wire:loading.attr="disabled"
                                                                        class="flex-1 min-w-[120px] py-3 px-4 bg-emerald-500 hover:bg-emerald-400 text-white font-semibold rounded-xl transition-all disabled:opacity-50">Registrar Pagamento</button>
                            @endif
                        </div>
                        @if ($detail['has_payment'] ?? false)
                            <div class="mt-2 text-xs text-emerald-400 font-medium">Pagamento registrado</div>
                        @endif
                    </div>
                @endforeach

                @if ($selTable)
                    <div class="flex flex-wrap gap-2 mb-4">
                        @if ($selTable->status !== 'free')
                            <button wire:click="setTableFree({{ $selTable->id }})" wire:loading.attr="disabled"
                                    class="flex-1 min-w-[80px] py-2 px-3 text-xs font-semibold rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition-all">Desocupar</button>
                        @endif
                        @if ($selTable->status !== 'occupied')
                            <button wire:click="setTableOccupied({{ $selTable->id }})" wire:loading.attr="disabled"
                                    class="flex-1 min-w-[80px] py-2 px-3 text-xs font-semibold rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-all">Ocupar</button>
                        @endif
                        @if ($selTable->status !== 'reserved')
                            <button wire:click="setTableReserved({{ $selTable->id }})" wire:loading.attr="disabled"
                                    class="flex-1 min-w-[80px] py-2 px-3 text-xs font-semibold rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20 hover:bg-blue-500/20 transition-all">Reservar</button>
                        @endif
                    </div>
                    @if ($selTable->status === 'occupied')
                        @php
                            $allOrders = \App\Models\Order::with('payments')
                                ->where('table_id', $selTable->id)
                                ->whereIn('status', ['novo', 'em_preparo', 'pronto', 'saiu_entrega', 'entregue'])
                                ->get();
                            $totalOrders = (float) $allOrders->sum('total');
                            $paidOrders = (float) $allOrders->sum(fn($o) => (float) $o->payments->where('status', 'paid')->sum('amount'));
                            $pendingOrders = max(0, $totalOrders - $paidOrders);
                            $hasNotDelivered = $allOrders->contains(fn($o) => !in_array($o->status, ['entregue']));
                        @endphp
                        @if ($allOrders->isNotEmpty())
                            @if ($hasNotDelivered)
                                <div class="text-center mb-3">
                                    <p class="text-xs text-amber-400">Ha pedidos em andamento nesta mesa</p>
                                </div>
                            @endif
                            <button wire:click="openCloseTableModal({{ $selTable->id }})" wire:loading.attr="disabled"
                                     class="w-full py-3.5 px-4 bg-emerald-500 hover:bg-emerald-400 text-neutral-950 font-bold rounded-xl transition-all disabled:opacity-50 mb-3">
                                Fechar Conta da Mesa {{ $selTable->number }} (R$ {{ number_format($totalOrders, 2, ',', '.') }})
                            </button>
                        @endif
                        <button wire:click="freeTable({{ $selTable->id }})" wire:loading.attr="disabled"
                                 class="w-full py-3.5 px-4 bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 font-semibold rounded-xl transition-all disabled:opacity-50">Liberar Mesa</button>
                    @endif
                @endif
            @else
                <div class="text-center py-12">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-3xl bg-emerald-500/10 flex items-center justify-center">
                        <svg class="w-10 h-10 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-lg font-semibold text-neutral-300 mb-2">Mesa {{ $selTable?->number }} disponivel</p>
                    <p class="text-sm text-neutral-500 mb-6">Nenhum pedido ativo para esta mesa</p>
                    <button wire:click="startOrdering({{ $selTable?->id }}, '{{ $selTable?->number }}')" wire:loading.attr="disabled"
                             class="inline-flex items-center gap-2 px-6 py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Novo Pedido
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Close Table Payment Modal --}}
@if ($showCloseTableModal)
    <div class="fixed inset-0 z-[90] flex items-center justify-center p-4"
         @keydown.window.escape="$wire.closeCloseTableModal()">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closeCloseTableModal"></div>
        <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6">
            <h3 class="text-lg font-bold mb-2">Fechar Conta da Mesa</h3>
            <div class="mb-6 p-4 rounded-xl bg-neutral-800/50 border border-neutral-700">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-neutral-400">Total da Mesa</span>
                    <span class="text-2xl font-bold text-amber-400">R$ {{ number_format($closeTableTotal, 2, ',', '.') }}</span>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Forma de Pagamento</label>
                    <select wire:model="closeTablePaymentMethod"
                            class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <option value="pix">PIX</option>
                        <option value="credit_card">Cartão de Crédito</option>
                        <option value="debit_card">Cartão de Débito</option>
                        <option value="cash">Dinheiro</option>
                        <option value="other">Outro</option>
                    </select>
                </div>
                @if ($closeTablePaymentMethod === 'pix' && $pixQrCode)
                    <x-pix-qr-code
                        :qr-code="$pixQrCode"
                        :copia-e-cola="$pixCopiaECola"
                        :id="'close-table-' . $closeTableId"
                        :loading="false" />
                @endif
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Observacao (opcional)</label>
                    <input wire:model="closeTablePaymentNotes" type="text" placeholder="Observacao"
                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                </div>
                <div class="flex gap-3 pt-2">
                    <button wire:click="closeCloseTableModal"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all">
                        Cancelar
                    </button>
                    <div class="flex-1 flex gap-2">
                        @if ($closeTablePaymentMethod === 'pix' && !$pixQrCode)
                            <button wire:click="generateCloseTablePix" wire:loading.attr="disabled"
                                    class="flex-1 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold transition-all flex items-center justify-center gap-2">
                                @if ($generatingPix)
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                @endif
                                Gerar QR Code PIX
                            </button>
                        @endif
                        <button wire:click="confirmCloseTableBill" wire:loading.class="opacity-50"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-neutral-950 font-semibold transition-all">
                            Confirmar Pagamento (R$ {{ number_format($closeTableTotal, 2, ',', '.') }})
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Payment Modal --}}
@if ($showPaymentModal)
    <div class="fixed inset-0 z-[80] flex items-center justify-center p-4"
         @keydown.window.escape="$wire.closePaymentModal()">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closePaymentModal"></div>
        <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6">
            <h3 class="text-lg font-bold mb-4">Registrar Pagamento</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Valor (R$)</label>
                    <input wire:model="paymentAmount" type="number" step="0.01" min="0.01" readonly
                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white text-sm transition-all opacity-75">
                    @error('paymentAmount') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Forma de Pagamento</label>
                    <select wire:model="paymentMethodInput"
                            class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <option value="pix">PIX</option>
                        <option value="credit_card">Cartão de Crédito</option>
                        <option value="debit_card">Cartão de Débito</option>
                        <option value="cash">Dinheiro</option>
                        <option value="other">Outro</option>
                    </select>
                </div>
                @if ($paymentMethodInput === 'pix' && $pixQrCode)
                    <x-pix-qr-code
                        :qr-code="$pixQrCode"
                        :copia-e-cola="$pixCopiaECola"
                        :id="'pay-' . $paymentOrderId"
                        :loading="false" />
                @endif
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Observacao (opcional)</label>
                    <input wire:model="paymentNotes" type="text" placeholder="Observacao"
                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                </div>
                <div class="flex gap-3 pt-2">
                    <button wire:click="closePaymentModal"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all">
                        Cancelar
                    </button>
                    <div class="flex-1 flex gap-2">
                        @if ($paymentMethodInput === 'pix' && !$pixQrCode)
                            <button wire:click="generatePaymentPix" wire:loading.attr="disabled"
                                    class="flex-1 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold transition-all flex items-center justify-center gap-2">
                                @if ($generatingPix)
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                @endif
                                Gerar QR Code PIX
                            </button>
                        @endif
                        <button wire:click="registerPayment" wire:loading.class="opacity-50"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-neutral-950 font-semibold transition-all">
                            Confirmar Pagamento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
