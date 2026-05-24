<div wire:poll.10s
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

    @if (auth()->user()->tenant->hasHiddenTables())
        <div class="p-4 rounded-2xl bg-gradient-to-r from-amber-500/10 to-amber-600/5 border border-amber-500/20">
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

                    @if ($orderDetail)
                        @foreach ((array) $orderDetail as $detail)
                            <div class="p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800 mb-4 {{ count((array) $orderDetail) > 1 ? 'border-l-4 border-l-amber-500/50' : '' }}">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <p class="font-semibold text-lg">{{ $detail['customer_name'] ?? 'Cliente' }}</p>
                                        <p class="text-xs text-neutral-500 mt-0.5">Pedido #{{ $detail['id'] }}</p>
                                    </div>
                                    <span class="px-3 py-1.5 text-xs font-semibold rounded-full border {{ $detail['statusColor'] }}">
                                        {{ $detail['statusLabel'] }}
                                    </span>
                                </div>

                                <div class="space-y-2 mb-4">
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
                                </div>
                                @if ($hasPayment)
                                    <div class="mt-2 text-xs text-emerald-400 font-medium">Pagamento registrado</div>
                                @endif
                            </div>
                        @endforeach

                        @if ($selectedTable && $selectedTable->status === 'occupied')
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
                                <a href="{{ route('menu.show', ['slug' => $selectedTable->tenant->slug, 'token' => $selectedTable->token]) }}"
                                   target="_blank"
                                   class="inline-block px-6 py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200">
                                    Fazer Pedido
                                </a>
                            @endif
                        </div>
                    @endif
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
                        <input wire:model="paymentAmount" type="number" step="0.01" min="0.01"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all">
                        @error('paymentAmount') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">Forma de Pagamento</label>
                        <select wire:model="paymentMethod"
                                class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all">
                            <option value="pix">PIX</option>
                            <option value="credit_card">Cartao de Credito</option>
                            <option value="debit_card">Cartao de Debito</option>
                            <option value="cash">Dinheiro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">Observacao (opcional)</label>
                        <input wire:model="paymentNotes" type="text" placeholder="Troco para 100, etc"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button wire:click="closePaymentModal"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all">
                            Cancelar
                        </button>
                        <button wire:click="registerPayment"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-neutral-950 font-semibold transition-all">
                            Confirmar Pagamento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
