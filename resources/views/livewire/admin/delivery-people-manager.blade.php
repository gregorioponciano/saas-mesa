<div class="p-4 lg:p-8 space-y-6">
    <x-admin.page-header
        title="Entregadores"
        subtitle="Gerencie os entregadores do restaurante"
    >
        <x-slot:action>
            <x-admin.button
                variant="primary"
                wire:click="openModal"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>'
            >
                Novo Entregador
            </x-admin.button>
        </x-slot:action>
    </x-admin.page-header>

    {{-- Search --}}
    <div class="max-w-md">
        <input wire:model.live.debounce="search" type="text" placeholder="Buscar por nome ou telefone..."
               class="w-full px-4 py-2.5 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
    </div>

    {{-- Table --}}
    <x-admin.card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-800">
                <thead class="bg-neutral-900">
                    <tr>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">Nome</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">Telefone</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">API Token</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">Acoes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800">
                    @forelse ($deliveryPeople as $delivery)
                        <tr class="hover:bg-neutral-800/50 transition-colors">
                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm font-medium">{{ $delivery->name }}</td>
                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-neutral-400">{{ $delivery->phone }}</td>
                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                <x-admin.badge variant="{{ $delivery->isActive() ? 'success' : 'neutral' }}">
                                    {{ $delivery->isActive() ? 'Ativo' : 'Inativo' }}
                                </x-admin.badge>
                            </td>
                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm">
                                @if ($delivery->api_token)
                                    <span class="text-xs text-emerald-400">Gerado</span>
                                @else
                                    <x-admin.button variant="ghost" wire:click="generateToken({{ $delivery->id }})" class="px-2 py-1 text-xs rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/20">
                                        Gerar Token
                                    </x-admin.button>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm space-x-2">
                                <x-admin.button variant="secondary" wire:click="openModal({{ $delivery->id }})" class="px-2.5 py-1 text-xs rounded-lg">
                                    Editar
                                </x-admin.button>
                                <x-admin.button variant="danger" wire:click="delete({{ $delivery->id }})" wire:confirm="Remover entregador?" class="px-2.5 py-1 text-xs rounded-lg">
                                    Remover
                                </x-admin.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-neutral-500">Nenhum entregador cadastrado</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    @if ($deliveryPeople->hasPages())
        <div class="mt-4">
            {{ $deliveryPeople->links() }}
        </div>
    @endif

    {{-- Modal --}}
    <div x-data="{ open: @entangle('showModal') }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[70] flex items-center justify-center p-4"
         @keydown.window.escape="$wire.closeModal()">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="closeModal"></div>
        <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6">
            <h3 class="text-lg font-bold mb-4">{{ $editingId ? 'Editar' : 'Novo' }} Entregador</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1">Nome *</label>
                    <input wire:model="name" type="text" placeholder="Nome do entregador"
                           class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('name') border-red-500 @enderror">
                    @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1">Telefone *</label>
                    <input wire:model="phone" type="text" placeholder="(11) 99999-9999"
                           class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('phone') border-red-500 @enderror">
                    @error('phone') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1">Status</label>
                    <select wire:model="status"
                            class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        <option value="active">Ativo</option>
                        <option value="inactive">Inativo</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" wire:click="closeModal"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium text-sm transition-all">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold text-sm transition-all">
                        {{ $editingId ? 'Atualizar' : 'Salvar' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
