<div class="h-full flex flex-col">
    {{-- Tabs --}}
    <div class="flex items-center gap-3 px-3 sm:px-6 py-3 sm:py-4 border-b border-neutral-800 shrink-0">
        <div class="flex gap-1 p-1 rounded-xl bg-neutral-900 border border-neutral-800">
            <button wire:click="$set('tab', 'tickets')"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $tab === 'tickets' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                </svg>
                Tickets
            </button>
            <button wire:click="$set('tab', 'metricas')"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $tab === 'metricas' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Métricas
            </button>
        </div>
    </div>

    <div class="flex-1 flex overflow-hidden">
        @if ($tab === 'tickets')
            {{-- Ticket List --}}
            <div class="w-full {{ $showDetail ? 'hidden lg:block lg:w-1/2 xl:w-3/5' : '' }} overflow-y-auto border-r border-neutral-800/50">
                <div class="p-3 sm:p-4 space-y-3 border-b border-neutral-800/50">
                    <div class="flex flex-wrap gap-2">
                        <select wire:model.live="statusFilter"
                                class="px-3 py-1.5 rounded-lg bg-neutral-900 border border-neutral-800 text-white text-xs focus:outline-none focus:ring-1 focus:ring-amber-500">
                            <option value="all">Todos os Status</option>
                            @foreach (\App\Models\SupportTicket::STATUS_LABELS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="categoryFilter"
                                class="px-3 py-1.5 rounded-lg bg-neutral-900 border border-neutral-800 text-white text-xs focus:outline-none focus:ring-1 focus:ring-amber-500">
                            <option value="all">Todas Categorias</option>
                            @foreach (\App\Models\SupportTicket::CATEGORY_LABELS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="priorityFilter"
                                class="px-3 py-1.5 rounded-lg bg-neutral-900 border border-neutral-800 text-white text-xs focus:outline-none focus:ring-1 focus:ring-amber-500">
                            <option value="all">Todas Prioridades</option>
                            @foreach (\App\Models\SupportTicket::PRIORITY_LABELS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="search" wire:model.live.debounce.300ms="search"
                               class="flex-1 min-w-[120px] px-3 py-1.5 rounded-lg bg-neutral-900 border border-neutral-800 text-white text-xs placeholder-neutral-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                               placeholder="Buscar...">
                    </div>
                </div>

                <div class="p-3 sm:p-4 space-y-2">
                    @forelse ($this->tickets as $ticket)
                        <div wire:key="ticket-{{ $ticket->id }}"
                             class="p-3 sm:p-4 rounded-xl bg-neutral-900/30 border border-neutral-800/50 hover:border-neutral-700 transition-colors cursor-pointer {{ $viewingTicketId === $ticket->id ? 'ring-1 ring-amber-500/50' : '' }}"
                             wire:click="viewTicket({{ $ticket->id }})">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-medium truncate">{{ $ticket->subject }}</h3>
                                    <p class="text-[10px] text-neutral-500 mt-0.5">{{ $ticket->user?->name ?? '—' }}</p>
                                </div>
                                <span class="shrink-0 px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $ticket->statusClasses() }}">
                                    {{ $ticket->statusLabel() }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-[10px] text-neutral-500">
                                <span class="px-1.5 py-0.5 rounded bg-neutral-800/50">{{ $ticket->categoryLabel() }}</span>
                                <span class="px-1.5 py-0.5 rounded {{ $ticket->priorityClasses() }}">{{ $ticket->priorityLabel() }}</span>
                                @if ($ticket->assignedTo)
                                    <span>{{ $ticket->assignedTo->name }}</span>
                                @endif
                                <span>{{ $ticket->updated_at->format('d/m H:i') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-16">
                            <p class="text-sm text-neutral-400">Nenhum ticket encontrado.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Detail Panel --}}
            @if ($showDetail && $viewingTicket)
                <div class="w-full lg:w-1/2 xl:w-2/5 overflow-y-auto bg-neutral-950/50">
                    <div class="p-4 sm:p-5">
                        <button wire:click="closeDetail" class="lg:hidden flex items-center gap-2 text-sm text-neutral-400 hover:text-white transition-colors mb-4">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Voltar
                        </button>

                        <div class="p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800 mb-4">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <h2 class="text-sm font-bold">{{ $viewingTicket['subject'] }}</h2>
                                <span class="shrink-0 px-2.5 py-1 text-[10px] font-semibold rounded-full {{ $viewingTicket['statusClasses'] }}">
                                    {{ $viewingTicket['statusLabel'] }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs text-neutral-400">
                                <span class="text-neutral-500">Cliente: <span class="text-neutral-300">{{ $viewingTicket['user_name'] }}</span></span>
                                <span class="px-2 py-0.5 rounded bg-neutral-800/50">{{ $viewingTicket['categoryLabel'] }}</span>
                                <span class="px-2 py-0.5 rounded {{ $viewingTicket['priorityClasses'] }}">{{ $viewingTicket['priorityLabel'] }}</span>
                            </div>
                        </div>

                        {{-- Status Actions --}}
                        <div class="flex flex-wrap gap-1.5 mb-4">
                            @foreach (['aberto', 'em_atendimento', 'aguardando_cliente', 'resolvido', 'fechado'] as $s)
                                <button wire:click="updateStatus({{ $viewingTicket['id'] }}, '{{ $s }}')"
                                        class="px-2.5 py-1 text-[10px] rounded-lg font-medium transition-all {{ $viewingTicket['status'] === $s ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-neutral-800/30 text-neutral-500 hover:text-white border border-transparent' }}">
                                    {{ \App\Models\SupportTicket::STATUS_LABELS[$s] }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Reassign --}}
                        <div class="p-3 rounded-xl bg-neutral-900/30 border border-neutral-800 mb-4">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-neutral-400 shrink-0">Atendente:</span>
                                <span class="text-xs text-neutral-300 flex-1">{{ $viewingTicket['assigned_to'] ?? '—' }}</span>
                                <select wire:model="reassignToUserId"
                                        class="px-2 py-1 rounded-lg bg-neutral-950 border border-neutral-800 text-white text-[10px] focus:outline-none focus:ring-1 focus:ring-amber-500">
                                    <option value="">Reatribuir para...</option>
                                    @foreach ($this->staffUsers as $staff)
                                        <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->roleLabel() }})</option>
                                    @endforeach
                                </select>
                                <button wire:click="reassignTicket({{ $viewingTicket['id'] }})"
                                        class="text-[10px] px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 transition-all">
                                    OK
                                </button>
                            </div>
                        </div>

                        {{-- Messages --}}
                        <div class="space-y-3 mb-4">
                            @foreach ($viewingTicket['messages'] as $msg)
                                <div class="flex {{ $msg['author_role'] === 'cliente' ? 'justify-start' : 'justify-end' }}">
                                    <div class="max-w-[85%] p-3 rounded-2xl {{ $msg['is_internal'] ? 'bg-amber-900/20 border border-amber-500/30' : ($msg['author_role'] === 'cliente' ? 'bg-neutral-800/50 border border-neutral-700/50' : 'bg-amber-500/10 border border-amber-500/20') }}">
                                        @if ($msg['is_internal'])
                                            <span class="inline-block px-1.5 py-0.5 text-[9px] font-semibold rounded bg-amber-500/20 text-amber-400 mb-1.5">Nota Interna</span>
                                        @endif
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-medium {{ $msg['is_internal'] ? 'text-amber-400' : ($msg['author_role'] === 'cliente' ? 'text-neutral-300' : 'text-amber-400') }}">
                                                {{ $msg['author_name'] ?? ($msg['author_role'] === 'cliente' ? 'Cliente' : 'Equipe') }}
                                            </span>
                                            <span class="text-[10px] text-neutral-500">{{ $msg['created_at'] }}</span>
                                        </div>
                                        <p class="text-sm text-neutral-200 whitespace-pre-wrap">{{ $msg['body'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Reply Form --}}
                        <div class="p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800 mb-4">
                            <textarea wire:model="replyBody" rows="3"
                                      class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm resize-none"
                                      placeholder="Digite sua resposta..."></textarea>
                            @error('replyBody') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                            <div class="flex items-center justify-between mt-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="replyIsInternal" class="rounded bg-neutral-800 border-neutral-600 text-amber-500 focus:ring-amber-500">
                                    <span class="text-xs text-neutral-400">Nota Interna</span>
                                </label>
                                <button wire:click="sendReply" wire:loading.attr="disabled"
                                        class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all disabled:opacity-50">
                                    <span wire:loading.remove>Enviar</span>
                                    <span wire:loading>Enviando...</span>
                                </button>
                            </div>
                        </div>

                        {{-- Danger Zone --}}
                        <div class="p-4 rounded-2xl bg-red-500/5 border border-red-500/20">
                            <h4 class="text-xs font-semibold text-red-400 mb-2">Zona de Perigo</h4>
                            <div class="flex gap-2">
                                <button wire:click="forceClose({{ $viewingTicket['id'] }})"
                                        wire:confirm="Tem certeza que deseja fechar este ticket forçadamente?"
                                        class="px-3 py-1.5 text-[10px] rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/30 transition-all">
                                    Fechar Forçado
                                </button>
                                <button wire:click="deleteTicket({{ $viewingTicket['id'] }})"
                                        wire:confirm="Tem certeza? Esta ação não pode ser desfeita."
                                        class="px-3 py-1.5 text-[10px] rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/30 transition-all">
                                    Deletar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @else
            {{-- Metrics Tab --}}
            <div class="w-full overflow-y-auto p-4 sm:p-6">
                @php $m = $this->metrics; @endphp

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
                    <div class="p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800">
                        <p class="text-xs text-neutral-500 mb-1">Total de Tickets</p>
                        <p class="text-2xl font-black text-white">{{ $m['total'] }}</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800">
                        <p class="text-xs text-neutral-500 mb-1">Em Aberto</p>
                        <p class="text-2xl font-black text-red-400">{{ $m['abertos'] }}</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800">
                        <p class="text-xs text-neutral-500 mb-1">Em Atendimento</p>
                        <p class="text-2xl font-black text-amber-400">{{ $m['em_atendimento'] }}</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800">
                        <p class="text-xs text-neutral-500 mb-1">Resolvidos Hoje</p>
                        <p class="text-2xl font-black text-emerald-400">{{ $m['resolvidos_hoje'] }}</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800">
                        <p class="text-xs text-neutral-500 mb-1">Tempo Médio (dias)</p>
                        <p class="text-2xl font-black text-sky-400">{{ $m['tempo_medio_dias'] }}</p>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800">
                    <h3 class="text-sm font-semibold mb-4">Tickets por Categoria</h3>
                    <div class="space-y-3">
                        @foreach (\App\Models\SupportTicket::CATEGORY_LABELS as $value => $label)
                            @php $count = $m['por_categoria'][$value] ?? 0; @endphp
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-neutral-300 w-32">{{ $label }}</span>
                                <div class="flex-1 h-2 rounded-full bg-neutral-800 overflow-hidden">
                                    <div class="h-full rounded-full bg-amber-500 transition-all"
                                         style="width: {{ $m['total'] > 0 ? ($count / $m['total'] * 100) : 0 }}%"></div>
                                </div>
                                <span class="text-xs text-neutral-500 w-8 text-right">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
