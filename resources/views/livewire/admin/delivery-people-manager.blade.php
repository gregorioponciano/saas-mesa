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

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-neutral-900 rounded-xl p-4 border border-neutral-800">
            <p class="text-xs text-neutral-400">Total</p>
            <p class="text-xl font-bold text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-neutral-900 rounded-xl p-4 border border-neutral-800">
            <p class="text-xs text-neutral-400">Ativos</p>
            <p class="text-xl font-bold text-emerald-400">{{ $stats['active'] }}</p>
        </div>
        <div class="bg-neutral-900 rounded-xl p-4 border border-neutral-800">
            <p class="text-xs text-neutral-400">Ativados</p>
            <p class="text-xl font-bold text-white">{{ $stats['activated'] }}</p>
        </div>
        <div class="bg-neutral-900 rounded-xl p-4 border border-neutral-800">
            <p class="text-xs text-neutral-400">Convites Pendentes</p>
            <p class="text-xl font-bold text-amber-400">{{ $stats['pending_invite'] }}</p>
        </div>
    </div>

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
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">Email</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">Telefone</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">CPF</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">Veículo</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">Auth</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">Desempenho</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800">
                    @forelse ($deliveryPeople as $delivery)
                        <tr class="hover:bg-neutral-800/50 transition-colors">
                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm font-medium">{{ $delivery->name }}</td>
                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-neutral-400">{{ $delivery->email ?: '-' }}</td>
                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-neutral-400">{{ $delivery->phone }}</td>
                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-neutral-400">{{ $delivery->cpf ?: '-' }}</td>
                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-neutral-400">
                                @if ($delivery->vehicle_model || $delivery->vehicle_plate)
                                    <span class="text-xs">{{ $delivery->vehicle_model ?: '-' }}</span>
                                    @if ($delivery->vehicle_plate)
                                        <span class="text-xs text-neutral-500 block">{{ $delivery->vehicle_plate }}</span>
                                    @endif
                                @else
                                    <span class="text-xs text-neutral-600">-</span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                <x-admin.badge variant="{{ $delivery->isActive() ? 'success' : 'neutral' }}">
                                    {{ $delivery->isActive() ? 'Ativo' : 'Inativo' }}
                                </x-admin.badge>
                            </td>
                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm">
                                @if ($delivery->isActivated())
                                    <span class="text-xs text-emerald-400">Ativado</span>
                                @elseif ($delivery->invite_token)
                                    <span class="text-xs text-amber-400">Convite pendente</span>
                                @elseif ($delivery->api_token)
                                    <span class="text-xs text-emerald-400">Token API</span>
                                @else
                                    <span class="text-xs text-neutral-500">Pendente</span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                <x-admin.button variant="ghost" wire:click="openPerformance({{ $delivery->id }})" class="px-2.5 py-1 text-xs rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/20">
                                    Ver
                                </x-admin.button>
                            </td>
                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm space-x-1">
                                <div class="flex flex-wrap gap-1">
                                    @if (!$delivery->isActivated())
                                        @if ($delivery->invite_token)
                                            <x-admin.button variant="secondary" wire:click="resendInvite({{ $delivery->id }})" class="px-2 py-1 text-xs rounded-lg">
                                                Reenviar
                                            </x-admin.button>
                                        @else
                                            <x-admin.button variant="secondary" wire:click="generateInvite({{ $delivery->id }})" class="px-2 py-1 text-xs rounded-lg">
                                                Convidar
                                            </x-admin.button>
                                        @endif
                                        <x-admin.button variant="ghost" wire:click="generateToken({{ $delivery->id }})" class="px-2 py-1 text-xs rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/20">
                                            Token
                                        </x-admin.button>
                                    @endif
                                    <x-admin.button variant="secondary" wire:click="openModal({{ $delivery->id }})" class="px-2 py-1 text-xs rounded-lg">
                                        Editar
                                    </x-admin.button>
                                    <x-admin.button variant="danger" wire:click="delete({{ $delivery->id }})" wire:confirm="Remover entregador?" class="px-2 py-1 text-xs rounded-lg">
                                        Remover
                                    </x-admin.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-neutral-500">Nenhum entregador cadastrado</td>
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

    {{-- Modal: Create/Edit --}}
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
                    <input wire:model="name" type="text" placeholder="Nome do entregador" autocomplete="name"
                           class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('name') border-red-500 @enderror">
                    @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1">Email</label>
                    <input wire:model="email" type="email" placeholder="entregador@email.com"
                           class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('email') border-red-500 @enderror">
                    @error('email') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div x-data="{ 
                    phoneDisplay: '',
                    init() { this.phoneDisplay = $wire.phone ? this.fmt($wire.phone) : ''; },
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
                        $wire.phone = raw;
                    }
                }">
                    <label class="block text-xs font-medium text-neutral-400 mb-1">Telefone *</label>
                    <input type="tel" inputmode="numeric" placeholder="(11) 99999-9999" autocomplete="tel" maxlength="15"
                           x-model="phoneDisplay"
                           @input="onPhoneInput"
                           class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('phone') border-red-500 @enderror">
                    @error('phone') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div x-data="{ 
                    cpfDisplay: '',
                    init() { this.cpfDisplay = $wire.cpf ? this.fmt($wire.cpf) : ''; },
                    fmt(v) {
                        let r = (v||'').replace(/\D/g,'').substring(0,11);
                        if (r.length<=3) return r;
                        if (r.length<=6) return r.substring(0,3)+'.'+r.substring(3);
                        if (r.length<=9) return r.substring(0,3)+'.'+r.substring(3,6)+'.'+r.substring(6);
                        return r.substring(0,3)+'.'+r.substring(3,6)+'.'+r.substring(6,9)+'-'+r.substring(9);
                    },
                    onCpfInput() {
                        let raw = (this.cpfDisplay||'').replace(/\D/g,'').substring(0,11);
                        this.cpfDisplay = this.fmt(raw);
                        $wire.cpf = raw;
                    }
                }">
                    <label class="block text-xs font-medium text-neutral-400 mb-1">CPF</label>
                    <input type="text" inputmode="numeric" placeholder="000.000.000-00" maxlength="14"
                           x-model="cpfDisplay"
                           @input="onCpfInput"
                           class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('cpf') border-red-500 @enderror">
                    @error('cpf') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
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

    {{-- Modal: Invite Link --}}
    <div x-data="{ open: @entangle('showInviteModal') }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[70] flex items-center justify-center p-4"
         @keydown.window.escape="$wire.closeInviteModal()">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="closeInviteModal"></div>
        <div class="relative w-full max-w-lg bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6">
            <h3 class="text-lg font-bold mb-2">Link de Convite</h3>
            <p class="text-sm text-neutral-400 mb-4">Compartilhe este link com o entregador. Ele expira em 48 horas.</p>
            <div class="flex gap-2">
                <input type="text" readonly value="{{ $inviteLink }}"
                       class="flex-1 px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none select-all">
                <button onclick="navigator.clipboard.writeText('{{ $inviteLink }}'); $wire.copyInviteLink()"
                        class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold text-sm transition-all">
                    Copiar
                </button>
            </div>
            <button type="button" wire:click="closeInviteModal"
                    class="mt-4 w-full px-4 py-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium text-sm transition-all">
                Fechar
            </button>
        </div>
    </div>

    {{-- Modal: Performance + History --}}
    <div x-data="{ open: @entangle('showPerformance') }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[70] flex items-center justify-center p-4"
         @keydown.window.escape="$wire.closePerformance()">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="closePerformance"></div>
        <div class="relative w-full max-w-2xl bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6 max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between mb-4 shrink-0">
                <h3 class="text-lg font-bold">Desempenho: {{ $performanceData['name'] ?? '' }}</h3>
                <button wire:click="closePerformance" class="p-1 rounded-lg hover:bg-neutral-800 text-neutral-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="mb-4 shrink-0">
                <label class="block text-xs font-medium text-neutral-400 mb-1">Período</label>
                <select wire:model.live="reportPeriod" wire:change="loadPerformance"
                        class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                    <option value="all">Todo período</option>
                    <option value="today">Hoje</option>
                    <option value="week">Últimos 7 dias</option>
                    <option value="month">Este mês</option>
                </select>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4 shrink-0">
                <div class="bg-neutral-950 rounded-xl p-3 border border-neutral-800">
                    <p class="text-xs text-neutral-400">Entregas</p>
                    <p class="text-lg font-bold text-white">{{ $performanceData['total_deliveries'] ?? 0 }}</p>
                </div>
                <div class="bg-neutral-950 rounded-xl p-3 border border-neutral-800">
                    <p class="text-xs text-neutral-400">Ganhos totais</p>
                    <p class="text-lg font-bold text-emerald-400">R$ {{ number_format($performanceData['earnings'] ?? 0, 2, ',', '.') }}</p>
                    <p class="text-[10px] text-neutral-500">Pendente R$ {{ number_format($performanceData['earnings_pending'] ?? 0, 2, ',', '.') }} · Pago R$ {{ number_format($performanceData['earnings_paid'] ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="bg-neutral-950 rounded-xl p-3 border border-amber-500/20">
                    <p class="text-xs text-neutral-400">Pendente</p>
                    <p class="text-lg font-bold text-amber-400">R$ {{ number_format($performanceData['earnings_summary']['pending'] ?? 0, 2, ',', '.') }}</p>
                    <p class="text-[10px] text-neutral-500">{{ $performanceData['earnings_summary']['pending_count'] ?? 0 }} entrega(s)</p>
                </div>
                <div class="bg-neutral-950 rounded-xl p-3 border border-emerald-500/20">
                    <p class="text-xs text-neutral-400">Pago</p>
                    <p class="text-lg font-bold text-emerald-400">R$ {{ number_format($performanceData['earnings_summary']['paid'] ?? 0, 2, ',', '.') }}</p>
                    <p class="text-[10px] text-neutral-500">{{ $performanceData['earnings_summary']['paid_count'] ?? 0 }} entrega(s)</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-4 shrink-0">
                <div class="bg-neutral-950 rounded-xl p-3 border border-neutral-800">
                    <p class="text-xs text-neutral-400">Tempo médio</p>
                    <p class="text-lg font-bold text-white">{{ $performanceData['avg_time_minutes'] ?? 0 }} min</p>
                </div>
                <div class="bg-neutral-950 rounded-xl p-3 border border-neutral-800">
                    <p class="text-xs text-neutral-400">Cancelamento</p>
                    <p class="text-lg font-bold {{ ($performanceData['cancel_rate'] ?? 0) > 10 ? 'text-red-400' : 'text-neutral-300' }}">
                        {{ $performanceData['cancel_rate'] ?? 0 }}%
                    </p>
                </div>
            </div>

            {{-- Daily Earnings --}}
            <div class="bg-neutral-950 rounded-xl border border-neutral-800 mb-4 shrink-0">
                <div class="flex items-center justify-between px-4 py-3 border-b border-neutral-800">
                    <h4 class="text-sm font-semibold text-neutral-300">Ganhos Diários</h4>
                    <button type="button" wire:click="markAllEarningsPaid"
                            class="px-3 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 text-xs font-semibold hover:bg-emerald-500/20 transition-colors">
                        Marcar todos como pagos
                    </button>
                </div>
                @if (!empty($performanceData['earnings_days']))
                    <div class="divide-y divide-neutral-800/60 max-h-44 overflow-y-auto">
                        @foreach ($performanceData['earnings_days'] as $day)
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-white">{{ $day['weekday'] }}, {{ $day['label'] }}</p>
                                    <p class="text-xs text-neutral-500">{{ $day['count'] }} entrega(s)</p>
                                </div>
                                <div class="text-right shrink-0 ml-3 space-y-0.5">
                                    <p class="text-sm font-bold text-white">R$ {{ number_format($day['total'], 2, ',', '.') }}</p>
                                    @if ($day['pending'] > 0)
                                        <p class="text-[10px] font-semibold text-amber-400">Pendente R$ {{ number_format($day['pending'], 2, ',', '.') }}</p>
                                    @endif
                                    @if ($day['paid'] > 0)
                                        <p class="text-[10px] font-semibold text-emerald-400">Pago R$ {{ number_format($day['paid'], 2, ',', '.') }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-neutral-500 text-center py-4">Sem ganhos no período.</p>
                @endif
            </div>

            {{-- Order History --}}
            <div class="flex-1 overflow-y-auto min-h-0">
                <h4 class="text-sm font-semibold text-neutral-300 mb-2">Histórico de Pedidos</h4>
                @if (!empty($performanceData['recent_orders']))
                    <div class="space-y-2">
                        @foreach ($performanceData['recent_orders'] as $order)
                            <div class="bg-neutral-950 rounded-lg p-3 border border-neutral-800 flex items-center justify-between">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium truncate">{{ $order['customer_name'] }}</p>
                                    <p class="text-xs text-neutral-500">{{ $order['created_at'] }} · {{ $order['items_count'] }} itens</p>
                                    @if ($order['earning_amount'] !== null)
                                        <p class="text-xs text-emerald-400 font-semibold mt-1">Ganho: R$ {{ number_format($order['earning_amount'], 2, ',', '.') }}</p>
                                    @endif
                                </div>
                                <div class="text-right shrink-0 ml-3 flex items-center gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-white">R$ {{ number_format($order['total'], 2, ',', '.') }}</p>
                                        <span class="text-xs px-1.5 py-0.5 rounded-full font-medium
                                            {{ $order['status'] === 'entregue' || $order['status'] === 'fechado' ? 'text-emerald-400 bg-emerald-500/10' : ($order['status'] === 'cancelado' ? 'text-red-400 bg-red-500/10' : 'text-amber-400 bg-amber-500/10') }}">
                                            {{ $order['status_label'] }}
                                        </span>
                                    </div>
                                    @if ($order['earning_status'] === 'pending')
                                        <button type="button" wire:click="markEarningPaid({{ $order['earning_id'] }})"
                                                title="Marcar como pago"
                                                class="px-2.5 py-1.5 rounded-lg bg-amber-500/10 text-amber-400 text-xs font-semibold hover:bg-amber-500/20 transition-colors">
                                            Pagar
                                        </button>
                                    @elseif ($order['earning_status'] === 'paid')
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 font-medium" title="Pago em {{ $order['earning_paid_at'] }}">
                                            Pago
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-neutral-500 text-center py-6">Nenhum pedido no período.</p>
                @endif
            </div>

            <button type="button" wire:click="closePerformance"
                    class="mt-4 w-full px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium text-sm transition-all shrink-0">
                Fechar
            </button>
        </div>
    </div>
</div>
