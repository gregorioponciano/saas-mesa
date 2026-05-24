<div class="h-full flex flex-col">
    {{-- Navigation --}}
    <div class="flex items-center gap-3 px-3 sm:px-6 py-3 sm:py-4 border-b border-neutral-800 shrink-0">
        <div class="flex gap-1 p-1 rounded-xl bg-neutral-900 border border-neutral-800 overflow-x-auto flex-1 min-w-0">
            <button wire:click="switchTab('orders')"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $tab === 'orders' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Meus Pedidos
                @if ($myActiveOrders->count() > 0)
                    <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/20 text-amber-400">{{ $myActiveOrders->count() }}</span>
                @endif
            </button>
            <button wire:click="switchTab('history')"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $tab === 'history' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Historico
            </button>
            <button wire:click="switchTab('profile')"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $tab === 'profile' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Meu Perfil
            </button>
            <button wire:click="switchTab('restaurant')"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $tab === 'restaurant' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Restaurante
            </button>
        </div>

        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
            <a href="{{ route('menu.show', $tenant->slug) }}" target="_blank"
               class="flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1.5 sm:py-2 text-[10px] sm:text-xs font-medium rounded-lg bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all whitespace-nowrap">
                <svg class="w-3 sm:w-3.5 h-3 sm:h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span class="hidden sm:inline">Novo Pedido</span><span class="sm:hidden">+</span>
            </a>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto">
        {{-- TAB: My Orders --}}
        @if ($tab === 'orders')
            <div class="p-4 sm:p-6">
                <div class="flex items-center gap-3 mb-4 sm:mb-6">
                    <h2 class="text-sm sm:text-lg font-bold">Meus Pedidos</h2>
                    @if ($myActiveOrders->count() > 0)
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            {{ $myActiveOrders->count() }} em andamento
                        </span>
                    @endif
                </div>

                @if ($myOrders->count() === 0)
                    <div class="text-center py-16 text-neutral-500">
                        <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                        </svg>
                        <p class="text-lg font-medium text-neutral-300 mb-2">Nenhum pedido ainda</p>
                        <a href="{{ route('menu.show', $tenant->slug) }}" target="_blank"
                           class="inline-flex items-center gap-2 px-4 sm:px-6 py-2 sm:py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all hover:scale-[1.02] active:scale-[0.98]">
                             Fazer Pedido
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4" wire:poll.10s>
                        @foreach ($myOrders as $order)
                            <div class="p-3 sm:p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800 hover:border-neutral-700 transition-all">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold">Pedido #{{ $order->id }}</span>
                                            @if ($order->table)
                                                <span class="text-xs text-neutral-400 bg-neutral-800 px-2 py-0.5 rounded-full">Mesa {{ $order->table->number }}</span>
                                            @else
                                                <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full">Entrega</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-neutral-500 mt-0.5">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full border {{ $order->statusClasses() }}">
                                        {{ $order->statusLabel() }}
                                    </span>
                                </div>

                                <div class="space-y-1.5 mb-4">
                                    @foreach ($order->items as $item)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-neutral-300">{{ $item->quantity }}x {{ $item->product_name }}</span>
                                            <span class="text-neutral-400">R$ {{ number_format($item->price * $item->quantity, 2, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="flex items-center justify-between pt-3 border-t border-neutral-800">
                                    <span class="font-bold text-amber-400">R$ {{ number_format($order->total, 2, ',', '.') }}</span>
                                    <div class="flex items-center gap-2">
                                        @if ($order->isActive())
                                            <span class="flex items-center gap-1.5 text-xs text-amber-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                                {{ $order->status === 'novo' ? 'Na fila' : ($order->status === 'em_preparo' ? 'Preparando' : 'Saindo') }}
                                            </span>
                                        @endif
                                        @if ($order->status === 'fechado')
                                            <span class="text-xs text-purple-400 font-medium">Conta fechada</span>
                                        @endif
                                        @if ($order->isFinished())
                                            <a href="{{ route('menu.show', $tenant->slug) }}" target="_blank"
                                               class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all">
                                                Novo Pedido
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- TAB: History --}}
        @if ($tab === 'history')
            <div class="p-4 sm:p-6">
            <div class="flex items-center gap-2 sm:gap-4 mb-4 sm:mb-6 flex-wrap">
                <h2 class="text-sm sm:text-lg font-bold shrink-0">Historico</h2>
                <div class="flex gap-1 p-0.5 rounded-lg bg-neutral-900 border border-neutral-800">
                        @foreach (['all' => 'Todas', 'today' => 'Hoje', 'week' => '7 Dias', 'month' => '30 Dias'] as $k => $l)
                            <button wire:click="$set('historyPeriod', '{{ $k }}')"
                                    class="px-2 sm:px-3 py-1 sm:py-1.5 text-[10px] sm:text-xs font-medium rounded-md transition-all {{ $historyPeriod === $k ? 'bg-amber-500 text-neutral-950' : 'text-neutral-400 hover:text-white' }}">{{ $l }}</button>
                        @endforeach
                    </div>
                </div>

                @if ($orderHistory->count() === 0)
                    <div class="text-center py-16 text-neutral-500">
                        <p class="text-lg font-medium text-neutral-300">Nenhum pedido encontrado</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($orderHistory as $order)
                            <div class="p-3 sm:p-4 rounded-2xl bg-neutral-900/30 border border-neutral-800/50 hover:border-neutral-700 transition-all">
                                <div class="flex items-center gap-2 sm:gap-3">
                                    <div class="flex flex-col items-center shrink-0">
                                        <span class="text-sm sm:text-lg font-bold">#{{ $order->id }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs sm:text-sm font-medium truncate">{{ $order->customer_name }}</p>
                                        <div class="flex flex-wrap items-center gap-1 sm:gap-2 text-[10px] sm:text-xs text-neutral-500 mt-0.5">
                                            <span class="shrink-0">{{ $order->created_at->format('d/m H:i') }}</span>
                                            @if ($order->table)
                                                <span class="shrink-0">&middot; Mesa {{ $order->table->number }}</span>
                                            @else
                                                <span class="shrink-0">&middot; Entrega</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                                        <span class="text-xs sm:text-sm font-bold text-amber-400">R$ {{ number_format($order->total, 2, ',', '.') }}</span>
                                        <span class="px-1.5 sm:px-2.5 py-0.5 sm:py-1 text-[10px] sm:text-xs font-medium rounded-full border shrink-0 {{ $order->statusClasses() }}">
                                            {{ $order->statusLabel() }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- TAB: Profile --}}
        @if ($tab === 'profile')
            <div class="p-4 sm:p-6">
                <h2 class="text-sm sm:text-lg font-bold mb-4 sm:mb-6">Meu Perfil</h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                    {{-- Profile Form --}}
                    <div class="p-4 sm:p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800">
                        <h3 class="text-sm font-semibold text-neutral-300 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Dados Pessoais
                        </h3>
                        <form wire:submit="saveProfile" class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-neutral-400 mb-1">Nome</label>
                                <input wire:model="name" type="text" placeholder="Seu nome"
                                       class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('name') border-red-500 @enderror">
                                @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-neutral-400 mb-1">Email</label>
                                <input wire:model="email" type="email" placeholder="email@exemplo.com"
                                       class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('email') border-red-500 @enderror">
                                @error('email') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-neutral-400 mb-1">Nova Senha (opcional)</label>
                                <input wire:model="password" type="password" placeholder="Minimo 6 caracteres"
                                       class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('password') border-red-500 @enderror">
                                @error('password') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-neutral-400 mb-1">Confirmar Senha</label>
                                <input wire:model="passwordConfirmation" type="password" placeholder="Repita a senha"
                                       class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('passwordConfirmation') border-red-500 @enderror">
                                @error('passwordConfirmation') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div class="pt-1">
                                <button type="submit" wire:loading.attr="disabled"
                                         class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 text-sm hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 flex items-center gap-2">
                                    <span wire:loading.remove>Salvar Alteracoes</span>
                                    <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Addresses Section --}}
                    <div class="p-4 sm:p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-neutral-300 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Meus Enderecos
                                @if ($myAddresses->count() > 0)
                                    <span class="text-xs text-neutral-500 font-normal">({{ $myAddresses->count() }}/5)</span>
                                @endif
                            </h3>
                            @if ($myAddresses->count() < 5)
                                <button wire:click="openAddressModal" wire:loading.attr="disabled"
                                         class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all disabled:opacity-50">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Novo
                                </button>
                            @endif
                        </div>

                        @if ($myAddresses->count() === 0)
                            <div class="text-center py-8 text-neutral-500">
                                <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <p class="text-sm text-neutral-400 mb-3">Nenhum endereco salvo</p>
                                <button wire:click="openAddressModal" wire:loading.attr="disabled"
                                         class="px-4 py-2 text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all disabled:opacity-50">
                                    Adicionar Endereco
                                </button>
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach ($myAddresses as $address)
                                    <div class="p-3 rounded-xl {{ $address->is_default ? 'bg-amber-500/5 border border-amber-500/20' : 'bg-neutral-950 border border-neutral-800' }}">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2 mb-0.5">
                                                    <span class="text-xs font-semibold text-neutral-200">{{ $address->label }}</span>
                                                    @if ($address->is_default)
                                                        <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30">Padrao</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-neutral-400 truncate">{{ $address->summary }}</p>
                                                @if ($address->reference)
                                                    <p class="text-[11px] text-neutral-500 mt-0.5">Ref: {{ $address->reference }}</p>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-1 shrink-0">
                                                @if (!$address->is_default)
                                                    <button wire:click="setDefaultAddress({{ $address->id }})" wire:loading.attr="disabled"
                                                             title="Definir como padrao"
                                                             class="p-1.5 rounded-lg text-neutral-500 hover:text-amber-400 hover:bg-amber-500/10 transition-all disabled:opacity-30">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                    </button>
                                                @endif
                                                <button wire:click="openAddressModal({{ $address->id }})" wire:loading.attr="disabled"
                                                         title="Editar"
                                                         class="p-1.5 rounded-lg text-neutral-500 hover:text-blue-400 hover:bg-blue-500/10 transition-all disabled:opacity-30">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>
                                                <button wire:click="confirmDeleteAddress({{ $address->id }})" wire:loading.attr="disabled"
                                                         title="Remover"
                                                         class="p-1.5 rounded-lg text-neutral-500 hover:text-red-400 hover:bg-red-500/10 transition-all disabled:opacity-30">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- LGPD Privacy --}}
                <div class="mt-4 sm:mt-6 p-4 sm:p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800">
                    <h3 class="text-sm font-semibold text-neutral-300 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Privacidade e Dados (LGPD)
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-neutral-950 border border-neutral-800">
                            <div>
                                <p class="text-xs font-semibold text-neutral-200">Exportar meus dados</p>
                                <p class="text-[11px] text-neutral-500">Baixe um JSON com seus dados pessoais</p>
                            </div>
                            <button wire:click="exportData" wire:loading.attr="disabled"
                                     class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-neutral-800 hover:bg-neutral-700 text-neutral-300 transition-all disabled:opacity-50 flex items-center gap-1">
                                <span wire:loading.remove>Exportar</span>
                                <span wire:loading><svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                            </button>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-neutral-950 border border-red-500/10">
                            <div>
                                <p class="text-xs font-semibold text-red-400">Excluir minha conta</p>
                                <p class="text-[11px] text-neutral-500">Remove seu usuario e anonimiza pedidos</p>
                            </div>
                            <button wire:click="confirmDeleteAccount" wire:loading.attr="disabled"
                                     class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-all disabled:opacity-50">
                                Excluir
                            </button>
                        </div>
                    </div>
                    <p class="text-[10px] text-neutral-600 text-center mt-3">
                        Ao utilizar nossos servicos, voce concorda com o tratamento de seus dados conforme a Lei Geral de Protecao de Dados (LGPD).
                    </p>
                </div>

                {{-- Delete Account Confirmation Modal --}}
                <div x-data="{ open: @entangle('showDeleteAccountConfirm') }"
                     x-show="open" x-cloak
                     class="fixed inset-0 z-[80] flex items-center justify-center p-4"
                     @keydown.window.escape="$wire.cancelDeleteAccount()">
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="cancelDeleteAccount"></div>
                    <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-red-400">Excluir conta</h3>
                                <p class="text-xs text-neutral-500">Esta acao nao pode ser desfeita</p>
                            </div>
                        </div>
                        <p class="text-sm text-neutral-300 mb-4">Seus pedidos serao anonimizados e sua conta removida permanentemente.</p>
                        <form wire:submit="deleteMyAccount" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-400 mb-2">
                                    Digite <span class="font-bold text-red-400">EXCLUIR</span> para confirmar
                                </label>
                                <input wire:model="deleteConfirmation" type="text" placeholder="EXCLUIR"
                                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-700 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all @error('deleteConfirmation') border-red-500 @enderror">
                                @error('deleteConfirmation') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex gap-2">
                                <button type="button" wire:click="cancelDeleteAccount"
                                        class="flex-1 px-4 py-2.5 text-sm font-medium rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 transition-all">
                                    Cancelar
                                </button>
                                <button type="submit" wire:loading.attr="disabled"
                                         class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-xl bg-red-500 hover:bg-red-400 text-white transition-all disabled:opacity-50 flex items-center justify-center gap-1">
                                    <span wire:loading.remove>Excluir Conta</span>
                                    <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB: Restaurant Info --}}
        @if ($tab === 'restaurant')
            <div class="p-4 sm:p-6">
                <h2 class="text-sm sm:text-lg font-bold mb-4 sm:mb-6">Restaurante</h2>
                <div class="p-4 sm:p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800 space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-neutral-800">
                        <div class="w-14 h-14 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-400 font-black text-xl">
                            {{ substr($tenant->name, 0, 1) }}
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">{{ $tenant->name }}</h3>
                            <p class="text-xs text-neutral-400">{{ $tenant->email }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl bg-neutral-950 border border-neutral-800">
                            <p class="text-[10px] text-neutral-500 uppercase tracking-wider mb-1">Plano</p>
                            <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full {{ $tenant->isPaid() ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-neutral-800 text-neutral-400 border border-neutral-700' }}">
                                {{ $tenant->planLabel() }}
                            </span>
                        </div>
                        <div class="p-3 rounded-xl bg-neutral-950 border border-neutral-800">
                            <p class="text-[10px] text-neutral-500 uppercase tracking-wider mb-1">Status</p>
                            <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full {{ $tenant->isActive() ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' }}">
                                {{ $tenant->isActive() ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>
                        @if ($tenant->whatsapp)
                            <div class="p-3 rounded-xl bg-neutral-950 border border-neutral-800">
                                <p class="text-[10px] text-neutral-500 uppercase tracking-wider mb-1">WhatsApp</p>
                                <p class="text-xs font-medium">{{ $tenant->whatsapp }}</p>
                            </div>
                        @endif
                        <div class="p-3 rounded-xl bg-neutral-950 border border-neutral-800">
                            <p class="text-[10px] text-neutral-500 uppercase tracking-wider mb-1">Total de Mesas</p>
                            <p class="text-xs font-medium">{{ $tenant->tables()->count() }} mesas</p>
                        </div>
                    </div>

                    <a href="{{ route('menu.show', $tenant->slug) }}" target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all text-sm hover:scale-[1.02] active:scale-[0.98]">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Ver Cardapio
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- Address Modal --}}
    <div x-data="{
        open: @entangle('showAddressModal'),
        viaCepLoading: false,
        async searchCep() {
            let cep = $wire.addressZipcode.replace(/\D/g, '');
            if (cep.length !== 8) return;
            this.viaCepLoading = true;
            try {
                let response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                let data = await response.json();
                if (!data.erro) {
                    $wire.addressAddress = data.logradouro || '';
                    $wire.addressNeighborhood = data.bairro || '';
                    $wire.addressCity = data.localidade || '';
                    $wire.addressState = data.uf || '';
                }
            } catch (e) {}
            this.viaCepLoading = false;
        }
    }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[70] flex items-center justify-center p-4"
         @keydown.window.escape="$wire.closeAddressModal()">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closeAddressModal"></div>
        <div class="relative w-full max-w-lg bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0">
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-800">
                <h3 class="font-bold text-sm">{{ $editingAddressId ? 'Editar' : 'Novo' }} Endereco</h3>
                <button wire:click="closeAddressModal" class="p-1 rounded-lg hover:bg-neutral-800 text-neutral-400 transition-all">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit="saveAddress" class="p-5 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="col-span-3">
                        <label class="block text-xs font-medium text-neutral-400 mb-1">Identificacao</label>
                        <select wire:model="addressLabel"
                                class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                            <option value="Casa">Casa</option>
                            <option value="Trabalho">Trabalho</option>
                            <option value="Outro">Outro</option>
                        </select>
                        @error('addressLabel') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-neutral-400 mb-1">Logradouro</label>
                        <input wire:model="addressAddress" type="text" placeholder="Rua, Avenida..."
                               class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('addressAddress') border-red-500 @enderror">
                        @error('addressAddress') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1">Numero</label>
                        <input wire:model="addressNumber" type="text" placeholder="N°"
                               class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('addressNumber') border-red-500 @enderror">
                        @error('addressNumber') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1">Bairro</label>
                        <input wire:model="addressNeighborhood" type="text" placeholder="Bairro"
                               class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        @error('addressNeighborhood') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1">Complemento</label>
                        <input wire:model="addressComplement" type="text" placeholder="Apto, Bloco..."
                               class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        @error('addressComplement') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- CEP with ViaCEP --}}
                    <div class="relative">
                        <label class="block text-xs font-medium text-neutral-400 mb-1">CEP</label>
                        <input wire:model="addressZipcode" type="text" placeholder="00000-000" maxlength="9"
                               x-on:blur="searchCep"
                               class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('addressZipcode') border-red-500 @enderror">
                        <div x-show="viaCepLoading"
                             class="absolute right-2.5 top-7">
                            <svg class="w-4 h-4 animate-spin text-amber-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </div>
                        @error('addressZipcode') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1">Cidade</label>
                        <input wire:model="addressCity" type="text" placeholder="Cidade"
                               class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('addressCity') border-red-500 @enderror">
                        @error('addressCity') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1">Estado</label>
                        <input wire:model="addressState" type="text" placeholder="UF"
                               class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('addressState') border-red-500 @enderror">
                        @error('addressState') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-3">
                        <label class="block text-xs font-medium text-neutral-400 mb-1">Ponto de Referencia</label>
                        <input wire:model="addressReference" type="text" placeholder="Proximo a..."
                               class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        @error('addressReference') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-neutral-300 cursor-pointer pt-1">
                    <input type="checkbox" wire:model="addressIsDefault"
                           class="w-4 h-4 rounded bg-neutral-950 border-neutral-700 text-amber-500 focus:ring-amber-500">
                    Definir como endereco padrao
                </label>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-neutral-800">
                    <button type="button" wire:click="closeAddressModal"
                            class="px-4 py-2 text-sm font-medium text-neutral-400 hover:text-white transition-all">
                        Cancelar
                    </button>
                    <button type="submit" wire:loading.attr="disabled"
                             class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all text-sm hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 flex items-center gap-1">
                        <span wire:loading.remove>{{ $editingAddressId ? 'Atualizar' : 'Salvar' }}</span>
                        <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Address Confirmation Modal --}}
    <div x-data="{ open: @entangle('confirmDeleteAddressId') }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[80] flex items-center justify-center p-4"
         @keydown.window.escape="$wire.cancelDeleteAddress()">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="cancelDeleteAddress"></div>
        <div class="relative w-full max-w-sm bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6 text-center"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-red-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h3 class="font-bold text-neutral-200 mb-2">Remover Endereco?</h3>
            <p class="text-sm text-neutral-400 mb-6">Esta acao nao pode ser desfeita.</p>
            <div class="flex items-center justify-center gap-3">
                <button wire:click="cancelDeleteAddress"
                        class="px-5 py-2 text-sm font-medium text-neutral-400 hover:text-white transition-all rounded-xl hover:bg-neutral-800">
                    Cancelar
                </button>
                <button wire:click="deleteAddress" wire:loading.attr="disabled"
                         class="px-5 py-2 text-sm font-semibold bg-red-500 hover:bg-red-400 text-white rounded-xl transition-all disabled:opacity-50 flex items-center gap-1">
                    <span wire:loading.remove>Remover</span>
                    <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                </button>
            </div>
        </div>
    </div>
</div>
