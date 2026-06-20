<div wire:poll.20s
     x-data="{
         selectedTableId: @entangle('selectedTableId'),
         init() {
             this.$watch('selectedTableId', val => { document.body.style.overflow = val !== null ? 'hidden' : '' });
         }
     }"
     @keydown.window.escape="if (selectedTableId !== null) $wire.closeDetail()">
    {{-- Header Stats --}}
    <div class="flex items-center gap-2 sm:gap-3 mb-4 sm:mb-6 overflow-x-auto pb-2">
        <button wire:click="$set('filter', 'all')"
                class="px-3 sm:px-5 py-1.5 sm:py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all duration-200 whitespace-nowrap border {{ ($filter ?? 'all') === 'all' ? 'bg-amber-500 text-neutral-950 border-amber-500' : 'bg-neutral-900 text-neutral-400 hover:text-white border-neutral-800 hover:border-neutral-700' }}">
            <span class="font-bold">{{ $this->tables->count() }}</span> Todas
        </button>
        <button wire:click="$set('filter', 'free')"
                class="px-3 sm:px-5 py-1.5 sm:py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all duration-200 whitespace-nowrap border {{ ($filter ?? 'all') === 'free' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-neutral-900 text-neutral-400 hover:text-white border-neutral-800 hover:border-neutral-700' }}">
            <span class="font-bold">{{ $this->freeTables->count() }}</span> Livres
        </button>
        <button wire:click="$set('filter', 'occupied')"
                class="px-3 sm:px-5 py-1.5 sm:py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all duration-200 whitespace-nowrap border {{ ($filter ?? 'all') === 'occupied' ? 'bg-red-500/10 text-red-400 border-red-500/30' : 'bg-neutral-900 text-neutral-400 hover:text-white border-neutral-800 hover:border-neutral-700' }}">
            <span class="font-bold">{{ $this->occupiedTables->count() }}</span> Ocupadas
        </button>
        <button wire:click="$set('filter', 'reserved')"
                class="px-3 sm:px-5 py-1.5 sm:py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all duration-200 whitespace-nowrap border {{ ($filter ?? 'all') === 'reserved' ? 'bg-blue-500/10 text-blue-400 border-blue-500/30' : 'bg-neutral-900 text-neutral-400 hover:text-white border-neutral-800 hover:border-neutral-700' }}">
            <span class="font-bold">{{ $this->reservedTables->count() }}</span> Reservadas
        </button>
    </div>

    {{-- New Order Buttons --}}
    <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
        <button wire:click="startDeliveryOrder" wire:loading.attr="disabled"
                 class="flex items-center justify-center gap-2 sm:gap-3 px-3 sm:px-4 py-3 sm:py-4 rounded-2xl border-2 border-dashed border-amber-500/30 bg-amber-500/5 hover:bg-amber-500/10 hover:border-amber-500/50 text-amber-400 font-semibold transition-all duration-200 group disabled:opacity-50">
            <svg class="w-5 sm:w-6 h-5 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-xs sm:text-sm">Novo Pedido - Entrega</span>
            <svg class="w-4 sm:w-5 h-4 sm:h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </button>
        <button wire:click="startPickupOrder" wire:loading.attr="disabled"
                 class="flex items-center justify-center gap-2 sm:gap-3 px-3 sm:px-4 py-3 sm:py-4 rounded-2xl border-2 border-dashed border-purple-500/30 bg-purple-500/5 hover:bg-purple-500/10 hover:border-purple-500/50 text-purple-400 font-semibold transition-all duration-200 group disabled:opacity-50">
            <svg class="w-5 sm:w-6 h-5 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <span class="text-xs sm:text-sm">Novo Pedido - Retirada</span>
            <svg class="w-4 sm:w-5 h-4 sm:h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </button>
    </div>

    @if (auth()->user()->tenant->hasHiddenTables())
        <div class="p-4 rounded-2xl bg-gradient-to-r from-amber-500/10 to-amber-600/5 border border-amber-500/20 mb-4 sm:mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-amber-400">
                        {{ auth()->user()->tenant->hiddenTablesCount() }} mesas ocultas
                    </p>
                    <p class="text-xs text-neutral-400 mt-0.5">Seu plano Gratuito permite gerenciar apenas {{ auth()->user()->tenant->maxTablesAllowed() }} mesas. Faca upgrade para Premium e gerencie todas.</p>
                </div>
                <a href="{{ route('subscription.checkout') }}"
                   class="px-4 py-2 text-xs font-semibold rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all duration-200 hover:scale-105 shrink-0">
                    Fazer Upgrade
                </a>
            </div>
        </div>
    @endif

    {{-- Table Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2 sm:gap-3">
        @forelse ($this->tables as $table)
            @if (($filter ?? 'all') === 'all' || $table->status === ($filter ?? 'all'))
                <button wire:click="selectTable({{ $table->id }})"
                         class="relative p-3 sm:p-4 rounded-2xl border-2 text-center transition-all duration-300 hover:scale-[1.03] active:scale-[0.97] group shadow-lg
                        {{ $table->status === 'free' ? 'bg-gradient-to-b from-emerald-500/5 to-emerald-600/5 border-emerald-500/30 hover:border-emerald-500/60 shadow-emerald-500/5' : '' }}
                        {{ $table->status === 'occupied' ? 'bg-gradient-to-b from-red-500/5 to-red-600/5 border-red-500/30 hover:border-red-500/60 shadow-red-500/5' : '' }}
                        {{ $table->status === 'reserved' ? 'bg-gradient-to-b from-blue-500/5 to-blue-600/5 border-blue-500/30 hover:border-blue-500/60 shadow-blue-500/5' : '' }}
                        {{ $selectedTableId === $table->id ? 'ring-2 ring-amber-500 scale-[1.03]' : '' }}">

                    @if ($table->orders_count > 0)
                        <span class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center shadow-lg shadow-red-500/30 animate-bounce">
                            {{ $table->orders_count }}
                        </span>
                    @endif

                    <span class="absolute top-2 right-2 w-2 h-2 rounded-full
                        {{ $table->status === 'free' ? 'bg-emerald-400 animate-pulse' : '' }}
                        {{ $table->status === 'occupied' ? 'bg-red-400' : '' }}
                        {{ $table->status === 'reserved' ? 'bg-blue-400' : 'bg-emerald-400/50' }}">
                    </span>

                    <div class="flex flex-col items-center gap-2">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl font-black transition-all duration-300 group-hover:scale-110
                            {{ $table->status === 'free' ? 'bg-emerald-500/10 text-emerald-400 group-hover:bg-emerald-500/20' : '' }}
                            {{ $table->status === 'occupied' ? 'bg-red-500/10 text-red-400 group-hover:bg-red-500/20' : '' }}
                            {{ $table->status === 'reserved' ? 'bg-blue-500/10 text-blue-400 group-hover:bg-blue-500/20' : '' }}">
                            {{ $table->number }}
                        </div>

                        <span class="text-[10px] text-neutral-500">Cap. {{ $table->capacity }}</span>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full
                            {{ $table->status === 'free' ? 'bg-emerald-500/10 text-emerald-400' : '' }}
                            {{ $table->status === 'occupied' ? 'bg-red-500/10 text-red-400' : '' }}
                            {{ $table->status === 'reserved' ? 'bg-blue-500/10 text-blue-400' : '' }}">
                            {{ $table->status === 'free' ? 'Livre' : ($table->status === 'occupied' ? 'Ocupada' : 'Reservada') }}
                        </span>
                    </div>
                </button>
            @endif
        @empty
            <div class="col-span-full text-center py-16 text-neutral-500">
                <div class="w-20 h-20 mx-auto mb-4 rounded-3xl bg-neutral-900 flex items-center justify-center">
                    <svg class="w-10 h-10 text-neutral-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                </div>
                <p class="text-lg font-medium text-neutral-300">Nenhuma mesa cadastrada</p>
                <p class="text-sm mt-1">Crie mesas no menu Gerenciar Mesas</p>
                <a href="{{ route('dashboard.tables') }}" class="inline-block mt-4 px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200">
                    Gerenciar Mesas
                </a>
            </div>
        @endforelse
    </div>

    {{-- Order Detail Drawer --}}
    @if ($selectedTableId !== null)
        <div class="fixed inset-0 z-60">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="closeDetail"></div>
            <div class="absolute right-0 top-0 bottom-0 w-full max-w-lg bg-neutral-950 border-l border-neutral-800 shadow-2xl shadow-black/50 overflow-y-auto">
                <div class="p-6">
                    {{-- Header --}}
                    <div class="flex items-center justify-between mb-8">
                        @php $selectedTable = $this->tables->firstWhere('id', $selectedTableId); @endphp
                        <div>
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl font-black
                                    {{ $selectedTable?->status === 'free' ? 'bg-emerald-500/10 text-emerald-400' : '' }}
                                    {{ $selectedTable?->status === 'occupied' ? 'bg-red-500/10 text-red-400' : '' }}
                                    {{ $selectedTable?->status === 'reserved' ? 'bg-blue-500/10 text-blue-400' : '' }}">
                                    {{ $selectedTable?->number }}
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold">Mesa {{ $selectedTable?->number }}</h2>
                                    <p class="text-sm text-neutral-400">Capacidade: {{ $selectedTable?->capacity }} pessoas</p>
                                </div>
                            </div>
                        </div>
                        <button wire:click="closeDetail" class="p-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    @if ($selectedTable && $selectedTable->status === 'occupied')
                        <div class="flex items-center justify-between p-4 mb-4 rounded-xl bg-amber-500/10 border border-amber-500/20">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                <span class="text-sm text-amber-300 font-medium">Compartilhe o cardápio com o cliente</span>
                            </div>
                            <button onclick="navigator.clipboard?.writeText('{{ route('menu.show', ['slug' => $selectedTable->tenant->slug, 'token' => $selectedTable->token]) }}'); this.textContent = 'Copiado!'; setTimeout(() => this.textContent = 'Copiar Link', 2000)"
                                    class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all shrink-0">
                                Copiar Link
                            </button>
                        </div>
                    @endif

                    @if ($orderDetail)
                        @foreach ((array) $orderDetail as $group)
                            <div class="p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800 mb-4 {{ count((array) $orderDetail) > 1 ? 'border-l-4 border-l-amber-500/50' : '' }}">
                                <p class="font-semibold text-lg mb-4">{{ $group['customer_name'] ?? 'Cliente' }}</p>

                                @foreach ($group['orders'] as $detail)
                                    <div class="{{ !$loop->first ? 'mt-4 pt-4 border-t border-neutral-800' : '' }}">
                                        <div class="flex items-center justify-between mb-3">
                                            <p class="text-xs text-neutral-500">Pedido #{{ $detail['id'] }}</p>
                                            <span class="px-3 py-1.5 text-xs font-semibold rounded-full border {{ $detail['statusColor'] }}">
                                                {{ $detail['statusLabel'] }}
                                            </span>
                                        </div>

                                        <div class="space-y-2 mb-3">
                                            @foreach ($detail['items'] as $item)
                                                <div class="flex items-center justify-between p-3 rounded-xl bg-neutral-800/50">
                                                    <div class="flex items-center gap-3">
                                                        <span class="w-6 h-6 rounded-lg bg-neutral-700 flex items-center justify-center text-xs font-bold text-neutral-300">
                                                            {{ $item['quantity'] }}
                                                        </span>
                                                        <span class="text-sm">{{ $item['product_name'] }}</span>
                                                    </div>
                                                    <span class="text-sm text-neutral-300 font-medium">
                                                        R$ {{ number_format($item['price'] * $item['quantity'], 2, ',', '.') }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>

                                        @php
                                            $nextStatus = $detail['nextStatus'] ?? null;
                                            $nextStatusLabel = $detail['nextStatusLabel'] ?? 'Avancar';
                                            $isFechado = in_array($detail['status'], ['fechado', 'cancelado']);
                                        @endphp
                                        <div class="flex flex-wrap gap-2 mt-3">
                                            @if ($nextStatus && !$isFechado)
                                                <button wire:click="advanceOrder({{ $detail['id'] }})"
                                                        class="flex-1 min-w-[120px] py-3 px-4 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.01] active:scale-[0.99]">
                                                    {{ $nextStatusLabel }}
                                                </button>
                                            @endif
                                            @if (in_array($detail['status'], ['novo', 'em_preparo', 'pronto']) && !$isFechado)
                                                <button wire:click="updateOrderStatus({{ $detail['id'] }}, 'cancelado')"
                                                        class="flex-1 min-w-[120px] py-3 px-4 bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 font-semibold rounded-xl transition-all duration-200">
                                                    Cancelar Pedido
                                                </button>
                                            @endif
                                            @if (!$isFechado && in_array($detail['status'], ['entregue']) && ($detail['pending_payment'] ?? 0) > 0)
                                                <button wire:click="openPaymentModal({{ $detail['id'] }})"
                                                        class="flex-1 min-w-[120px] py-3 px-4 bg-emerald-500 hover:bg-emerald-400 text-white font-semibold rounded-xl transition-all duration-200">
                                                    Registrar Pagamento
                                                </button>
                                            @endif
                                            @if (!$isFechado)
                                                <button wire:click="openAddItem({{ $detail['id'] }})"
                                                        class="flex-1 min-w-[120px] py-3 px-4 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 hover:bg-indigo-500/20 font-semibold rounded-xl transition-all duration-200">
                                                    + Adicionar Item
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                <div class="flex items-center justify-between pt-4 mt-4 border-t border-neutral-800">
                                    <span class="text-lg font-bold">Total</span>
                                    <span class="text-xl font-bold text-amber-400">R$ {{ number_format($group['total'], 2, ',', '.') }}</span>
                                </div>

                                @if ($group['has_payment'])
                                    <div class="mt-2 text-xs text-emerald-400 font-medium">Pagamento registrado</div>
                                @endif
                            </div>
                        @endforeach

                        @if ($selectedTable && $selectedTable->status === 'occupied')
                            @php $tableTotal = $orderDetail ? array_sum(array_column($orderDetail, 'total')) : 0; @endphp
                            <button wire:click="openCloseTableModal({{ $selectedTable->id }})" wire:loading.attr="disabled"
                                    class="w-full py-3.5 px-4 bg-emerald-500 hover:bg-emerald-400 text-neutral-950 font-bold rounded-xl transition-all disabled:opacity-50 mb-3">
                                Fechar Conta da Mesa {{ $selectedTable->number }} (R$ {{ number_format($tableTotal, 2, ',', '.') }})
                            </button>
                            <button wire:click="freeTable({{ $selectedTable->id }})"
                                    class="w-full py-3.5 px-4 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 font-semibold rounded-xl transition-all duration-200">
                                Liberar Mesa
                            </button>
                        @endif
                    @else
                        {{-- Empty State --}}
                        <div class="text-center py-16">
                            <div class="w-20 h-20 mx-auto mb-4 rounded-3xl bg-neutral-900 flex items-center justify-center">
                                <svg class="w-10 h-10 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-lg font-semibold text-neutral-300 mb-1">Mesa Disponível</p>
                            <p class="text-sm text-neutral-500 mb-6">Nenhum pedido ativo para esta mesa</p>
                            @if ($selectedTable)
                                <div class="flex flex-col gap-3">
                                    <button wire:click="startOrdering({{ $selectedTable->id }}, '{{ $selectedTable->number }}')"
                                            class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        Novo Pedido - Mesa
                                    </button>
                                    <a href="{{ route('menu.show', ['slug' => $selectedTable->tenant->slug, 'token' => $selectedTable->token]) }}"
                                       class="inline-block px-6 py-3 bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-semibold rounded-xl transition-all duration-200 text-center">
                                        Cardapio Publico
                                    </a>
                                    @if ($selectedTable->status === 'free')
                                        <button wire:click="setTableReserved({{ $selectedTable->id }})"
                                                class="w-full py-3.5 px-4 bg-blue-500/10 text-blue-400 border border-blue-500/20 hover:bg-blue-500/20 font-semibold rounded-xl transition-all duration-200">
                                            Reservar Mesa
                                        </button>
                                    @endif
                                    @if ($selectedTable->status === 'occupied' || $selectedTable->status === 'reserved')
                                        <button wire:click="freeTable({{ $selectedTable->id }})"
                                                class="w-full py-3.5 px-4 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 font-semibold rounded-xl transition-all duration-200">
                                            Liberar Mesa
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Close Table Modal --}}
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
                        <select wire:model="paymentMethod"
                                class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            <option value="pix">PIX</option>
                            <option value="credit_card">Cartão de Crédito</option>
                            <option value="debit_card">Cartão de Débito</option>
                            <option value="cash">Dinheiro</option>
                            <option value="other">Outro</option>
                        </select>
                    </div>
                    @if ($paymentMethod === 'pix' && $pixQrCode)
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
                            @if ($paymentMethod === 'pix' && !$pixQrCode)
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

    {{-- Add Item Modal --}}
    @if ($showAddItemModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-4"
             @keydown.window.escape="$wire.set('showAddItemModal', false)">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="$set('showAddItemModal', false)"></div>
            <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6">
                <h3 class="text-lg font-bold mb-4">Adicionar Item ao Pedido #{{ $addItemOrderId }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">Produto</label>
                        <select wire:model="addItemProductId"
                                class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all">
                            <option value="">Selecione...</option>
                            @foreach ($this->availableProducts as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} - R$ {{ number_format($product->price, 2, ',', '.') }} ({{ $product->category->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">Quantidade</label>
                        <input wire:model="addItemQuantity" type="number" min="1" max="99"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button wire:click="$set('showAddItemModal', false)"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all">Cancelar</button>
                        <button wire:click="addItemToOrder"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold transition-all">Adicionar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- New Order Modal --}}
    @if ($orderingTableId !== null || $orderType === 'entrega' || $orderType === 'retirada')
        <div class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="cancelOrdering"></div>
            <div class="relative w-full sm:max-w-2xl max-h-[90vh] bg-neutral-950 border border-neutral-800 rounded-t-3xl sm:rounded-3xl shadow-2xl shadow-black/50 overflow-hidden flex flex-col">
                <div class="flex items-center justify-between p-5 border-b border-neutral-800 shrink-0">
                    <div class="flex items-center gap-3">
                        @if ($orderType === 'entrega')
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div><h3 class="font-bold">Novo Pedido - Entrega</h3><p class="text-xs text-neutral-400">{{ $this->cartItemsCount }} itens | R$ {{ number_format($this->cartTotal, 2, ',', '.') }}</p></div>
                        @elseif ($orderType === 'retirada')
                            <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            </div>
                            <div><h3 class="font-bold">Novo Pedido - Retirada</h3><p class="text-xs text-neutral-400">{{ $this->cartItemsCount }} itens | R$ {{ number_format($this->cartTotal, 2, ',', '.') }}</p></div>
                        @else
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400 font-black">{{ $orderingTableNumber }}</div>
                            <div><h3 class="font-bold">Novo Pedido - Mesa {{ $orderingTableNumber }}</h3><p class="text-xs text-neutral-400">{{ $this->cartItemsCount }} itens | R$ {{ number_format($this->cartTotal, 2, ',', '.') }}</p></div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($this->cartItemsCount > 0)
                            <button wire:click="placeOrder" wire:loading.attr="disabled"
                                     class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl text-sm transition-all hover:scale-105 active:scale-95 disabled:opacity-50 flex items-center gap-1">
                                <span wire:loading.remove>Finalizar Pedido</span>
                                <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                            </button>
                        @endif
                        <button wire:click="cancelOrdering" class="p-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 transition-colors"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="p-5 border-b border-neutral-800">
                        <div class="flex gap-1 p-1 mb-4 rounded-xl bg-neutral-900 border border-neutral-800 w-fit">
                            @if ($orderingTableId)
                            <button wire:click="$set('orderType', 'mesa')" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $orderType === 'mesa' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg> Mesa
                            </button>
                            @endif
                            <button wire:click="$set('orderType', 'entrega')" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $orderType === 'entrega' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Entrega
                            </button>
                            <button wire:click="$set('orderType', 'retirada')" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $orderType === 'retirada' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg> Retirada
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-neutral-400 mb-1.5">Cliente *</label>
                                <input wire:model="customerName" type="text" placeholder="Nome do cliente"
                                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-900 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                                @error('customerName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-neutral-400 mb-1.5">Telefone</label>
                                <input wire:model="customerPhone" type="text" placeholder="(11) 99999-9999"
                                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-900 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                            </div>
                        </div>

                        @if ($orderType === 'entrega')
                            <div class="mt-3 space-y-3 p-4 rounded-xl bg-neutral-800/30 border border-neutral-700/50" x-data="{
                                viaCepLoading: false,
                                async searchCep() {
                                    let cep = $wire.deliveryAddress.replace(/\D/g, '').slice(0, 8);
                                    if (cep.length !== 8) return;
                                    this.viaCepLoading = true;
                                    try {
                                        let response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                                        let data = await response.json();
                                        if (!data.erro) {
                                            let parts = [data.logradouro || ''];
                                            if (data.bairro) parts.push(data.bairro);
                                            if (data.localidade) parts.push(data.localidade + ' - ' + (data.uf || ''));
                                            $wire.deliveryAddress = parts.join(', ');
                                        }
                                    } catch (e) {}
                                    this.viaCepLoading = false;
                                }
                            }">
                                <div class="relative">
                                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Buscar Endereco de Cliente</label>
                                    <div class="relative">
                                        <input wire:model.live.debounce.300ms="addressSearch" type="text" placeholder="Nome ou email do cliente..."
                                               class="w-full px-3.5 py-2 rounded-xl bg-neutral-900 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                                        @if ($foundAddresses)
                                            <div class="absolute top-full left-0 right-0 mt-1 z-50 bg-neutral-900 border border-neutral-700 rounded-xl overflow-hidden shadow-xl">
                                                @forelse ($foundAddresses as $addr)
                                                    <button type="button" wire:click="selectDeliveryAddress({{ $addr['id'] }})"
                                                            class="w-full text-left px-4 py-3 hover:bg-neutral-800 transition-all border-b border-neutral-800 last:border-0">
                                                        <p class="text-sm font-medium text-neutral-200">{{ $addr['user']['name'] ?? 'Cliente' }}</p>
                                                        <p class="text-xs text-neutral-400 truncate">{{ $addr['full_address'] }}</p>
                                                    </button>
                                                @empty <p class="px-4 py-3 text-xs text-neutral-500">Nenhum endereco encontrado</p> @endforelse
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="relative">
                                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Endereco de Entrega *</label>
                                    <input wire:model="deliveryAddress" type="text" placeholder="Rua, numero, bairro, cidade"
                                           x-on:blur="searchCep"
                                           class="w-full px-3.5 py-2 rounded-xl bg-neutral-900 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                                    <div x-show="viaCepLoading" class="absolute right-2.5 top-8"><svg class="w-4 h-4 animate-spin text-amber-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Ponto de Referencia</label>
                                    <input wire:model="deliveryReference" type="text" placeholder="Proximo a..."
                                           class="w-full px-3.5 py-2 rounded-xl bg-neutral-900 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                                </div>
                            </div>
                        @endif

                        @if ($orderType === 'entrega')
                            <div class="grid grid-cols-2 gap-3 mt-3">
                                <div>
                                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Pagamento</label>
                                    <select wire:model="orderPaymentMethod"
                                            class="w-full px-4 py-2.5 rounded-xl bg-neutral-900 border border-neutral-700 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all">
                                        <option value="pix">Pix</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Observacao</label>
                                    <input wire:model="notes" type="text" placeholder="Observacoes..."
                                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-900 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                                </div>
                            </div>

                            @if ($orderPaymentMethod === 'cash')
                                <div class="p-3 rounded-xl bg-neutral-800/50 border border-neutral-700 mt-3">
                                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Valor em Dinheiro</label>
                                    <input wire:model="cashAmount" type="number" step="0.01" min="0"
                                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-900 border border-neutral-700 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all"
                                           placeholder="Quanto vai pagar?">
                                    @php $change = $cashAmount ? $cashAmount - $this->cartTotal : 0; @endphp
                                    @if ($cashAmount && $change > 0)
                                        <p class="mt-2 text-sm text-emerald-400">
                                            Troco: R$ {{ number_format($change, 2, ',', '.') }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        @else
                            <div class="mt-3">
                                <label class="block text-xs font-medium text-neutral-400 mb-1.5">Observacao</label>
                                <input wire:model="notes" type="text" placeholder="Observacoes..."
                                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-900 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                            </div>
                        @endif
                    </div>

                    @if (!empty($cartItems))
                        <div class="p-5 border-b border-neutral-800">
                            <h4 class="text-sm font-semibold text-neutral-400 mb-3 uppercase tracking-wider">Itens do Pedido</h4>
                            <div class="space-y-2">
                                @foreach ($cartItems as $key => $item)
                                    <div class="flex items-center gap-3 p-3 rounded-xl bg-neutral-900/50 border border-neutral-800">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium">{{ $item['product_name'] }}</p>
                                            @if (!empty($item['options']))
                                                <div class="flex flex-wrap gap-1 mt-0.5">
                                                    @foreach ($item['options'] as $opt)<span class="text-[10px] text-neutral-500 bg-neutral-800 px-1.5 py-0.5 rounded">{{ $opt['option_name'] ?? '' }}</span>@endforeach
                                                </div>
                                            @endif
                                            <p class="text-xs text-neutral-500 mt-0.5">R$ {{ number_format($item['unit_price'], 2, ',', '.') }} un.</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button wire:click="updateCartQuantity('{{ $key }}', -1)" class="w-7 h-7 rounded-lg bg-neutral-800 hover:bg-neutral-700 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg></button>
                                            <span class="w-6 text-center text-sm font-medium">{{ $item['quantity'] }}</span>
                                            <button wire:click="updateCartQuantity('{{ $key }}', 1)" class="w-7 h-7 rounded-lg bg-neutral-800 hover:bg-neutral-700 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg></button>
                                        </div>
                                        <p class="text-sm font-medium w-16 text-right">R$ {{ number_format($item['unit_price'] * $item['quantity'], 2, ',', '.') }}</p>
                                        <button wire:click="removeFromCart('{{ $key }}')" class="p-1.5 rounded-lg text-neutral-500 hover:text-red-400 hover:bg-red-500/10 transition-colors"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex items-center justify-between pt-3 mt-3 border-t border-neutral-800">
                                <span class="text-sm font-medium">Total</span>
                                <span class="text-lg font-bold text-amber-400">R$ {{ number_format($this->cartTotal, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="p-5">
                        <h4 class="text-sm font-semibold text-neutral-400 mb-3 uppercase tracking-wider">Cardapio</h4>
                        <div class="flex gap-2 overflow-x-auto pb-3 mb-4 scrollbar-hide">
                            @foreach ($this->categories as $cat)
                                <a href="#staff-menu-cat-{{ $cat->slug }}" class="px-4 py-2 rounded-full text-xs font-medium whitespace-nowrap bg-neutral-800 text-neutral-300 hover:bg-neutral-700 transition-all">{{ $cat->name }}</a>
                            @endforeach
                        </div>
                        <div class="space-y-6 max-h-[40vh] overflow-y-auto">
                            @foreach ($this->categories as $category)
                                <div id="staff-menu-cat-{{ $category->slug }}">
                                    <h5 class="text-sm font-bold text-neutral-300 mb-3">{{ $category->name }}</h5>
                                    <div class="grid grid-cols-1 gap-2">
                                        @foreach ($category->products as $product)
                                            <button wire:click="showProduct({{ $product->id }})"
                                                    class="w-full text-left p-3 rounded-xl bg-neutral-900 border border-neutral-800 hover:border-amber-500/30 transition-all group">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-12 h-12 rounded-lg overflow-hidden shrink-0 bg-neutral-800">
                                                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" loading="lazy">
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium group-hover:text-amber-400 transition-colors">{{ $product->name }}</p>
                                                        <p class="text-xs text-neutral-400 mt-0.5 line-clamp-1">{{ $product->description }}</p>
                                                    </div>
                                                    <p class="text-sm font-bold text-amber-400">R$ {{ number_format($product->price, 2, ',', '.') }}</p>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Product Detail Modal --}}
    @if ($this->selectedProductModel)
        <div class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center"
             x-data x-init="$nextTick(() => document.body.style.overflow = 'hidden')"
             @keydown.window.escape="$wire.closeProduct()">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="closeProduct"></div>
            <div class="relative w-full sm:max-w-lg max-h-[85vh] bg-neutral-900 border border-neutral-800 rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-xl font-bold">{{ $this->selectedProductModel->name }}</h3>
                            <p class="text-2xl font-bold text-amber-400 mt-2">R$ {{ number_format($this->selectedProductModel->price, 2, ',', '.') }}</p>
                        </div>
                        <button wire:click="closeProduct" class="p-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 transition-colors"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    @if ($this->selectedProductModel->description)<p class="text-sm text-neutral-400 mb-6">{{ $this->selectedProductModel->description }}</p>@endif
                    @if ($this->selectedProductModel->image_url)<img src="{{ $this->selectedProductModel->imageUrl() }}" alt="{{ $this->selectedProductModel->name }}" class="w-full h-48 object-cover rounded-xl mb-6">@endif
                    <form @submit.prevent="
                        const form = $event.target;
                        const options = [];
                        form.querySelectorAll('select, input[type=radio]:checked, input[type=checkbox]:checked').forEach(el => {
                            if (el.value && el.name) { options.push(JSON.parse(el.value)); }
                        });
                        $wire.addToCart({{ $this->selectedProductModel->id }}, @js($this->selectedProductModel->name), {{ $this->selectedProductModel->price }}, options, 1);
                        $wire.closeProduct();
                    ">
                        @foreach ($this->selectedProductModel->attributes as $attribute)
                            <div class="mb-5">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="font-medium text-sm">{{ $attribute->name }}</label>
                                    @if ($attribute->is_required)<span class="text-xs text-red-400">*Obrigatorio</span>@endif
                                </div>
                                @if ($attribute->type === 'single')
                                    <div class="space-y-2">
                                        @foreach ($attribute->options as $option)
                                            <label class="flex items-center justify-between p-3 rounded-xl bg-neutral-800/50 border border-neutral-700/50 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/5 transition-all cursor-pointer">
                                                <div class="flex items-center gap-3">
                                                    <input type="radio" name="attr_{{ $attribute->id }}"
                                                           value='{{ json_encode(['attribute_id' => $attribute->id, 'attribute_name' => $attribute->name, 'option_id' => $option->id, 'option_name' => $option->name, 'price_additional' => $option->price_additional]) }}'
                                                           class="text-amber-500 focus:ring-amber-500 bg-neutral-800 border-neutral-600" {{ $loop->first ? 'checked' : '' }}>
                                                    <span class="text-sm">{{ $option->name }}</span>
                                                </div>
                                                @if ($option->price_additional > 0)<span class="text-xs text-amber-400">+R$ {{ number_format($option->price_additional, 2, ',', '.') }}</span>@endif
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($attribute->options as $option)
                                            <label class="flex items-center justify-between p-3 rounded-xl bg-neutral-800/50 border border-neutral-700/50 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/5 transition-all cursor-pointer">
                                                <div class="flex items-center gap-3">
                                                    <input type="checkbox" name="attr_{{ $attribute->id }}[]"
                                                           value='{{ json_encode(['attribute_id' => $attribute->id, 'attribute_name' => $attribute->name, 'option_id' => $option->id, 'option_name' => $option->name, 'price_additional' => $option->price_additional]) }}'
                                                           class="rounded text-amber-500 focus:ring-amber-500 bg-neutral-800 border-neutral-600">
                                                    <span class="text-sm">{{ $option->name }}</span>
                                                </div>
                                                @if ($option->price_additional > 0)<span class="text-xs text-amber-400">+R$ {{ number_format($option->price_additional, 2, ',', '.') }}</span>@endif
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        <button type="submit" class="w-full py-3.5 px-4 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">Adicionar ao Pedido</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
