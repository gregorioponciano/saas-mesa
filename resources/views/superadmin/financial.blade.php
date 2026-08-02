@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6" x-data="superadminFinancial()">
    <div>
        <h1 class="text-2xl font-bold text-white">Financeiro</h1>
        <p class="mt-1 text-sm text-neutral-400">Pagamentos, assinaturas e faturas da plataforma</p>
    </div>

    @include('superadmin.partials.subnav')

    <div class="flex gap-2 border-b border-neutral-800">
        <template x-for="tab in ['payments', 'subscriptions', 'invoices']" :key="tab">
            <button @click="switchTab(tab)"
                    class="px-4 py-3 text-sm font-semibold transition-colors border-b-2"
                    :class="activeTab === tab ? 'text-amber-400 border-amber-500' : 'text-neutral-500 hover:text-white border-transparent'"
                    x-text="tab === 'payments' ? 'Pagamentos' : (tab === 'subscriptions' ? 'Assinaturas' : 'Faturas')"></button>
        </template>
    </div>

    {{-- STATS POR ABA --}}
    <div x-show="activeTab === 'subscriptions'" class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <template x-for="(value, key) in subStats" :key="key">
            <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-4">
                <p class="text-xs text-neutral-500 capitalize" x-text="key.replace(/_/g, ' ')"></p>
                <p class="mt-1 text-xl font-bold text-white" x-text="value ?? 0"></p>
            </div>
        </template>
    </div>

    <div x-show="activeTab === 'invoices'" class="grid grid-cols-3 gap-4">
        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-4">
            <p class="text-xs text-neutral-500">Total de faturas</p>
            <p class="mt-1 text-xl font-bold text-white" x-text="invoiceStats.total ?? 0"></p>
        </div>
        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-4">
            <p class="text-xs text-neutral-500">Em aberto</p>
            <p class="mt-1 text-xl font-bold text-amber-400" x-text="formatCents(invoiceStats.open_cents)"></p>
        </div>
        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-4">
            <p class="text-xs text-neutral-500">Coletado</p>
            <p class="mt-1 text-xl font-bold text-emerald-400" x-text="formatCents(invoiceStats.collected_cents)"></p>
        </div>
    </div>

    {{-- FILTROS PAGAMENTOS --}}
    <div x-show="activeTab === 'payments'" class="flex flex-wrap gap-3">
        <button @click="setFilter('status', '')"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                :class="filters.status === '' ? 'bg-amber-500 text-neutral-950' : 'bg-neutral-900 text-neutral-400 hover:text-white border border-neutral-800'">
            Todos
        </button>
        <template x-for="s in ['paid', 'pending', 'failed']" :key="s">
            <button @click="setFilter('status', s)"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors capitalize"
                    :class="filters.status === s ? 'bg-amber-500 text-neutral-950' : 'bg-neutral-900 text-neutral-400 hover:text-white border border-neutral-800'"
                    x-text="s"></button>
        </template>
        <template x-for="m in ['pix', 'boleto', 'credit_card']" :key="m">
            <button @click="setFilter('method', m)"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors capitalize"
                    :class="filters.method === m ? 'bg-amber-500 text-neutral-950' : 'bg-neutral-900 text-neutral-400 hover:text-white border border-neutral-800'"
                    x-text="m"></button>
        </template>
        <button @click="clearFilters()"
                class="px-3 py-1.5 rounded-lg text-xs font-medium text-neutral-500 hover:text-white transition-colors">
            Limpar filtros
        </button>
    </div>

    {{-- FILTROS ASSINATURAS --}}
    <div x-show="activeTab === 'subscriptions'" class="flex flex-wrap gap-3">
        <template x-for="s in ['', 'trial', 'active', 'past_due', 'suspended', 'cancelled']" :key="s">
            <button @click="setSubFilter(s)"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors capitalize"
                    :class="subFilter === s ? 'bg-amber-500 text-neutral-950' : 'bg-neutral-900 text-neutral-400 hover:text-white border border-neutral-800'"
                    x-text="s === '' ? 'Todas' : s"></button>
        </template>
    </div>

    {{-- FILTROS FATURAS --}}
    <div x-show="activeTab === 'invoices'" class="flex flex-wrap gap-3">
        <template x-for="s in ['', 'paid', 'pending', 'failed']" :key="s">
            <button @click="setInvoiceFilter(s)"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors capitalize"
                    :class="invoiceFilter === s ? 'bg-amber-500 text-neutral-950' : 'bg-neutral-900 text-neutral-400 hover:text-white border border-neutral-800'"
                    x-text="s === '' ? 'Todas' : s"></button>
        </template>
    </div>

    {{-- TABELA PAGAMENTOS --}}
    <div x-show="activeTab === 'payments'" class="rounded-2xl bg-neutral-900 border border-neutral-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-neutral-500 uppercase tracking-wide border-b border-neutral-800">
                        <th class="p-4 font-medium">Data</th>
                        <th class="p-4 font-medium">Empresa</th>
                        <th class="p-4 font-medium">Método</th>
                        <th class="p-4 font-medium">Status</th>
                        <th class="p-4 text-right font-medium">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="p in payments" :key="p.id">
                        <tr class="border-b border-neutral-800/60 last:border-0">
                            <td class="p-4 text-neutral-400" x-text="formatDate(p.created_at)"></td>
                            <td class="p-4 text-white" x-text="p.tenant?.name || '—'"></td>
                            <td class="p-4 text-neutral-400 capitalize" x-text="p.method || '—'"></td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                      :class="p.status === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                                            : p.status === 'pending' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20'
                                            : 'bg-red-500/10 text-red-400 border border-red-500/20'"
                                      x-text="p.status"></span>
                            </td>
                            <td class="p-4 text-right font-semibold text-white" x-text="formatCents(p.amount_cents)"></td>
                        </tr>
                    </template>
                    <tr x-show="payments.length === 0">
                        <td colspan="5" class="p-10 text-center text-neutral-500">Nenhum pagamento encontrado.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- TABELA ASSINATURAS --}}
    <div x-show="activeTab === 'subscriptions'" class="rounded-2xl bg-neutral-900 border border-neutral-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-neutral-500 uppercase tracking-wide border-b border-neutral-800">
                        <th class="p-4 font-medium">Empresa</th>
                        <th class="p-4 font-medium">Plano</th>
                        <th class="p-4 font-medium">Status</th>
                        <th class="p-4 font-medium">Método</th>
                        <th class="p-4 font-medium">Próx. cobrança</th>
                        <th class="p-4 text-right font-medium">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="s in subscriptions" :key="s.id">
                        <tr class="border-b border-neutral-800/60 last:border-0">
                            <td class="p-4 text-white" x-text="s.tenant_name || '—'"></td>
                            <td class="p-4 text-neutral-300" x-text="s.plan_name || '—'"></td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                      :class="s.status === 'active' || s.status === 'trial' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                                            : s.status === 'past_due' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20'
                                            : 'bg-red-500/10 text-red-400 border border-red-500/20'"
                                      x-text="s.status"></span>
                            </td>
                            <td class="p-4 text-neutral-400 capitalize" x-text="s.payment_method || '—'"></td>
                            <td class="p-4 text-neutral-400" x-text="formatDate(s.next_billing_date)"></td>
                            <td class="p-4 text-right font-semibold text-white" x-text="formatCents(s.price_cents)"></td>
                        </tr>
                    </template>
                    <tr x-show="subscriptions.length === 0">
                        <td colspan="6" class="p-10 text-center text-neutral-500">Nenhuma assinatura encontrada.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- TABELA FATURAS --}}
    <div x-show="activeTab === 'invoices'" class="rounded-2xl bg-neutral-900 border border-neutral-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-neutral-500 uppercase tracking-wide border-b border-neutral-800">
                        <th class="p-4 font-medium">Empresa</th>
                        <th class="p-4 font-medium">Período</th>
                        <th class="p-4 font-medium">Status</th>
                        <th class="p-4 font-medium">Pago em</th>
                        <th class="p-4 text-right font-medium">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="i in invoices" :key="i.id">
                        <tr class="border-b border-neutral-800/60 last:border-0">
                            <td class="p-4 text-white" x-text="i.tenant_name || '—'"></td>
                            <td class="p-4 text-neutral-400">
                                <span x-text="formatDate(i.period_start) + ' → ' + formatDate(i.period_end)"></span>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                      :class="i.status === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                                            : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'"
                                      x-text="i.status"></span>
                            </td>
                            <td class="p-4 text-neutral-400" x-text="formatDate(i.paid_at)"></td>
                            <td class="p-4 text-right font-semibold text-white" x-text="formatCents(i.amount_cents)"></td>
                        </tr>
                    </template>
                    <tr x-show="invoices.length === 0">
                        <td colspan="5" class="p-10 text-center text-neutral-500">Nenhuma fatura encontrada.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex items-center justify-between text-sm text-neutral-400">
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

<script>
    function superadminFinancial() {
        return {
            activeTab: 'payments',
            payments: [],
            subscriptions: [],
            invoices: [],
            subStats: {},
            invoiceStats: {},
            meta: {},
            filters: { status: '', method: '' },
            subFilter: '',
            invoiceFilter: '',
            load() {
                if (this.activeTab === 'payments') this.loadPayments();
                if (this.activeTab === 'subscriptions') this.loadSubscriptions();
                if (this.activeTab === 'invoices') this.loadInvoices();
            },
            async loadPayments() {
                const params = new URLSearchParams();
                if (this.filters.status) params.set('status', this.filters.status);
                if (this.filters.method) params.set('method', this.filters.method);
                if (this.meta.current_page) params.set('page', this.meta.current_page);
                const r = await fetch('/api/superadmin/financial/payments?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                if (!r.ok) return;
                const data = await r.json();
                this.payments = data.data || [];
                this.meta = data.meta || {};
            },
            async loadSubscriptions() {
                const params = new URLSearchParams();
                if (this.subFilter) params.set('status', this.subFilter);
                if (this.meta.current_page) params.set('page', this.meta.current_page);
                const r = await fetch('/api/superadmin/financial/subscriptions?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                if (!r.ok) return;
                const data = await r.json();
                this.subscriptions = data.subscriptions.data || [];
                this.subStats = data.stats || {};
                this.meta = data.subscriptions.meta || {};
            },
            async loadInvoices() {
                const params = new URLSearchParams();
                if (this.invoiceFilter) params.set('status', this.invoiceFilter);
                if (this.meta.current_page) params.set('page', this.meta.current_page);
                const r = await fetch('/api/superadmin/financial/invoices?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                if (!r.ok) return;
                const data = await r.json();
                this.invoices = data.invoices.data || [];
                this.invoiceStats = data.stats || {};
                this.meta = data.invoices.meta || {};
            },
            switchTab(tab) {
                this.activeTab = tab;
                this.meta.current_page = 1;
                this.load();
            },
            init() { this.load(); },
            setFilter(key, value) {
                this.filters[key] = value === this.filters[key] ? '' : value;
                this.meta.current_page = 1;
                this.load();
            },
            clearFilters() { this.filters = { status: '', method: '' }; this.meta.current_page = 1; this.load(); },
            setSubFilter(s) { this.subFilter = s === this.subFilter ? '' : s; this.meta.current_page = 1; this.load(); },
            setInvoiceFilter(s) { this.invoiceFilter = s === this.invoiceFilter ? '' : s; this.meta.current_page = 1; this.load(); },
            go(page) {
                if (page < 1 || (this.meta.last_page && page > this.meta.last_page)) return;
                this.meta.current_page = page;
                this.load();
            },
            formatCents(cents) {
                return 'R$ ' + (Number(cents || 0) / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            },
            formatDate(date) {
                if (!date) return '—';
                return new Date(date).toLocaleDateString('pt-BR');
            }
        };
    }
</script>
@endsection
