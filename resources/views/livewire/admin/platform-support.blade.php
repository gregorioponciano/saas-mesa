<div class="h-full flex flex-col" wire:poll.10s>
    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 px-3 sm:px-6 py-3 sm:py-4 border-b border-neutral-800 shrink-0">
        <div>
            <h1 class="text-base sm:text-lg font-bold">Falar com a Plataforma</h1>
            <p class="text-[11px] sm:text-xs text-neutral-500 mt-0.5">Tire dúvidas com o suporte do BurguerSaaS — cobrança, plano, bugs e mais.</p>
        </div>
        <button wire:click="$set('showCreateForm', true)"
                class="flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all duration-200 whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nova Chamada
        </button>
    </div>

    <div class="flex-1 flex overflow-hidden">
        @if ($showDetail && $viewingTicket)
            {{-- Detail Panel --}}
            <div class="w-full overflow-y-auto bg-neutral-950/50">
                <div class="p-4 sm:p-5 max-w-3xl mx-auto">
                    <div class="flex items-center justify-between mb-4">
                        <button wire:click="closeDetail" class="flex items-center gap-2 text-sm text-neutral-400 hover:text-white transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Voltar
                        </button>
                        <span class="shrink-0 px-2.5 py-1 text-[10px] font-semibold rounded-full {{ $viewingTicket['statusClasses'] }}">
                            {{ $viewingTicket['statusLabel'] }}
                        </span>
                    </div>

                    <div class="p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800 mb-4">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <h2 class="text-sm font-bold">{{ $viewingTicket['subject'] }}</h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs text-neutral-400">
                            <span class="px-2 py-0.5 rounded bg-neutral-800/50">{{ $viewingTicket['categoryLabel'] }}</span>
                            <span class="px-2 py-0.5 rounded {{ $viewingTicket['priorityClasses'] }}">{{ $viewingTicket['priorityLabel'] }}</span>
                            @if ($viewingTicket['order_id'])
                                <span>Pedido: {{ $viewingTicket['order_id'] }}</span>
                            @endif
                            <span>Aberto: {{ $viewingTicket['created_at'] }}</span>
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

                    {{-- Messages --}}
                    <div class="space-y-3 mb-4">
                        @foreach ($viewingTicket['messages'] as $msg)
                            <div class="flex {{ $msg['author_role'] === 'platform' ? 'justify-start' : 'justify-end' }}">
                                <div class="max-w-[85%] p-3 rounded-2xl {{ $msg['author_role'] === 'platform' ? 'bg-neutral-800/50 border border-neutral-700/50' : 'bg-amber-500/10 border border-amber-500/20' }}">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-medium {{ $msg['author_role'] === 'platform' ? 'text-neutral-300' : 'text-amber-400' }}">
                                            {{ $msg['author_role'] === 'platform' ? 'Suporte BurguerSaaS' : ($msg['author_name'] ?? 'Minha Empresa') }}
                                        </span>
                                        <span class="text-[10px] text-neutral-500">{{ $msg['created_at'] }}</span>
                                    </div>
                                    <p class="text-sm text-neutral-200 whitespace-pre-wrap">{{ $msg['body'] }}</p>
                                    @include('partials.support-message-attachment')
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Reply Form --}}
                    @if (in_array($viewingTicket['status'], \App\Models\SupportTicket::STATUS_CLOSED, true))
                        <div class="p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800 mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-neutral-300">Chamado encerrado</p>
                                <p class="text-xs text-neutral-500 mt-0.5">Reabra o chamado para mandar nova mensagem.</p>
                            </div>
                            <button wire:click="updateStatus({{ $viewingTicket['id'] }}, 'aberto')"
                                    class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all disabled:opacity-50"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove>Reabrir Chamado</span>
                                <span wire:loading>Reabrindo...</span>
                            </button>
                        </div>
                    @else
                    <div class="p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800 mb-4">
                        <textarea wire:model="replyBody" rows="3"
                                  class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm resize-none"
                                  placeholder="Escreva sua mensagem para a plataforma..."></textarea>
                        @error('replyBody') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                        <div class="flex items-center justify-between gap-3 mt-3">
                            <div class="flex items-center gap-3">
                                @include('partials.support-attachment-input')
                                <span class="text-[10px] text-neutral-600">JPG, PNG ou PDF até 2MB</span>
                            </div>
                            <button wire:click="sendReply" wire:loading.attr="disabled"
                                    class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all disabled:opacity-50">
                                <span wire:loading.remove>Enviar</span>
                                <span wire:loading>Enviando...</span>
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        @elseif ($showCreateForm)
            {{-- New Ticket Form --}}
            <div class="w-full overflow-y-auto p-4 sm:p-6 max-w-2xl mx-auto">
                <button wire:click="$set('showCreateForm', false)" class="flex items-center gap-2 text-sm text-neutral-400 hover:text-white transition-colors mb-6">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Voltar
                </button>
                <h2 class="text-sm sm:text-lg font-bold mb-6">Abrir Chamado com a Plataforma</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-300 mb-1.5">Assunto</label>
                        <input type="text" wire:model="newSubject" maxlength="200"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                               placeholder="Ex: Falha ao emitir PIX, dúvida sobre plano...">
                        @error('newSubject') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-1.5">Categoria</label>
                            <select wire:model="newCategory"
                                    class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm">
                                @foreach (\App\Models\SupportTicket::CATEGORY_LABELS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-1.5">Prioridade</label>
                            <select wire:model="newPriority"
                                    class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm">
                                @foreach (\App\Models\SupportTicket::PRIORITY_LABELS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-300 mb-1.5">Descreva o problema</label>
                        <textarea wire:model="newBody" rows="5"
                                  class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm resize-none min-h-[120px]"
                                  placeholder="Explique com detalhes o que está acontecendo..."></textarea>
                        @error('newBody') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-300 mb-1.5">Pedido (opcional)</label>
                        <input type="text" wire:model="newOrderRef"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                               placeholder="Ex: #00123">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-300 mb-1.5">Anexo (opcional)</label>
                        @include('partials.support-attachment-input')
                    </div>

                    <div class="flex justify-end pt-2">
                        <button wire:click="openTicket" wire:loading.attr="disabled"
                                class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all disabled:opacity-50">
                            <span wire:loading.remove>Enviar Chamado</span>
                            <span wire:loading>Enviando...</span>
                        </button>
                    </div>
                </div>
            </div>
        @else
            {{-- Ticket List --}}
            <div class="w-full overflow-y-auto">
                <div class="p-3 sm:p-4 space-y-3 border-b border-neutral-800/50">
                    <div class="flex flex-wrap gap-2">
                        <select wire:model.live="statusFilter"
                                class="px-3 py-1.5 rounded-lg bg-neutral-900 border border-neutral-800 text-white text-xs focus:outline-none focus:ring-1 focus:ring-amber-500">
                            <option value="all">Todos os Status</option>
                            @foreach (\App\Models\SupportTicket::STATUS_LABELS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="search" wire:model.live.debounce.300ms="search"
                               class="flex-1 min-w-[120px] px-3 py-1.5 rounded-lg bg-neutral-900 border border-neutral-800 text-white text-xs placeholder-neutral-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                               placeholder="Buscar por assunto...">
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
                                    <p class="text-[10px] text-neutral-500 mt-0.5">#{{ $ticket->id }}</p>
                                </div>
                                <span class="shrink-0 px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $ticket->statusClasses() }}">
                                    {{ $ticket->statusLabel() }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-[10px] text-neutral-500">
                                <span class="px-1.5 py-0.5 rounded bg-neutral-800/50">{{ $ticket->categoryLabel() }}</span>
                                <span class="px-1.5 py-0.5 rounded {{ $ticket->priorityClasses() }}">{{ $ticket->priorityLabel() }}</span>
                                <span>{{ $ticket->updated_at->format('d/m H:i') }}</span>
                                @if ($ticket->lastMessage)
                                    <span class="text-neutral-600">· Última resposta: {{ $ticket->lastMessage->created_at->format('d/m H:i') }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-16">
                            <p class="text-sm text-neutral-400 mb-4">Nenhum chamado com a plataforma ainda.</p>
                            <button wire:click="$set('showCreateForm', true)"
                                    class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all">
                                Abrir Primeiro Chamado
                            </button>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>