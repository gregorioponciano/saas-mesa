<div class="p-4 lg:p-8 space-y-6">
    <x-admin.page-header
        title="Programa de Fidelidade"
        subtitle="Gerencie o sistema de pontos, defina produtos para troca e acompanhe o ranking de clientes."
    />

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        {{-- Coluna de Configurações --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Card de Configurações Gerais --}}
            <x-admin.card>
                <h3 class="text-lg font-semibold leading-6 text-white mb-1">Configurações do Programa de Pontos</h3>
                <p class="text-sm text-neutral-400 mb-6">
                    Os clientes acumulam pontos a cada pedido pago e podem trocá-los por produtos no cardápio.
                </p>

                <form wire:submit="saveLoyaltyConfig" class="space-y-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="font-medium text-neutral-200">Ativar Programa de Fidelidade</h4>
                            <p class="text-sm text-neutral-500">Permite que clientes acumulem e gastem pontos.</p>
                             @if (!$tenant->isPaid())
                                <p class="text-xs text-amber-400 mt-1">Exclusivo do plano Premium.</p>
                            @endif
                        </div>
                        <div wire:ignore>
                            <button
                                type="button"
                                @if(!$tenant->isPaid()) disabled title="Funcionalidade exclusiva do plano Premium" @endif
                                x-data="{ on: @js($points_enabled) }"
                                x-on:click="on = !on; $wire.set('points_enabled', on)"
                                x-bind:style="`background-color: ${on ? '#f59e0b' : '#404040'}`"
                                class="relative inline-flex h-7 w-12 items-center rounded-full transition-all duration-300 ease-in-out cursor-pointer focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-neutral-950 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span x-bind:style="`transform: translateX(${on ? 26 : 2}px)`"
                                      class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform duration-300 ease-in-out"></span>
                            </button>
                        </div>
                    </div>

                    @if($points_enabled)
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 pt-4 border-t border-neutral-800">
                            <div>
                                <label for="points_percentage" class="block text-sm font-medium leading-6 text-neutral-300">Percentual de Pontos Ganhos (%)</label>
                                <input wire:model="points_percentage" type="number" id="points_percentage" class="mt-2 block w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all" placeholder="Ex: 5">
                                <p class="mt-1 text-xs text-neutral-500">Percentual do valor do pedido que vira pontos. Ex: 5% de R$100 = 5 pontos.</p>
                                @error('points_percentage') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="points_to_money_rate" class="block text-sm font-medium leading-6 text-neutral-300">Taxa de Conversão (1 Ponto = R$)</label>
                                <input wire:model="points_to_money_rate" type="number" step="0.0001" id="points_to_money_rate" class="mt-2 block w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all" placeholder="Ex: 0.10">
                                <p class="mt-1 text-xs text-neutral-500">Valor em Reais de cada ponto. Ex: 0.10 significa que 1 ponto vale R$0,10.</p>
                                @error('points_to_money_rate') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="min_points_order_value" class="block text-sm font-medium leading-6 text-neutral-300">Valor Mínimo do Pedido para Usar Pontos</label>
                                <input wire:model="min_points_order_value" type="number" step="0.01" id="min_points_order_value" class="mt-2 block w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all" placeholder="Ex: 20.00">
                                <p class="mt-1 text-xs text-neutral-500">Valor mínimo que o pedido deve ter para o cliente poder usar seus pontos.</p>
                                @error('min_points_order_value') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end pt-4 border-t border-neutral-800">
                        <x-admin.button type="submit" variant="primary">
                            Salvar Configurações
                        </x-admin.button>
                    </div>
                </form>
            </x-admin.card>

            {{-- Card de Produtos para Troca --}}
            <x-admin.card>
                <h3 class="text-lg font-semibold leading-6 text-white">Produtos para Troca por Pontos</h3>
                <p class="mt-1 text-sm text-neutral-400">
                    Defina quais produtos do seu cardápio podem ser resgatados com pontos e o custo de cada um.
                </p>
                <div class="mt-4">
                    <a href="{{ route('dashboard.menu') }}?view=pontos" wire:navigate
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 bg-neutral-800 text-neutral-300 hover:bg-neutral-700 hover:text-white">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Gerenciar Produtos por Pontos
                    </a>
                </div>
            </x-admin.card>
        </div>

        {{-- Coluna do Ranking --}}
        <div class="lg:col-span-1">
            <x-admin.card :padding="false">
                <div class="p-6">
                    <h3 class="text-lg font-semibold leading-6 text-white">Clientes com Mais Pontos</h3>
                </div>
                <div class="border-t border-neutral-800">
                    <ul role="list" class="divide-y divide-neutral-800">
                        @forelse($customerRanking as $index => $point)
                            <li class="flex items-center justify-between gap-x-6 px-6 py-4 hover:bg-neutral-800/30 transition-colors">
                                <div class="flex min-w-0 items-center gap-x-4">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-800 text-sm font-medium text-neutral-400">
                                        #{{ $customerRanking->firstItem() + $index }}
                                    </span>
                                    <div class="min-w-0 flex-auto">
                                        <p class="text-sm font-semibold leading-6 text-neutral-200">{{ $point->user->name }}</p>
                                        <p class="mt-1 truncate text-xs leading-5 text-neutral-500">{{ $point->user->email }}</p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-x-4">
                                    <div class="sm:flex sm:flex-col sm:items-end">
                                        <p class="text-sm font-bold leading-6 text-amber-400">{{ number_format($point->balance, 0, ',', '.') }} pts</p>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="px-6 py-8 text-center text-sm text-neutral-500">
                                Nenhum cliente com pontos ainda.
                            </li>
                        @endforelse
                    </ul>
                </div>
                @if($customerRanking->hasPages())
                    <div class="border-t border-neutral-800 px-4 py-3">
                        {{ $customerRanking->links() }}
                    </div>
                @endif
            </x-admin.card>
        </div>
    </div>
</div>