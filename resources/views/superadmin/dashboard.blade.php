@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6" x-data="superadminDashboard()">
    <div>
        <h1 class="text-2xl font-bold text-white">Visão Geral</h1>
        <p class="mt-1 text-sm text-neutral-400">Indicadores gerais da plataforma BurguerSaaS</p>
    </div>

    <template x-if="loading">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <template x-for="i in 4" :key="i">
                    <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6 animate-pulse">
                        <div class="h-3 w-24 bg-neutral-800 rounded mb-4"></div>
                        <div class="h-7 w-32 bg-neutral-800 rounded"></div>
                    </div>
                </template>
            </div>
            <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6 h-64 animate-pulse"></div>
        </div>
    </template>

    <template x-if="!loading && error">
        <div class="rounded-2xl bg-red-500/10 border border-red-500/20 p-6 text-red-400 text-sm" x-text="error"></div>
    </template>

    <template x-if="!loading && !error">
        <div>
            {{-- Cards principais --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-gradient-to-br from-amber-500/10 to-neutral-900 border border-amber-500/20 p-6">
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-neutral-400 uppercase tracking-wide">MRR</p>
                        <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="mt-2 text-3xl font-bold text-amber-400" x-text="stats.mrr_formatted"></p>
                </div>
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <p class="text-xs text-neutral-500 uppercase tracking-wide">Empresas ativas</p>
                    <p class="mt-2 text-3xl font-bold text-white" x-text="stats.active_tenants"></p>
                    <p class="mt-1 text-xs text-neutral-500">
                        <span class="text-emerald-400" x-text="stats.paid_tenants"></span> pagantes ·
                        <span class="text-violet-400" x-text="stats.trial_tenants"></span> trial
                    </p>
                </div>
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <p class="text-xs text-neutral-500 uppercase tracking-wide">Total recebido</p>
                    <p class="mt-2 text-3xl font-bold text-white" x-text="formatCents(stats.total_collected_cents)"></p>
                </div>
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <p class="text-xs text-neutral-500 uppercase tracking-wide">Assinaturas ativas</p>
                    <p class="mt-2 text-3xl font-bold text-white" x-text="stats.active_subscriptions"></p>
                </div>
            </div>

            {{-- Cards secundários --}}
            <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-4">
                    <p class="text-[10px] text-neutral-500 uppercase tracking-wide">Suspensas</p>
                    <p class="mt-1 text-xl font-bold text-white" x-text="stats.suspended_tenants"></p>
                </div>
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-4">
                    <p class="text-[10px] text-neutral-500 uppercase tracking-wide">Renovações (7d)</p>
                    <p class="mt-1 text-xl font-bold text-white" x-text="stats.pending_renewals_7days"></p>
                </div>
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-4">
                    <p class="text-[10px] text-neutral-500 uppercase tracking-wide">Webhooks falhos (24h)</p>
                    <p class="mt-1 text-xl font-bold text-white" x-text="stats.failed_webhooks_24h"></p>
                </div>
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-4">
                    <p class="text-[10px] text-neutral-500 uppercase tracking-wide">Backups</p>
                    <p class="mt-1 text-xl font-bold text-white" x-text="stats.total_backups"></p>
                </div>
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-4">
                    <p class="text-[10px] text-neutral-500 uppercase tracking-wide">Espaço em backup</p>
                    <p class="mt-1 text-xl font-bold text-white" x-text="formatBytes(stats.backups_size_bytes)"></p>
                </div>
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-4">
                    <p class="text-[10px] text-neutral-500 uppercase tracking-wide">Em trial</p>
                    <p class="mt-1 text-xl font-bold text-white" x-text="stats.trial_tenants"></p>
                </div>
            </div>

            {{-- Gráfico + empresas recentes --}}
            <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <h2 class="text-sm font-semibold text-white mb-5">Receita dos últimos 12 meses</h2>
                    <template x-if="revenue.length === 0">
                        <p class="text-sm text-neutral-500">Sem receita registrada no período.</p>
                    </template>
                    <div class="flex items-end gap-2 h-48" x-show="revenue.length > 0">
                        <template x-for="item in revenue" :key="item.month">
                            <div class="flex-1 flex flex-col items-center gap-2 group">
                                <span class="text-[10px] text-neutral-500 opacity-0 group-hover:opacity-100 transition-opacity" x-text="formatCents(item.total_cents)"></span>
                                <div class="w-full rounded-t-lg bg-amber-500/80 hover:bg-amber-400 transition-colors"
                                     :style="'height:' + barHeight(item.total_cents) + 'px'"></div>
                                <span class="text-[10px] text-neutral-500" x-text="shortMonth(item.month)"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-white">Empresas recentes</h2>
                        <a href="{{ route('superadmin.tenants') }}" class="text-xs text-amber-400 hover:text-amber-300 font-medium">Ver todas →</a>
                    </div>
                    <div class="space-y-3">
                        <template x-for="t in recentTenants" :key="t.id">
                            <div class="flex items-center justify-between gap-3 p-3 rounded-xl bg-neutral-950/60 border border-neutral-800/60">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-white truncate" x-text="t.name"></p>
                                    <p class="text-xs text-neutral-500" x-text="t.users_count + ' usuários · ' + t.orders_count + ' pedidos'"></p>
                                </div>
                                <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                      :class="t.plan === 'paid' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-neutral-800 text-neutral-400 border border-neutral-700'"
                                      x-text="t.plan"></span>
                            </div>
                        </template>
                        <template x-if="recentTenants.length === 0">
                            <p class="text-sm text-neutral-500 text-center py-6">Nenhuma empresa cadastrada.</p>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
    function superadminDashboard() {
        return {
            loading: true,
            error: null,
            stats: {},
            revenue: [],
            recentTenants: [],
            maxRevenue: 0,
            init() {
                fetch('/api/superadmin/financial/overview', { headers: { 'Accept': 'application/json' } })
                    .then(r => { if (!r.ok) throw new Error('Falha ao carregar dados (' + r.status + ')'); return r.json(); })
                    .then(data => {
                        this.stats = data.stats;
                        this.revenue = data.revenue_last_12_months || [];
                        this.recentTenants = data.recent_tenants || [];
                        this.maxRevenue = Math.max(1, ...this.revenue.map(i => i.total_cents));
                        this.loading = false;
                    })
                    .catch(e => { this.error = e.message; this.loading = false; });
            },
            formatCents(cents) {
                return 'R$ ' + Number(cents || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            },
            formatBytes(bytes) {
                bytes = Number(bytes || 0);
                if (bytes >= 1048576) return (bytes / 1048576).toFixed(2).replace('.', ',') + ' MB';
                if (bytes >= 1024) return (bytes / 1024).toFixed(2).replace('.', ',') + ' KB';
                return bytes + ' B';
            },
            shortMonth(ym) {
                const [y, m] = ym.split('-');
                return new Date(y, Number(m) - 1, 1).toLocaleDateString('pt-BR', { month: 'short' });
            },
            barHeight(cents) {
                return Math.max(4, Math.round((cents / this.maxRevenue) * 160));
            }
        };
    }
</script>
@endsection
