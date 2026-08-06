<div class="h-full flex flex-col" wire:poll.5s>
    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 px-3 sm:px-6 py-3 sm:py-4 border-b border-neutral-800 shrink-0">
        <div>
            <h1 class="text-base sm:text-lg font-bold">Suporte às Empresas</h1>
            <p class="text-[11px] sm:text-xs text-neutral-500 mt-0.5">Chamados abertos por empresas para a plataforma.</p>
        </div>
    </div>

    <div class="flex-1 flex overflow-hidden">
        @if ($showDetail && $viewingTicket)
            {{-- Detail --}}
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
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <h2 class="text-sm font-bold">{{ $viewingTicket['subject'] }}</h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs text-neutral-400">
                            <span class="text-neutral-500">Empresa: <span class="text-neutral-300 font-medium">{{ $viewingTicket['tenant_name'] }}</span></span>
                            <span>Aberto por: {{ $viewingTicket['user_name'] }}</span>
                            <span class="px-2 py-0.5 rounded bg-neutral-800/50">{{ $viewingTicket['categoryLabel'] }}</span>
                            <span class="px-2 py-0.5 rounded {{ $viewingTicket['priorityClasses'] }}">{{ $viewingTicket['priorityLabel'] }}</span>
                            @if ($viewingTicket['order_id'])
                                <span>Pedido: {{ $viewingTicket['order_id'] }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Status --}}
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
                            <div class="flex {{ $msg['author_role'] === 'platform' ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[85%] p-3 rounded-2xl {{ $msg['author_role'] === 'platform' ? 'bg-amber-500/10 border border-amber-500/20' : 'bg-neutral-800/50 border border-neutral-700/50' }}">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-medium {{ $msg['author_role'] === 'platform' ? 'text-amber-400' : 'text-neutral-300' }}">
                                            {{ $msg['author_role'] === 'platform' ? 'Suporte BurguerSaaS' : ($msg['author_name'] ?? $viewingTicket['tenant_name']) }}
                                        </span>
                                        <span class="text-[10px] text-neutral-500">{{ $msg['created_at'] }}</span>
                                    </div>
                                    <p class="text-sm text-neutral-200 whitespace-pre-wrap">{{ $msg['body'] }}</p>
                                    @include('partials.support-message-attachment')
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Reply --}}
                    @if ($viewingTicket['status'] === 'fechado')
                        <div class="p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800 mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-neutral-300">Chamado encerrado</p>
                                <p class="text-xs text-neutral-500 mt-0.5">Reabrir para responder ou receber novas mensagens.</p>
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
                                  placeholder="Responder para a empresa..."></textarea>
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
        @else
            {{-- List --}}
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
                               placeholder="Buscar por assunto ou empresa...">
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
                                    <p class="text-[10px] text-neutral-500 mt-0.5">{{ $ticket->tenant?->name }}</p>
                                </div>
                                <span class="shrink-0 px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $ticket->statusClasses() }}">
                                    {{ $ticket->statusLabel() }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-[10px] text-neutral-500">
                                <span class="px-1.5 py-0.5 rounded bg-neutral-800/50">{{ $ticket->categoryLabel() }}</span>
                                <span class="px-1.5 py-0.5 rounded {{ $ticket->priorityClasses() }}">{{ $ticket->priorityLabel() }}</span>
                                <span>{{ $ticket->updated_at->format('d/m H:i') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-16">
                            <p class="text-sm text-neutral-400">Nenhum chamado de empresa para a plataforma.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>