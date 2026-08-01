<div class="p-4 lg:p-8 space-y-6">
    <x-admin.page-header
        title="Configurações"
        subtitle="Gerencie as informações do restaurante e seu perfil"
    />

    {{-- Restaurant Info --}}
    <x-admin.card>
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold">Dados do Restaurante</h2>
        </div>

        <form wire:submit="saveTenant" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-neutral-300 mb-2">Logo do Restaurante</label>
                <div class="flex items-center gap-4">
                    <div class="w-20 h-20 rounded-xl bg-neutral-950 border border-neutral-800 flex items-center justify-center overflow-hidden shrink-0">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" class="w-full h-full object-contain">
                        @elseif ($tenant->logo)
                            <img src="{{ Storage::url($tenant->logo) }}" class="w-full h-full object-contain">
                        @else
                            <svg class="w-8 h-8 text-neutral-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1 space-y-2">
                        <input wire:model="logo" type="file" accept="image/jpeg,image/png,image/jpg,image/webp"
                               class="w-full text-sm text-neutral-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-amber-500/10 file:text-amber-400 hover:file:bg-amber-500/20 cursor-pointer @error('logo') border-red-500 @enderror">
                        <p class="text-xs text-neutral-500">PNG, JPG ou WebP. Max 2MB.</p>
                        @error('logo') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                        <div class="flex items-center gap-4">
                            <div>
                                <label class="block text-xs font-medium text-neutral-400 mb-1">Largura (px)</label>
                                <input wire:model="logoWidth" type="number" min="20" max="120"
                                       class="w-24 px-3 py-1.5 rounded-lg bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent @error('logoWidth') border-red-500 @enderror">
                                @error('logoWidth') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-neutral-400 mb-1">Altura (px)</label>
                                <input wire:model="logoHeight" type="number" min="20" max="120"
                                       class="w-24 px-3 py-1.5 rounded-lg bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent @error('logoHeight') border-red-500 @enderror">
                                @error('logoHeight') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                    @if ($logo || $tenant->logo)
                        <button type="button" wire:click="removeLogo"
                                class="p-2 rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-colors shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
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
            <div x-data="{
                phoneDisplay: '',
                init() { this.phoneDisplay = $wire.whatsapp ? this.fmt($wire.whatsapp) : ''; },
                fmt(v) {
                    let r = (v||'').replace(/\D/g,'').substring(0,11);
                    if (r.length<=2) return r.length ? '('+r : '';
                    if (r.length<=6) return '('+r.substring(0,2)+') '+r.substring(2);
                    if (r.length<=7) return '('+r.substring(0,2)+') '+r.substring(2,7);
                    return '('+r.substring(0,2)+') '+r.substring(2,7)+'-'+r.substring(7);
                },
                onPhoneInput() {
                    let raw = (this.phoneDisplay||'').replace(/\D/g,'').substring(0,11);
                    this.phoneDisplay = this.fmt(raw);
                    $wire.whatsapp = raw;
                }
            }">
                <label class="block text-sm font-medium text-neutral-300 mb-2">WhatsApp</label>
                <input type="tel" inputmode="numeric" placeholder="(11) 99999-9999" autocomplete="tel" maxlength="15"
                       x-model="phoneDisplay"
                       @input="onPhoneInput"
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
            <div class="md:col-span-2">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-neutral-300">Cobranca da Taxa de Entrega</label>
                    <button type="button" wire:click="$toggle('deliveryCostEnabled')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $deliveryCostEnabled ? 'bg-amber-500' : 'bg-neutral-700' }}">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $deliveryCostEnabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                </div>
                @if ($deliveryCostEnabled)
                <p class="text-xs text-neutral-500 mb-3">A taxa final do cliente e a soma: valor fixo + (custo por km x distancia). Deixe R$ 0,00 no campo que nao quiser cobrar.</p>
                <p class="text-xs text-neutral-500 mt-2">O raio de entrega definido abaixo e o limite de seguranca: ninguem fora dele consegue fechar pedido de entrega.</p>
                @else
                <p class="text-xs text-neutral-500">Taxa de entrega desativada. O cliente nao pagara taxa adicional nos pedidos de entrega.</p>
                @endif
            </div>
            @if ($deliveryCostEnabled)
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Custo Fixo de Entrega (p/ entregador)</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-neutral-500">R$</span>
                    <input wire:model="deliveryCostPerOrder" type="number" step="0.01" min="0" placeholder="0.00"
                           class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('deliveryCostPerOrder') border-red-500 @enderror">
                </div>
                <p class="text-xs text-neutral-500 mt-1">Valor fixo cobrado por entrega, somado ao custo por km.</p>
                @error('deliveryCostPerOrder') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Custo por Km (p/ entregador)</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-neutral-500">R$</span>
                    <input wire:model="deliveryCostPerKm" type="number" step="0.01" min="0" placeholder="0.00"
                           class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('deliveryCostPerKm') border-red-500 @enderror">
                </div>
                <p class="text-xs text-neutral-500 mt-1">Valor multiplicado pela distancia (km) da entrega, somado ao valor fixo.</p>
                @error('deliveryCostPerKm') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            @endif

            {{-- Delivery Address --}}
            <div class="md:col-span-2 border-t border-neutral-800 pt-4 mt-2">
                <h3 class="text-sm font-semibold text-neutral-200 mb-3">Area de Entrega</h3>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Endereco (Rua)</label>
                <input wire:model="deliveryAddress" type="text" placeholder="Rua do restaurante"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('deliveryAddress') border-red-500 @enderror">
                @error('deliveryAddress') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Numero</label>
                <input wire:model="deliveryNumber" type="text" placeholder="S/N"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('deliveryNumber') border-red-500 @enderror">
                @error('deliveryNumber') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Bairro</label>
                <input wire:model="deliveryNeighborhood" type="text" placeholder="Bairro"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('deliveryNeighborhood') border-red-500 @enderror">
                @error('deliveryNeighborhood') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Cidade</label>
                <input wire:model="deliveryCity" type="text" placeholder="Cidade"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('deliveryCity') border-red-500 @enderror">
                @error('deliveryCity') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Estado (UF)</label>
                <input wire:model="deliveryState" type="text" maxlength="2" placeholder="SP"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('deliveryState') border-red-500 @enderror">
                @error('deliveryState') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">CEP</label>
                <input wire:model="deliveryZipcode" type="text" maxlength="10" placeholder="00000-000"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('deliveryZipcode') border-red-500 @enderror">
                @error('deliveryZipcode') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Raio de Entrega (km)</label>
                <input wire:model="deliveryRadius" type="number" step="0.5" min="1" max="100" placeholder="10"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('deliveryRadius') border-red-500 @enderror">
                <p class="text-xs text-neutral-500 mt-1">Distancia maxima para entrega a partir do restaurante.</p>
                @error('deliveryRadius') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2 flex items-center gap-3 pt-2">
                <x-admin.button variant="primary" type="submit">
                    Salvar Restaurante
                </x-admin.button>
            </div>
        </form>
    </x-admin.card>

    {{-- User Profile --}}
    <x-admin.card>
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
                <x-admin.button variant="primary" type="submit">
                    Salvar Perfil
                </x-admin.button>
            </div>
        </form>
    </x-admin.card>

    {{-- LGPD & Privacy --}}
    <x-admin.card>
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
                    <x-admin.button variant="secondary" wire:click="exportData" wire:loading.attr="disabled" wire:target="exportData">
                        <span wire:loading.remove.delay wire:target="exportData">Exportar</span>
                        <span wire:loading wire:target="exportData">Exportando...</span>
                    </x-admin.button>
                </div>
            </div>

            {{-- Delete Account --}}
            <div class="p-5 rounded-xl bg-neutral-950 border border-red-500/10">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-red-400">Excluir minha conta</h3>
                        <p class="text-xs text-neutral-500 mt-1">Remove seu usuario do restaurante. Pedidos feitos por voce serao anonimizados.</p>
                    </div>
                    <x-admin.button variant="danger" @click="$wire.openAccountDelete()">
                        Excluir Conta
                    </x-admin.button>
                </div>
            </div>

            {{-- Delete Tenant --}}
            <div class="p-5 rounded-xl bg-neutral-950 border border-red-500/20">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-red-400">Excluir restaurante e todos os dados</h3>
                        <p class="text-xs text-neutral-500 mt-1">Remove permanentemente seu restaurante, usuarios, mesas, cardapio e historico de pedidos. Esta acao nao pode ser desfeita.</p>
                    </div>
                    <x-admin.button variant="danger" @click="$wire.openTenantDelete()">
                        Excluir Tudo
                    </x-admin.button>
                </div>
            </div>
        </div>

        {{-- Account Delete Confirmation Modal --}}
        <div x-data="{ showAccountDelete: $wire.entangle('showAccountDeleteConfirm') }"
             x-show="showAccountDelete" x-cloak
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
                        <x-admin.button variant="secondary" type="button" @click="$wire.cancelAccountDelete()" class="flex-1">
                            Cancelar
                        </x-admin.button>
                        <x-admin.button variant="danger" type="submit" class="flex-1">
                            Excluir Conta
                        </x-admin.button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tenant Delete Confirmation Modal --}}
        <div x-data="{ showTenantDelete: $wire.entangle('showTenantDeleteConfirm') }"
             x-show="showTenantDelete" x-cloak
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
                        <x-admin.button variant="secondary" type="button" @click="$wire.cancelTenantDelete()" class="flex-1">
                            Cancelar
                        </x-admin.button>
                        <x-admin.button variant="danger" type="submit" class="flex-1">
                            Excluir Tudo
                        </x-admin.button>
                    </div>
                </form>
            </div>
        </div>
    </x-admin.card>
</div>
