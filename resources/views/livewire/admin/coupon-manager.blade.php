<div class="p-4 lg:p-8 space-y-6">
    <x-admin.page-header title="Cupons de Desconto">
        <x-slot:action>
            <x-admin.button variant="primary" wire:click="switchTab('form')">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Novo Cupom
            </x-admin.button>
        </x-slot:action>
    </x-admin.page-header>

    {{-- List --}}
    @if ($tab === 'list')
        <div class="space-y-3">
            @forelse ($coupons as $coupon)
                <x-admin.card :padding="false" class="p-5 hover:border-neutral-700 transition-all">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-lg tracking-wider">{{ $coupon->code }}</span>
                                    <x-admin.badge :variant="$coupon->active ? 'success' : 'neutral'">
                                        {{ $coupon->active ? 'Ativo' : 'Inativo' }}
                                    </x-admin.badge>
                                </div>
                                <div class="flex items-center gap-3 mt-1 text-sm text-neutral-400">
                                    <span>{{ $coupon->discount_type === 'percentage' ? $coupon->discount_value . '%' : 'R$ ' . number_format($coupon->discount_value, 2, ',', '.') }}</span>
                                    @if ($coupon->min_order_value)
                                        <span>Min. R$ {{ number_format($coupon->min_order_value, 2, ',', '.') }}</span>
                                    @endif
                                    @if ($coupon->max_uses)
                                        <span>{{ $coupon->used_count }}/{{ $coupon->max_uses }} usos</span>
                                    @else
                                        <span>{{ $coupon->used_count }} usos</span>
                                    @endif
                                    @if ($coupon->expires_at)
                                        <span>Expira {{ $coupon->expires_at->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button wire:click="toggleActive({{ $coupon->id }})"
                                    class="p-2 rounded-lg hover:bg-neutral-800 text-neutral-400 hover:text-amber-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </button>
                            <button wire:click="edit({{ $coupon->id }})"
                                    class="p-2 rounded-lg hover:bg-neutral-800 text-neutral-400 hover:text-white transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button wire:click="delete({{ $coupon->id }})"
                                    wire:confirm="Remover cupom {{ $coupon->code }}?"
                                    class="p-2 rounded-lg hover:bg-neutral-800 text-neutral-400 hover:text-red-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </x-admin.card>
            @empty
                <div class="text-center py-16 text-neutral-500">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <p class="text-lg font-medium text-neutral-300 mb-2">Nenhum cupom ainda</p>
                    <x-admin.button variant="primary" wire:click="switchTab('form')">
                        Criar Cupom
                    </x-admin.button>
                </div>
            @endforelse
        </div>

        @if ($coupons->hasPages())
            <div class="mt-4">{{ $coupons->links() }}</div>
        @endif

    {{-- Form --}}
    @elseif ($tab === 'form')
        <div class="max-w-lg">
            <x-admin.card>
                <h2 class="text-lg font-bold mb-6">{{ $editingCouponId ? 'Editar Cupom' : 'Novo Cupom' }}</h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-300 mb-2">Codigo do Cupom</label>
                        <input wire:model="code" type="text" placeholder="Ex: BEMVINDO10"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent uppercase tracking-wider font-bold transition-all @error('code') border-red-500 @enderror">
                        @error('code') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Tipo</label>
                            <select wire:model="discountType"
                                    class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                                <option value="percentage">Porcentagem</option>
                                <option value="fixed">Valor Fixo</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Valor</label>
                            <input wire:model="discountValue" type="number" step="0.01" min="0.01"
                                   placeholder="{{ $discountType === 'percentage' ? '10' : '5.00' }}"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('discountValue') border-red-500 @enderror">
                            @error('discountValue') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Valor Minimo (opcional)</label>
                            <input wire:model="minOrderValue" type="number" step="0.01" min="0" placeholder="0.00"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Usos Maximos (opcional)</label>
                            <input wire:model="maxUses" type="number" min="1" placeholder="100"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-300 mb-2">Expira em (opcional)</label>
                        <input wire:model="expiresAt" type="datetime-local"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                    </div>

                    <div class="flex items-center gap-3">
                        <input wire:model="active" type="checkbox" id="coupon-active"
                               class="rounded text-amber-500 focus:ring-amber-500 bg-neutral-800 border-neutral-600">
                        <label for="coupon-active" class="text-sm font-medium text-neutral-300">Ativo</label>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <x-admin.button variant="primary" type="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ $editingCouponId ? 'Atualizar Cupom' : 'Criar Cupom' }}</span>
                            <span wire:loading>
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </span>
                        </x-admin.button>
                        <x-admin.button variant="secondary" type="button" wire:click="switchTab('list')">
                            Cancelar
                        </x-admin.button>
                    </div>
                </form>
            </x-admin.card>
        </div>
    @endif
</div>
