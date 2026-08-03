@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6" x-data="superadminTenants()">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Empresas</h1>
            <p class="mt-1 text-sm text-neutral-400">Todas as empresas cadastradas na plataforma</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative">
                <input type="text" x-model="search" @input="filter()" placeholder="Buscar por nome ou email..."
                       class="w-full sm:w-72 px-4 py-2.5 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
            </div>
            <button @click="openCreate()"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all duration-200 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nova Empresa
            </button>
        </div>
    </div>

    @include('superadmin.partials.subnav')

    <div class="rounded-2xl bg-neutral-900 border border-neutral-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-neutral-500 uppercase tracking-wide border-b border-neutral-800">
                        <th class="p-4 font-medium">Empresa</th>
                        <th class="p-4 font-medium">Plano</th>
                        <th class="p-4 font-medium">Status</th>
                        <th class="p-4 font-medium">Dados</th>
                        <th class="p-4 text-right font-medium">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="t in tenants" :key="t.id">
                        <tr class="border-b border-neutral-800/60 last:border-0 hover:bg-neutral-800/20 transition-colors cursor-pointer"
                            @click="openDetail(t)">
                            <td class="p-4">
                                <p class="text-white font-medium" x-text="t.name"></p>
                                <p class="text-xs text-neutral-500" x-text="t.email"></p>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                      :class="t.plan === 'paid' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-neutral-800 text-neutral-400 border border-neutral-700'"
                                      x-text="t.plan"></span>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                      :class="t.status === 'suspended' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'"
                                      x-text="t.status"></span>
                                <p class="mt-1 text-[11px] text-neutral-500" x-text="t.subscription_status || ''"></p>
                            </td>
                            <td class="p-4 text-neutral-400">
                                <span x-text="t.users_count + ' usuários'"></span> ·
                                <span x-text="t.tables_count + ' mesas'"></span> ·
                                <span x-text="t.orders_count + ' pedidos'"></span>
                            </td>
                            <td class="p-4 text-right whitespace-nowrap">
                                <button @click.stop="toggleStatus(t)"
                                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                        :class="t.status === 'suspended' ? 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20' : 'bg-red-500/10 text-red-400 hover:bg-red-500/20'"
                                        x-text="t.status === 'suspended' ? 'Reativar' : 'Suspender'"></button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="tenants.length === 0">
                        <td colspan="5" class="p-10 text-center text-neutral-500">Nenhuma empresa encontrada.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Drawer de detalhe --}}
    <div x-show="detail.open" x-cloak class="fixed inset-0 z-50 flex justify-end">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="detail.open = false"
             x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"></div>
        <div class="relative w-full max-w-md bg-neutral-900 border-l border-neutral-800 h-full overflow-y-auto p-6"
             x-transition:enter="transition transform duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-bold text-white" x-text="detail.data?.name || 'Empresa'"></h2>
                    <p class="text-sm text-neutral-500" x-text="detail.data?.slug ? '/' + detail.data.slug : ''"></p>
                </div>
                <button @click="detail.open = false" class="p-2 rounded-lg hover:bg-neutral-800 text-neutral-400 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="detail.loading">
                <div class="mt-6 space-y-3">
                    <div class="h-4 bg-neutral-800 rounded animate-pulse"></div>
                    <div class="h-4 bg-neutral-800 rounded animate-pulse"></div>
                    <div class="h-4 bg-neutral-800 rounded animate-pulse"></div>
                </div>
            </template>

            <template x-if="!detail.loading && detail.data">
                <div class="mt-6 space-y-6">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-xl bg-neutral-950/60 border border-neutral-800 p-3 text-center">
                            <p class="text-xl font-bold text-white" x-text="detail.data.stats?.total_orders ?? 0"></p>
                            <p class="text-[10px] text-neutral-500 uppercase tracking-wide">Pedidos</p>
                        </div>
                        <div class="rounded-xl bg-neutral-950/60 border border-neutral-800 p-3 text-center">
                            <p class="text-xl font-bold text-white" x-text="detail.data.stats?.total_users ?? 0"></p>
                            <p class="text-[10px] text-neutral-500 uppercase tracking-wide">Usuários</p>
                        </div>
                        <div class="rounded-xl bg-neutral-950/60 border border-neutral-800 p-3 text-center">
                            <p class="text-xl font-bold text-white" x-text="detail.data.stats?.total_tables ?? 0"></p>
                            <p class="text-[10px] text-neutral-500 uppercase tracking-wide">Mesas</p>
                        </div>
                    </div>

                    <div class="rounded-xl bg-neutral-950/60 border border-neutral-800 p-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Plano</span>
                            <span class="text-white font-medium capitalize" x-text="detail.data.tenant?.plan"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Status</span>
                            <span class="text-white font-medium capitalize" x-text="detail.data.tenant?.status"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Assinatura</span>
                            <span class="text-white font-medium capitalize" x-text="detail.data.subscription?.status || '—'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Trial termina</span>
                            <span class="text-white font-medium" x-text="detail.data.subscription?.trial_ends_at ? formatDate(detail.data.subscription.trial_ends_at) : '—'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Próxima cobrança</span>
                            <span class="text-white font-medium" x-text="detail.data.subscription?.next_billing_date ? formatDate(detail.data.subscription.next_billing_date) : '—'"></span>
                        </div>
                    </div>

                    <a :href="'/superadmin/empresas/' + detail.data.tenant?.id + '/configuracoes'"
                       class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        </svg>
                        Configurações
                    </a>

                    <div class="grid grid-cols-2 gap-3">
                        <button @click="exportData(detail.data.tenant)"
                                class="flex items-center justify-center gap-2 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-200 text-sm font-semibold transition-all duration-200">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Exportar (LGPD)
                        </button>
                        <button @click="anonymize(detail.data.tenant)"
                                class="flex items-center justify-center gap-2 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 text-sm font-semibold transition-all duration-200">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Anonimizar
                        </button>
                    </div>

                    <div>
                        <h3 class="text-xs font-semibold text-neutral-400 uppercase tracking-wide mb-3">Últimos pedidos</h3>
                        <div class="space-y-2" x-show="detail.data.orders?.length">
                            <template x-for="o in detail.data.orders" :key="o.id">
                                <div class="flex items-center justify-between rounded-xl bg-neutral-950/60 border border-neutral-800 p-3 text-sm">
                                    <span class="text-neutral-300" x-text="'#' + o.id + ' · ' + (o.customer_name || 'Cliente')"></span>
                                    <span class="text-white font-medium" x-text="'R$ ' + Number(o.total || 0).toFixed(2).replace('.', ',')"></span>
                                </div>
                            </template>
                        </div>
                        <p x-show="!detail.data.orders?.length" class="text-sm text-neutral-500 text-center py-4">Sem pedidos ainda.</p>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Modal: Nova Empresa --}}
    <div x-show="showCreate" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showCreate = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-neutral-900 border border-neutral-800 p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-white">Nova Empresa</h3>
                <button @click="showCreate = false" class="text-neutral-500 hover:text-white">✕</button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Nome da empresa</label>
                    <input x-model="createForm.name" type="text" placeholder="Ex.: Burguer do João"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 text-white text-sm outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">E-mail</label>
                        <input x-model="createForm.email" type="email"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 text-white text-sm outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">WhatsApp</label>
                        <input x-model="createForm.whatsapp" type="text"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 text-white text-sm outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Nome do administrador</label>
                    <input x-model="createForm.admin_name" type="text"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 text-white text-sm outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">Senha do administrador</label>
                        <input x-model="createForm.admin_password" type="password"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 text-white text-sm outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">Plano</label>
                        <select x-model="createForm.plan_id"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 text-white text-sm outline-none">
                            <option value="">Gratuito (padrão)</option>
                            <template x-for="p in plans" :key="p.id">
                                <option :value="p.id" x-text="p.name + ' (' + formatCents(p.price_cents) + '/' + intervalLabel(p.interval) + ')'"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div x-show="createError" class="px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm" x-text="createError"></div>

                <div class="flex items-center gap-3 pt-2">
                    <button @click="create()" x-show="!creating"
                            class="flex-1 py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-bold transition-all duration-200">
                        Criar Empresa
                    </button>
                    <div x-show="creating" class="flex-1 py-3 rounded-xl bg-neutral-800 text-neutral-400 text-sm font-bold text-center">Criando…</div>
                    <button @click="showCreate = false"
                            class="px-4 py-3 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 text-sm font-semibold">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function superadminTenants() {
        return {
            tenants: [],
            all: [],
            plans: [],
            search: '',
            detail: { open: false, loading: false, data: null },
            showCreate: false,
            creating: false,
            createError: '',
            createForm: { name: '', email: '', whatsapp: '', admin_name: '', admin_password: '', plan_id: '' },
            init() {
                fetch('/api/superadmin/tenants', { headers: { 'Accept': 'application/json' } })
                    .then(r => { if (!r.ok) throw new Error('Falha ao carregar empresas (' + r.status + ')'); return r.json(); })
                    .then(data => { this.all = data; this.filter(); })
                    .catch(() => { this.all = []; this.filter(); });
                fetch('/api/superadmin/plans', { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : [])
                    .then(data => { this.plans = data; })
                    .catch(() => { this.plans = []; });
            },
            filter() {
                const q = this.search.toLowerCase();
                this.tenants = this.all.filter(t =>
                    !q || t.name.toLowerCase().includes(q) || (t.email || '').toLowerCase().includes(q)
                );
            },
            openDetail(t) {
                this.detail.open = true;
                this.detail.loading = true;
                this.detail.data = null;
                fetch('/api/superadmin/tenants/' + t.id, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : Promise.reject(new Error('Falha ao carregar detalhes (' + r.status + ')')))
                    .then(data => { this.detail.data = data; this.detail.loading = false; })
                    .catch(() => { this.detail.loading = false; });
            },
            async toggleStatus(t) {
                const action = t.status === 'suspended' ? 'reactivate' : 'suspend';
                const r = await fetch('/api/superadmin/tenants/' + t.id + '/' + action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                if (r.ok) {
                    t.status = t.status === 'suspended' ? 'active' : 'suspended';
                    t.subscription_status = '';
                }
            },
            openCreate() {
                this.createError = '';
                this.createForm = { name: '', email: '', whatsapp: '', admin_name: '', admin_password: '', plan_id: '' };
                this.showCreate = true;
            },
            async create() {
                this.creating = true;
                this.createError = '';
                const payload = { ...this.createForm };
                if (!payload.plan_id) delete payload.plan_id;
                const r = await fetch('/api/superadmin/tenants', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });
                const data = await r.json().catch(() => ({}));
                this.creating = false;
                if (!r.ok) {
                    this.createError = (data.errors && Object.values(data.errors).flat().join(' • ')) || data.error || 'Falha ao criar a empresa.';
                    return;
                }
                this.showCreate = false;
                fetch('/api/superadmin/tenants', { headers: { 'Accept': 'application/json' } })
                    .then(res => res.ok ? res.json() : [])
                    .then(d => { this.all = d; this.filter(); });
            },
            async exportData(t) {
                const blob = await (await fetch('/api/superadmin/tenants/' + t.id + '/export', {
                    headers: { 'Accept': 'application/json' }
                })).blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'lgpd-empresa-' + t.id + '.json';
                a.click();
                URL.revokeObjectURL(url);
            },
            async anonymize(t) {
                if (!await saasConfirm('ANONIMIZAR E ENCERRAR "' + t.name + '"?\n\nEsta ação é irreversível: usuários, entregadores, senhas, endereços e backups serão removidos. Pedidos ficam anonimizados para fins contábeis.', { type: 'danger', title: 'Anonimizar empresa', confirmLabel: 'Anonimizar' })) return;
                if (!await saasConfirm('Tem certeza absoluta? O acesso de toda a empresa será perdido imediatamente.', { title: 'Última confirmação', confirmLabel: 'Sim, anonimizar' })) return;
                const r = await fetch('/api/superadmin/tenants/' + t.id, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await r.json().catch(() => ({}));
                if (r.ok) {
                    this.detail.open = false;
                    fetch('/api/superadmin/tenants', { headers: { 'Accept': 'application/json' } })
                        .then(res => res.ok ? res.json() : [])
                        .then(d => { this.all = d; this.filter(); });
                } else {
                    saasAlert(data.error || 'Falha ao anonimizar a empresa.', { title: 'Erro' });
                }
            },
            formatDate(date) {
                if (!date) return '—';
                return new Date(date).toLocaleDateString('pt-BR');
            },
            formatCents(cents) {
                return 'R$ ' + (Number(cents || 0) / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            },
            intervalLabel(interval) {
                return interval === 'month' ? 'mês'
                    : (interval === 'quarter' ? '3 meses'
                    : (interval === 'semiannual' ? '6 meses'
                    : (interval === 'year' ? 'ano' : interval)));
            }
        };
    }
</script>
@endsection
