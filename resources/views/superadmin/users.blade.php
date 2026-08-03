@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6" x-data="superadminUsers()">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Usuários da Plataforma</h1>
            <p class="mt-1 text-sm text-neutral-400">Superadmins e administradores de todas as empresas</p>
        </div>
        <button @click="openCreate()"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all duration-200">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Superadmin
        </button>
    </div>

    <div class="flex flex-wrap gap-3 items-center">
        <select x-model="filters.role" @change="load()"
                class="px-3.5 py-2.5 rounded-xl bg-neutral-900 border border-neutral-800 text-white text-sm outline-none">
            <option value="">Todos os perfis</option>
            <option value="superadmin">Superadmin</option>
            <option value="admin">Administrador</option>
            <option value="atendente">Atendente</option>
            <option value="cliente">Cliente</option>
        </select>
        <div class="relative flex-1 max-w-xs">
            <input x-model="filters.search" @keyup.enter="load()" type="text" placeholder="Buscar por nome ou e-mail…"
                   class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-900 border border-neutral-800 focus:border-amber-500 text-white text-sm outline-none">
        </div>
        <button @click="load()" class="px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 text-sm font-semibold">Filtrar</button>
    </div>

    <div class="rounded-2xl bg-neutral-900 border border-neutral-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-neutral-500 uppercase tracking-wider border-b border-neutral-800">
                    <th class="px-4 py-3">Usuário</th>
                    <th class="px-4 py-3">Perfil</th>
                    <th class="px-4 py-3">Empresa</th>
                    <th class="px-4 py-3">Criado em</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="u in users.data" :key="u.id">
                    <tr class="border-b border-neutral-800/60 hover:bg-neutral-800/30">
                        <td class="px-4 py-3">
                            <p class="text-white font-medium" x-text="u.name"></p>
                            <p class="text-xs text-neutral-500" x-text="u.email"></p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide" :class="u.role_color" x-text="u.role_label"></span>
                        </td>
                        <td class="px-4 py-3 text-neutral-300" x-text="u.tenant_name || '—'"></td>
                        <td class="px-4 py-3 text-neutral-400" x-text="formatDate(u.created_at)"></td>
                        <td class="px-4 py-3 text-right">
                            <button x-show="u.role === 'superadmin'"
                                    @click="revoke(u)"
                                    class="px-3 py-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs font-semibold">
                                Revogar acesso
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        <div x-show="!users.data || !users.data.length" class="p-10 text-center text-neutral-500 text-sm">Nenhum usuário encontrado.</div>
    </div>

    <div class="flex items-center justify-between text-sm text-neutral-400">
        <span x-text="'Página ' + (users.current_page || 1) + ' de ' + (users.last_page || 1)"></span>
        <div class="flex gap-2">
            <button @click="goPage(users.current_page - 1)" :disabled="!users.prev_page_url" class="px-4 py-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 text-sm font-semibold disabled:opacity-40">Anterior</button>
            <button @click="goPage(users.current_page + 1)" :disabled="!users.next_page_url" class="px-4 py-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 text-sm font-semibold disabled:opacity-40">Próxima</button>
        </div>
    </div>

    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-neutral-900 border border-neutral-800 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-white">Novo Superadmin</h3>
                <button @click="showModal = false" class="text-neutral-500 hover:text-white">✕</button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Nome completo</label>
                    <input x-model="form.name" type="text"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 text-white text-sm outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">E-mail</label>
                    <input x-model="form.email" type="email"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 text-white text-sm outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Senha (mín. 8 caracteres)</label>
                    <input x-model="form.password" type="password"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 text-white text-sm outline-none">
                </div>

                <div x-show="error" class="px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm" x-text="error"></div>

                <div class="flex items-center gap-3 pt-2">
                    <button @click="create()" x-show="!saving"
                            class="flex-1 py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-bold transition-all duration-200">
                        Criar Superadmin
                    </button>
                    <div x-show="saving" class="flex-1 py-3 rounded-xl bg-neutral-800 text-neutral-400 text-sm font-bold text-center">Criando…</div>
                    <button @click="showModal = false"
                            class="px-4 py-3 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 text-sm font-semibold">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function superadminUsers() {
        return {
            users: { data: [] },
            filters: { role: '', search: '' },
            showModal: false,
            saving: false,
            error: '',
            form: { name: '', email: '', password: '' },
            init() { this.load(); },
            async load(page = 1) {
                const params = new URLSearchParams({ page });
                if (this.filters.role) params.set('role', this.filters.role);
                if (this.filters.search) params.set('search', this.filters.search);
                const r = await fetch('/api/superadmin/users?' + params, { headers: { 'Accept': 'application/json' } });
                if (r.ok) this.users = await r.json();
            },
            goPage(p) {
                if (p < 1 || (this.users.last_page && p > this.users.last_page)) return;
                this.load(p);
            },
            openCreate() {
                this.error = '';
                this.form = { name: '', email: '', password: '' };
                this.showModal = true;
            },
            async create() {
                this.saving = true;
                this.error = '';
                const r = await fetch('/api/superadmin/users', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const data = await r.json().catch(() => ({}));
                this.saving = false;
                if (!r.ok) {
                    this.error = (data.errors && Object.values(data.errors).flat().join(' • ')) || data.error || 'Falha ao criar.';
                    return;
                }
                this.showModal = false;
                this.load();
            },
            async revoke(u) {
                if (!await saasConfirm('Revogar o acesso de superadmin de "' + u.name + '"?')) return;
                const r = await fetch('/api/superadmin/users/' + u.id + '/revoke', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await r.json().catch(() => ({}));
                if (r.ok) this.load();
                else saasAlert(data.error || 'Falha ao revogar.', { title: 'Erro' });
            },
            formatDate(d) {
                return d ? new Date(d).toLocaleDateString('pt-BR') : '—';
            }
        };
    }
</script>
@endsection
