@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6" x-data="superadminBackups()">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Backups</h1>
            <p class="mt-1 text-sm text-neutral-400">Cópias de segurança de todas as empresas</p>
        </div>
        <button @click="openCreate = !openCreate"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all duration-200 hover:scale-[1.02]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Backup
        </button>
    </div>

    {{-- Criar backup --}}
    <div x-show="openCreate" x-cloak
         class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6 space-y-4"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="flex items-center gap-3">
            <label for="create-tenant" class="text-sm font-medium text-neutral-300 shrink-0">Empresa</label>
            <select id="create-tenant" x-model="createTenantId" @change="createError = null"
                    class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                <option value="">Selecione a empresa...</option>
                <template x-for="t in tenants" :key="t.id">
                    <option :value="t.id" x-text="t.name + ' (' + t.plan + ')'"></option>
                </template>
            </select>
            <button @click="createBackup()" :disabled="!createTenantId || creating"
                    class="shrink-0 px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!creating">Criar agora</span>
                <span x-show="creating">Gerando...</span>
            </button>
        </div>
        <p x-show="createError" x-cloak class="text-sm text-red-400" x-text="createError"></p>
        <p x-show="createMessage" x-cloak class="text-sm text-emerald-400" x-text="createMessage"></p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
            <p class="text-xs text-neutral-500 uppercase tracking-wide">Total de backups</p>
            <p class="mt-2 text-3xl font-bold text-white" x-text="stats.total_backups"></p>
        </div>
        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
            <p class="text-xs text-neutral-500 uppercase tracking-wide">Espaço utilizado</p>
            <p class="mt-2 text-3xl font-bold text-white" x-text="formatBytes(stats.total_size_bytes)"></p>
        </div>
        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
            <p class="text-xs text-neutral-500 uppercase tracking-wide">Expirados (prontos p/ purga)</p>
            <p class="mt-2 text-3xl font-bold text-white" x-text="stats.expired_count"></p>
        </div>
    </div>

    {{-- Lista --}}
    <div class="rounded-2xl bg-neutral-900 border border-neutral-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-neutral-500 uppercase tracking-wide border-b border-neutral-800">
                        <th class="p-4 font-medium">Empresa</th>
                        <th class="p-4 font-medium">Data</th>
                        <th class="p-4 font-medium">Tamanho</th>
                        <th class="p-4 font-medium">Tipo</th>
                        <th class="p-4 font-medium">Expira</th>
                        <th class="p-4 text-right font-medium">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="b in backups" :key="b.id">
                        <tr class="border-b border-neutral-800/60 last:border-0">
                            <td class="p-4">
                                <p class="text-white font-medium" x-text="b.tenant?.name || '—'"></p>
                                <p class="text-xs text-neutral-500" x-text="'/' + (b.tenant?.slug || '—')"></p>
                            </td>
                            <td class="p-4 text-neutral-400" x-text="formatDate(b.created_at)"></td>
                            <td class="p-4 text-neutral-300" x-text="formatBytes(b.size_bytes)"></td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                      :class="b.type === 'scheduled' ? 'bg-violet-500/10 text-violet-400 border border-violet-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'"
                                      x-text="b.type"></span>
                            </td>
                            <td class="p-4 text-neutral-400">
                                <span x-show="b.expires_at" x-text="formatDate(b.expires_at)"></span>
                                <span x-show="!b.expires_at" class="text-emerald-400">Nunca</span>
                            </td>
                            <td class="p-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-2" x-data="{ confirm: false }">
                                    <button x-show="!confirm" @click="confirm = true"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-neutral-800 hover:bg-red-500/20 text-neutral-300 hover:text-red-400 text-xs font-medium transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Excluir
                                    </button>
                                    <button x-show="confirm" @click="confirm = false; removeBackup(b)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-500/20 text-red-400 text-xs font-semibold transition-colors">
                                        Confirmar?
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="backups.length === 0">
                        <td colspan="6" class="p-10 text-center text-neutral-500">Nenhum backup encontrado.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="p-4 flex items-center justify-between border-t border-neutral-800">
            <p class="text-xs text-neutral-500" x-text="'Página ' + (meta.current_page || 1) + ' de ' + (meta.last_page || 1)"></p>
            <div class="flex gap-2">
                <button @click="go(meta.current_page - 1)" :disabled="(meta.current_page || 1) <= 1"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium bg-neutral-800 hover:bg-neutral-700 text-neutral-300 transition-colors disabled:opacity-40">
                    Anterior
                </button>
                <button @click="go(meta.current_page + 1)" :disabled="(meta.current_page || 1) >= (meta.last_page || 1)"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium bg-neutral-800 hover:bg-neutral-700 text-neutral-300 transition-colors disabled:opacity-40">
                    Próxima
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function superadminBackups() {
        return {
            backups: [],
            tenants: [],
            meta: {},
            stats: {},
            openCreate: false,
            createTenantId: '',
            creating: false,
            createError: null,
            createMessage: null,
            load() {
                const params = new URLSearchParams();
                if (this.meta.current_page) params.set('page', this.meta.current_page);
                fetch('/api/superadmin/backups?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : Promise.reject(new Error('Falha ao carregar backups (' + r.status + ')')))
                    .then(data => {
                        this.backups = data.backups.data || [];
                        this.meta = data.backups.meta || {};
                        this.stats = data.stats || {};
                    })
                    .catch(e => { this.backups = []; this.stats = {}; this.createError = e.message; });
            },
            init() {
                this.load();
                fetch('/api/superadmin/tenants', { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : [])
                    .then(data => { this.tenants = data; })
                    .catch(() => { this.tenants = []; });
            },
            go(page) {
                if (page < 1 || (this.meta.last_page && page > this.meta.last_page)) return;
                this.meta.current_page = page;
                this.load();
            },
            async createBackup() {
                if (!this.createTenantId || this.creating) return;
                this.creating = true;
                this.createError = null;
                this.createMessage = null;
                try {
                    const r = await fetch('/api/superadmin/backups', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ tenant_id: Number(this.createTenantId) })
                    });
                    if (!r.ok) throw new Error('Falha ao criar o backup (' + r.status + ')');
                    this.createMessage = 'Backup criado com sucesso!';
                    this.createTenantId = '';
                    this.meta.current_page = 1;
                    this.load();
                } catch (e) {
                    this.createError = e.message;
                } finally {
                    this.creating = false;
                }
            },
            async removeBackup(b) {
                try {
                    const r = await fetch('/api/superadmin/backups/' + b.id, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    if (!r.ok) throw new Error('Falha ao excluir (' + r.status + ')');
                    this.load();
                } catch (e) {
                    this.createError = e.message;
                }
            },
            formatBytes(bytes) {
                bytes = Number(bytes || 0);
                if (bytes >= 1048576) return (bytes / 1048576).toFixed(2).replace('.', ',') + ' MB';
                if (bytes >= 1024) return (bytes / 1024).toFixed(2).replace('.', ',') + ' KB';
                return bytes + ' B';
            },
            formatDate(date) {
                if (!date) return '—';
                return new Date(date).toLocaleDateString('pt-BR');
            }
        };
    }
</script>
@endsection
