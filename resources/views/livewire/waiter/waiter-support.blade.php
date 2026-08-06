<div class="h-full flex flex-col">
    {{-- Header --}}
    <div class="flex items-center justify-between px-3 sm:px-6 py-3 sm:py-4 border-b border-neutral-800 shrink-0">
        <h1 class="text-base sm:text-lg font-bold">Suporte</h1>
    </div>

    <div class="flex-1 flex overflow-hidden">
        {{-- Ticket List --}}
        <div class="w-full {{ $showDetail ? 'hidden lg:block lg:w-1/2 xl:w-3/5' : '' }} overflow-y-auto border-r border-neutral-800/50">
            {{-- Filters --}}
            <div class="p-3 sm:p-4 space-y-3 border-b border-neutral-800/50">
                <div class="flex flex-wrap gap-2">
                    <select wire:model.live="statusFilter"
                            class="px-3 py-1.5 rounded-lg bg-neutral-900 border border-neutral-800 text-white text-xs focus:outline-none focus:ring-1 focus:ring-amber-500">
                        <option value="all">Todos os Status</option>
                        <option value="aberto">Aberto</option>
                        <option value="em_atendimento">Em Atendimento</option>
                        <option value="aguardando_cliente">Aguardando Cliente</option>
                        <option value="resolvido">Resolvido</option>
                        <option value="fechado">Fechado</option>
                    </select>
                    <select wire:model.live="categoryFilter"
                            class="px-3 py-1.5 rounded-lg bg-neutral-900 border border-neutral-800 text-white text-xs focus:outline-none focus:ring-1 focus:ring-amber-500">
                        <option value="all">Todas as Categorias</option>
                        @foreach (\App\Models\SupportTicket::CATEGORY_LABELS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="search" wire:model.live.debounce.300ms="search"
                           class="flex-1 min-w-[120px] px-3 py-1.5 rounded-lg bg-neutral-900 border border-neutral-800 text-white text-xs placeholder-neutral-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                           placeholder="Buscar por assunto...">
                </div>
            </div>

            {{-- Tickets --}}
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
                                <span class="text-neutral-600">{{ $ticket->assignedTo->name }}</span>
                            @endif
                            <span class="text-neutral-600">{{ $ticket->updated_at->format('d/m H:i') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-16">
                        <svg class="w-12 h-12 mx-auto text-neutral-700 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                        </svg>
                        <p class="text-sm text-neutral-400">Nenhum ticket encontrado.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Ticket Detail Panel --}}
        @if ($showDetail && $viewingTicket)
            <div class="w-full lg:w-1/2 xl:w-2/5 overflow-y-auto bg-neutral-950/50 {{ $showDetail ? '' : 'hidden' }}">
                <div class="p-4 sm:p-5">
                    {{-- Close button (mobile) --}}
                    <button wire:click="closeDetail" class="lg:hidden flex items-center gap-2 text-sm text-neutral-400 hover:text-white transition-colors mb-4">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Voltar
                    </button>

                    {{-- Header --}}
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

                    {{-- Assignment --}}
                    <div class="p-3 rounded-xl bg-neutral-900/30 border border-neutral-800 mb-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-neutral-400">
                                Atendente:
                                <span class="text-neutral-300">{{ $viewingTicket['assigned_to'] ?? '—' }}</span>
                            </span>
                            <div class="flex gap-2">
                                @if ($viewingTicket['assigned_to_id'] !== Auth::id())
                                    <button wire:click="assignToMe({{ $viewingTicket['id'] }})"
                                            class="text-[10px] px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition-all">
                                        Assumir
                                    </button>
                                @else
                                    <button wire:click="unassign({{ $viewingTicket['id'] }})"
                                            class="text-[10px] px-2.5 py-1 rounded-lg bg-neutral-700/30 text-neutral-400 hover:text-red-400 transition-all">
                                        Liberar
                                    </button>
                                @endif
                            </div>
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
                                    @include('partials.support-message-attachment')
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Reply Form --}}
                    @if ($viewingTicket['status'] === 'fechado')
                        <div class="p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-neutral-300">Ticket encerrado</p>
                                <p class="text-xs text-neutral-500 mt-0.5">Reabra para responder ou receber novas mensagens.</p>
                            </div>
                            <button wire:click="updateStatus({{ $viewingTicket['id'] }}, 'aberto')"
                                    class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all disabled:opacity-50"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove>Reabrir Ticket</span>
                                <span wire:loading>Reabrindo...</span>
                            </button>
                        </div>
                    @else
                    <div class="p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800">
                        <textarea wire:model="replyBody" rows="3"
                                  class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm resize-none"
                                  placeholder="Digite sua resposta..."></textarea>
                        @error('replyBody') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                        <div class="flex items-center justify-between mt-3">
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="replyIsInternal" class="rounded bg-neutral-800 border-neutral-600 text-amber-500 focus:ring-amber-500">
                                    <span class="text-xs text-neutral-400">Nota Interna</span>
                                </label>
                                @include('partials.support-attachment-input')
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
        @endif
    </div>
</div>
