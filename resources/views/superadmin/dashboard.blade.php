@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6" x-data="superadminDashboard()">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Visão Geral</h1>
            <p class="mt-1 text-sm text-neutral-400">Relatório completo da plataforma BurguerSaaS</p>
        </div>
        <div class="flex items-center gap-3 text-xs">
            <span class="px-3 py-1.5 rounded-full bg-neutral-900 border border-neutral-800 text-neutral-400">
                Gerado em <span x-text="generatedAt" class="text-neutral-300 font-medium"></span>
            </span>
            <button @click="load()" class="px-3 py-1.5 rounded-full bg-neutral-900 border border-neutral-800 text-neutral-300 hover:border-amber-500/40 hover:text-amber-400 transition-colors">
                ↻ Atualizar
            </button>
        </div>
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
        <div class="space-y-6">
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
                    <p class="mt-1 text-xs text-neutral-500" x-text="stats.pending_renewals_7days + ' renovações em 7d'"></p>
                </div>
            </div>

            {{-- Integridade do sistema --}}
            <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-semibold text-white">Integridade do sistema</h2>
                    <span class="text-xs text-neutral-500" x-text="'v' + system.laravel_version + ' · PHP ' + system.php_version"></span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <template x-for="check in [
                        { key: 'database', label: 'Banco de dados', desc: connections.database.driver },
                        { key: 'cache', label: 'Cache', desc: connections.cache.driver },
                        { key: 'storage', label: 'Armazenamento', desc: connections.storage.disk },
                        { key: 'queue', label: 'Fila de jobs', desc: connections.queue.driver },
                        { key: 'efi', label: 'PIX / EFI', desc: connections.integrations.efi_configured_tenants + ' config.' },
                        { key: 'smtp', label: 'E-mail (SMTP)', desc: connections.integrations.smtp_configured_tenants + ' config.' },
                    ]" :key="check.key">
                        <div class="rounded-xl bg-neutral-950/60 border border-neutral-800/60 p-3.5">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full shrink-0"
                                      :class="isOk(check) ? 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,.6)]' : 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,.6)]'"></span>
                                <p class="text-xs font-medium text-white" x-text="check.label"></p>
                            </div>
                            <p class="mt-1.5 text-[11px] text-neutral-500 truncate" x-text="check.desc"></p>
                        </div>
                    </template>
                </div>
                <div class="mt-4 flex flex-wrap gap-2 text-[11px]">
                    <span class="px-2.5 py-1 rounded-full bg-neutral-950/60 border border-neutral-800/60 text-neutral-400">
                        Ambiente: <strong class="text-white" x-text="system.app_env"></strong>
                    </span>
                    <span class="px-2.5 py-1 rounded-full bg-neutral-950/60 border border-neutral-800/60 text-neutral-400">
                        Debug: <strong class="text-white" x-text="system.app_debug ? 'ligado' : 'desligado'"></strong>
                    </span>
                    <span class="px-2.5 py-1 rounded-full bg-neutral-950/60 border border-neutral-800/60 text-neutral-400">
                        Uptime: <strong class="text-white" x-text="formatUptime(system.uptime_seconds)"></strong>
                    </span>
                    <span class="px-2.5 py-1 rounded-full bg-neutral-950/60 border border-neutral-800/60 text-neutral-400">
                        Disco: <strong class="text-white" x-text="resources.disk_used_percent !== null ? resources.disk_used_percent + '% usado' : '—'"></strong>
                    </span>
                </div>
            </div>

            {{-- Erros e alertas --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-white">Alertas e erros</h2>
                        <span class="text-xs text-neutral-500">24h</span>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-neutral-950/60 border border-neutral-800/60">
                            <p class="text-xs text-neutral-300">Webhooks inválidos</p>
                            <span class="text-sm font-bold text-white" x-text="errors.failed_webhooks_24h"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-neutral-950/60 border border-neutral-800/60">
                            <p class="text-xs text-neutral-300">Jobs com falha</p>
                            <span class="text-sm font-bold text-white" x-text="errors.failed_jobs"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-neutral-950/60 border border-neutral-800/60">
                            <p class="text-xs text-neutral-300">Erros no log (últimos)</p>
                            <span class="text-sm font-bold text-white" x-text="errors.recent_log_errors.length"></span>
                        </div>
                        <template x-if="errors.recent_log_errors.length > 0">
                            <div class="pt-1 space-y-2">
                                <template x-for="e in errors.recent_log_errors" :key="e.time + e.message">
                                    <div class="p-2.5 rounded-lg bg-red-500/5 border border-red-500/15">
                                        <p class="text-[10px] text-red-400/80" x-text="e.time"></p>
                                        <p class="text-[11px] text-red-300/90 mt-0.5 line-clamp-2" x-text="e.message"></p>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Scheduler / rotinas --}}
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <h2 class="text-sm font-semibold text-white mb-4">Tarefas agendadas</h2>
                    <div class="space-y-3">
                        <template x-for="task in schedulerTasks" :key="task.key">
                            <div class="flex items-center justify-between gap-3 p-3 rounded-xl bg-neutral-950/60 border border-neutral-800/60">
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-white truncate" x-text="task.command"></p>
                                    <p class="text-[10px] text-neutral-500 mt-0.5" x-text="task.last_run_at ? 'Última execução: ' + task.last_run_at : 'Nunca executou'"></p>
                                </div>
                                <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                      :class="task.status === 'ran' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'"
                                      x-text="task.status === 'ran' ? 'OK' : 'pendente'"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Recursos --}}
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <h2 class="text-sm font-semibold text-white mb-4">Recursos</h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-neutral-950/60 border border-neutral-800/60">
                            <p class="text-xs text-neutral-300">Backups armazenados</p>
                            <span class="text-sm font-bold text-white" x-text="stats.total_backups + ' · ' + formatBytes(stats.backups_size_bytes)"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-neutral-950/60 border border-neutral-800/60">
                            <p class="text-xs text-neutral-300">Espaço em disco</p>
                            <span class="text-sm font-bold text-white" x-text="resources.disk_free_bytes ? formatBytes(resources.disk_free_bytes) + ' livres' : '—'"></span>
                        </div>
                        <div class="p-3 rounded-xl bg-neutral-950/60 border border-neutral-800/60">
                            <div class="flex items-center justify-between mb-1.5">
                                <p class="text-xs text-neutral-300">Uso do disco</p>
                                <span class="text-xs text-white font-semibold" x-text="resources.disk_used_percent + '%'"></span>
                            </div>
                            <div class="h-2 rounded-full bg-neutral-800 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                     :class="resources.disk_used_percent > 85 ? 'bg-red-500' : resources.disk_used_percent > 70 ? 'bg-amber-500' : 'bg-emerald-500'"
                                     :style="'width:' + Math.min(resources.disk_used_percent, 100) + '%'"></div>
                            </div>
                        </div>
                        <template x-if="connections.database.error">
                            <div class="p-2.5 rounded-lg bg-red-500/5 border border-red-500/15 text-[11px] text-red-300/90" x-text="connections.database.error"></div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Gráfico + empresas recentes --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
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

            {{-- Status da plataforma + auditoria recente --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <h2 class="text-sm font-semibold text-white mb-4">Status da plataforma</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="item in statusTotals" :key="item.label">
                            <div class="p-3 rounded-xl bg-neutral-950/60 border border-neutral-800/60">
                                <p class="text-[10px] text-neutral-500 uppercase tracking-wide" x-text="item.label"></p>
                                <p class="mt-1 text-lg font-bold text-white" x-text="item.value"></p>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-white">Auditoria recente</h2>
                        <a href="{{ route('superadmin.audit') }}" class="text-xs text-amber-400 hover:text-amber-300 font-medium">Ver todas →</a>
                    </div>
                    <div class="space-y-2.5">
                        <template x-for="log in recentAudit" :key="log.id">
                            <div class="flex items-start gap-3 p-2.5 rounded-xl bg-neutral-950/60 border border-neutral-800/60">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mt-1.5 shrink-0"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs text-neutral-300 truncate" x-text="log.description || log.action"></p>
                                    <p class="text-[10px] text-neutral-500 mt-0.5" x-text="log.admin_name + ' · ' + formatDate(log.created_at)"></p>
                                </div>
                            </div>
                        </template>
                        <template x-if="recentAudit.length === 0">
                            <p class="text-sm text-neutral-500 text-center py-6">Nenhuma atividade registrada.</p>
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
            system: {},
            connections: {},
            errors: {},
            resources: {},
            recentAudit: [],
            schedulerTasks: [],
            statusTotals: [],
            generatedAt: '',
            init() {
                this.load();
            },
            load() {
                this.loading = true;
                this.error = null;
                Promise.all([
                    fetch('/api/superadmin/financial/overview', { headers: { 'Accept': 'application/json' } }),
                    fetch('/api/superadmin/system/report', { headers: { 'Accept': 'application/json' } }),
                ])
                    .then(async ([fin, rep]) => {
                        if (!fin.ok) throw new Error('Falha ao carregar dados (' + fin.status + ')');
                        if (!rep.ok) throw new Error('Falha ao carregar relatório (' + rep.status + ')');
                        const finData = await fin.json();
                        const repData = await rep.json();

                        this.stats = finData.stats;
                        this.revenue = finData.revenue_last_12_months || [];
                        this.recentTenants = finData.recent_tenants || [];
                        this.maxRevenue = Math.max(1, ...this.revenue.map(i => i.total_cents));

                        this.system = repData.system || {};
                        this.connections = repData.connections || {};
                        this.errors = repData.errors || {};
                        this.resources = repData.resources || {};
                        this.recentAudit = repData.recent_audit || [];
                        this.schedulerTasks = Object.values(repData.scheduler || {});
                        this.generatedAt = this.formatDate(repData.generated_at);

                        const t = repData.status || {};
                        this.statusTotals = Object.entries(t.totals || {}).map(([k, v]) => ({
                            label: k.replace(/_/g, ' '),
                            value: v,
                        }));

                        this.loading = false;
                    })
                    .catch(e => { this.error = e.message; this.loading = false; });
            },
            isOk(check) {
                if (check.key === 'database') return this.connections.database.ok;
                if (check.key === 'cache') return this.connections.cache.ok;
                if (check.key === 'storage') return this.connections.storage.writable;
                if (check.key === 'queue') return true;
                if (check.key === 'efi') return true;
                if (check.key === 'smtp') return true;
                return true;
            },
            formatCents(cents) {
                return 'R$ ' + Number(cents || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            },
            formatBytes(bytes) {
                bytes = Number(bytes || 0);
                if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2).replace('.', ',') + ' GB';
                if (bytes >= 1048576) return (bytes / 1048576).toFixed(2).replace('.', ',') + ' MB';
                if (bytes >= 1024) return (bytes / 1024).toFixed(2).replace('.', ',') + ' KB';
                return bytes + ' B';
            },
            formatUptime(seconds) {
                if (!seconds) return '—';
                const d = Math.floor(seconds / 86400);
                const h = Math.floor((seconds % 86400) / 3600);
                if (d > 0) return d + 'd ' + h + 'h';
                const m = Math.floor((seconds % 3600) / 60);
                return h + 'h ' + m + 'min';
            },
            formatDate(iso) {
                if (!iso) return '—';
                return new Date(iso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
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
