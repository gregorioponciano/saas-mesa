<div class="p-3 sm:p-4 lg:p-8 space-y-4 sm:space-y-6 lg:space-y-8" wire:poll.10s>
    @php
        use App\Models\Order;
    @endphp
    {{-- Tab Navigation --}}
    <div class="flex gap-1 p-1 rounded-2xl bg-neutral-900 border border-neutral-800 w-fit overflow-x-auto">
        <button wire:click="switchTab('overview')"
                class="flex items-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl text-[11px] sm:text-sm font-medium transition-all duration-200 {{ $tab === 'overview' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
            <svg class="w-3.5 sm:w-4 h-3.5 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span class="hidden xs:inline">Visao Geral</span><span class="xs:hidden">Geral</span>
        </button>
        <button wire:click="switchTab('grid')"
                class="flex items-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl text-[11px] sm:text-sm font-medium transition-all duration-200 {{ $tab === 'grid' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
            <svg class="w-3.5 sm:w-4 h-3.5 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <span class="hidden xs:inline">Mapa de Mesas</span><span class="xs:hidden">Mesas</span>
            @if ($overviewStats->occupied_tables > 0)
                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-red-500/20 text-red-400">{{ $overviewStats->occupied_tables }}</span>
            @endif
        </button>

        <button wire:click="switchTab('deliveries')"
                class="flex items-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl text-[11px] sm:text-sm font-medium transition-all duration-200 {{ $tab === 'deliveries' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
            <svg class="w-3.5 sm:w-4 h-3.5 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2-1m2 1l2-1m2 1l2-1m2-2v2a1 1 0 001 1h2m0 0a1 1 0 100 2m-2-2a1 1 0 110 2m-10-4h.01M16 12h4m0 0l-3-3m3 3l-3 3"/>
            </svg>
            <span class="hidden xs:inline">Entregas</span><span class="xs:hidden">Delivery</span>
            @if ($overviewStats->pending_delivery_count > 0)
                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-blue-500/20 text-blue-400">{{ $overviewStats->pending_delivery_count }}</span>
            @endif
        </button>
        <button wire:click="switchTab('history')"
                class="flex items-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl text-[11px] sm:text-sm font-medium transition-all duration-200 {{ $tab === 'history' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
            <svg class="w-3.5 sm:w-4 h-3.5 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="hidden xs:inline">Historico</span><span class="xs:hidden">Hist.</span>
        </button>
    </div>

    {{-- Tab: Overview --}}
    @if ($tab === 'overview')
          {{-- Stats Grid --}}
          <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 sm:gap-4">
              <div class="p-3 sm:p-4 lg:p-5 rounded-2xl bg-gradient-to-br from-amber-500/10 to-amber-600/5 border border-amber-500/10 hover:border-amber-500/20 transition-all duration-300">
                  <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
                      <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-amber-500/20 flex items-center justify-center shrink-0">
                          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                          </svg>
                      </div>
                      <span class="text-[10px] sm:text-xs font-medium text-neutral-500 uppercase tracking-wider truncate">Faturamento Total</span>
                  </div>
                   <p class="text-lg sm:text-xl lg:text-2xl font-bold text-amber-400">R$ {{ number_format($revenueStats->total_revenue, 2, ',', '.') }}</p>
              </div>

              <div class="p-3 sm:p-4 lg:p-5 rounded-2xl bg-gradient-to-br from-blue-500/10 to-blue-600/5 border border-blue-500/10 hover:border-blue-500/20 transition-all duration-300">
                  <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
                      <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-blue-500/20 flex items-center justify-center shrink-0">
                          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 002-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                          </svg>
                      </div>
                      <span class="text-[10px] sm:text-xs font-medium text-neutral-500 uppercase tracking-wider truncate">Faturamento Delivery</span>
                  </div>
                   <p class="text-lg sm:text-xl lg:text-2xl font-bold text-blue-400">R$ {{ number_format($revenueStats->delivery_revenue, 2, ',', '.') }}</p>
              </div>

              <div class="p-3 sm:p-4 lg:p-5 rounded-2xl bg-gradient-to-br from-red-500/10 to-red-600/5 border border-red-500/10 hover:border-red-500/20 transition-all duration-300">
                  <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
                      <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-red-500/20 flex items-center justify-center shrink-0">
                          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                          </svg>
                      </div>
                      <span class="text-[10px] sm:text-xs font-medium text-neutral-500 uppercase tracking-wider truncate">Faturamento Mesa</span>
                  </div>
                   <p class="text-lg sm:text-xl lg:text-2xl font-bold text-red-400">R$ {{ number_format($revenueStats->table_revenue, 2, ',', '.') }}</p>
              </div>

              <div class="p-3 sm:p-4 lg:p-5 rounded-2xl bg-gradient-to-br from-green-500/10 to-green-600/5 border border-green-500/10 hover:border-green-500/20 transition-all duration-300">
                  <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
                      <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-green-500/20 flex items-center justify-center shrink-0">
                          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12l2 2m0 0l2-2m2 2l-2-2m2 2l-2 2"/>
                          </svg>
                      </div>
                      <span class="text-[10px] sm:text-xs font-medium text-neutral-500 uppercase tracking-wider truncate">Hoje Delivery</span>
                  </div>
                  <p class="text-lg sm:text-xl lg:text-2xl font-bold text-green-400">{{ $revenueStats->delivery_orders_today }}</p>
              </div>

              <div class="p-3 sm:p-4 lg:p-5 rounded-2xl bg-gradient-to-br from-indigo-500/10 to-indigo-600/5 border border-indigo-500/10 hover:border-indigo-500/20 transition-all duration-300">
                  <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
                      <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center shrink-0">
                          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12l2 2m0 0l2-2m2 2l-2-2m2 2l-2 2"/>
                          </svg>
                      </div>
                      <span class="text-[10px] sm:text-xs font-medium text-neutral-500 uppercase tracking-wider truncate">Hoje Mesa</span>
                  </div>
                  <p class="text-lg sm:text-xl lg:text-2xl font-bold text-indigo-400">{{ $revenueStats->table_orders_today }}</p>
              </div>

              <div class="p-3 sm:p-4 lg:p-5 rounded-2xl bg-gradient-to-br from-purple-500/10 to-purple-600/5 border border-purple-500/10 hover:border-purple-500/20 transition-all duration-300">
                  <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
                      <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-purple-500/20 flex items-center justify-center shrink-0">
                          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 002-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                          </svg>
                      </div>
                      <span class="text-[10px] sm:text-xs font-medium text-neutral-500 uppercase tracking-wider truncate">Pedidos Hoje</span>
                  </div>
                  <p class="text-lg sm:text-xl lg:text-2xl font-bold text-purple-400">{{ $revenueStats->orders_today }}</p>
              </div>
          </div>

        {{-- Table Stats Mini --}}
        <div class="grid grid-cols-4 gap-2 sm:gap-3">
            <div class="p-2 sm:p-4 rounded-xl bg-emerald-500/5 border border-emerald-500/10 text-center">
                <p class="text-base sm:text-2xl font-bold text-emerald-400">{{ $overviewStats->free_tables }}</p>
                <p class="text-[10px] sm:text-xs text-neutral-500 mt-0.5 sm:mt-1">Livres</p>
            </div>
            <div class="p-2 sm:p-4 rounded-xl bg-red-500/5 border border-red-500/10 text-center">
                <p class="text-base sm:text-2xl font-bold text-red-400">{{ $overviewStats->occupied_tables }}</p>
                <p class="text-[10px] sm:text-xs text-neutral-500 mt-0.5 sm:mt-1">Ocupadas</p>
            </div>
            <div class="p-2 sm:p-4 rounded-xl bg-blue-500/5 border border-blue-500/10 text-center">
                <p class="text-base sm:text-2xl font-bold text-blue-400">{{ $overviewStats->reserved_tables }}</p>
                <p class="text-[10px] sm:text-xs text-neutral-500 mt-0.5 sm:mt-1">Reservadas</p>
            </div>
            <div class="p-2 sm:p-4 rounded-xl bg-purple-500/5 border border-purple-500/10 text-center">
                <p class="text-base sm:text-2xl font-bold text-purple-400">{{ $revenueStats->pickup_orders_today }}</p>
                <p class="text-[10px] sm:text-xs text-neutral-500 mt-0.5 sm:mt-1">Retiradas</p>
            </div>
        </div>

        {{-- Delivery Costs --}}
        <div class="grid grid-cols-2 gap-2 sm:gap-3">
            <div class="p-2 sm:p-4 rounded-xl bg-amber-500/5 border border-amber-500/10 text-center">
                <p class="text-base sm:text-2xl font-bold text-amber-400">R$ {{ number_format($overviewStats->total_delivery_cost, 2, ',', '.') }}</p>
                <p class="text-[10px] sm:text-xs text-neutral-500 mt-0.5 sm:mt-1">Custo Total Entregas</p>
            </div>
            <div class="p-2 sm:p-4 rounded-xl bg-blue-500/5 border border-blue-500/10 text-center">
                <p class="text-base sm:text-2xl font-bold text-blue-400">R$ {{ number_format($overviewStats->pending_delivery_cost, 2, ',', '.') }}</p>
                <p class="text-[10px] sm:text-xs text-neutral-500 mt-0.5 sm:mt-1">Custo Pendente Entregas</p>
            </div>
        </div>

        {{-- Occupied Tables Detail --}}
        @if ($occupiedTablesWithOrders->count() > 0)
        <x-admin.card :padding="false" class="p-3 sm:p-5">
            <h2 class="text-xs sm:text-sm font-bold mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Mesas Ocupadas
                    <span class="text-xs text-neutral-500 font-normal">({{ $overviewStats->occupied_tables }})</span>
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach ($occupiedTablesWithOrders as $table)
                        <div class="p-3 rounded-xl bg-red-500/5 border border-red-500/10">
                            <div class="flex items-center justify-between">
                                <p class="text-lg font-bold text-red-400">Mesa {{ $table->number }}</p>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-500/15 text-red-400">
                                    {{ $table->orders_count }} pedido{{ $table->orders_count !== 1 ? 's' : '' }}
                                </span>
                            </div>
                            <p class="text-[10px] text-neutral-500 mt-1">Cap. {{ $table->capacity }}</p>
                        </div>
                    @endforeach
                </div>
            </x-admin.card>
        @endif

        {{-- Revenue Chart & Period Filter --}}
        <x-admin.card :padding="false" class="p-3 sm:p-6">
            <div class="flex items-center justify-between mb-3 sm:mb-6 flex-wrap gap-2">
                <h2 class="text-sm sm:text-lg font-bold shrink-0">Receita</h2>
                <div class="flex gap-1 p-0.5 rounded-lg bg-neutral-800">
                    @foreach (['today' => 'Hoje', 'week' => '7 Dias', 'month' => '30 Dias'] as $key => $label)
                        <button wire:click="$set('period', '{{ $key }}')"
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200 {{ $period === $key ? 'bg-amber-500 text-neutral-950' : 'text-neutral-400 hover:text-white' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex items-end gap-1.5 h-32">
                @php $maxRevenue = max(1, collect($revenueData)->max('total')); @endphp
                @foreach ($revenueData as $data)
                    <div class="flex-1 flex flex-col items-center gap-1 group">
                        <div class="relative w-full flex items-end justify-center"
                             style="height: {{ max(8, ($data['total'] / $maxRevenue) * 100) }}%">
                            <div class="w-full rounded-t-lg bg-gradient-to-t from-amber-600 to-amber-400 hover:from-amber-500 hover:to-amber-300 transition-all duration-300 min-h-[4px]"
                                 style="height: {{ max(8, ($data['total'] / $maxRevenue) * 100) }}%">
                            </div>
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-neutral-800 text-white text-xs px-2 py-1 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                R$ {{ number_format($data['total'], 2, ',', '.') }}
                            </div>
                        </div>
                        <span class="text-xs text-neutral-500">{{ $data['date'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-admin.card>

        {{-- Low Stock Products --}}
        <x-admin.card :padding="false" class="p-3 sm:p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs sm:text-sm font-bold flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Produtos com Estoque Baixo
                    <span class="text-xs text-neutral-500 font-normal">({{ $lowStockProducts->count() }})</span>
                </h2>
            </div>
            @if ($lowStockProducts->count() === 0)
                <div class="text-center py-6 text-neutral-500">
                    <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs">Todos os produtos estão com estoque adequado</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach ($lowStockProducts as $product)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-red-500/5 border border-red-500/10">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-neutral-200 truncate">{{ $product->name }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    @if ($product->stock > 0)
                                        <span class="text-xs text-amber-400 font-medium">{{ $product->stock }} unid.</span>
                                    @else
                                        <span class="text-xs text-red-400 font-medium">Sem estoque</span>
                                    @endif
                                </div>
                            </div>
                            @if (auth()->user()->isAdmin())
                                <button wire:click="openStockModal({{ $product->id }})"
                                        class="shrink-0 px-3 py-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 text-xs font-medium transition-all">
                                    Ajustar Estoque
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-admin.card>

        {{-- Active Orders --}}
         <div>
             <h2 class="text-sm sm:text-lg font-bold mb-4">Pedidos Ativos</h2>
             @if ($activeOrders->count() === 0)
                 <div class="text-center py-12 text-neutral-500">
                     <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                     </svg>
                     <p>Nenhum pedido ativo no momento</p>
                 </div>
             @else
                 @php
                     $nonTableOrders = $activeOrders->filter(fn($o) => !$o->table_id);
                 @endphp

                 {{-- Mesa Orders (grouped by table) --}}
                 @if ($tableGroups->isNotEmpty())
                     <div class="space-y-4 mb-6">
                         @foreach ($tableGroups as $group)
                             <div class="rounded-2xl bg-neutral-900/50 border border-neutral-800 overflow-hidden">
                                 <div class="p-3 sm:p-4 bg-neutral-800/50 border-b border-neutral-700 flex items-center justify-between gap-4">
                                     <div class="flex items-center gap-3 min-w-0">
                                         <div class="w-10 h-10 rounded-xl bg-red-500/15 flex items-center justify-center shrink-0">
                                             <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                             </svg>
                                         </div>
                                         <div>
                                             <h3 class="text-base sm:text-lg font-bold">Mesa {{ $group->table->number }}</h3>
                                             <p class="text-xs text-neutral-500">
                                                 Cap. {{ $group->table->capacity }} &middot; {{ $group->orders->count() }} pedido{{ $group->orders->count() !== 1 ? 's' : '' }}
                                             </p>
                                         </div>
                                     </div>
                                     <div class="text-right shrink-0">
                                         <p class="text-xs text-neutral-400">Total da Mesa</p>
                                         <p class="text-lg sm:text-xl font-bold text-amber-400">R$ {{ number_format($group->total, 2, ',', '.') }}</p>
                                         @if ($group->pending > 0)
                                             <p class="text-xs text-amber-500">R$ {{ number_format($group->pending, 2, ',', '.') }} pendente</p>
                                         @else
                                             <p class="text-xs text-emerald-400">Pago</p>
                                         @endif
                                     </div>
                                 </div>

                                 <div class="divide-y divide-neutral-800">
                                     @foreach ($group->orders as $order)
                                         <div class="p-3 sm:p-4 hover:bg-neutral-800/30 transition-colors">
                                             <div class="flex items-start justify-between mb-2">
                                                 <div class="flex items-center gap-2 flex-wrap">
                                                     <span class="text-xs font-medium text-neutral-400">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</span>
                                                     <span class="text-[10px] sm:text-xs px-1.5 py-0.5 rounded-full {{ $order->statusClasses() }}">{{ $order->statusLabel() }}</span>
                                                     @if ($order->hasPayment())
                                                         <span class="text-[10px] font-medium text-emerald-400">Pago</span>
                                                     @endif
                                                 </div>
                                                 <span class="text-sm font-semibold text-neutral-200">R$ {{ number_format($order->total, 2, ',', '.') }}</span>
                                             </div>
                                             <div class="text-xs text-neutral-500 mb-2">
                                                 {{ $order->customer_name }} &middot; {{ $order->created_at->format('d/m H:i') }}
                                             </div>
                                             <div class="text-xs text-neutral-400 mb-3 space-y-0.5">
                                                 @foreach ($order->items as $item)
                                                     <div>{{ $item->quantity }}x {{ $item->product_name }} <span class="text-neutral-500">R$ {{ number_format($item->price * $item->quantity, 2, ',', '.') }}</span></div>
                                                 @endforeach
                                             </div>
                                             <div class="flex items-center gap-2">
                                                 <button wire:click="viewOrder({{ $order->id }})"
                                                         class="px-2.5 py-1 text-[10px] sm:text-xs font-semibold rounded-lg bg-neutral-800 text-neutral-300 border border-neutral-700 hover:bg-neutral-700 transition-all duration-200">
                                                     Detalhes
                                                 </button>
                                                 @php $nextSt = $order->nextStatus(); @endphp
                                                 @if ($nextSt && !$order->isBillClosed())
                                                     <button wire:click="updateStatus({{ $order->id }}, '{{ $nextSt }}')"
                                                             class="px-2.5 py-1 text-[10px] sm:text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all duration-200 hover:scale-105 active:scale-95">
                                                         {{ $order->statusFlowLabels()[$order->status] ?? 'Avançar' }}
                                                     </button>
                                                 @endif
                                                @if (!in_array($order->status, ['fechado', 'cancelado']))
                                                    <button wire:click="updateStatus({{ $order->id }}, 'cancelado')"
                                                            class="px-2.5 py-1 text-[10px] sm:text-xs font-semibold rounded-lg bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-all duration-200">
                                                        Cancelar
                                                    </button>
                                                @endif
                                             </div>
                                         </div>
                                     @endforeach
                                 </div>

                                {{-- Table Bill Footer --}}
                                <div class="p-3 sm:p-4 border-t border-neutral-700/50">
                                    <button wire:click="openCloseTableModal({{ $group->table->id }})"
                                            class="w-full py-2.5 text-sm font-bold rounded-xl bg-emerald-500 hover:bg-emerald-400 text-neutral-950 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                                        Fechar Conta da Mesa {{ $group->table->number }} (R$ {{ number_format($group->total, 2, ',', '.') }})
                                    </button>
                                </div>
                             </div>
                         @endforeach
                     </div>
                 @endif

                 {{-- Non-table Orders (Delivery / Pickup) --}}
                 @if ($nonTableOrders->isNotEmpty())
                     <h3 class="text-xs sm:text-sm font-semibold text-neutral-400 uppercase tracking-wider mb-3">Entregas &amp; Retiradas</h3>
                     <div class="overflow-x-auto">
                         <table class="min-w-full divide-y divide-neutral-700">
                             <thead class="bg-neutral-900">
                                 <tr>
                                     <th class="px-2 sm:px-6 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-neutral-400 uppercase tracking-wider">#</th>
                                     <th class="px-2 sm:px-6 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-neutral-400 uppercase tracking-wider">Cliente</th>
                                     <th class="px-2 sm:px-6 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-neutral-400 uppercase tracking-wider">Tipo</th>
                                     <th class="px-2 sm:px-6 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-neutral-400 uppercase tracking-wider">Itens</th>
                                     <th class="px-2 sm:px-6 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-neutral-400 uppercase tracking-wider">Total</th>
                                     <th class="px-2 sm:px-6 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-neutral-400 uppercase tracking-wider">Status</th>
                                     <th class="px-2 sm:px-6 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-neutral-400 uppercase tracking-wider">Pagto</th>
                                     <th class="px-2 sm:px-6 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-neutral-400 uppercase tracking-wider">Hora</th>
                                     <th class="px-2 sm:px-6 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-neutral-400 uppercase tracking-wider">Ações</th>
                                 </tr>
                             </thead>
                             <tbody class="bg-neutral-800 divide-y divide-neutral-700">
                                 @foreach ($nonTableOrders as $order)
                                     <tr class="hover:bg-neutral-700/50 transition-colors">
                                         <td class="px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-xs sm:text-sm font-medium text-neutral-200">
                                             #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                                         </td>
                                         <td class="px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-neutral-200">
                                             {{ $order->customer_name }}
                                         </td>
                                         <td class="px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-xs sm:text-sm">
                                             @php $tClass = $order->typeClasses(); @endphp
                                             <span class="text-[10px] sm:text-xs font-semibold px-1 sm:px-2 py-0.5 rounded-full {{ $tClass }}">{{ $order->typeLabel() }}</span>
                                         </td>
                                         <td class="px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-neutral-200">
                                             @foreach ($order->items as $item)
                                                 <div>{{ $item->quantity }}x {{ $item->product_name }}</div>
                                             @endforeach
                                         </td>
                                         <td class="px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-neutral-200 text-right">
                                             R$ {{ number_format($order->total, 2, ',', '.') }}
                                         </td>
                                         <td class="px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-xs sm:text-sm">
                                             <span class="px-1.5 sm:px-2.5 py-0.5 text-[10px] sm:text-xs font-medium rounded-full {{ $order->statusClasses() }}">{{ $order->statusLabel() }}</span>
                                         </td>
                                         <td class="px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-xs sm:text-sm">
                                             @if ($order->hasPayment())
                                                 <span class="text-[10px] sm:text-xs font-medium text-emerald-400">Pago</span>
                                             @elseif ($order->isBillClosed())
                                                 <span class="text-[10px] sm:text-xs font-medium text-purple-400">Fechado</span>
                                             @else
                                                 <span class="text-[10px] sm:text-xs font-medium text-amber-400">R$ {{ number_format($order->pendingPaymentAmount(), 2, ',', '.') }}</span>
                                             @endif
                                         </td>
                                         <td class="px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-neutral-400 text-right">
                                             {{ $order->created_at->format('d/m H:i') }}
                                         </td>
                                         <td class="px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-right space-x-1 sm:space-x-2">
                                             <button wire:click="viewOrder({{ $order->id }})"
                                                     class="px-2 sm:px-3 py-1 text-[10px] sm:text-xs font-semibold rounded-lg bg-neutral-800 text-neutral-300 border border-neutral-700 hover:bg-neutral-700 transition-all duration-200">
                                                 Detalhes
                                             </button>
                                             @php $nextSt = $order->nextStatus(); @endphp
                                             @if ($nextSt && !$order->isBillClosed())
                                                 <button wire:click="updateStatus({{ $order->id }}, '{{ $nextSt }}')"
                                                         class="px-2 sm:px-3 py-1 text-[10px] sm:text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all duration-200 hover:scale-105 active:scale-95">
                                                     {{ $order->statusFlowLabels()[$order->status] ?? 'Avançar' }}
                                                 </button>
                                             @endif
                                              @if (!in_array($order->status, ['fechado', 'cancelado']))
                                                  <button wire:click="updateStatus({{ $order->id }}, 'cancelado')"
                                                          class="px-2 sm:px-3 py-1 text-[10px] sm:text-xs font-semibold rounded-lg bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-all duration-200">
                                                      Cancelar
                                                  </button>
                                              @endif
                                          </td>
                                     </tr>
                                 @endforeach
                             </tbody>
                         </table>
                     </div>
                 @endif
             @endif
         </div>


    @endif



    {{-- Tab: Deliveries --}}
    @if ($tab === 'deliveries')
        <div class="space-y-4">
            {{-- Real-time Notification Bar --}}
            <div wire:poll.5s class="flex gap-2 flex-wrap">
                @php
                    $deliveryNovos = $deliveryOrders->whereIn('status', ['novo'])->count();
                    $deliveryPreparo = $deliveryOrders->whereIn('status', ['em_preparo'])->count();
                    $deliveryRota = $deliveryOrders->where('status', 'saiu_entrega')->count();
                @endphp
                @if ($deliveryNovos > 0)
                    <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-medium">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        {{ $deliveryNovos }} novo(s)
                    </span>
                @endif
                @if ($deliveryPreparo > 0)
                    <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-medium">
                        <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                        {{ $deliveryPreparo }} em preparo
                    </span>
                @endif
                @if ($deliveryRota > 0)
                    <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 text-xs font-medium">
                        <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                        {{ $deliveryRota }} em rota
                    </span>
                @endif
            </div>

            {{-- Filters --}}
            <div class="flex items-center gap-2 sm:gap-4 flex-wrap">
                <h2 class="text-lg font-bold shrink-0">Delivery</h2>
                <div class="flex gap-1 p-0.5 rounded-lg bg-neutral-900 border border-neutral-800 overflow-x-auto">
                    <button wire:click="$set('deliveryFilter', 'all')"
                            class="px-3 py-1.5 text-xs font-medium rounded-md transition-all whitespace-nowrap {{ $deliveryFilter === 'all' ? 'bg-amber-500 text-neutral-950' : 'text-neutral-400 hover:text-white' }}">Todas ({{ $deliveryOrders->count() }})</button>
                    <button wire:click="$set('deliveryFilter', 'pending')"
                            class="px-3 py-1.5 text-xs font-medium rounded-md transition-all whitespace-nowrap {{ $deliveryFilter === 'pending' ? 'bg-red-500 text-white' : 'text-neutral-400 hover:text-white' }}">Pendentes</button>
                    <button wire:click="$set('deliveryFilter', 'in_transit')"
                            class="px-3 py-1.5 text-xs font-medium rounded-md transition-all whitespace-nowrap {{ $deliveryFilter === 'in_transit' ? 'bg-blue-500 text-white' : 'text-neutral-400 hover:text-white' }}">Em Rota</button>
                    <button wire:click="$set('deliveryFilter', 'delivered')"
                            class="px-3 py-1.5 text-xs font-medium rounded-md transition-all whitespace-nowrap {{ $deliveryFilter === 'delivered' ? 'bg-emerald-500 text-white' : 'text-neutral-400 hover:text-white' }}">Entregues</button>
                </div>
            </div>

            @if ($deliveryOrders->count() === 0)
                <div class="text-center py-16 text-neutral-500">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2-1m2 1l2-1m2 1l2-1m2-2v2a1 1 0 001 1h2m0 0a1 1 0 100 2m-2-2a1 1 0 110 2m-10-4h.01M16 12h4m0 0l-3-3m3 3l-3 3"/></svg>
                    <p class="text-lg font-medium text-neutral-300">Nenhuma entrega encontrada</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach ($deliveryOrders as $order)
                        @php
                            $orderPaid = $order->payments->where('status', 'paid')->count() > 0;
                            $nextSt = $order->nextStatus();
                            $orderPaymentPending = $order->pendingPaymentAmount() > 0;
                        @endphp
                        <div class="p-4 rounded-2xl bg-neutral-900/70 border transition-all duration-200 hover:shadow-lg hover:shadow-black/30
                            {{ $order->status === 'novo' ? 'border-amber-500/30' : '' }}
                            {{ $order->status === 'em_preparo' ? 'border-blue-500/30' : '' }}
                            {{ $order->status === 'saiu_entrega' ? 'border-purple-500/30' : '' }}
                            {{ $order->status === 'entregue' ? 'border-emerald-500/30' : '' }}">
                            {{-- Card Header --}}
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-base">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</span>
                                        <span class="px-2 py-0.5 text-[10px] font-medium rounded-full {{ $order->statusClasses() }}">{{ $order->statusLabel() }}</span>
                                    </div>
                                    <p class="text-xs text-neutral-400 mt-1">{{ $order->customer_name }}</p>
                                </div>
                                <span class="text-sm font-bold text-amber-400">R$ {{ number_format($order->total, 2, ',', '.') }}</span>
                            </div>

                            {{-- Quick Info --}}
                            <div class="flex items-center gap-3 text-[11px] text-neutral-500 mb-3 flex-wrap">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $order->created_at->format('d/m H:i') }}
                                </span>
                                @if ($orderPaid)
                                    <span class="flex items-center gap-1 text-emerald-400">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Pago
                                    </span>
                                @else
                                    <span class="flex items-center gap-1 text-rose-400">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Nao pago
                                    </span>
                                @endif
                            </div>

                            {{-- Address --}}
                            @if ($order->address_json)
                                <div class="text-[11px] text-neutral-400 bg-neutral-800/50 rounded-lg px-3 py-2 mb-3 truncate">
                                    <span class="text-neutral-500 block text-[10px]">Endereco</span>
                                    {{ $order->address_json['address'] ?? '-' }}
                                    @if (!empty($order->address_json['reference']))
                                        <span class="text-[10px] text-neutral-500 block">Ref: {{ $order->address_json['reference'] }}</span>
                                    @endif
                                </div>
                            @endif

                            {{-- Delivery Person --}}
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs text-neutral-500">Entregador:</span>
                                @if ($order->deliveryPerson)
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-medium text-emerald-400">{{ $order->deliveryPerson->name }}</span>
                                        <button wire:click="removeDeliveryPerson({{ $order->id }})"
                                                class="p-0.5 rounded text-red-400 hover:text-red-300 text-xs">&times;</button>
                                    </div>
                                @else
                                    <div x-data="{ open: false }" class="relative">
                                        <button @click="open = !open"
                                                class="text-xs font-medium text-amber-400 hover:text-amber-300 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                            Designar
                                        </button>
                                        <div x-show="open" @click.outside="open = false" x-cloak
                                             class="absolute right-0 mt-1 z-50 w-52 bg-neutral-900 border border-neutral-700 rounded-xl shadow-2xl shadow-black/60 py-1 max-h-48 overflow-y-auto">
                                            @forelse ($availableDeliveryPeople as $dp)
                                                <button wire:click="assignDeliveryPerson({{ $order->id }}, {{ $dp->id }})"
                                                        @click="open = false"
                                                        class="w-full text-left px-4 py-2.5 text-sm text-neutral-300 hover:bg-neutral-800 hover:text-white transition-colors border-b border-neutral-800 last:border-0">{{ $dp->name }}</button>
                                            @empty
                                                <p class="px-4 py-3 text-xs text-neutral-500">Nenhum entregador ativo</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="flex gap-1.5 flex-wrap pt-3 border-t border-neutral-800">
                                <button wire:click="viewOrder({{ $order->id }})"
                                        class="flex-1 px-3 py-2 text-[11px] font-semibold rounded-xl bg-neutral-800 text-neutral-300 hover:bg-neutral-700 transition-all border border-neutral-700/50">
                                    Detalhes
                                </button>
                                @if (!$orderPaid && !$order->isBillClosed())
                                    <button wire:click="openPaymentModal({{ $order->id }})"
                                            class="px-3 py-2 text-[11px] font-semibold rounded-xl bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition-all border border-emerald-500/20">
                                        Pagamento
                                    </button>
                                @endif
                                @if ($nextSt && !$order->isBillClosed())
                                    <button wire:click="updateStatus({{ $order->id }}, '{{ $nextSt }}')"
                                            class="px-3 py-2 text-[11px] font-semibold rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all">
                                        {{ $order->statusFlowLabels()[$order->status] ?? 'Avancar' }}
                                    </button>
                                @endif
                                @if (!in_array($order->status, ['fechado', 'cancelado']))
                                    <button wire:click="updateStatus({{ $order->id }}, 'cancelado')"
                                            class="px-3 py-2 text-[11px] font-semibold rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-all border border-red-500/20">
                                        Cancelar
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Payment Modal --}}
    @if ($showPaymentModal)
        <div class="fixed inset-0 z-80 flex items-center justify-center p-4"
             @keydown.window.escape="$wire.closeOrderModal()">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closeOrderModal"></div>
            <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold">Registrar Pagamento</h3>
                    <button wire:click="closeOrderModal" class="p-1.5 rounded-lg hover:bg-neutral-800 text-neutral-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
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
                            <option value="credit_card">Cartao de Credito</option>
                            <option value="debit_card">Cartao de Debito</option>
                            <option value="cash">Dinheiro</option>
                            <option value="other">Outro</option>
                        </select>
                    </div>
                    @if ($paymentMethod === 'pix' && !$pixQrCode)
                        <button wire:click="generatePaymentPix" wire:loading.attr="disabled"
                                class="w-full px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold transition-all flex items-center justify-center gap-2">
                            @if ($generatingPix)
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            @endif
                            Gerar QR Code PIX
                        </button>
                    @endif
                    @if ($pixQrCode)
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
                        <button wire:click="closeOrderModal"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all">Cancelar</button>
                        <button wire:click="registerPayment" wire:loading.class="opacity-50"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-neutral-950 font-semibold transition-all">
                            Confirmar Pagamento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Tab: History --}}
    @if ($tab === 'history')
        <div x-data="{ animate: true }" x-init="setTimeout(() => animate = false, 500)">
            <div class="flex items-center gap-2 sm:gap-4 mb-4 sm:mb-6 flex-wrap">
                <h2 class="text-sm sm:text-lg font-bold shrink-0">Historico</h2>
                <div class="flex gap-1 p-0.5 rounded-lg bg-neutral-900 border border-neutral-800">
                    @foreach (['today' => 'Hoje', 'week' => '7 Dias', 'month' => '30 Dias'] as $k => $l)
                        <button wire:click="$set('historyPeriod', '{{ $k }}')"
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-all {{ $historyPeriod === $k ? 'bg-amber-500 text-neutral-950' : 'text-neutral-400 hover:text-white' }}">{{ $l }}</button>
                    @endforeach
                </div>
                <div class="flex gap-1 p-0.5 rounded-lg bg-neutral-900 border border-neutral-800">
                    <button wire:click="$set('historyTypeFilter', 'all')"
                            class="px-3 py-1.5 text-xs font-medium rounded-md transition-all {{ $historyTypeFilter === 'all' ? 'bg-amber-500 text-neutral-950' : 'text-neutral-400 hover:text-white' }}">Todas</button>
                    <button wire:click="$set('historyTypeFilter', 'mesa')"
                            class="px-3 py-1.5 text-xs font-medium rounded-md transition-all {{ $historyTypeFilter === 'mesa' ? 'bg-blue-500 text-white' : 'text-neutral-400 hover:text-white' }}">Mesa</button>
                    <button wire:click="$set('historyTypeFilter', 'entrega')"
                            class="px-3 py-1.5 text-xs font-medium rounded-md transition-all {{ $historyTypeFilter === 'entrega' ? 'bg-green-500 text-white' : 'text-neutral-400 hover:text-white' }}">Delivery</button>
                    <button wire:click="$set('historyTypeFilter', 'retirada')"
                            class="px-3 py-1.5 text-xs font-medium rounded-md transition-all {{ $historyTypeFilter === 'retirada' ? 'bg-purple-500 text-white' : 'text-neutral-400 hover:text-white' }}">Retirada</button>
                </div>
                <div class="w-full sm:w-auto">
                    <input wire:model.live.debounce="historySearch" type="text" placeholder="Buscar..."
                           class="w-full sm:w-48 lg:w-64 px-3 sm:px-4 py-1.5 sm:py-2 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                </div>
            </div>

            @if ($orderHistory->count() === 0)
                <div class="text-center py-16 text-neutral-500">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-lg font-medium text-neutral-300">Nenhum pedido encontrado</p>
                    <p class="text-sm mt-1">Tente alterar o periodo ou buscar por nome</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($orderHistory as $order)
                        <div class="p-3 sm:p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800 hover:border-neutral-700 transition-all duration-300 group"
                              x-transition:enter="transition ease-out duration-500"
                              x-transition:enter-start="opacity-0 translate-y-4"
                              x-transition:enter-end="opacity-100 translate-y-0"
                              style="animation: fadeInUp 0.3s ease-out {{ $loop->index * 0.05 }}s both;">
                            <div class="flex items-start justify-between mb-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="text-sm sm:text-lg font-bold text-neutral-200 truncate">{{ $order->display_id }}</span>
                                        @if ($order->is_grouped)
                                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">{{ $order->order_count }} pedidos</span>
                                        @endif
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $order->typeClasses }}">{{ $order->typeLabel }}</span>
                                    </div>
                                     <p class="text-xs sm:text-sm text-neutral-300 mt-1 font-medium truncate">{{ $order->customer_name }}</p>
                                    <p class="text-xs text-neutral-500 mt-0.5">{{ $order->created_at }}</p>
                                </div>
                                <span class="px-2 sm:px-2.5 py-0.5 sm:py-1 text-[10px] sm:text-xs font-medium rounded-full shrink-0 {{ $order->statusClasses }}">{{ $order->statusLabel }}</span>
                            </div>
                            @if (!$order->is_grouped && $order->address_json && isset($order->address_json['address']))
                                <div class="mb-3 p-2 rounded-lg bg-neutral-800/50 border border-neutral-800/50">
                                    <p class="text-xs text-neutral-400 truncate" title="{{ $order->address_json['address'] }}">
                                        <svg class="w-3 h-3 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        {{ $order->address_json['address'] }}
                                    </p>
                                    @if (!empty($order->address_json['reference']))
                                        <p class="text-xs text-neutral-500 mt-0.5 ml-4">Ref: {{ $order->address_json['reference'] }}</p>
                                    @endif
                                </div>
                            @endif
                            <div class="space-y-1.5 mb-4">
                                @foreach ($order->items->take(3) as $item)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-neutral-300">{{ $item->quantity }}x {{ $item->product_name }}</span>
                                        <span class="text-neutral-400">R$ {{ number_format($item->price * $item->quantity, 2, ',', '.') }}</span>
                                    </div>
                                @endforeach
                                @if ($order->items->count() > 3)
                                    <p class="text-xs text-neutral-500">+{{ $order->items->count() - 3 }} itens</p>
                                @endif
                            </div>
                            <div class="flex items-center justify-between pt-3 border-t border-neutral-800">
                                <span class="font-bold text-amber-400">R$ {{ number_format($order->total, 2, ',', '.') }}</span>
                                <button wire:click="viewOrder({{ $order->id }})"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-neutral-800 text-neutral-300 border border-neutral-700 hover:bg-amber-500 hover:text-neutral-950 hover:border-amber-500 transition-all duration-200 group-hover:scale-105">
                                    Detalhes
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <style>
            @keyframes fadeInUp {
                from { opacity: 0; transform: translateY(1rem); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
    @endif

    {{-- Tab: Grid --}}
    @if ($tab === 'grid')
        <livewire:admin.table-grid wire:key="table-grid" />
    @endif

    {{-- Order Detail Modal --}}
    @if ($showOrderModal && $viewingOrder)
        <div class="fixed inset-0 z-60 flex items-center justify-center p-4"
             x-data x-init="$el.querySelector('button')?.focus()"
             @keydown.window.escape="$wire.closeOrderModal()">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"
                 wire:click="closeOrderModal"></div>
            <div class="relative w-full max-w-lg bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 max-h-[90vh] flex flex-col"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-4">
                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-800">
                    <div>
                        <h3 class="text-lg font-bold">Pedido #{{ $viewingOrder['id'] }}</h3>
                        <p class="text-xs text-neutral-500">{{ $viewingOrder['created_at'] }}</p>
                    </div>
                    <button wire:click="closeOrderModal"
                            class="p-1.5 rounded-lg hover:bg-neutral-800 text-neutral-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $viewingOrder['typeClasses'] }}">
                                {{ $viewingOrder['typeLabel'] }}
                            </span>
                            <span class="px-3 py-1 text-xs font-medium rounded-full {{ $viewingOrder['statusColor'] }}">
                                {{ $viewingOrder['statusLabel'] }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-neutral-500">{{ $viewingOrder['payment_method'] ?: '-' }}</span>
                            @if ($viewingOrder['payment_change'])
                                <span class="text-xs text-emerald-400">(Troco para R$ {{ number_format($viewingOrder['payment_change'], 2, ',', '.') }})</span>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 p-4 rounded-xl bg-neutral-800/50">
                        <div>
                            <p class="text-xs text-neutral-500 mb-1">Cliente</p>
                            <p class="text-sm font-medium">{{ $viewingOrder['customer_name'] }}</p>
                        </div>
                        @if ($viewingOrder['customer_phone'])
                            <div>
                                <p class="text-xs text-neutral-500 mb-1">Telefone</p>
                                <p class="text-sm font-medium">{{ $viewingOrder['customer_phone'] }}</p>
                            </div>
                        @endif
                        @if ($viewingOrder['customer_points'] > 0)
                            <div>
                                <p class="text-xs text-neutral-500 mb-1">Pontos do Cliente</p>
                                <p class="text-sm font-medium text-emerald-400 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ number_format($viewingOrder['customer_points'], 0, ',', '.') }} pts
                                </p>
                            </div>
                        @endif
                        @if ($viewingOrder['table_number'])
                            <div>
                                <p class="text-xs text-neutral-500 mb-1">Mesa</p>
                                <p class="text-sm font-medium">{{ $viewingOrder['table_number'] }}</p>
                            </div>
                        @elseif ($viewingOrder['address_json'])
                            <div class="col-span-2">
                                <p class="text-xs text-amber-400 mb-1">Entrega</p>
                                <p class="text-sm text-neutral-300">{{ $viewingOrder['address_json']['address'] ?? '' }}</p>
                                @if (!empty($viewingOrder['address_json']['reference']))
                                    <p class="text-xs text-neutral-500 mt-0.5">Ref: {{ $viewingOrder['address_json']['reference'] }}</p>
                                @endif
                            </div>
                        @else
                            <div>
                                <p class="text-xs text-amber-400 mb-1">Tipo</p>
                                <p class="text-sm font-medium text-amber-400">{{ $viewingOrder['typeLabel'] }}</p>
                            </div>
                        @endif
                        @if ($viewingOrder['delivery_cost'])
                            <div>
                                <p class="text-xs text-neutral-500 mb-1">Custo de Entrega</p>
                                <p class="text-sm font-medium text-amber-400">R$ {{ number_format($viewingOrder['delivery_cost'], 2, ',', '.') }}</p>
                            </div>
                        @endif
                        @if ($viewingOrder['delivery_person'])
                            <div>
                                <p class="text-xs text-neutral-500 mb-1">Entregador</p>
                                <p class="text-sm font-medium">{{ $viewingOrder['delivery_person'] }}</p>
                            </div>
                        @endif
                        @if ($viewingOrder['notes'])
                            <div class="col-span-2">
                                <p class="text-xs text-neutral-500 mb-1">Observacoes</p>
                                <p class="text-sm text-neutral-300">{{ $viewingOrder['notes'] }}</p>
                            </div>
                        @endif
                    </div>

                     {{-- Items --}}
                     <div>
                         <div class="flex items-center justify-between mb-3">
                             <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Itens</p>
                                @if (!$viewingOrder['is_fechado'] && !in_array($viewingOrder['status'], \App\Models\Order::STATUS_FINISHED))
                                <button wire:click="openAddItem({{ $viewingOrder['id'] }})"
                                        class="text-xs text-amber-400 hover:text-amber-300 transition-colors flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    Adicionar Item
                                </button>
                            @endif
                        </div>
                        <div class="space-y-2">
                            @foreach ($viewingOrder['items'] as $item)
                                <div class="flex items-center justify-between text-sm p-2 rounded-lg {{ $item['change_requested'] ? 'bg-blue-500/10 border border-blue-500/20' : '' }}">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-neutral-300">{{ $item['quantity'] }}x {{ $item['product_name'] }}</span>
                                            @if ($item['change_requested'])
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400">Troca</span>
                                            @endif
                                        </div>
                                        @if ($item['change_requested'] && $item['change_note'])
                                            <p class="text-xs text-neutral-500 mt-0.5">{{ $item['change_note'] }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="text-neutral-400">R$ {{ number_format($item['subtotal'], 2, ',', '.') }}</span>
                                @if (!$viewingOrder['is_fechado'] && !in_array($viewingOrder['status'], \App\Models\Order::STATUS_FINISHED))
                                            <button wire:click="removeItemFromOrder({{ $item['id'] }})"
                                                    wire:confirm="Remover este item do pedido?"
                                                    class="p-1 rounded text-neutral-500 hover:text-red-400 hover:bg-red-500/10 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Payments --}}
                    @if (!empty($viewingOrder['payments']))
                        <div>
                            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider mb-2">Pagamentos</p>
                            <div class="space-y-2">
                                @foreach ($viewingOrder['payments'] as $payment)
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-emerald-500/5 border border-emerald-500/10">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-medium">{{ $payment['payment_method_label'] }}</span>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded {{ $payment['status_classes'] }}">{{ $payment['status_label'] }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-bold text-emerald-400">R$ {{ number_format($payment['amount'], 2, ',', '.') }}</span>
                                            @if ($payment['paid_at'])
                                                <p class="text-[10px] text-neutral-500">{{ $payment['paid_at'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($viewingOrder['points_used'])
                        <div class="flex items-center justify-between text-sm pt-2">
                            <span class="text-emerald-400 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Desconto por Pontos
                            </span>
                            <span class="text-emerald-400">-R$ {{ number_format($viewingOrder['points_discount'], 2, ',', '.') }} ({{ number_format($viewingOrder['points_spent'], 0, ',', '.') }} pts)</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between pt-3 border-t border-neutral-800">
                        <span class="text-sm font-medium text-neutral-400">Total</span>
                        <span class="text-lg font-bold text-amber-400">R$ {{ number_format($viewingOrder['total'], 2, ',', '.') }}</span>
                    </div>
                    @if ($viewingOrder['delivery_cost'])
                        <div class="flex items-center justify-between text-xs text-neutral-500 pt-1">
                            <span>Custo de Entrega (entregador)</span>
                            <span>-R$ {{ number_format($viewingOrder['delivery_cost'], 2, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs font-medium pt-1 border-t border-neutral-800">
                            <span class="text-neutral-400">Liquido Restaurante</span>
                            <span class="text-neutral-400">R$ {{ number_format(max(0, $viewingOrder['total'] - $viewingOrder['delivery_cost']), 2, ',', '.') }}</span>
                        </div>
                    @endif
                    @if ($viewingOrder['pending_payment'] > 0 && !$viewingOrder['is_fechado'])
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-neutral-400">Pendente</span>
                            <span class="font-medium text-amber-400">R$ {{ number_format($viewingOrder['pending_payment'], 2, ',', '.') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Footer Actions --}}
                <div class="px-6 py-4 border-t border-neutral-800 flex flex-wrap gap-2">
                    @php $nextSt = $viewingOrder['nextStatus']; @endphp
                    @if ($nextSt && !$viewingOrder['is_fechado'])
                        <button wire:click="updateStatus({{ $viewingOrder['id'] }}, '{{ $nextSt }}')"
                                class="flex-1 min-w-[120px] px-4 py-2.5 text-sm font-semibold rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all duration-200 hover:scale-[1.02] active:scale-95">
                            {{ $viewingOrder['nextStatusLabel'] }}
                        </button>
                    @endif
                     @if (!in_array($viewingOrder['status'], ['fechado', 'cancelado']) && !$viewingOrder['is_fechado'])
                        <button wire:click="updateStatus({{ $viewingOrder['id'] }}, 'cancelado')"
                                class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-all duration-200">
                            Cancelar
                        </button>
                    @endif
                    @if (!$viewingOrder['is_fechado'] && $viewingOrder['pending_payment'] > 0)
                        <button wire:click="openPaymentModal({{ $viewingOrder['id'] }})"
                                class="flex-1 min-w-[120px] px-4 py-2.5 text-sm font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-400 text-neutral-950 transition-all duration-200 hover:scale-[1.02] active:scale-95">
                            Registrar Pagamento
                        </button>
                    @endif
                    @if (!$viewingOrder['is_fechado'] && in_array($viewingOrder['status'], ['entregue', 'saiu_entrega']) && $viewingOrder['pending_payment'] <= 0)
                        <button wire:click="closeBill({{ $viewingOrder['id'] }})"
                                wire:confirm="Fechar a conta?"
                                class="flex-1 min-w-[120px] px-4 py-2.5 text-sm font-semibold rounded-xl bg-purple-500 hover:bg-purple-400 text-neutral-950 transition-all duration-200 hover:scale-[1.02] active:scale-95">
                            Fechar Conta
                        </button>
                    @endif
                    @if ($viewingOrder['is_fechado'])
                        <div class="w-full text-center text-sm text-purple-400 py-2">
                            Conta fechada
                        </div>
                        @if (Auth::user()?->isAdmin())
                            <div class="flex gap-2 w-full mt-2">
                                <button wire:click="reopenAccount({{ $viewingOrder['id'] }})"
                                        class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20 hover:bg-blue-500/20 transition-all duration-200">
                                    Reabrir Conta
                                </button>
                                <button wire:click="cancelClosedOrder({{ $viewingOrder['id'] }})"
                                        wire:confirm="Cancelar esta conta fechada?"
                                        class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-all duration-200">
                                    Cancelar
                                </button>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Add Item Modal --}}
    @if ($showAddItemModal)
        <div class="fixed inset-0 z-70 flex items-center justify-center p-4"
             @keydown.window.escape="$wire.closeOrderModal()">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closeOrderModal"></div>
            <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6">
                <h3 class="text-lg font-bold mb-4">Adicionar Item ao Pedido #{{ $addItemOrderId }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">Produto</label>
                        <select wire:model="addItemProductId"
                                class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all">
                            <option value="">Selecione...</option>
                            @foreach ($availableProducts as $product)
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
                        <button wire:click="closeOrderModal"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all">
                            Cancelar
                        </button>
                        <button wire:click="addItemToOrder"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold transition-all">
                            Adicionar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Payment Modal (single order) --}}
    @if ($showPaymentModal)
        <div class="fixed inset-0 z-80 flex items-center justify-center p-4"
             @keydown.window.escape="$wire.closeOrderModal()">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closeOrderModal"></div>
            <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6">
                <h3 class="text-lg font-bold mb-4">Registrar Pagamento - PIX</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">Valor (R$)</label>
                        <input wire:model="paymentAmount" type="number" step="0.01" min="0.01" readonly
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white text-sm transition-all opacity-75">
                        @error('paymentAmount') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    @if ($pixQrCode)
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
                        <button wire:click="closeOrderModal"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all">
                            Cancelar
                        </button>
                        <div class="flex-1 flex gap-2">
                            @if (!$pixQrCode)
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

    {{-- Close Table Payment Modal --}}
    @if ($showCloseTableModal)
        <div class="fixed inset-0 z-80 flex items-center justify-center p-4"
             @keydown.window.escape="$wire.closeOrderModal()">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closeOrderModal"></div>
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
                        <button wire:click="closeOrderModal"
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

    {{-- Stock Adjustment Modal --}}
    @if ($showStockModal)
        <div class="fixed inset-0 z-80 flex items-center justify-center p-4"
             @keydown.window.escape="$wire.closeStockModal()">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closeStockModal"></div>
            <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6">
                <h3 class="text-lg font-bold mb-2">Ajustar Estoque</h3>
                <p class="text-sm text-neutral-400 mb-6">Defina a nova quantidade para este produto.</p>
                <form wire:submit="adjustStock" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-300 mb-2">Nova quantidade em estoque</label>
                        <input wire:model="stockAdjustmentValue" type="number" step="1" min="0" placeholder="0"
                               class="w-full px-4 py-3 text-2xl font-bold text-center rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                        <p class="mt-2 text-xs text-neutral-500">Defina a quantidade total disponível para venda. 0 = produto indisponível.</p>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" wire:loading.class="opacity-50"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-neutral-950 font-semibold transition-all flex items-center justify-center gap-2">
                            <span wire:loading.remove>Salvar Estoque</span>
                            <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                        </button>
                        <button type="button" wire:click="closeStockModal"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
