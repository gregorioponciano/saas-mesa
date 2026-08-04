<div class="p-4 lg:p-8 space-y-6">
    <x-admin.page-header
        title="Backup do Sistema"
        subtitle="Crie e baixe cópias de segurança completas dos dados da sua empresa"
    />

    @if ($noTenant)
        <x-admin.card>
            <div class="py-10 text-center space-y-3">
                <div class="mx-auto flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20">
                    <svg class="w-7 h-7 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-white">Nenhuma empresa vinculada</h3>
                <p class="text-sm text-neutral-400 max-w-md mx-auto">
                    Esta página gerencia os backups da sua empresa. Sua conta não possui uma empresa vinculada,
                    então não há dados para backup.
                </p>
            </div>
        </x-admin.card>
    @else

    {{-- Retenção por plano --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-admin.card class="md:col-span-2">
            <div class="flex items-start gap-3">
                <div class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20">
                    <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white">Política de retenção</h3>
                    <p class="mt-1 text-sm text-neutral-400">
                        Seus backups são armazenados por
                        <span class="font-semibold text-amber-400">{{ $this->retentionLabel }}</span>.
                        @if (Auth::user()->tenant->hasFeature('backup_retention_days'))
                            Enquanto o plano Premium estiver ativo, seus backups nunca são removidos automaticamente.
                        @else
                            Faça upgrade para o plano Premium para manter seus backups por tempo ilimitado.
                        @endif
                    </p>
                </div>
            </div>
        </x-admin.card>

        <x-admin.card>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-neutral-500 uppercase tracking-wide">Armazenamento em uso</p>
                    <p class="text-xl font-bold text-white mt-0.5">{{ $this->totalSize }}</p>
                </div>
                <div>
                    <p class="text-xs text-neutral-500 uppercase tracking-wide">Backups disponíveis</p>
                    <p class="text-xl font-bold text-white mt-0.5">{{ $backups->total() }}</p>
                </div>
            </div>
        </x-admin.card>
    </div>

    {{-- Criar backup --}}
    <x-admin.card>
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-white">Criar novo backup</h3>
                <p class="mt-1 text-sm text-neutral-400">
                    Exporta todos os dados da sua empresa (cardápio, mesas, pedidos, clientes, financeiro e configurações).
                </p>
                @if ($error)
                    <p class="mt-2 text-sm text-red-400">{{ $error }}</p>
                @endif
            </div>
            <button wire:click="createBackup" wire:loading.attr="disabled" wire:target="createBackup"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all duration-200 hover:scale-[1.02] disabled:opacity-60 disabled:cursor-not-allowed">
                <svg wire:loading.remove wire:target="createBackup" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <svg wire:loading wire:target="createBackup" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="createBackup">Criar Backup</span>
                <span wire:loading wire:target="createBackup">Gerando backup...</span>
            </button>
        </div>
    </x-admin.card>

    {{-- Lista --}}
    <x-admin.card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-neutral-500 uppercase tracking-wide border-b border-neutral-800">
                        <th class="pb-3 pr-4 font-medium">Data</th>
                        <th class="pb-3 pr-4 font-medium">Tamanho</th>
                        <th class="pb-3 pr-4 font-medium">Tipo</th>
                        <th class="pb-3 pr-4 font-medium">Expira em</th>
                        <th class="pb-3 text-right font-medium">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($backups as $backup)
                        <tr class="border-b border-neutral-800/60 last:border-0">
                            <td class="py-3.5 pr-4 text-neutral-300">
                                {{ $backup->created_at->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3.5 pr-4 text-neutral-300">{{ $backup->sizeLabel() }}</td>
                            <td class="py-3.5 pr-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide {{ $backup->type === 'scheduled' ? 'bg-violet-500/10 text-violet-400 border border-violet-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                                    {{ $backup->type === 'scheduled' ? 'Agendado' : 'Manual' }}
                                </span>
                            </td>
                            <td class="py-3.5 pr-4 text-neutral-400">
                                @if ($backup->expires_at)
                                    {{ $backup->expires_at->setTimezone('America/Sao_Paulo')->format('d/m/Y') }}
                                @else
                                    <span class="text-emerald-400">Nunca</span>
                                @endif
                            </td>
                            <td class="py-3.5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-2" x-data="{ confirm: false }">
                                    <a href="{{ route('dashboard.backup.download', $backup) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-neutral-800 hover:bg-neutral-700 text-neutral-300 text-xs font-medium transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Baixar
                                    </a>
                                    <button x-show="!confirm" @click="confirm = true"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-neutral-800 hover:bg-red-500/20 text-neutral-300 hover:text-red-400 text-xs font-medium transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Excluir
                                    </button>
                                    <button x-show="confirm" @click="confirm = false; $wire.deleteBackup({{ $backup->id }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-500/20 text-red-400 text-xs font-semibold transition-colors">
                                        Confirmar exclusão?
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-neutral-500">
                                Nenhum backup criado ainda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($backups->hasPages())
            <div class="mt-4">
                {{ $backups->links() }}
            </div>
        @endif
    </x-admin.card>

    {{-- Nota de segurança --}}
    <p class="text-xs text-neutral-600 leading-relaxed">
        Os backups contêm dados sensíveis da sua empresa e dos seus clientes e ficam em armazenamento privado
        (nunca em URLs públicas). O download exige autenticação de um usuário administrador desta empresa.
    </p>
    @endif
</div>
