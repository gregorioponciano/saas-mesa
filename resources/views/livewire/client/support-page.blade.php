<div class="h-full flex flex-col">
    {{-- Navigation Tabs --}}
    <div class="flex items-center gap-3 px-3 sm:px-6 py-3 sm:py-4 border-b border-neutral-800 shrink-0">
        <div class="flex gap-1 p-1 rounded-xl bg-neutral-900 border border-neutral-800 overflow-x-auto flex-1 min-w-0">
            <button wire:click="$set('tab', 'meus_tickets')"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $tab === 'meus_tickets' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Meus Tickets
            </button>
            <button wire:click="$set('tab', 'novo_ticket')"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $tab === 'novo_ticket' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Abrir Ticket
            </button>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto">
        @if ($showTicketDetail)
            {{-- Ticket Detail View --}}
            <div class="p-4 sm:p-6 max-w-3xl mx-auto">
                <button wire:click="backToList" class="flex items-center gap-2 text-sm text-neutral-400 hover:text-white transition-colors mb-4">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Voltar
                </button>

                {{-- Ticket Header --}}
                <div class="p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800 mb-6">
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <h2 class="text-lg font-bold">{{ $viewingTicket['subject'] }}</h2>
                        <span class="shrink-0 px-3 py-1 text-xs font-semibold rounded-full {{ $viewingTicket['statusClasses'] }}">
                            {{ $viewingTicket['statusLabel'] }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-neutral-400">
                        <span class="px-2 py-1 rounded-md bg-neutral-800/50">{{ $viewingTicket['categoryLabel'] }}</span>
                        <span class="px-2 py-1 rounded-md {{ $viewingTicket['priorityClasses'] }}">{{ $viewingTicket['priorityLabel'] }}</span>
                        @if ($viewingTicket['assigned_to'])
                            <span>Atendente: <span class="text-neutral-300">{{ $viewingTicket['assigned_to'] }}</span></span>
                        @endif
                        <span>Aberto: {{ $viewingTicket['created_at'] }}</span>
                    </div>
                </div>

                {{-- Messages --}}
                <div class="space-y-4 mb-6">
                    @foreach ($viewingTicket['messages'] as $msg)
                        <div class="flex {{ $msg['author_role'] === 'cliente' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[80%] p-4 rounded-2xl {{ $msg['author_role'] === 'cliente' ? 'bg-amber-500/10 border border-amber-500/20' : 'bg-neutral-800/50 border border-neutral-700/50' }}">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-medium {{ $msg['author_role'] === 'cliente' ? 'text-amber-400' : 'text-neutral-300' }}">
                                        {{ $msg['author_name'] ?? ($msg['author_role'] === 'cliente' ? 'Você' : 'Equipe') }}
                                    </span>
                                    <span class="text-[10px] text-neutral-500">{{ $msg['created_at'] }}</span>
                                </div>
                                <p class="text-sm text-neutral-200 whitespace-pre-wrap">{{ $msg['body'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Reply Form --}}
                @if (!in_array($viewingTicket['status'], ['resolvido', 'fechado']))
                    <div class="p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800">
                        <h3 class="text-sm font-semibold mb-3">Responder</h3>
                        <textarea wire:model="replyBody" rows="3"
                                  class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm resize-none"
                                  placeholder="Digite sua resposta..."></textarea>
                        @error('replyBody') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                        <div class="flex items-center justify-between mt-3">
                            <button wire:click="closeTicket({{ $viewingTicket['id'] }})"
                                    class="text-xs text-neutral-500 hover:text-red-400 transition-colors">
                                Fechar Ticket
                            </button>
                            <button wire:click="sendReply" wire:loading.attr="disabled"
                                    class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all disabled:opacity-50">
                                <span wire:loading.remove>Enviar Resposta</span>
                                <span wire:loading>Enviando...</span>
                            </button>
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800 text-center">
                        <p class="text-sm text-neutral-400">Este ticket está {{ $viewingTicket['statusLabel'] }}.</p>
                    </div>
                @endif
            </div>
        @elseif ($tab === 'meus_tickets')
            {{-- My Tickets List --}}
            <div class="p-4 sm:p-6">
                <h2 class="text-sm sm:text-lg font-bold mb-4 sm:mb-6">Meus Tickets</h2>

                @forelse ($this->myTickets as $ticket)
                    <div wire:key="ticket-{{ $ticket->id }}"
                         class="p-4 sm:p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800 mb-3 hover:border-neutral-700 transition-colors cursor-pointer"
                         wire:click="viewTicket({{ $ticket->id }})">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <h3 class="text-sm font-semibold">{{ $ticket->subject }}</h3>
                            <span class="shrink-0 px-2.5 py-1 text-[10px] font-semibold rounded-full {{ $ticket->statusClasses() }}">
                                {{ $ticket->statusLabel() }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-[10px] sm:text-xs text-neutral-500">
                            <span class="px-2 py-0.5 rounded-md bg-neutral-800/50">{{ $ticket->categoryLabel() }}</span>
                            <span class="px-2 py-0.5 rounded-md {{ $ticket->priorityClasses() }}">{{ $ticket->priorityLabel() }}</span>
                            <span>{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                            @if ($ticket->lastMessage)
                                <span class="text-neutral-600">· Última resposta: {{ $ticket->lastMessage->created_at->format('d/m/Y H:i') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-16">
                        <svg class="w-16 h-16 mx-auto text-neutral-700 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                        </svg>
                        <p class="text-neutral-400 mb-4">Você ainda não abriu nenhum ticket.</p>
                        <button wire:click="$set('tab', 'novo_ticket')"
                                class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all">
                            Abrir Primeiro Ticket
                        </button>
                    </div>
                @endforelse
            </div>
        @else
            {{-- New Ticket Form --}}
            <div class="p-4 sm:p-6 max-w-2xl mx-auto">
                <h2 class="text-sm sm:text-lg font-bold mb-6">Abrir Novo Ticket</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-300 mb-1.5">Assunto</label>
                        <input type="text" wire:model="newSubject"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                               placeholder="Resumo do problema" maxlength="200">
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
                        <label class="block text-sm font-medium text-neutral-300 mb-1.5">Pedido (opcional)</label>
                        <input type="text" wire:model="newOrderRef"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                               placeholder="Ex: #00123">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-300 mb-1.5">Descreva o problema</label>
                        <textarea wire:model="newBody" rows="5"
                                  class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm resize-none min-h-[120px]"
                                  placeholder="Conte-nos detalhadamente o que está acontecendo..."></textarea>
                        @error('newBody') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end pt-2">
                        <button wire:click="openTicket" wire:loading.attr="disabled"
                                class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all disabled:opacity-50">
                            <span wire:loading.remove>Enviar Ticket</span>
                            <span wire:loading>Enviando...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
