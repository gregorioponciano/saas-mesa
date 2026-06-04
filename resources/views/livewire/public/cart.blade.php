<div x-data="{ open: false }"
     x-init="
         $watch('open', val => document.body.style.overflow = val ? 'hidden' : '');
         $wire.$on('cartUpdated', () => open = true);
         document.addEventListener('open-cart', () => open = true);
     "
     @cart-cleared.window="open = false"
     @keydown.window.escape="open = false">

    {{-- Floating Cart Button --}}
    <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40"
         x-data="{ pulse: false }"
         x-show="!open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-20 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-init="$wire.$on('cartUpdated', () => { pulse = true; setTimeout(() => pulse = false, 600); })"
         x-cloak>
        <button @click="open = !open"
                class="flex items-center gap-3 px-6 py-3.5 rounded-full font-semibold shadow-2xl transition-all duration-200 hover:scale-105 active:scale-95"
                :class="pulse ? 'bg-emerald-500 text-neutral-950 shadow-emerald-500/40 scale-110' : 'bg-gradient-to-r from-amber-500 to-amber-400 text-neutral-950 shadow-amber-500/25'">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
            </svg>
            <span class="hidden xs:inline">
                @php $firstItem = collect($cartItems)->first(); @endphp
                @if (!empty($cartItems))
                    {{ $firstItem['product_name'] }}
                @elseif ($lastOrderId && $orderTracking)
                    Pedido #{{ $lastOrderId }}
                @else
                    Ver Carrinho
                @endif
            </span>
            <span class="flex items-center justify-center min-w-[22px] h-[22px] rounded-full bg-neutral-950 text-amber-400 text-xs font-bold px-1">
                {{ $itemsCount > 0 ? $itemsCount : ($lastOrderId ? '#' . $lastOrderId : '0') }}
            </span>
            <span class="text-sm opacity-80">
                @if (!empty($cartItems))
                    R$ {{ number_format($total, 2, ',', '.') }}
                @elseif ($lastOrderId && $orderTracking)
                    {{ $orderTracking['statusLabel'] }}
                @else
                    R$ 0,00
                @endif
            </span>
        </button>
    </div>

    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="transition-opacity duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm"
         @click="open = false"
         x-cloak></div>

    {{-- Cart Drawer --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed right-0 top-0 h-full w-full max-w-md z-50 bg-neutral-900 border-l border-neutral-800 shadow-2xl flex flex-col"
         x-cloak>

        {{-- Header --}}
        <div class="flex items-center justify-between p-6 border-b border-neutral-800 shrink-0">
            <h2 class="text-xl font-bold">
                @if (!empty($cartItems) && $lastOrderId && $orderTracking)
                    Carrinho & Pedidos
                @elseif (!empty($cartItems))
                    Seu Pedido
                @elseif ($lastOrderId && $orderTracking)
                    Pedido #{{ $lastOrderId }}
                @else
                    Carrinho
                @endif
            </h2>
            <button @click="open = false"
                    class="p-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Scrollable Content --}}
        <div class="flex-1 overflow-y-auto p-6" wire:poll.15s="loadOrderTracking">

            {{-- Cart Items --}}
            @if (!empty($cartItems))
                <div class="space-y-3 mb-6">
                    <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Itens para enviar</p>
                    @foreach ($cartItems as $key => $item)
                        <div class="flex items-center gap-3 p-4 rounded-2xl bg-neutral-800/50 border border-neutral-800">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-sm">{{ $item['product_name'] }}</p>
                                @if (!empty($item['options']))
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach ($item['options'] as $opt)
                                            <span class="text-xs text-neutral-400 bg-neutral-800 px-1.5 py-0.5 rounded">{{ $opt['option_name'] ?? '' }}{{ isset($opt['price_additional']) && $opt['price_additional'] > 0 ? ' +R$'.number_format($opt['price_additional'], 2, ',', '.') : '' }}</span>
                                        @endforeach
                                    </div>
                                    @if (($item['attribute_price_total'] ?? 0) > 0)
                                        <p class="text-xs text-amber-400/70 mt-1">Base personalização: +R$ {{ number_format($item['attribute_price_total'], 2, ',', '.') }}</p>
                                    @endif
                                @endif
                                <p class="text-xs text-neutral-400 mt-1">R$ {{ number_format($item['unit_price'], 2, ',', '.') }} un.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button wire:click="updateQuantity('{{ $key }}', -1)"
                                        wire:loading.attr="disabled"
                                        class="w-7 h-7 rounded-lg bg-neutral-800 hover:bg-neutral-700 flex items-center justify-center transition-colors disabled:opacity-30">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                </button>
                                <span class="w-6 text-center text-sm font-medium">{{ $item['quantity'] }}</span>
                                <button wire:click="updateQuantity('{{ $key }}', 1)"
                                        wire:loading.attr="disabled"
                                        class="w-7 h-7 rounded-lg bg-neutral-800 hover:bg-neutral-700 flex items-center justify-center transition-colors disabled:opacity-30">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                </button>
                            </div>
                            <p class="text-sm font-medium w-20 text-right">R$ {{ number_format($item['unit_price'] * $item['quantity'], 2, ',', '.') }}</p>
                            <button wire:click="removeItem('{{ $key }}')"
                                    wire:loading.attr="disabled"
                                    class="p-1.5 rounded-lg text-neutral-500 hover:text-red-400 hover:bg-red-500/10 transition-colors disabled:opacity-30">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    @endforeach
                </div>

                {{-- Order Type Toggle --}}
                <div class="mb-4 p-1 rounded-xl bg-neutral-800/50 border border-neutral-700 flex gap-1">
                    <button wire:click="$set('orderType', 'mesa')"
                            wire:loading.attr="disabled"
                            class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-all disabled:opacity-50 {{ $orderType === 'mesa' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Mesa
                    </button>
                    <button wire:click="$set('orderType', 'entrega')"
                            wire:loading.attr="disabled"
                            class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-all disabled:opacity-50 {{ $orderType === 'entrega' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2-1m2 1l2-1m2 1l2-1m2-2v2a1 1 0 001 1h2m0 0a1 1 0 100 2m-2-2a1 1 0 110 2m-10-4h.01M16 12h4m0 0l-3-3m3 3l-3 3"/>
                        </svg>
                        Entrega
                    </button>
                    <button wire:click="$set('orderType', 'retirada')"
                            wire:loading.attr="disabled"
                            class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-all disabled:opacity-50 {{ $orderType === 'retirada' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        Retirada
                    </button>
                </div>

                @if ($orderType === 'mesa')
                    {{-- Table Selection --}}
                    @php $tableLocked = $this->hasTableLocked(); @endphp
                    <div class="mb-4 p-3 rounded-xl bg-neutral-800/50 border border-neutral-700">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 {{ $tableLocked ? 'text-amber-500' : 'text-neutral-400' }} shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                @if ($tableLocked)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                @endif
                            </svg>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-neutral-400 mb-1">Mesa</label>
                                @if ($qrTableNumber)
                                    <button wire:click="showQrCode" class="flex items-center gap-2 text-sm hover:opacity-80 transition-opacity text-left">
                                        <span class="text-amber-400 font-bold">Mesa {{ $qrTableNumber }}</span>
                                        <span class="text-xs text-neutral-500">(via QR Code)</span>
                                        <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M12 4h8M4 8h8"/>
                                        </svg>
                                    </button>
                                @else
                                    @if ($tableLocked)
                                        <div class="flex items-center gap-2 text-sm text-neutral-500">
                                            <span class="text-amber-400 font-bold">Mesa {{ $tableNumber }}</span>
                                            <span class="text-xs">Mesa fixa — altere apenas no painel</span>
                                        </div>
                                    @else
                                    <select wire:model="tableNumber"
                                            class="w-full px-3 py-1.5 rounded-lg bg-neutral-900 border border-neutral-700 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all">
                                        <option value="">Selecione uma mesa</option>
                                        @foreach ($freeTables as $table)
                                            <option value="{{ $table->number }}">Mesa {{ $table->number }} (Cap. {{ $table->capacity }})</option>
                                        @endforeach
                                    </select>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                  @elseif ($orderType === 'entrega')
                      {{-- Delivery Address --}}
                      <div class="mb-4 space-y-3 p-3 rounded-xl bg-neutral-800/50 border border-neutral-700">
                          <div class="flex items-center gap-2">
                              <svg class="w-5 h-5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                              </svg>
                              <span class="text-xs font-medium text-neutral-400">Endereco de Entrega</span>
                          </div>

                          @if (Auth::check() && !empty($userAddresses))
                              <div class="space-y-2">
                                  @foreach ($userAddresses as $address)
                                      <div class="flex items-center gap-3 p-3 rounded-xl bg-neutral-900/50 border border-neutral-800 cursor-pointer hover:bg-neutral-800/50 transition-all"
                                           wire:click="selectAddress({{ $address['id'] }})"
                                           x-data="{ selected: {{ $selectedAddressId == $address['id'] ? 'true' : 'false' }} }"
                                           :class="{ 'ring-2 ring-amber-500': selected }">
                                          <div class="flex-1 min-w-0">
                                              <div class="flex items-center gap-2">
                                                  <p class="font-medium text-sm">{{ $address['label'] }}</p>
                                                  @if ($address['is_default'])
                                                      <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-500/20 text-amber-400">Padrao</span>
                                                  @endif
                                              </div>
                                              <p class="text-xs text-neutral-400 truncate">{{ $address['full_address'] }}</p>
                                          </div>
                                          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                               :class="{ 'text-amber-400': selected, 'text-neutral-600': !selected }">
                                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                          </svg>
                                      </div>
                                  @endforeach
                              </div>
                          @endif

                          <button wire:click="openAddressModal" type="button"
                                  class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 border-dashed border-neutral-700 text-neutral-400 hover:text-amber-400 hover:border-amber-500/50 transition-all text-sm font-medium">
                              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                              {{ $selectedAddressId ? 'Trocar Endereco' : 'Adicionar Endereco de Entrega' }}
                          </button>

                          @if ($selectedAddressId)
                              <div class="flex items-center gap-2 p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                                  <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                  <span class="text-xs text-emerald-400">Endereco selecionado</span>
                              </div>
                          @endif
                      </div>
                 @else
                     {{-- Pickup (Retirada) -- no additional info needed --}}
                     <div class="mb-4 p-3 rounded-xl bg-neutral-800/50 border border-neutral-700">
                         <div class="flex items-center gap-2">
                             <svg class="w-5 h-5 text-purple-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                             </svg>
                             <span class="text-xs font-medium text-purple-400">Retirada no Local</span>
                         </div>
                         <p class="text-sm text-neutral-400 mt-2">Você retirará o pedido no estabelecimento.</p>
                     </div>
                 @endif

                {{-- Customer Info --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">Nome *</label>
                        <input wire:model="customerName" type="text" placeholder="Seu nome"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all">
                        @error('customerName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Coupon --}}
                    <div class="p-3 rounded-xl bg-neutral-800/50 border border-neutral-700">
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">Cupom de Desconto</label>
                        @if ($appliedCoupon)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-emerald-400">{{ $appliedCoupon['code'] }}</span>
                                    <span class="text-xs text-neutral-400">
                                        {{ $appliedCoupon['discount_type'] === 'percentage' ? $appliedCoupon['discount_value'] . '%' : 'R$ ' . number_format($appliedCoupon['discount_value'], 2, ',', '.') }}
                                    </span>
                                </div>
                                <button wire:click="removeCoupon" wire:loading.attr="disabled" class="text-xs text-red-400 hover:text-red-300 transition-colors disabled:opacity-50">Remover</button>
                            </div>
                        @else
                            <div class="flex gap-2">
                                <input wire:model="couponCode" type="text" placeholder="Digite o codigo"
                                       class="flex-1 px-3 py-2 rounded-lg bg-neutral-900 border border-neutral-700 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-xs transition-all uppercase">
                                <button wire:click="applyCoupon"
                                        wire:loading.attr="disabled"
                                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all disabled:opacity-50">
                                    <span wire:loading.remove>Aplicar</span>
                                    <span wire:loading class="flex items-center gap-1"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                                </button>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">Observacao</label>
                        <textarea wire:model="notes" rows="2" placeholder="Algo a mais?"
                                  class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all resize-none"></textarea>
                    </div>

                    @if ($orderType === 'entrega')
                        {{-- Payment Method --}}
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-neutral-400">Forma de Pagamento</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button wire:click="$set('paymentMethod', 'pix')" type="button"
                                        class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-medium transition-all border {{ $paymentMethod === 'pix' ? 'bg-amber-500/10 text-amber-400 border-amber-500/30' : 'bg-neutral-800 text-neutral-400 border-neutral-700 hover:border-neutral-600' }}">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Pix
                                </button>
                                <button wire:click="$set('paymentMethod', 'credit_card')" type="button"
                                        class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-medium transition-all border {{ $paymentMethod === 'credit_card' ? 'bg-amber-500/10 text-amber-400 border-amber-500/30' : 'bg-neutral-800 text-neutral-400 border-neutral-700 hover:border-neutral-600' }}">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    Cartao Credito
                                </button>
                                <button wire:click="$set('paymentMethod', 'cash')" type="button"
                                        class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-medium transition-all border {{ $paymentMethod === 'cash' ? 'bg-amber-500/10 text-amber-400 border-amber-500/30' : 'bg-neutral-800 text-neutral-400 border-neutral-700 hover:border-neutral-600' }}">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    Dinheiro
                                </button>
                            </div>
                        </div>

                        @if ($paymentMethod === 'cash')
                            <div class="p-3 rounded-xl bg-neutral-800/50 border border-neutral-700">
                                <label class="block text-xs font-medium text-neutral-400 mb-1.5">Valor em Dinheiro</label>
                                <input wire:model="cashAmount" type="number" step="0.01" min="0"
                                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-900 border border-neutral-700 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all"
                                       placeholder="Quanto vai pagar?">
                                @php $change = $cashAmount ? $cashAmount - $total : 0; @endphp
                                @if ($cashAmount && $change > 0)
                                    <p class="mt-2 text-sm text-emerald-400">
                                        Troco: R$ {{ number_format($change, 2, ',', '.') }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    @endif

                    {{-- Total Breakdown --}}
                    <div class="p-4 rounded-2xl bg-neutral-800/30 border border-neutral-800">
                        @php $subtotal = $cartItems ? collect($cartItems)->sum(fn($i) => $i['unit_price'] * $i['quantity']) : 0; @endphp
                        @php $cost = $orderType === 'entrega' ? (float) ($tenant->delivery_cost_per_order ?? 0) : 0; @endphp
                        @php $netTotal = $total - $cost; @endphp

                        <div class="space-y-1.5 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-neutral-400">Subtotal</span>
                                <span class="text-neutral-200">R$ {{ number_format($subtotal, 2, ',', '.') }}</span>
                            </div>
                            @if ($discount > 0)
                                <div class="flex items-center justify-between">
                                    <span class="text-emerald-400">Desconto</span>
                                    <span class="text-emerald-400">-R$ {{ number_format($discount, 2, ',', '.') }}</span>
                                </div>
                            @endif
                            @if ($cost > 0)
                                <div class="flex items-center justify-between">
                                    <span class="text-neutral-400">Taxa de Entrega (entregador)</span>
                                    <span class="text-amber-400">-R$ {{ number_format($cost, 2, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="flex items-center justify-between pt-3 mt-3 border-t border-neutral-700">
                            <span class="text-sm font-semibold text-neutral-300">Total do Pedido</span>
                            <span class="text-lg font-bold text-amber-400">R$ {{ number_format($total, 2, ',', '.') }}</span>
                        </div>
                        @if ($cost > 0)
                            <div class="flex items-center justify-between pt-1">
                                <span class="text-xs text-neutral-500">Liquido Restaurante</span>
                                <span class="text-xs font-medium text-neutral-400">R$ {{ number_format($netTotal, 2, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button wire:click="checkout"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-60 scale-95"
                                class="flex-1 px-8 py-3.5 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                            <span wire:loading.remove>Enviar Pedido</span>
                            <span wire:loading class="flex items-center gap-2"><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>Enviando...</span>
                        </button>
                    </div>
                </div>

                @if ($lastOrderId && $orderTracking)
                    <hr class="my-8 border-neutral-800">
                @endif
            @endif

            {{-- Active Orders --}}
            @if ($lastOrderId && $orderTracking)
                @php
                    $isDelivered = $orderTracking['status'] === 'entregue';
                    $isCancelled = $orderTracking['status'] === 'cancelado';
                    $isFechado = $orderTracking['status'] === 'fechado';
                    $isFinished = $isDelivered || $isCancelled || $isFechado;
                    $type = $orderTracking['type'] ?? 'mesa';
                @endphp

                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-3 rounded-2xl flex items-center justify-center {{ $isDelivered || $isFechado ? 'bg-emerald-500/10' : 'bg-amber-500/10' }}">
                        <svg class="w-8 h-8 {{ $isDelivered || $isFechado ? 'text-emerald-400' : 'text-amber-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            @if ($isDelivered || $isFechado)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @elseif ($isCancelled)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @endif
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-1">Pedido #{{ $lastOrderId }}</h3>

                    @if ($type === 'mesa' && $qrTableNumber)
                        <p class="text-sm text-neutral-400 mb-2">Mesa <strong>{{ $qrTableNumber }}</strong></p>
                    @elseif ($type === 'entrega')
                        <p class="text-sm text-amber-400 mb-2">Entrega</p>
                    @elseif ($type === 'retirada')
                        <p class="text-sm text-purple-400 mb-2">Retirada</p>
                    @endif

                    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold border {{ $orderTracking['statusColor'] }}">
                        <span class="w-2 h-2 rounded-full {{ \App\Models\Order::STATUS_DOT_COLORS[$orderTracking['status']] ?? 'bg-neutral-400' }}{{ (\App\Models\Order::STATUS_ANIMATED[$orderTracking['status']] ?? false) ? ' animate-pulse' : '' }}">
                        </span>
                        {{ $orderTracking['statusLabel'] }}
                    </span>

                    @if (!$isFinished)
                        <div class="flex justify-center gap-4 mt-4">
                            @php
                                $activeStatuses = match($type) {
                                    'entrega' => ['novo', 'em_preparo', 'saiu_entrega', 'entregue'],
                                    default => ['novo', 'em_preparo', 'pronto', 'entregue'],
                                };
                            @endphp
                            @foreach ($activeStatuses as $s)
                                @php $currentIdx = array_search($orderTracking['status'], $activeStatuses); $sIdx = array_search($s, $activeStatuses); @endphp
                                <div class="flex flex-col items-center gap-1 {{ $sIdx <= $currentIdx ? 'text-amber-400' : 'text-neutral-600' }}">
                                    <div class="w-2.5 h-2.5 rounded-full {{ $sIdx <= $currentIdx ? 'bg-amber-400' : 'bg-neutral-700' }}"></div>
                                    <span class="text-[10px]">{{ \App\Models\Order::STATUS_LABELS[$s] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 p-4 rounded-2xl bg-neutral-800/50 border border-neutral-800 text-left">
                        <p class="text-xs text-neutral-400 mb-3">Itens do pedido:</p>
                        <div class="space-y-3">
                            @foreach ($orderTracking['items'] as $item)
                                <div class="flex items-start justify-between text-sm">
                                    <div class="flex-1 min-w-0">
                                        <span>{{ $item['quantity'] }}x {{ $item['product_name'] }}</span>
                                        @if ($item['change_requested'])
                                            <div class="flex items-center gap-1 mt-1">
                                                <span class="text-xs text-blue-400 bg-blue-500/10 px-1.5 py-0.5 rounded">Troca solicitada</span>
                                                @if ($item['change_note'])
                                                    <span class="text-xs text-neutral-500">: {{ $item['change_note'] }}</span>
                                                @endif
                                            </div>
                                        @elseif (!$isFinished && Auth::check() && Auth::user()->isStaff())
                                            <button @click="openChangeModal({{ $item['id'] }}, '{{ $item['product_name'] }}')"
                                                    class="text-xs text-neutral-500 hover:text-amber-400 transition-colors ml-1">Solicitar troca</button>
                                        @endif
                                    </div>
                                    <span class="text-neutral-400 shrink-0 ml-2">R$ {{ number_format($item['price'] * $item['quantity'], 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                            @php $trackingCost = (float) ($orderTracking['delivery_cost'] ?? 0); @endphp
                            <div class="flex items-center justify-between pt-2 border-t border-neutral-700 font-bold">
                                <span>Total</span>
                                <span class="text-amber-400">R$ {{ number_format($orderTracking['total'], 2, ',', '.') }}</span>
                            </div>
                            @if ($trackingCost > 0)
                                <div class="flex items-center justify-between text-xs text-neutral-500 pt-1">
                                    <span>Taxa de Entrega (entregador)</span>
                                    <span>-R$ {{ number_format($trackingCost, 2, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs font-medium pt-1 border-t border-neutral-800">
                                    <span class="text-neutral-400">Liquido Restaurante</span>
                                    <span class="text-neutral-400">R$ {{ number_format(max(0, $orderTracking['total'] - $trackingCost), 2, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($isFechado)
                        <div class="mt-4 p-4 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-left">
                            <p class="text-sm text-purple-400 font-medium">Conta fechada.</p>
                            <p class="text-xs text-neutral-400 mt-1">Pague diretamente com o atendente.</p>
                        </div>
                    @endif

                    @if ($isFinished)
                        <button wire:click="newOrder"
                                wire:loading.attr="disabled"
                                class="mt-6 px-8 py-3.5 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50">
                            Fazer Novo Pedido
                        </button>
                    @endif
                </div>
            @endif

            {{-- Empty state --}}
            @if (empty($cartItems) && !($lastOrderId && $orderTracking))
                <div class="text-center py-12 text-neutral-500">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                    </svg>
                    <p>Carrinho vazio</p>
                    <p class="text-sm mt-2">Selecione um produto para comecar</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Address Modal --}}
    <div x-data="{
        open: @entangle('showAddressModal'),
        viaCepLoading: false,
        async searchCep() {
            let cep = $wire.newAddressZipcode.replace(/\D/g, '');
            if (cep.length !== 8) return;
            this.viaCepLoading = true;
            try {
                let response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                let data = await response.json();
                if (!data.erro) {
                    $wire.newAddressStreet = data.logradouro || '';
                    $wire.newAddressNeighborhood = data.bairro || '';
                    $wire.newAddressCity = data.localidade || '';
                    $wire.newAddressState = data.uf || '';
                }
            } catch (e) {}
            this.viaCepLoading = false;
        }
    }"
         x-init="$watch('open', val => document.body.style.overflow = val ? 'hidden' : '')"
         x-show="open"
         x-transition:enter="transition-opacity duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[80] bg-black/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4"
         @keydown.window.escape="$wire.closeAddressModal()"
         x-cloak>
        <div class="absolute inset-0" wire:click="closeAddressModal"></div>
        <div class="relative w-full max-w-lg bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 max-h-[95vh] sm:max-h-[90vh] flex flex-col m-2 sm:m-0"
             @click.stop
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4">
            <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-neutral-800 shrink-0">
                <h3 class="text-base sm:text-lg font-bold">Novo Endereco</h3>
                <button wire:click="closeAddressModal" class="p-1.5 rounded-lg hover:bg-neutral-800 text-neutral-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3 sm:space-y-4">
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1">Nome / Identificacao *</label>
                    <input wire:model.blur="newAddressLabel" type="text" placeholder="Casa, Trabalho, etc"
                           class="w-full px-3.5 sm:px-4 py-2.5 rounded-xl bg-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('newAddressLabel') border-red-500 bg-red-500/10 @else border-neutral-700 @enderror">
                    @error('newAddressLabel') <p class="mt-1 text-xs text-red-400 flex items-center gap-1"><svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-neutral-400 mb-1">Logradouro *</label>
                        <input wire:model.blur="newAddressStreet" type="text" placeholder="Rua, Avenida..."
                               class="w-full px-3.5 sm:px-4 py-2.5 rounded-xl bg-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('newAddressStreet') border-red-500 bg-red-500/10 @else border-neutral-700 @enderror">
                        @error('newAddressStreet') <p class="mt-1 text-xs text-red-400 flex items-center gap-1"><svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1">Numero</label>
                        <input wire:model="newAddressNumber" type="text" placeholder="S/N"
                               class="w-full px-3.5 sm:px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1">Complemento</label>
                        <input wire:model="newAddressComplement" type="text" placeholder="Apto, Bloco"
                               class="w-full px-3.5 sm:px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1">Bairro</label>
                        <input wire:model="newAddressNeighborhood" type="text" placeholder="Centro"
                               class="w-full px-3.5 sm:px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-neutral-400 mb-1">Cidade</label>
                        <input wire:model.blur="newAddressCity" type="text" placeholder="Sao Paulo"
                               class="w-full px-3.5 sm:px-4 py-2.5 rounded-xl bg-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('newAddressCity') border-red-500 bg-red-500/10 @else border-neutral-700 @enderror">
                        @error('newAddressCity') <p class="mt-1 text-xs text-red-400 flex items-center gap-1"><svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1">UF</label>
                        <input wire:model.blur="newAddressState" type="text" maxlength="2" placeholder="SP"
                               class="w-full px-3.5 sm:px-4 py-2.5 rounded-xl bg-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all uppercase @error('newAddressState') border-red-500 bg-red-500/10 @else border-neutral-700 @enderror">
                        @error('newAddressState') <p class="mt-1 text-xs text-red-400 flex items-center gap-1"><svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="relative">
                    <label class="block text-xs font-medium text-neutral-400 mb-1">CEP</label>
                    <input wire:model.blur="newAddressZipcode" type="text" placeholder="00000-000" maxlength="9"
                           x-on:blur="searchCep"
                           class="w-full px-3.5 sm:px-4 py-2.5 rounded-xl bg-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('newAddressZipcode') border-red-500 bg-red-500/10 @else border-neutral-700 @enderror">
                    <div x-show="viaCepLoading" class="absolute right-3 top-7 sm:top-8">
                        <svg class="w-4 h-4 animate-spin text-amber-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </div>
                    @error('newAddressZipcode') <p class="mt-1 text-xs text-red-400 flex items-center gap-1"><svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1">Referencia</label>
                    <input wire:model="newAddressReference" type="text" placeholder="Proximo ao mercado..."
                           class="w-full px-3.5 sm:px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                </div>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-t border-neutral-800 flex gap-3 shrink-0">
                <button wire:click="closeAddressModal"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all text-sm">Cancelar</button>
                <button wire:click="saveNewAddress"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold transition-all text-sm">Salvar</button>
            </div>
        </div>
    </div>

    {{-- PIX Checkout Modal --}}
    <div x-data="{ open: @entangle('showPixCheckoutModal') }"
         x-init="$watch('open', val => document.body.style.overflow = val ? 'hidden' : '')"
         x-show="open"
         x-transition:enter="transition-opacity duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[90] bg-black/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4"
         x-cloak>
        <div class="absolute inset-0" wire:click="closePixCheckoutModal"></div>
        <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-3xl p-6 shadow-2xl"
             @click.stop>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">Pagamento PIX</h3>
                <button wire:click="closePixCheckoutModal" class="p-1.5 rounded-lg hover:bg-neutral-800 text-neutral-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div wire:poll.5s="verifyCheckoutPixPayment">
                @if ($generatingPix)
                    <div class="flex flex-col items-center py-8">
                        <svg class="w-12 h-12 animate-spin text-amber-400 mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <p class="text-sm text-neutral-400">Gerando QR Code PIX...</p>
                    </div>
                @elseif ($pixPaymentConfirmed)
                    <div class="flex flex-col items-center py-8">
                        <svg class="w-16 h-16 text-emerald-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-lg font-bold text-emerald-400">Pagamento Confirmado!</p>
                        <p class="text-sm text-neutral-400 mt-1">Seu pedido foi enviado.</p>
                    </div>
                @elseif ($pixQrCode)
                    <div class="space-y-4">
                        <p class="text-sm text-neutral-400 text-center">Escaneie o QR Code para pagar</p>
                        <div class="flex justify-center">
                            <img src="data:image/png;base64,{{ $pixQrCode }}" alt="QR Code PIX" class="w-56 h-56 rounded-2xl bg-white p-2">
                        </div>
                        @if ($pixCopiaECola)
                            <div class="bg-neutral-800 rounded-xl p-3">
                                <p class="text-xs text-neutral-400 mb-1">PIX Copia e Cola</p>
                                <div class="flex gap-2">
                                    <input type="text" readonly value="{{ $pixCopiaECola }}"
                                           class="flex-1 bg-neutral-700 text-xs text-neutral-300 rounded-lg px-3 py-2 border border-neutral-600 select-all"
                                           onclick="this.select()">
                                    <button onclick="navigator.clipboard.writeText('{{ $pixCopiaECola }}'); this.textContent='Copiado!'; setTimeout(()=>this.textContent='Copiar', 2000)"
                                            class="px-3 py-2 bg-amber-500 hover:bg-amber-400 text-neutral-950 text-xs font-semibold rounded-lg transition-all shrink-0">
                                        Copiar
                                    </button>
                                </div>
                            </div>
                        @endif
                        <p class="text-xs text-neutral-500 text-center">Aguardando pagamento... A pagina sera atualizada automaticamente.</p>
                    </div>
                @elseif ($pixPaymentError)
                    <div class="flex flex-col items-center py-8">
                        <p class="text-sm text-red-400">Erro ao verificar pagamento.</p>
                    </div>
                @endif
            </div>
            <div class="mt-4 flex gap-3">
                <button wire:click="closePixCheckoutModal"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all text-sm">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    {{-- Change Request Modal --}}
    <div x-data="{
        open: false,
        itemId: null,
        itemName: '',
        note: '',
        openChangeModal(id, name) { this.itemId = id; this.itemName = name; this.note = ''; this.open = true; document.body.style.overflow = 'hidden'; },
        closeChangeModal() { this.open = false; this.itemId = null; this.itemName = ''; this.note = ''; document.body.style.overflow = ''; }
    }"
         x-cloak>
        <div x-show="open"
             x-transition:enter="transition-opacity duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[70] bg-black/60 backdrop-blur-sm"
             @click="closeChangeModal()"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full"
             class="fixed inset-x-0 bottom-0 z-[70] rounded-t-3xl bg-neutral-900 border-t border-neutral-800 p-6">
            <h3 class="text-lg font-bold mb-2">Solicitar Troca</h3>
            <p class="text-sm text-neutral-400 mb-4" x-text="'Item: ' + itemName"></p>
            <p class="text-xs text-neutral-500 mb-4">Voce tem ate 5 minutos apos o pedido para solicitar troca.</p>
            <textarea x-model="note" rows="3"
                      class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all resize-none"
                      placeholder="Descreva o que deseja trocar..."></textarea>
            <div class="flex gap-3 mt-4">
                <button @click="closeChangeModal()"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all">
                    Cancelar
                </button>
                <button @click="
                    if (note.trim()) {
                        $wire.requestItemChange(itemId, note);
                        closeChangeModal();
                    }
                "
                        class="flex-1 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold transition-all">
                    Solicitar
                </button>
            </div>
        </div>
        {{-- QR Code Modal --}}
        @if ($qrTableToken && $showQrModal)
        <div class="fixed inset-0 z-[80] flex items-center justify-center p-4"
             @keydown.window.escape="$wire.set('showQrModal', false)">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"
                 wire:click="$set('showQrModal', false)"></div>
            <div class="relative w-full max-w-sm bg-neutral-900 border border-neutral-800 rounded-3xl shadow-2xl shadow-black/60 p-8 text-center">
                <button wire:click="$set('showQrModal', false)"
                        class="absolute top-4 right-4 p-1.5 rounded-lg hover:bg-neutral-800 text-neutral-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-amber-500/10 flex items-center justify-center">
                    <svg class="w-8 h-8 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M12 4h8M4 8h8"/>
                    </svg>
                </div>

                <h3 class="text-xl font-bold mb-2">Mesa {{ $qrTableNumber }}</h3>
                <p class="text-sm text-neutral-400 mb-6">Compartilhe o QR code com sua mesa para todos pedirem juntos.</p>

                <div class="bg-white rounded-2xl p-4 mb-6 inline-block">
                    <img src="{{ $this->getQrCodeUrl() }}"
                         alt="QR Code Mesa {{ $qrTableNumber }}"
                         class="w-48 h-48 mx-auto"
                         loading="lazy">
                </div>

                <div class="space-y-3">
                    <a href="{{ $this->getTableEntryUrl() }}"
                       class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        Entrar na Mesa {{ $qrTableNumber }}
                    </a>

                    <button wire:click="confirmQrModal" wire:loading.attr="disabled"
                              class="w-full px-6 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all disabled:opacity-50">
                        Continuar
                    </button>
                </div>

                <p class="text-xs text-neutral-500 mt-4">
                    Ou compartilhe o link:
                    <a href="{{ $this->getTableEntryUrl() }}"
                       class="text-amber-400 hover:text-amber-300 underline break-all block mt-1">
                        {{ $this->getTableEntryUrl() }}
                    </a>
                </p>
            </div>
        </div>
        @endif
    </div>
</div>
