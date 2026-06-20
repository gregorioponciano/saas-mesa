@php
    $isStaff = Auth::check() && Auth::user()->isStaff();
    $isAdmin = Auth::check() && Auth::user()->isAdmin();
@endphp

<div class="p-3 sm:p-4 lg:p-8 space-y-4 sm:space-y-6 lg:space-y-8" wire:poll.20s x-data="{
    orderModal: @entangle('showOrderModal'),
    init() {
        this.$watch('orderModal', val => { document.body.style.overflow = val ? 'hidden' : ''; });
    }
}">
    @php use App\Models\Order; @endphp

    {{-- Tab Navigation --}}
    <div class="flex gap-1 p-1 rounded-2xl bg-neutral-900 border border-neutral-800 w-fit overflow-x-auto">
        <button wire:click="switchTab('overview')"
                class="flex items-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl text-[11px] sm:text-sm font-medium transition-all duration-200 {{ $tab === 'overview' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
            <svg class="w-3.5 sm:w-4 h-3.5 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span class="hidden xs:inline">Visao Geral</span><span class="xs:hidden">Geral</span>
        </button>
        <button wire:click="switchTab('grid')"
                class="flex items-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl text-[11px] sm:text-sm font-medium transition-all duration-200 {{ $tab === 'grid' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
            <svg class="w-3.5 sm:w-4 h-3.5 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            <span class="hidden xs:inline">Mapa de Mesas</span><span class="xs:hidden">Mesas</span>
            @if ($waiterOccupiedTablesCount > 0)
                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-red-500/20 text-red-400">{{ $waiterOccupiedTablesCount }}</span>
            @endif
        </button>
        <button wire:click="switchTab('orders')"
                class="flex items-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl text-[11px] sm:text-sm font-medium transition-all duration-200 {{ $tab === 'orders' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
            <svg class="w-3.5 sm:w-4 h-3.5 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2-1m2 1l2-1m2 1l2-1m2-2v2a1 1 0 001 1h2m0 0a1 1 0 100 2m-2-2a1 1 0 110 2m-10-4h.01M16 12h4m0 0l-3-3m3 3l-3 3"/></svg>
            <span>Delivery</span>
            @if ($waiterDeliveryOrders->count() > 0)
                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-blue-500/20 text-blue-400">{{ $waiterDeliveryOrders->count() }}</span>
            @endif
        </button>

        <button wire:click="switchTab('history')"
                class="flex items-center gap-1.5 sm:gap-2 px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl text-[11px] sm:text-sm font-medium transition-all duration-200 {{ $tab === 'history' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
            <svg class="w-3.5 sm:w-4 h-3.5 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="hidden xs:inline">Historico</span><span class="xs:hidden">Hist.</span>
        </button>
    </div>

    {{-- ===== TAB: OVERVIEW ===== --}}
    @if ($tab === 'overview')
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 sm:gap-4">
            <div class="p-3 sm:p-4 lg:p-5 rounded-2xl bg-gradient-to-br from-amber-500/10 to-amber-600/5 border border-amber-500/10 hover:border-amber-500/20 transition-all duration-300">
                <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-amber-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] sm:text-xs font-medium text-neutral-500 uppercase tracking-wider truncate">Faturamento Total</span>
                </div>
                <p class="text-lg sm:text-xl lg:text-2xl font-bold text-amber-400">R$ {{ number_format($totalRevenue, 2, ',', '.') }}</p>
            </div>
            <div class="p-3 sm:p-4 lg:p-5 rounded-2xl bg-gradient-to-br from-blue-500/10 to-blue-600/5 border border-blue-500/10 hover:border-blue-500/20 transition-all duration-300">
                <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-blue-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 002-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <span class="text-[10px] sm:text-xs font-medium text-neutral-500 uppercase tracking-wider truncate">Faturamento Delivery</span>
                </div>
                <p class="text-lg sm:text-xl lg:text-2xl font-bold text-blue-400">R$ {{ number_format($deliveryRevenue, 2, ',', '.') }}</p>
            </div>
            <div class="p-3 sm:p-4 lg:p-5 rounded-2xl bg-gradient-to-br from-red-500/10 to-red-600/5 border border-red-500/10 hover:border-red-500/20 transition-all duration-300">
                <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-red-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] sm:text-xs font-medium text-neutral-500 uppercase tracking-wider truncate">Faturamento Mesa</span>
                </div>
                <p class="text-lg sm:text-xl lg:text-2xl font-bold text-red-400">R$ {{ number_format($tableRevenue, 2, ',', '.') }}</p>
            </div>
            <div class="p-3 sm:p-4 lg:p-5 rounded-2xl bg-gradient-to-br from-green-500/10 to-green-600/5 border border-green-500/10 hover:border-green-500/20 transition-all duration-300">
                <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-green-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12l2 2m0 0l2-2m2 2l-2-2m2 2l-2 2"/></svg>
                    </div>
                    <span class="text-[10px] sm:text-xs font-medium text-neutral-500 uppercase tracking-wider truncate">Hoje Delivery</span>
                </div>
                <p class="text-lg sm:text-xl lg:text-2xl font-bold text-green-400">{{ $deliveryOrdersToday }}</p>
            </div>
            <div class="p-3 sm:p-4 lg:p-5 rounded-2xl bg-gradient-to-br from-indigo-500/10 to-indigo-600/5 border border-indigo-500/10 hover:border-indigo-500/20 transition-all duration-300">
                <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12l2 2m0 0l2-2m2 2l-2-2m2 2l-2 2"/></svg>
                    </div>
                    <span class="text-[10px] sm:text-xs font-medium text-neutral-500 uppercase tracking-wider truncate">Hoje Mesa</span>
                </div>
                <p class="text-lg sm:text-xl lg:text-2xl font-bold text-indigo-400">{{ $tableOrdersToday }}</p>
            </div>
            <div class="p-3 sm:p-4 lg:p-5 rounded-2xl bg-gradient-to-br from-purple-500/10 to-purple-600/5 border border-purple-500/10 hover:border-purple-500/20 transition-all duration-300">
                <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-purple-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 002-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <span class="text-[10px] sm:text-xs font-medium text-neutral-500 uppercase tracking-wider truncate">Pedidos Hoje</span>
                </div>
                <p class="text-lg sm:text-xl lg:text-2xl font-bold text-purple-400">{{ $ordersToday }}</p>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-2 sm:gap-3">
            <div class="p-2 sm:p-4 rounded-xl bg-emerald-500/5 border border-emerald-500/10 text-center">
                <p class="text-base sm:text-2xl font-bold text-emerald-400">{{ $tableStats['free'] }}</p>
                <p class="text-[10px] sm:text-xs text-neutral-500 mt-0.5 sm:mt-1">Livres</p>
            </div>
            <div class="p-2 sm:p-4 rounded-xl bg-red-500/5 border border-red-500/10 text-center">
                <p class="text-base sm:text-2xl font-bold text-red-400">{{ $tableStats['occupied'] }}</p>
                <p class="text-[10px] sm:text-xs text-neutral-500 mt-0.5 sm:mt-1">Ocupadas</p>
            </div>
            <div class="p-2 sm:p-4 rounded-xl bg-blue-500/5 border border-blue-500/10 text-center">
                <p class="text-base sm:text-2xl font-bold text-blue-400">{{ $tableStats['reserved'] ?? 0 }}</p>
                <p class="text-[10px] sm:text-xs text-neutral-500 mt-0.5 sm:mt-1">Reservadas</p>
            </div>
            <div class="p-2 sm:p-4 rounded-xl bg-purple-500/5 border border-purple-500/10 text-center">
                <p class="text-base sm:text-2xl font-bold text-purple-400">{{ $pickupOrdersToday }}</p>
                <p class="text-[10px] sm:text-xs text-neutral-500 mt-0.5 sm:mt-1">Retiradas</p>
            </div>
        </div>

        @if ($occupiedTablesWithOrders->count() > 0)
            <div class="p-3 sm:p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800">
                <h2 class="text-xs sm:text-sm font-bold mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Mesas Ocupadas
                    <span class="text-xs text-neutral-500 font-normal">({{ $tableStats['occupied'] }})</span>
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach ($occupiedTablesWithOrders as $table)
                        <div class="p-3 rounded-xl bg-red-500/5 border border-red-500/10">
                            <div class="flex items-center justify-between">
                                <p class="text-lg font-bold text-red-400">Mesa {{ $table->number }}</p>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-500/15 text-red-400">{{ $table->orders_count }} pedido{{ $table->orders_count !== 1 ? 's' : '' }}</span>
                            </div>
                            <p class="text-[10px] text-neutral-500 mt-1">Cap. {{ $table->capacity }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="p-3 sm:p-6 rounded-2xl bg-neutral-900/50 border border-neutral-800">
            <div class="flex items-center justify-between mb-3 sm:mb-6 flex-wrap gap-2">
                <h2 class="text-sm sm:text-lg font-bold shrink-0">Receita</h2>
                <div class="flex gap-1 p-0.5 rounded-lg bg-neutral-800">
                    @foreach (['today' => 'Hoje', 'week' => '7 Dias', 'month' => '30 Dias'] as $key => $label)
                        <button wire:click="$set('period', '{{ $key }}')"
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200 {{ $period === $key ? 'bg-amber-500 text-neutral-950' : 'text-neutral-400 hover:text-white' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <div class="flex items-end gap-1.5 h-32">
                @php $maxRevenue = max(1, collect($revenueData)->max('total')); @endphp
                @foreach ($revenueData as $data)
                    <div class="flex-1 flex flex-col items-center gap-1 group">
                        <div class="relative w-full flex items-end justify-center" style="height: {{ max(8, ($data['total'] / $maxRevenue) * 100) }}%">
                            <div class="w-full rounded-t-lg bg-gradient-to-t from-amber-600 to-amber-400 hover:from-amber-500 hover:to-amber-300 transition-all duration-300 min-h-[4px]" style="height: {{ max(8, ($data['total'] / $maxRevenue) * 100) }}%"></div>
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-neutral-800 text-white text-xs px-2 py-1 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">R$ {{ number_format($data['total'], 2, ',', '.') }}</div>
                        </div>
                        <span class="text-xs text-neutral-500">{{ $data['date'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>


    @endif

    {{-- ===== TAB: GRID (Mapa de Mesas) ===== --}}
    @if ($tab === 'grid')
        <div>
            @if ($tenant->hasHiddenTables())
                <div class="p-4 mb-6 rounded-2xl bg-gradient-to-r from-amber-500/10 to-amber-600/5 border border-amber-500/20">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-amber-400">{{ $tenant->hiddenTablesCount() }} mesas ocultas</p>
                            <p class="text-xs text-neutral-400 mt-0.5">Plano Gratuito: apenas {{ $tenant->maxTablesAllowed() }} mesas disponiveis.</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex items-center gap-3 mb-6 overflow-x-auto pb-2">
                <button wire:click="$set('tableFilter', 'all')"
                        class="px-3 sm:px-5 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all duration-200 whitespace-nowrap border {{ $tableFilter === 'all' ? 'bg-amber-500 text-neutral-950 border-amber-500' : 'bg-neutral-900 text-neutral-400 hover:text-white border-neutral-800 hover:border-neutral-700' }}"><span class="font-bold">{{ $tables->count() }}</span> Todas</button>
                <button wire:click="$set('tableFilter', 'free')"
                        class="px-3 sm:px-5 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all duration-200 whitespace-nowrap border {{ $tableFilter === 'free' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-neutral-900 text-neutral-400 hover:text-white border-neutral-800 hover:border-neutral-700' }}"><span class="font-bold">{{ $freeTables->count() }}</span> Livres</button>
                <button wire:click="$set('tableFilter', 'occupied')"
                        class="px-3 sm:px-5 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all duration-200 whitespace-nowrap border {{ $tableFilter === 'occupied' ? 'bg-red-500/10 text-red-400 border-red-500/30' : 'bg-neutral-900 text-neutral-400 hover:text-white border-neutral-800 hover:border-neutral-700' }}"><span class="font-bold">{{ $occupiedTables->count() }}</span> Ocupadas</button>
                <button wire:click="$set('tableFilter', 'reserved')"
                        class="px-3 sm:px-5 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all duration-200 whitespace-nowrap border {{ $tableFilter === 'reserved' ? 'bg-blue-500/10 text-blue-400 border-blue-500/30' : 'bg-neutral-900 text-neutral-400 hover:text-white border-neutral-800 hover:border-neutral-700' }}"><span class="font-bold">{{ $reservedTables->count() }}</span> Reservadas</button>
            </div>

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

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2 sm:gap-3">
                @forelse ($tables as $table)
                    @if ($tableFilter === 'all' || $table->status === $tableFilter)
                        <button wire:click="selectTable({{ $table->id }})" wire:loading.attr="disabled"
                                class="relative p-3 sm:p-4 rounded-2xl border-2 text-center transition-all duration-300 hover:scale-[1.03] active:scale-[0.97] group shadow-lg disabled:opacity-60 disabled:cursor-not-allowed
                                {{ $table->status === 'free' ? 'bg-gradient-to-b from-emerald-500/5 to-emerald-600/5 border-emerald-500/30 hover:border-emerald-500/60' : '' }}
                                {{ $table->status === 'occupied' ? 'bg-gradient-to-b from-red-500/5 to-red-600/5 border-red-500/30 hover:border-red-500/60' : '' }}
                                {{ $table->status === 'reserved' ? 'bg-gradient-to-b from-blue-500/5 to-blue-600/5 border-blue-500/30 hover:border-blue-500/60' : '' }}
                                {{ $selectedTableId === $table->id ? 'ring-2 ring-amber-500 scale-[1.03]' : '' }}">
                            @if ($table->orders_count > 0)
                                <span class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center shadow-lg shadow-red-500/30 animate-bounce">{{ $table->orders_count }}</span>
                            @endif
                            <span class="absolute top-2 right-2 w-2 h-2 rounded-full
                                {{ $table->status === 'free' ? 'bg-emerald-400 animate-pulse' : '' }}
                                {{ $table->status === 'occupied' ? 'bg-red-400' : '' }}
                                {{ $table->status === 'reserved' ? 'bg-blue-400' : 'bg-emerald-400/50' }}"></span>
                                <div class="flex flex-col items-center gap-1 sm:gap-2">
                                <div class="w-10 sm:w-14 h-10 sm:h-14 rounded-xl flex items-center justify-center text-lg sm:text-2xl font-black transition-all duration-300 group-hover:scale-110
                                    {{ $table->status === 'free' ? 'bg-emerald-500/10 text-emerald-400 group-hover:bg-emerald-500/20' : '' }}
                                    {{ $table->status === 'occupied' ? 'bg-red-500/10 text-red-400 group-hover:bg-red-500/20' : '' }}
                                    {{ $table->status === 'reserved' ? 'bg-blue-500/10 text-blue-400 group-hover:bg-blue-500/20' : '' }}">{{ $table->number }}</div>
                                <span class="text-[10px] text-neutral-500">Cap. {{ $table->capacity }}</span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full
                                    {{ $table->status === 'free' ? 'bg-emerald-500/10 text-emerald-400' : '' }}
                                    {{ $table->status === 'occupied' ? 'bg-red-500/10 text-red-400' : '' }}
                                    {{ $table->status === 'reserved' ? 'bg-blue-500/10 text-blue-400' : '' }}">
                                    {{ $table->status === 'free' ? 'Livre' : ($table->status === 'occupied' ? 'Ocupada' : 'Reservada') }}</span>
                            </div>
                        </button>
                    @endif
                @empty
                    <div class="col-span-full text-center py-16 text-neutral-500">
                        <p class="text-lg font-medium text-neutral-300">Nenhuma mesa cadastrada</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Table Detail Drawer --}}
        @if ($selectedTableId !== null)
            @include('livewire.waiter._table-detail')
        @endif
    @endif

    {{-- ===== TAB: TABLES (Gerenciar Mesas) ===== --}}
    @if ($tab === 'tables')
        @php
            $allTables = $tables;
            if ($tableSearch) {
                $allTables = $allTables->filter(fn($t) => str_contains(strtolower($t->number), strtolower($tableSearch)) || str_contains(strtolower($t->observation ?? ''), strtolower($tableSearch)));
            }
            if ($tableStatusFilter) {
                $allTables = $allTables->where('status', $tableStatusFilter);
            }
        @endphp
        <div class="space-y-6">
            {{-- Header --}}
            <div>
                <h1 class="text-2xl font-bold">Gerenciar Mesas</h1>
                <p class="text-sm text-neutral-400 mt-1">
                    {{ $tableStats['total'] }} mesas cadastradas
                    <span class="text-neutral-600 mx-1">|</span>
                    Limite: {{ $tenant->maxTablesAllowed() }}
                    @if ($tenant->isFree())
                        <span class="text-amber-400 font-medium">(Gratuito)</span>
                    @endif
                </p>
            </div>

            {{-- Stats Bar --}}
            <div class="grid grid-cols-4 gap-3">
                <button wire:click="$set('tableStatusFilter', '')"
                        class="p-4 rounded-2xl text-center transition-all duration-200 border-2 {{ !$tableStatusFilter ? 'border-amber-500 bg-amber-500/5' : 'border-transparent bg-neutral-900/50 hover:bg-neutral-800/50' }}">
                    <p class="text-2xl font-bold">{{ $tableStats['total'] }}</p>
                    <p class="text-xs text-neutral-400 mt-0.5">Todas</p>
                </button>
                <button wire:click="$set('tableStatusFilter', 'free')"
                        class="p-4 rounded-2xl text-center transition-all duration-200 border-2 {{ $tableStatusFilter === 'free' ? 'border-emerald-500 bg-emerald-500/5' : 'border-transparent bg-neutral-900/50 hover:bg-neutral-800/50' }}">
                    <p class="text-2xl font-bold text-emerald-400">{{ $tableStats['free'] }}</p>
                    <p class="text-xs text-neutral-400 mt-0.5">Livres</p>
                </button>
                <button wire:click="$set('tableStatusFilter', 'occupied')"
                        class="p-4 rounded-2xl text-center transition-all duration-200 border-2 {{ $tableStatusFilter === 'occupied' ? 'border-red-500 bg-red-500/5' : 'border-transparent bg-neutral-900/50 hover:bg-neutral-800/50' }}">
                    <p class="text-2xl font-bold text-red-400">{{ $tableStats['occupied'] }}</p>
                    <p class="text-xs text-neutral-400 mt-0.5">Ocupadas</p>
                </button>
                <button wire:click="$set('tableStatusFilter', 'reserved')"
                        class="p-4 rounded-2xl text-center transition-all duration-200 border-2 {{ $tableStatusFilter === 'reserved' ? 'border-blue-500 bg-blue-500/5' : 'border-transparent bg-neutral-900/50 hover:bg-neutral-800/50' }}">
                    <p class="text-2xl font-bold text-blue-400">{{ $tableStats['reserved'] }}</p>
                    <p class="text-xs text-neutral-400 mt-0.5">Reservadas</p>
                </button>
            </div>

            {{-- Search --}}
            <div class="relative">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input wire:model.live.debounce.300ms="tableSearch" type="text" placeholder="Buscar mesa por numero ou nome..."
                       class="w-full pl-12 pr-4 py-3 rounded-2xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                @if ($tableSearch)
                    <button wire:click="$set('tableSearch', '')" class="absolute right-4 top-1/2 -translate-y-1/2 text-neutral-500 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                @endif
            </div>

            {{-- Edit Form --}}
            @if($showTableForm)
                <div class="fixed inset-0 z-60" wire:key="table-form">
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="resetTableForm"></div>
                    <div class="absolute inset-0 flex items-center justify-center p-4">
                        <div class="w-full max-w-2xl p-6 rounded-2xl bg-gradient-to-br from-neutral-900 to-neutral-950 border border-neutral-800 shadow-2xl shadow-black/30">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-bold">Editar Mesa {{ $editTableNumber }}</h3>
                                <button wire:click="resetTableForm" class="p-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <form wire:submit="saveTable" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-neutral-300 mb-2">Numero *</label>
                                    <input wire:model="editTableNumber" type="text" placeholder="Ex: 01, A1, Terraco 1"
                                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('editTableNumber') border-red-500 @enderror">
                                    @error('editTableNumber') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-300 mb-2">Capacidade *</label>
                                    <input wire:model="editTableCapacity" type="number" min="1" max="50"
                                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('editTableCapacity') border-red-500 @enderror">
                                    @error('editTableCapacity') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-300 mb-2">Status</label>
                                    <select wire:model="editTableStatus"
                                            class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                                        <option value="free">Livre</option>
                                        <option value="occupied">Ocupada</option>
                                        <option value="reserved">Reservada</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-neutral-300 mb-2">Observacao</label>
                                    <input wire:model="editTableObservation" type="text" placeholder="Observacoes sobre a mesa..."
                                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                                </div>
                                <div class="md:col-span-3 flex items-center gap-3 pt-2">
                                    <button type="submit" wire:loading.attr="disabled"
                                             class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 flex items-center gap-2">
                                        <span wire:loading.remove>Atualizar Mesa</span>
                                        <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                                    </button>
                                    <button type="button" wire:click="resetTableForm"
                                            class="px-6 py-2.5 bg-neutral-800 hover:bg-neutral-700 text-neutral-300 rounded-xl transition-all duration-200">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Tables Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @forelse ($allTables as $table)
                    <div class="relative group rounded-2xl bg-neutral-900/50 border transition-all duration-300 hover:shadow-2xl hover:shadow-black/40
                        {{ $table->status === 'free' ? 'border-emerald-500/20 hover:border-emerald-500/40' : '' }}
                        {{ $table->status === 'occupied' ? 'border-red-500/20 hover:border-red-500/40' : '' }}
                        {{ $table->status === 'reserved' ? 'border-blue-500/20 hover:border-blue-500/40' : '' }}">
                        {{-- Status Badge --}}
                        <div class="absolute top-3 right-3 flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full animate-pulse
                                {{ $table->status === 'free' ? 'bg-emerald-400' : '' }}
                                {{ $table->status === 'occupied' ? 'bg-red-400' : '' }}
                                {{ $table->status === 'reserved' ? 'bg-blue-400' : '' }}">
                            </span>
                            <span class="text-[10px] font-semibold uppercase tracking-wider
                                {{ $table->status === 'free' ? 'text-emerald-400' : '' }}
                                {{ $table->status === 'occupied' ? 'text-red-400' : '' }}
                                {{ $table->status === 'reserved' ? 'text-blue-400' : '' }}">
                                {{ $table->status === 'free' ? 'Livre' : ($table->status === 'occupied' ? 'Ocupada' : 'Reservada') }}
                            </span>
                        </div>

                        {{-- Quick Actions toolbar --}}
                        <div class="absolute top-3 left-3 flex gap-1 opacity-0 group-hover:opacity-100 transition-all duration-200">
                            <button wire:click="editTable({{ $table->id }})" wire:loading.attr="disabled"
                                     class="p-1.5 rounded-lg bg-neutral-800/80 text-neutral-400 hover:text-white hover:bg-neutral-700 transition-all backdrop-blur-sm disabled:opacity-30"
                                     title="Editar">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button wire:click="toggleTableStatus({{ $table->id }})" wire:loading.attr="disabled"
                                     class="p-1.5 rounded-lg bg-neutral-800/80 text-neutral-400 hover:text-white hover:bg-neutral-700 transition-all backdrop-blur-sm disabled:opacity-30"
                                     title="Alternar status">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                            <button wire:click="showTableQrCode({{ $table->id }})"
                                    class="p-1.5 rounded-lg bg-neutral-800/80 text-neutral-400 hover:text-white hover:bg-neutral-700 transition-all backdrop-blur-sm"
                                    title="QR Code">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Card Content --}}
                        <div class="p-6 pt-12">
                            <div class="flex flex-col items-center text-center">
                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-2xl font-black mb-3
                                    {{ $table->status === 'free' ? 'bg-emerald-500/10 text-emerald-400' : '' }}
                                    {{ $table->status === 'occupied' ? 'bg-red-500/10 text-red-400' : '' }}
                                    {{ $table->status === 'reserved' ? 'bg-blue-500/10 text-blue-400' : '' }}">
                                    {{ $table->number }}
                                </div>
                                <h3 class="font-bold text-lg">Mesa {{ $table->number }}</h3>
                                <div class="flex items-center gap-3 mt-2 text-xs text-neutral-500">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        {{ $table->capacity }} pessoas
                                    </span>
                                    @if ($table->status === 'free')
                                        <span class="flex items-center gap-1 text-emerald-400">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Disponivel
                                        </span>
                                    @endif
                                </div>
                                @if ($table->observation)
                                    <p class="text-xs text-neutral-500 mt-2 italic">"{{ $table->observation }}"</p>
                                @endif
                            </div>
                        </div>

                        {{-- Bottom Actions --}}
                        <div class="px-6 pb-4">
                            <div class="flex gap-1.5">
                                <button wire:click="toggleTableStatus({{ $table->id }})" wire:loading.attr="disabled"
                                         class="flex-1 py-2 text-xs font-semibold rounded-xl transition-all duration-200 disabled:opacity-50
                                         {{ $table->status === 'free' ? 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 border border-emerald-500/20' : '' }}
                                         {{ $table->status === 'occupied' ? 'bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20' : '' }}
                                         {{ $table->status === 'reserved' ? 'bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/20' : '' }}">
                                    {{ $table->status === 'free' ? 'Ocupar' : ($table->status === 'occupied' ? 'Reservar' : 'Liberar') }}
                                </button>
                                <a href="{{ route('menu.show', ['slug' => $table->tenant->slug]) }}"
                                   class="px-3 py-2 text-xs font-medium rounded-xl bg-neutral-800 text-neutral-400 hover:text-white hover:bg-neutral-700 transition-all border border-neutral-700/50">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="text-center py-20">
                            <div class="w-20 h-20 mx-auto mb-6 rounded-3xl bg-neutral-900 flex items-center justify-center">
                                <svg class="w-10 h-10 text-neutral-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-neutral-300 mb-2">Nenhuma mesa encontrada</h3>
                            <p class="text-neutral-500 mb-8 max-w-md mx-auto">
                                @if ($tableSearch || $tableStatusFilter)
                                    Nenhuma mesa corresponde aos filtros aplicados. Tente alterar os criterios de busca.
                                @else
                                    Nenhuma mesa cadastrada.
                                @endif
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- QR Code Modal --}}
            @if ($showQr)
                <div class="fixed inset-0 z-60" wire:key="qr-modal">
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closeQrCode"></div>
                    <div class="absolute inset-0 flex items-center justify-center p-4">
                        <div class="w-full max-w-sm p-8 rounded-3xl bg-neutral-900 border border-neutral-800 shadow-2xl shadow-black/50">
                            <div class="text-center">
                                <h3 class="text-lg font-bold mb-1">QR Code da Mesa</h3>
                                <p class="text-sm text-neutral-400 mb-6">Mesa {{ $qrTableNumber }}</p>
                                <div class="w-56 h-56 mx-auto mb-6 p-3 bg-white rounded-2xl flex items-center justify-center">
                                    <img src="{{ $qrImage }}" alt="QR Code da Mesa {{ $qrTableNumber }}" class="w-full h-full">
                                </div>
                                <p class="text-xs text-neutral-500 mb-6 break-all bg-neutral-800/50 p-3 rounded-xl">{{ $qrUrl }}</p>
                                <div class="flex gap-3">
                                    <a href="{{ $qrUrl }}"
                                       class="flex-1 py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl text-center transition-all duration-200">
                                        Abrir Cardapio
                                    </a>
                                    <button wire:click="closeQrCode"
                                            class="px-6 py-3 bg-neutral-800 hover:bg-neutral-700 text-neutral-300 rounded-xl transition-all duration-200">
                                        Fechar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ===== TAB: DELIVERY ===== --}}
    @if ($tab === 'orders')
        <div class="space-y-4">
            {{-- Real-time Notification Bar --}}
            <div wire:poll.5s class="flex gap-2 flex-wrap">
                @php
                    $novos = $waiterDeliveryOrders->whereIn('status', ['novo'])->count();
                    $preparo = $waiterDeliveryOrders->whereIn('status', ['em_preparo'])->count();
                    $rota = $waiterDeliveryOrders->where('status', 'saiu_entrega')->count();
                @endphp
                @if ($novos > 0)
                    <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-medium">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        {{ $novos }} novo(s)
                    </span>
                @endif
                @if ($preparo > 0)
                    <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-medium">
                        <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                        {{ $preparo }} em preparo
                    </span>
                @endif
                @if ($rota > 0)
                    <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 text-xs font-medium">
                        <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                        {{ $rota }} em rota
                    </span>
                @endif
            </div>

            {{-- Filters --}}
            <div class="flex items-center gap-2 sm:gap-4 flex-wrap">
                <h2 class="text-lg font-bold shrink-0">Delivery</h2>
                <div class="flex gap-1 p-0.5 rounded-lg bg-neutral-900 border border-neutral-800 overflow-x-auto">
                    <button wire:click="$set('waiterDeliveryFilter', 'all')"
                            class="px-3 py-1.5 text-xs font-medium rounded-md transition-all whitespace-nowrap {{ $waiterDeliveryFilter === 'all' ? 'bg-amber-500 text-neutral-950' : 'text-neutral-400 hover:text-white' }}">Todas ({{ $waiterDeliveryOrders->count() }})</button>
                    <button wire:click="$set('waiterDeliveryFilter', 'pending')"
                            class="px-3 py-1.5 text-xs font-medium rounded-md transition-all whitespace-nowrap {{ $waiterDeliveryFilter === 'pending' ? 'bg-red-500 text-white' : 'text-neutral-400 hover:text-white' }}">Pendentes</button>
                    <button wire:click="$set('waiterDeliveryFilter', 'in_transit')"
                            class="px-3 py-1.5 text-xs font-medium rounded-md transition-all whitespace-nowrap {{ $waiterDeliveryFilter === 'in_transit' ? 'bg-blue-500 text-white' : 'text-neutral-400 hover:text-white' }}">Em Rota</button>
                    <button wire:click="$set('waiterDeliveryFilter', 'delivered')"
                            class="px-3 py-1.5 text-xs font-medium rounded-md transition-all whitespace-nowrap {{ $waiterDeliveryFilter === 'delivered' ? 'bg-emerald-500 text-white' : 'text-neutral-400 hover:text-white' }}">Entregues</button>
                </div>
            </div>

            @if ($waiterDeliveryOrders->count() === 0)
                <div class="text-center py-16 text-neutral-500">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2-1m2 1l2-1m2 1l2-1m2-2v2a1 1 0 001 1h2m0 0a1 1 0 100 2m-2-2a1 1 0 110 2m-10-4h.01M16 12h4m0 0l-3-3m3 3l-3 3"/></svg>
                    <p class="text-lg font-medium text-neutral-300">Nenhuma entrega encontrada</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach ($waiterDeliveryOrders as $order)
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
                                    <button wire:click="advanceOrder({{ $order->id }})"
                                            class="px-3 py-2 text-[11px] font-semibold rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all">
                                        {{ $order->statusFlowLabels()[$order->status] ?? 'Avancar' }}
                                    </button>
                                @endif
                                @if (in_array($order->status, ['novo', 'em_preparo', 'pronto', 'entregue']) && !$order->isBillClosed())
                                    <button wire:click="updateOrderStatus({{ $order->id }}, 'cancelado')"
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

    {{-- ===== TAB: HISTORY ===== --}}
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

    {{-- Order Detail Modal (same as admin) --}}
    @if ($showOrderModal && $viewingOrder)
        <div class="fixed inset-0 z-60 flex items-center justify-center p-4"
             x-data x-init="$el.querySelector('button')?.focus()"
             @keydown.window.escape="$wire.closeOrderModal()">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closeOrderModal"></div>
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
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $viewingOrder['typeClasses'] }}">{{ $viewingOrder['typeLabel'] }}</span>
                            <span class="px-3 py-1 text-xs font-medium rounded-full {{ $viewingOrder['statusColor'] }}">{{ $viewingOrder['statusLabel'] }}</span>
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
                        @if ($viewingOrder['notes'])
                            <div class="col-span-2">
                                <p class="text-xs text-neutral-500 mb-1">Observacoes</p>
                                <p class="text-sm text-neutral-300">{{ $viewingOrder['notes'] }}</p>
                            </div>
                        @endif
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Itens</p>
                            @if (!$viewingOrder['is_fechado'])
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
                                            @if ($item['change_requested'])<span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400">Troca</span>@endif
                                        </div>
                                        @if ($item['change_requested'] && $item['change_note'])<p class="text-xs text-neutral-500 mt-0.5">{{ $item['change_note'] }}</p>@endif
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="text-neutral-400">R$ {{ number_format($item['subtotal'], 2, ',', '.') }}</span>
                                        @if (!$viewingOrder['is_fechado'])
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
                                            @if ($payment['paid_at'])<p class="text-[10px] text-neutral-500">{{ $payment['paid_at'] }}</p>@endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-between pt-3 border-t border-neutral-800">
                        <span class="text-sm font-medium text-neutral-400">Total</span>
                        <span class="text-lg font-bold text-amber-400">R$ {{ number_format($viewingOrder['total'], 2, ',', '.') }}</span>
                    </div>
                    @if ($viewingOrder['pending_payment'] > 0 && !$viewingOrder['is_fechado'])
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-neutral-400">Pendente</span>
                            <span class="font-medium text-amber-400">R$ {{ number_format($viewingOrder['pending_payment'], 2, ',', '.') }}</span>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-4 border-t border-neutral-800 flex flex-wrap gap-2">
                    @php $nextSt = $viewingOrder['nextStatus']; @endphp
                    @if ($nextSt && !$viewingOrder['is_fechado'])
                        <button wire:click="updateOrderStatus({{ $viewingOrder['id'] }}, '{{ $nextSt }}')"
                                class="flex-1 min-w-[120px] px-4 py-2.5 text-sm font-semibold rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all duration-200 hover:scale-[1.02] active:scale-95">
                            {{ $viewingOrder['nextStatusLabel'] }}
                        </button>
                    @endif
                    @if (!in_array($viewingOrder['status'], \App\Models\Order::STATUS_FINISHED) && !$viewingOrder['is_fechado'])
                        <button wire:click="updateOrderStatus({{ $viewingOrder['id'] }}, 'cancelado')"
                                class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-all duration-200">
                            Cancelar
                        </button>
                    @elseif ($viewingOrder['is_fechado'] && $viewingOrder['status'] !== 'cancelado')
                        @if ($isAdmin)
                            <button wire:click="reopenAccount({{ $viewingOrder['id'] }})"
                                    class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20 hover:bg-blue-500/20 transition-all duration-200">
                                Reabrir Conta
                            </button>
                        @endif
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
                        <div class="w-full text-center text-sm text-purple-400 py-2">Conta fechada</div>
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
                                class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all">Cancelar</button>
                        <button wire:click="addItemToOrder"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold transition-all">Adicionar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Payment Modal --}}
    @if ($showPaymentModal)
        <div class="fixed inset-0 z-80 flex items-center justify-center p-4"
             @keydown.window.escape="$wire.closePaymentModal()">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closePaymentModal"></div>
            <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold">Registrar Pagamento</h3>
                    <button wire:click="closePaymentModal" class="p-1.5 rounded-lg hover:bg-neutral-800 text-neutral-400">
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
                        <select wire:model="paymentMethodInput"
                                class="w-full px-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            <option value="pix">PIX</option>
                            <option value="credit_card">Cartao de Credito</option>
                            <option value="debit_card">Cartao de Debito</option>
                            <option value="cash">Dinheiro</option>
                            <option value="other">Outro</option>
                        </select>
                    </div>
                    @if ($paymentMethodInput === 'pix' && !$pixQrCode)
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
                        <button wire:click="closePaymentModal"
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

    {{-- New Order Modal for Staff --}}
    @if (($orderingTableId !== null || $orderType === 'entrega' || $orderType === 'retirada') && $isStaff)
        <div class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="cancelOrdering"></div>
            <div class="relative w-full sm:max-w-2xl max-h-[90vh] bg-neutral-950 border border-neutral-800 rounded-t-3xl sm:rounded-3xl shadow-2xl shadow-black/50 overflow-hidden flex flex-col">
                <div class="flex items-center justify-between p-5 border-b border-neutral-800 shrink-0">
                    <div class="flex items-center gap-3">
                        @if ($orderType === 'entrega')
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div><h3 class="font-bold">Novo Pedido - Entrega</h3><p class="text-xs text-neutral-400">{{ $cartItemsCount }} itens | R$ {{ number_format($cartTotal, 2, ',', '.') }}</p></div>
                        @elseif ($orderType === 'retirada')
                            <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            </div>
                            <div><h3 class="font-bold">Novo Pedido - Retirada</h3><p class="text-xs text-neutral-400">{{ $cartItemsCount }} itens | R$ {{ number_format($cartTotal, 2, ',', '.') }}</p></div>
                        @else
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400 font-black">{{ $orderingTableNumber }}</div>
                            <div><h3 class="font-bold">Novo Pedido - Mesa {{ $orderingTableNumber }}</h3><p class="text-xs text-neutral-400">{{ $cartItemsCount }} itens | R$ {{ number_format($cartTotal, 2, ',', '.') }}</p></div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($cartItemsCount > 0)
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
                                    <select wire:model="paymentMethod"
                                            class="w-full px-4 py-2.5 rounded-xl bg-neutral-900 border border-neutral-700 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all">
                                        <option value="pix">Pix</option>
                                        <option value="cash">Dinheiro</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Observacao</label>
                                    <input wire:model="notes" type="text" placeholder="Observacoes..."
                                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-900 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                                </div>
                            </div>

                            @if ($paymentMethod === 'cash')
                                <div class="p-3 rounded-xl bg-neutral-800/50 border border-neutral-700 mt-3">
                                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Valor em Dinheiro</label>
                                    <input wire:model="cashAmount" type="number" step="0.01" min="0"
                                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-900 border border-neutral-700 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all"
                                           placeholder="Quanto vai pagar?">
                                    @php $change = $cashAmount ? $cashAmount - $cartTotal : 0; @endphp
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
                                <span class="text-lg font-bold text-amber-400">R$ {{ number_format($cartTotal, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="p-5">
                        <h4 class="text-sm font-semibold text-neutral-400 mb-3 uppercase tracking-wider">Cardapio</h4>
                        <div class="flex gap-2 overflow-x-auto pb-3 mb-4 scrollbar-hide">
                            @foreach ($categories as $cat)
                                <a href="#staff-menu-cat-{{ $cat->slug }}" class="px-4 py-2 rounded-full text-xs font-medium whitespace-nowrap bg-neutral-800 text-neutral-300 hover:bg-neutral-700 transition-all">{{ $cat->name }}</a>
                            @endforeach
                        </div>
                        <div class="space-y-6 max-h-[40vh] overflow-y-auto">
                            @foreach ($categories as $category)
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
    @if ($selectedProductModel)
        <div class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center"
             x-data x-init="$nextTick(() => document.body.style.overflow = 'hidden')"
             @keydown.window.escape="$wire.closeProduct()">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="closeProduct"></div>
            <div class="relative w-full sm:max-w-lg max-h-[85vh] bg-neutral-900 border border-neutral-800 rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-xl font-bold">{{ $selectedProductModel->name }}</h3>
                            <p class="text-2xl font-bold text-amber-400 mt-2">R$ {{ number_format($selectedProductModel->price, 2, ',', '.') }}</p>
                        </div>
                        <button wire:click="closeProduct" class="p-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 transition-colors"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    @if ($selectedProductModel->description)<p class="text-sm text-neutral-400 mb-6">{{ $selectedProductModel->description }}</p>@endif
                    @if ($selectedProductModel->image_url)<img src="{{ $selectedProductModel->imageUrl() }}" alt="{{ $selectedProductModel->name }}" class="w-full h-48 object-cover rounded-xl mb-6">@endif
                    <form @submit.prevent="
                        const form = $event.target;
                        const options = [];
                        form.querySelectorAll('select, input[type=radio]:checked, input[type=checkbox]:checked').forEach(el => {
                            if (el.value && el.name) { options.push(JSON.parse(el.value)); }
                        });
                        $wire.addToCart({{ $selectedProductModel->id }}, @js($selectedProductModel->name), {{ $selectedProductModel->price }}, options, 1);
                        $wire.closeProduct();
                    ">
                        @foreach ($selectedProductModel->attributes as $attribute)
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
