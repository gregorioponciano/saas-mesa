<div class="p-4 lg:p-8 space-y-8">
    <div>
        <h1 class="text-2xl font-bold">Configurações</h1>
        <p class="text-sm text-neutral-400 mt-1">Gerencie as informações do restaurante e seu perfil</p>
    </div>

    {{-- Restaurant Info --}}
    <div class="p-6 rounded-2xl bg-neutral-900/50 border border-neutral-800">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold">Dados do Restaurante</h2>
        </div>

        <form wire:submit="saveTenant" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Nome do Restaurante</label>
                <input wire:model="tenantName" type="text" placeholder="Nome"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('tenantName') border-red-500 @enderror">
                @error('tenantName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Email do Restaurante</label>
                <input wire:model="tenantEmail" type="email" placeholder="email@restaurante.com"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('tenantEmail') border-red-500 @enderror">
                @error('tenantEmail') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">WhatsApp</label>
                <input wire:model="whatsapp" type="text" placeholder="(11) 99999-9999"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('whatsapp') border-red-500 @enderror">
                @error('whatsapp') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Horário de Abertura</label>
                <input wire:model="openingTime" type="time" step="60" 
                       placeholder="08:00"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('openingTime') border-red-500 @enderror">
                @error('openingTime') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Horário de Fechamento</label>
                <input wire:model="closingTime" type="time" step="60" 
                       placeholder="22:00"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('closingTime') border-red-500 @enderror">
                @error('closingTime') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Plano</label>
                <div class="flex items-center gap-3 px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800">
                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $tenant->isPaid() ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-neutral-800 text-neutral-400 border border-neutral-700' }}">
                        {{ $tenant->planLabel() }}
                    </span>
                    <span class="text-xs text-neutral-500">
                        @if ($tenant->isFree())
                            Limite de {{ $tenant->maxTablesAllowed() }} mesas
                        @else
                            {{ $tenant->maxTablesAllowed() }} mesas disponiveis
                        @endif
                    </span>
                    @if ($tenant->isFree())
                        <a href="{{ route('subscription.checkout') }}"
                           class="ml-auto text-xs font-semibold text-amber-400 hover:text-amber-300 transition-colors">Fazer Upgrade</a>
                    @endif
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Custo de Entrega (p/ entregador)</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-neutral-500">R$</span>
                    <input wire:model="deliveryCostPerOrder" type="number" step="0.01" min="0" placeholder="0.00"
                           class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('deliveryCostPerOrder') border-red-500 @enderror">
                </div>
                <p class="text-xs text-neutral-500 mt-1">Valor padrao pago ao entregador por entrega.</p>
                @error('deliveryCostPerOrder') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2 flex items-center gap-3 pt-2">
                <button type="submit"
                        class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                    Salvar Restaurante
                </button>
            </div>
        </form>
    </div>

    {{-- User Profile --}}
    <div class="p-6 rounded-2xl bg-neutral-900/50 border border-neutral-800">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold">Meu Perfil</h2>
        </div>

        <form wire:submit="saveProfile" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Nome</label>
                <input wire:model="name" type="text" placeholder="Seu nome"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('name') border-red-500 @enderror">
                @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Email</label>
                <input wire:model="email" type="email" placeholder="email@exemplo.com"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('email') border-red-500 @enderror">
                @error('email') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Nova Senha (opcional)</label>
                <input wire:model="password" type="password" placeholder="Minimo 6 caracteres"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('password') border-red-500 @enderror">
                @error('password') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Confirmar Senha</label>
                <input wire:model="passwordConfirmation" type="password" placeholder="Repita a senha"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('passwordConfirmation') border-red-500 @enderror">
                @error('passwordConfirmation') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2 flex items-center gap-3 pt-2">
                <button type="submit"
                        class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                    Salvar Perfil
                </button>
            </div>
        </form>
    </div>

    {{-- LGPD & Privacy --}}
    <div class="p-6 rounded-2xl bg-neutral-900/50 border border-neutral-800"
         x-data="{
             showAccountDelete: @entangle('showAccountDeleteConfirm'),
             showTenantDelete: @entangle('showTenantDeleteConfirm'),
         }">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-violet-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold">Privacidade e Dados (LGPD)</h2>
        </div>

        <div class="space-y-4">
            {{-- Export --}}
            <div class="p-5 rounded-xl bg-neutral-950 border border-neutral-800">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-neutral-200">Exportar meus dados</h3>
                        <p class="text-xs text-neutral-500 mt-1">Baixe um arquivo JSON com todos os seus dados pessoais e do restaurante.</p>
                    </div>
                    <button wire:click="exportData"
                            wire:loading.attr="disabled"
                            class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 transition-all duration-200 shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span wire:loading.remove>Exportar</span>
                        <span wire:loading>Exportando...</span>
                    </button>
                </div>
            </div>

            {{-- Delete Account --}}
            <div class="p-5 rounded-xl bg-neutral-950 border border-red-500/10">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-red-400">Excluir minha conta</h3>
                        <p class="text-xs text-neutral-500 mt-1">Remove seu usuario do restaurante. Pedidos feitos por voce serao anonimizados.</p>
                    </div>
                    <button @click="$wire.openAccountDelete()"
                            class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-all duration-200 shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Excluir Conta
                    </button>
                </div>
            </div>

            {{-- Delete Tenant --}}
            <div class="p-5 rounded-xl bg-neutral-950 border border-red-500/20">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-red-400">Excluir restaurante e todos os dados</h3>
                        <p class="text-xs text-neutral-500 mt-1">Remove permanentemente seu restaurante, usuarios, mesas, cardapio e historico de pedidos. Esta acao nao pode ser desfeita.</p>
                    </div>
                    <button @click="$wire.openTenantDelete()"
                            class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl bg-red-500/20 text-red-400 border border-red-500/30 hover:bg-red-500/30 transition-all duration-200 shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Excluir Tudo
                    </button>
                </div>
            </div>
        </div>

        {{-- Account Delete Confirmation Modal --}}
        <div x-show="showAccountDelete" x-cloak
             class="fixed inset-0 z-[70] flex items-center justify-center p-4"
             @keydown.window.escape="$wire.cancelAccountDelete()">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="$wire.cancelAccountDelete()"></div>
            <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">
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

                <p class="text-sm text-neutral-300 mb-4">Seus pedidos serao anonimizados e sua conta sera removida permanentemente.</p>

                <form wire:submit="deleteAccount" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-400 mb-2">
                            Digite <span class="font-bold text-red-400">EXCLUIR</span> para confirmar
                        </label>
                        <input wire:model="deleteConfirmation" type="text" placeholder="EXCLUIR"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-700 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all @error('deleteConfirmation') border-red-500 @enderror">
                        @error('deleteConfirmation') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="$wire.cancelAccountDelete()"
                                class="flex-1 px-4 py-2.5 text-sm font-medium rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 transition-all">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-xl bg-red-500 hover:bg-red-400 text-white transition-all duration-200 disabled:opacity-50">
                            Excluir Conta
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tenant Delete Confirmation Modal --}}
        <div x-show="showTenantDelete" x-cloak
             class="fixed inset-0 z-[70] flex items-center justify-center p-4"
             @keydown.window.escape="$wire.cancelTenantDelete()">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="$wire.cancelTenantDelete()"></div>
            <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-red-400">Excluir restaurante</h3>
                        <p class="text-xs text-neutral-500">Todos os dados serao perdidos permanentemente</p>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-red-500/5 border border-red-500/10 mb-4">
                    <p class="text-sm text-red-300">Isso excluira permanentemente:</p>
                    <ul class="text-xs text-neutral-400 mt-2 space-y-1 list-disc list-inside">
                        <li>Restaurante <strong class="text-neutral-300">{{ $tenant->name }}</strong></li>
                        <li>Todos os usuarios e suas contas</li>
                        <li>Todas as mesas cadastradas</li>
                        <li>Cardapio (categorias e produtos)</li>
                        <li>Historico completo de pedidos</li>
                    </ul>
                </div>

                <form wire:submit="deleteTenant" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-400 mb-2">
                            Digite <span class="font-bold text-red-400">EXCLUIR TUDO</span> para confirmar
                        </label>
                        <input wire:model="deleteTenantConfirmation" type="text" placeholder="EXCLUIR TUDO"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-700 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all @error('deleteTenantConfirmation') border-red-500 @enderror">
                        @error('deleteTenantConfirmation') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="$wire.cancelTenantDelete()"
                                class="flex-1 px-4 py-2.5 text-sm font-medium rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 transition-all">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-xl bg-red-500 hover:bg-red-400 text-white transition-all duration-200 disabled:opacity-50">
                            Excluir Tudo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
