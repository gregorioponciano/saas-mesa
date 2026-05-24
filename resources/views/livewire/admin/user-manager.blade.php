<div class="p-4 lg:p-8 space-y-6"
     x-data="{
         showForm: @entangle('showForm'),
         init() {
             this.$watch('showForm', val => { document.body.style.overflow = val ? 'hidden' : '' });
         }
     }"
     @keydown.window.escape="if (showForm) $wire.resetForm()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Gerenciar Usuários</h1>
            <p class="text-sm text-neutral-400 mt-1">{{ $users->count() }} usuários cadastrados</p>
        </div>
        <button wire:click="openCreate"
                class="flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Novo Usuário
        </button>
    </div>

    {{-- Form Modal --}}
    @if($showForm)
        <div class="fixed inset-0 z-60" wire:key="user-form">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="resetForm"></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-lg p-6 rounded-2xl bg-gradient-to-br from-neutral-900 to-neutral-950 border border-neutral-800 shadow-2xl shadow-black/30">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-lg">{{ $editingUserId ? 'Editar' : 'Novo' }} Usuário</h3>
            <button wire:click="resetForm" class="p-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Nome *</label>
                <input wire:model="name" type="text" placeholder="Nome completo"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('name') border-red-500 @enderror">
                @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Email *</label>
                <input wire:model="email" type="email" placeholder="email@exemplo.com"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('email') border-red-500 @enderror">
                @error('email') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">{{ $editingUserId ? 'Nova senha (opcional)' : 'Senha *' }}</label>
                <input wire:model="password" type="password" placeholder="Mínimo 6 caracteres"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('password') border-red-500 @enderror">
                @error('password') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Confirmar senha {{ $editingUserId ? '(opcional)' : '*' }}</label>
                <input wire:model="passwordConfirmation" type="password" placeholder="Repita a senha"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('passwordConfirmation') border-red-500 @enderror">
                @error('passwordConfirmation') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Função</label>
                <select wire:model="role"
                        class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                    <option value="atendente">Atendente (Garçom)</option>
                    <option value="cliente">Cliente</option>
                    <option value="admin">Administrador</option>
                </select>
                @error('role') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2 flex items-center gap-3 pt-2">
                <button type="submit" wire:loading.attr="disabled"
                         class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 flex items-center gap-2">
                    <span wire:loading.remove>{{ $editingUserId ? 'Atualizar' : 'Criar' }} Usuário</span>
                    <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                </button>
                <button type="button" wire:click="resetForm"
                        class="px-6 py-2.5 bg-neutral-800 hover:bg-neutral-700 text-neutral-300 rounded-xl transition-all duration-200">
                    Cancelar
                </button>
            </div>
        </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Users List --}}
    <div class="grid gap-3">
        @forelse ($users as $user)
            <div class="flex items-center gap-4 p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800 hover:border-amber-500/20 transition-all duration-200">
                <div class="w-10 h-10 rounded-full bg-amber-500/20 flex items-center justify-center text-amber-400 font-bold text-sm shrink-0">
                    {{ substr($user->name, 0, 2) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium">{{ $user->name }}</p>
                    <p class="text-sm text-neutral-400">{{ $user->email }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $user->roleColor() }}">
                        {{ $user->roleLabel() }}
                    </span>
                    @if ($user->is_staff && !$user->isAdmin())
                        <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20">Staff</span>
                    @endif
                </div>
                <div class="flex items-center gap-1">
                    @if (!$user->isAdmin())
                        <button wire:click="toggleStaff({{ $user->id }})"
                                class="p-2 rounded-xl bg-neutral-800 text-neutral-400 hover:text-amber-400 hover:bg-amber-500/10 transition-all"
                                title="{{ $user->is_staff ? 'Rebaixar para Cliente' : 'Promover para Atendente' }}">
                            @if ($user->is_staff)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @else
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            @endif
                        </button>
                    @endif
                    <button wire:click="edit({{ $user->id }})"
                            class="p-2 rounded-xl bg-neutral-800 text-neutral-400 hover:text-white hover:bg-neutral-700 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    @if ($user->id !== auth()->id())
                        <button wire:click="confirmDelete({{ $user->id }})"
                                class="p-2 rounded-xl bg-neutral-800 text-neutral-400 hover:text-red-400 hover:bg-red-500/10 transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    @endif
                    @if ($confirmDeleteUserId === $user->id)
                        <div class="flex items-center gap-1.5 p-1.5 rounded-lg bg-red-500/10 border border-red-500/20">
                            <span class="text-[10px] text-red-400 whitespace-nowrap">Excluir {{ $user->name }}?</span>
                            <button wire:click="delete({{ $user->id }})"
                                    class="px-2.5 py-1 text-[10px] font-bold bg-red-500 text-white rounded-md hover:bg-red-400">Sim</button>
                            <button wire:click="$set('confirmDeleteUserId', null)"
                                    class="px-2.5 py-1 text-[10px] font-bold bg-neutral-800 text-neutral-400 rounded-md hover:text-white">Nao</button>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-16 text-neutral-500">
                <p class="text-lg font-medium text-neutral-300 mb-1">Nenhum usuario cadastrado</p>
                <p class="text-sm">Crie atendentes para sua equipe</p>
            </div>
        @endforelse
    </div>
</div>
