<div class="p-4 lg:p-8 space-y-6"
     x-data="{
         showForm: @entangle('showForm'),
         showQr: @entangle('showQr'),
         init() {
             this.$watch('showForm', val => { document.body.style.overflow = val ? 'hidden' : '' });
             this.$watch('showQr', val => { document.body.style.overflow = val ? 'hidden' : '' });
         }
     }"
     @keydown.window.escape="
         if (showForm) $wire.resetForm();
         if (showQr) $wire.closeQrCode();
     ">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Gerenciar Mesas</h1>
            <p class="text-sm text-neutral-400 mt-1">
                {{ $stats['total'] }} mesas cadastradas
                <span class="text-neutral-600 mx-1">|</span>
                Limite: {{ auth()->user()->tenant->maxTablesAllowed() }}
                @if (auth()->user()->tenant->isFree())
                    <span class="text-amber-400 font-medium">(Gratuito)</span>
                    <a href="{{ route('subscription.checkout') }}" class="text-amber-400 hover:text-amber-300 underline ml-1">Fazer upgrade</a>
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            <button wire:click="openBulkForm"
                    class="flex items-center gap-2 px-4 py-2.5 bg-neutral-800 hover:bg-neutral-700 text-white font-medium rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                </svg>
                Criar em Lote
            </button>
            <button wire:click="openCreateForm"
                    class="flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nova Mesa
            </button>
        </div>
    </div>

    {{-- Upgrade banner for free plan with hidden tables --}}
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

    {{-- Stats Bar --}}
    <div class="grid grid-cols-4 gap-3">
        <button wire:click="$set('statusFilter', '')"
                class="p-4 rounded-2xl text-center transition-all duration-200 border-2 {{ !$statusFilter ? 'border-amber-500 bg-amber-500/5' : 'border-transparent bg-neutral-900/50 hover:bg-neutral-800/50' }}">
            <p class="text-2xl font-bold">{{ $stats['total'] }}</p>
            <p class="text-xs text-neutral-400 mt-0.5">Todas</p>
        </button>
        <button wire:click="$set('statusFilter', 'free')"
                class="p-4 rounded-2xl text-center transition-all duration-200 border-2 {{ $statusFilter === 'free' ? 'border-emerald-500 bg-emerald-500/5' : 'border-transparent bg-neutral-900/50 hover:bg-neutral-800/50' }}">
            <p class="text-2xl font-bold text-emerald-400">{{ $stats['free'] }}</p>
            <p class="text-xs text-neutral-400 mt-0.5">Livres</p>
        </button>
        <button wire:click="$set('statusFilter', 'occupied')"
                class="p-4 rounded-2xl text-center transition-all duration-200 border-2 {{ $statusFilter === 'occupied' ? 'border-red-500 bg-red-500/5' : 'border-transparent bg-neutral-900/50 hover:bg-neutral-800/50' }}">
            <p class="text-2xl font-bold text-red-400">{{ $stats['occupied'] }}</p>
            <p class="text-xs text-neutral-400 mt-0.5">Ocupadas</p>
        </button>
        <button wire:click="$set('statusFilter', 'reserved')"
                class="p-4 rounded-2xl text-center transition-all duration-200 border-2 {{ $statusFilter === 'reserved' ? 'border-blue-500 bg-blue-500/5' : 'border-transparent bg-neutral-900/50 hover:bg-neutral-800/50' }}">
            <p class="text-2xl font-bold text-blue-400">{{ $stats['reserved'] }}</p>
            <p class="text-xs text-neutral-400 mt-0.5">Reservadas</p>
        </button>
    </div>

    {{-- Search --}}
    <div class="relative">
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar mesa por numero ou nome..."
               class="w-full pl-12 pr-4 py-3 rounded-2xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
        @if ($search)
            <button wire:click="$set('search', '')" class="absolute right-4 top-1/2 -translate-y-1/2 text-neutral-500 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        @endif
    </div>

    {{-- Create/Edit Form --}}
    @if($showForm)
        <div class="fixed inset-0 z-60" wire:key="table-form">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="resetForm"></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-2xl p-6 rounded-2xl bg-gradient-to-br from-neutral-900 to-neutral-950 border border-neutral-800 shadow-2xl shadow-black/30">

        <div class="flex items-center justify-between mb-6">
            <div class="flex gap-2">
                <button wire:click="$set('formMode', 'single')"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 {{ $formMode === 'single' ? 'bg-amber-500 text-neutral-950' : 'bg-neutral-800 text-neutral-400 hover:text-white' }}">
                    Unica
                </button>
                <button wire:click="$set('formMode', 'bulk')"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 {{ $formMode === 'bulk' ? 'bg-amber-500 text-neutral-950' : 'bg-neutral-800 text-neutral-400 hover:text-white' }}">
                    Em Lote
                </button>
            </div>
            <button wire:click="resetForm" class="p-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        @if ($formMode === 'single')
            <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Numero *</label>
                    <input wire:model="number" type="text" placeholder="Ex: 01, A1, Terraco 1"
                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('number') border-red-500 @enderror">
                    @error('number') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Capacidade *</label>
                    <input wire:model="capacity" type="number" min="1" max="50"
                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('capacity') border-red-500 @enderror">
                    @error('capacity') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Status</label>
                    <select wire:model="status"
                            class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        <option value="free">Livre</option>
                        <option value="occupied">Ocupada</option>
                        <option value="reserved">Reservada</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Observacao</label>
                    <input wire:model="observation" type="text" placeholder="Observacoes sobre a mesa..."
                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                </div>
                <div class="md:col-span-3 flex items-center gap-3 pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                             class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 flex items-center gap-2">
                        <span wire:loading.remove>{{ $editingTableId ? 'Atualizar Mesa' : 'Criar Mesa' }}</span>
                        <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                    </button>
                    <button type="button" wire:click="resetForm"
                            class="px-6 py-2.5 bg-neutral-800 hover:bg-neutral-700 text-neutral-300 rounded-xl transition-all duration-200">
                        Cancelar
                    </button>
                </div>
            </form>
        @else
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-300 mb-2">Prefixo (opcional)</label>
                        <input wire:model="bulkPrefix" type="text" placeholder="Ex: Mesa "
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-300 mb-2">Numero inicial *</label>
                        <input wire:model="bulkStart" type="number" min="1"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('bulkStart') border-red-500 @enderror">
                        @error('bulkStart') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-300 mb-2">Numero final *</label>
                        <input wire:model="bulkEnd" type="number" min="1"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('bulkEnd') border-red-500 @enderror">
                        @error('bulkEnd') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-300 mb-2">Capacidade *</label>
                        <input wire:model="bulkCapacity" type="number" min="1" max="50"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('bulkCapacity') border-red-500 @enderror">
                        @error('bulkCapacity') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                @error('bulkEnd')
                    <p class="text-sm text-red-400">{{ $message }}</p>
                @enderror

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                             class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 flex items-center gap-2">
                        <span wire:loading.remove>Criar {{ max(0, $bulkEnd - $bulkStart + 1) }} Mesas</span>
                        <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                    </button>
                    <button type="button" wire:click="resetForm"
                            class="px-6 py-2.5 bg-neutral-800 hover:bg-neutral-700 text-neutral-300 rounded-xl transition-all duration-200">
                        Cancelar
                    </button>
                </div>
            </form>
        @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Tables Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse ($this->tables as $table)
            <div class="relative group rounded-2xl bg-neutral-900/50 border transition-all duration-300 hover:shadow-2xl hover:shadow-black/40
                {{ $table->status === 'free' ? 'border-emerald-500/20 hover:border-emerald-500/40' : '' }}
                {{ $table->status === 'occupied' ? 'border-red-500/20 hover:border-red-500/40' : '' }}
                {{ $table->status === 'reserved' ? 'border-blue-500/20 hover:border-blue-500/40' : '' }}"
                 x-data="{ confirmDelete: false }">

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
                    <button wire:click="edit({{ $table->id }})" wire:loading.attr="disabled"
                             class="p-1.5 rounded-lg bg-neutral-800/80 text-neutral-400 hover:text-white hover:bg-neutral-700 transition-all backdrop-blur-sm disabled:opacity-30"
                             title="Editar">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button wire:click="toggleStatus({{ $table->id }})" wire:loading.attr="disabled"
                             class="p-1.5 rounded-lg bg-neutral-800/80 text-neutral-400 hover:text-white hover:bg-neutral-700 transition-all backdrop-blur-sm disabled:opacity-30"
                             title="Alternar status">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <button wire:click="showQrCode({{ $table->id }})"
                            class="p-1.5 rounded-lg bg-neutral-800/80 text-neutral-400 hover:text-white hover:bg-neutral-700 transition-all backdrop-blur-sm"
                            title="QR Code">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                    </button>
                    <button x-show="!confirmDelete" @click="confirmDelete = true"
                            class="p-1.5 rounded-lg bg-neutral-800/80 text-neutral-400 hover:text-red-400 hover:bg-red-500/10 transition-all backdrop-blur-sm"
                            title="Excluir">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                    <div x-show="confirmDelete" x-cloak class="flex gap-1">
                        <button @click="confirmDelete = false; $wire.delete({{ $table->id }})" wire:loading.attr="disabled"
                                 class="px-2 py-1 text-[10px] font-bold bg-red-500 text-white rounded-lg hover:bg-red-400 transition-colors disabled:opacity-50">
                            Sim
                        </button>
                        <button @click="confirmDelete = false"
                                class="px-2 py-1 text-[10px] font-bold bg-neutral-800 text-neutral-400 rounded-lg hover:text-white transition-colors">
                            Nao
                        </button>
                    </div>
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
                    <button wire:click="toggleStatus({{ $table->id }})" wire:loading.attr="disabled"
                             class="flex-1 py-2 text-xs font-semibold rounded-xl transition-all duration-200 disabled:opacity-50
                             {{ $table->status === 'free' ? 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 border border-emerald-500/20' : '' }}
                             {{ $table->status === 'occupied' ? 'bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20' : '' }}
                             {{ $table->status === 'reserved' ? 'bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/20' : '' }}">
                        {{ $table->status === 'free' ? 'Ocupar' : ($table->status === 'occupied' ? 'Reservar' : 'Liberar') }}
                    </button>
                        <a href="{{ route('menu.show', ['slug' => $table->tenant->slug]) }}" target="_blank"
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
                        @if ($search || $statusFilter)
                            Nenhuma mesa corresponde aos filtros aplicados. Tente alterar os criterios de busca.
                        @else
                            Voce ainda nao possui mesas cadastradas. Crie mesas individuais ou em lote para comecar a usar o sistema de atendimento.
                        @endif
                    </p>
                    @if (!$search && !$statusFilter)
                        <div class="flex justify-center gap-3">
                            <button wire:click="openCreateForm"
                                    class="px-6 py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200">
                                Criar Primeira Mesa
                            </button>
                            <button wire:click="openBulkForm"
                                    class="px-6 py-3 bg-neutral-800 hover:bg-neutral-700 text-white font-medium rounded-xl transition-all duration-200">
                                Criar em Lote
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($this->tables->hasPages())
        <div class="pt-4">
            {{ $this->tables->onEachSide(1)->links() }}
        </div>
    @endif

    {{-- QR Code Modal --}}
    @if ($showQr)
        <div class="fixed inset-0 z-60" wire:key="qr-modal">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closeQrCode"></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-sm p-8 rounded-3xl bg-neutral-900 border border-neutral-800 shadow-2xl shadow-black/50">
                    <div class="text-center">
                        <h3 class="text-lg font-bold mb-1">QR Code da Mesa</h3>
                        <p class="text-sm text-neutral-400 mb-6">
                            Mesa {{ $qrTableNumber }}
                        </p>

                        <div class="w-56 h-56 mx-auto mb-6 p-3 bg-white rounded-2xl flex items-center justify-center">
                            <img src="{{ $qrImage }}" alt="QR Code da Mesa {{ $qrTableNumber }}" class="w-full h-full">
                        </div>

                        <p class="text-xs text-neutral-500 mb-6 break-all bg-neutral-800/50 p-3 rounded-xl">
                            {{ $qrUrl }}
                        </p>

                        <div class="flex gap-3">
                            <a href="{{ $qrUrl }}" target="_blank"
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
