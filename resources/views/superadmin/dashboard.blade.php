@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6" x-data="superadminDashboard()">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Visão Geral</h1>
            <p class="mt-1 text-sm text-neutral-400">Resumo da plataforma BurguerSaaS</p>
        </div>
        <div class="flex items-center gap-3 text-xs">
            <span class="px-3 py-1.5 rounded-full bg-neutral-900 border border-neutral-800 text-neutral-400">
                Atualizado em <span x-text="generatedAt" class="text-neutral-300 font-medium"></span>
            </span>
            <button @click="load()" class="px-3 py-1.5 rounded-full bg-neutral-900 border border-neutral-800 text-neutral-300 hover:border-amber-500/40 hover:text-amber-400 transition-colors">
                ↻ Atualizar
            </button>
        </div>
    </div>

    @include('superadmin.partials.subnav')

    <template x-if="loading">
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <template x-for="i in 4" :key="i">
                    <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6 animate-pulse">
                        <div class="h-3 w-24 bg-neutral-800 rounded mb-4"></div>
                        <div class="h-7 w-32 bg-neutral-800 rounded"></div>
                    </div>
                </template>
            </div>
            <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6 h-40 animate-pulse"></div>
        </div>
    </template>

    <template x-if="!loading && error">
        <div class="rounded-2xl bg-red-500/10 border border-red-500/20 p-6 text-red-400 text-sm" x-text="error"></div>
    </template>

    <template x-if="!loading && !error">
        <div class="space-y-6">
            {{-- Cards principais --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-gradient-to-br from-amber-500/10 to-neutral-900 border border-amber-500/20 p-6 transition-all duration-200 hover:border-amber-500/40">
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-neutral-400 uppercase tracking-wide">MRR</p>
                        <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="mt-2 text-3xl font-bold text-amber-400" x-text="stats.mrr_formatted"></p>
                </div>
                <a href="{{ route('superadmin.tenants') }}"
                   class="block rounded-2xl bg-neutral-900 border border-neutral-800 p-6 transition-all duration-200 hover:border-amber-500/40 hover:bg-neutral-800/50">
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-neutral-500 uppercase tracking-wide">Empresas ativas</p>
                        <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <p class="mt-2 text-3xl font-bold text-white" x-text="stats.active_tenants"></p>
                    <p class="mt-1 text-xs text-neutral-500">
                        <span class="text-emerald-400" x-text="stats.paid_tenants"></span> pagantes ·
                        <span class="text-violet-400" x-text="stats.trial_tenants"></span> trial
                    </p>
                </a>
                <a href="{{ route('superadmin.financial') }}"
                   class="block rounded-2xl bg-neutral-900 border border-neutral-800 p-6 transition-all duration-200 hover:border-amber-500/40 hover:bg-neutral-800/50">
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-neutral-500 uppercase tracking-wide">Total recebido</p>
                        <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="mt-2 text-3xl font-bold text-white" x-text="formatCents(stats.total_collected_cents)"></p>
                </a>
                <a href="{{ route('superadmin.plans') }}"
                   class="block rounded-2xl bg-neutral-900 border border-neutral-800 p-6 transition-all duration-200 hover:border-amber-500/40 hover:bg-neutral-800/50">
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-neutral-500 uppercase tracking-wide">Assinaturas ativas</p>
                        <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <p class="mt-2 text-3xl font-bold text-white" x-text="stats.active_subscriptions"></p>
                    <p class="mt-1 text-xs text-neutral-500" x-text="stats.pending_renewals_7days + ' renovações em 7d'"></p>
                </a>
            </div>

            {{-- Alertas em tempo real --}}
            <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-sm font-semibold text-white">Alertas em tempo real</h2>
                        <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Ao vivo
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] text-neutral-500">
                            <span class="text-red-400 font-semibold" x-text="criticalCount"></span> crítico(s) ·
                            <span class="text-amber-400 font-semibold" x-text="warningCount"></span> aviso(s)
                        </span>
                    </div>
                </div>

                <div class="space-y-2.5">
                    <template x-for="alert in alerts" :key="alert.title">
                        <div class="flex items-start gap-3 p-3 rounded-xl border transition-all duration-200"
                              :class="alert.level === 'critical' ? 'bg-red-500/5 border-red-500/20' : alert.level === 'warning' ? 'bg-amber-500/5 border-amber-500/20' : 'bg-neutral-950/60 border-neutral-800/60'">
                            <span class="w-2 h-2 rounded-full mt-1.5 shrink-0"
                                  :class="alert.level === 'critical' ? 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,.6)]' : alert.level === 'warning' ? 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,.6)]' : 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,.6)]'"></span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-white" x-text="alert.title"></p>
                                <p class="text-xs text-neutral-500 mt-0.5" x-text="alert.detail"></p>
                            </div>
                        </div>
                    </template>
                    <template x-if="alerts.length === 0">
                        <p class="text-sm text-neutral-500 text-center py-6">Nenhum alerta no momento.</p>
                    </template>
                </div>
            </div>

            {{-- Empresas recentes + auditoria recente --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-white">Empresas recentes</h2>
                        <a href="{{ route('superadmin.tenants') }}" class="text-xs text-amber-400 hover:text-amber-300 font-medium">Ver todas →</a>
                    </div>
                    <div class="space-y-2.5">
                        <template x-for="t in recentTenants" :key="t.id">
                            <a :href="'/superadmin/empresas/' + t.id + '/configuracoes'"
                               class="group flex items-center justify-between gap-3 p-3 rounded-xl bg-neutral-950/60 border border-neutral-800/60 transition-all duration-200 hover:border-amber-500/40 hover:bg-neutral-800/50">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-white truncate" x-text="t.name"></p>
                                    <p class="text-xs text-neutral-500" x-text="t.users_count + ' usuários · ' + t.orders_count + ' pedidos'"></p>
                                </div>
                                <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                      :class="t.plan === 'paid' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-neutral-800 text-neutral-400 border border-neutral-700'"
                                      x-text="t.plan"></span>
                            </a>
                        </template>
                        <template x-if="recentTenants.length === 0">
                            <p class="text-sm text-neutral-500 text-center py-6">Nenhuma empresa cadastrada.</p>
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
            recentTenants: [],
            recentAudit: [],
            system: {},
            connections: {},
            errors: {},
            resources: {},
            status: {},
            scheduler: {},
            alerts: [],
            generatedAt: '',
            init() {
                this.load();
                setInterval(() => this.load(), 60000);
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

                        this.stats = finData.stats || {};
                        this.recentTenants = finData.recent_tenants || [];
                        this.system = repData.system || {};
                        this.connections = repData.connections || {};
                        this.errors = repData.errors || {};
                        this.resources = repData.resources || {};
                        this.status = repData.status || {};
                        this.scheduler = repData.scheduler || {};
                        this.recentAudit = repData.recent_audit || [];
                        this.generatedAt = this.formatDate(repData.generated_at);
                        this.buildAlerts();

                        this.loading = false;
                    })
                    .catch(e => { this.error = e.message; this.loading = false; });
            },
            buildAlerts() {
                const alerts = [];
                const e = this.errors;
                const c = this.connections;
                const r = this.resources;
                const st = this.status;
                const sch = this.scheduler;
                const ints = c.integrations || {};

                if (c.database && c.database.ok === false) {
                    alerts.push({ level: 'critical', title: 'Banco de dados indisponível', detail: c.database.error || 'Falha de conexão com o banco' });
                }
                if (c.cache && c.cache.ok === false) {
                    alerts.push({ level: 'critical', title: 'Cache indisponível', detail: 'Driver de cache não respondeu' });
                }
                if (e.failed_jobs > 0) {
                    alerts.push({ level: 'critical', title: e.failed_jobs + ' job(s) com falha', detail: 'Há jobs falhando na fila — verifique o processamento' });
                }
                if (e.failed_webhooks_24h > 0) {
                    alerts.push({ level: 'critical', title: e.failed_webhooks_24h + ' webhook(s) rejeitados em 24h', detail: 'Assinatura inválida — possível falha de integração ou tentativa de acesso' });
                }
                if (r.disk_used_percent > 85) {
                    alerts.push({ level: 'critical', title: 'Disco quase cheio (' + r.disk_used_percent + '%)', detail: 'Libere espaço imediatamente' });
                } else if (r.disk_used_percent > 70) {
                    alerts.push({ level: 'warning', title: 'Uso de disco elevado (' + r.disk_used_percent + '%)', detail: 'Monitore o armazenamento da plataforma' });
                }
                if (e.recent_log_errors && e.recent_log_errors.length > 0) {
                    alerts.push({ level: 'warning', title: e.recent_log_errors.length + ' erro(s) recentes no log', detail: 'Ocorreram erros recentes no laravel.log' });
                }
                if (ints.efi_configured_tenants > 0 && ints.webhook_secret_tenants < ints.efi_configured_tenants) {
                    alerts.push({ level: 'warning', title: (ints.efi_configured_tenants - ints.webhook_secret_tenants) + ' tenant(s) com EFI sem segredo de webhook', detail: 'Configure o segredo para validar as assinaturas' });
                }
                const backups = st.totals ? st.totals.backups : null;
                if (backups !== null) {
                    if (backups === 0) {
                        alerts.push({ level: 'warning', title: 'Nenhum backup armazenado', detail: 'Ative a rotina de backups' });
                    } else {
                        alerts.push({ level: 'ok', title: backups + ' backup(s) armazenado(s)', detail: this.formatBytes(r.backups_size_bytes) + ' ocupados em disco' });
                    }
                }
                const purge = Object.values(sch).find(t => t.command === 'backups:purge');
                if (purge && purge.status !== 'ran') {
                    alerts.push({ level: 'warning', title: 'Limpeza de backups nunca executou', detail: 'A rotina backups:purge ainda não rodou' });
                }
                if (e.webhooks_24h > 0 && e.failed_webhooks_24h === 0) {
                    alerts.push({ level: 'ok', title: 'Webhooks saudáveis', detail: e.webhooks_24h + ' webhook(s) recebidos nas últimas 24h' });
                }

                this.alerts = alerts;
            },
            get criticalCount() {
                return this.alerts.filter(a => a.level === 'critical').length;
            },
            get warningCount() {
                return this.alerts.filter(a => a.level === 'warning').length;
            },
            formatCents(cents) {
                return 'R$ ' + (Number(cents || 0) / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            },
            formatBytes(bytes) {
                bytes = Number(bytes || 0);
                if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2).replace('.', ',') + ' GB';
                if (bytes >= 1048576) return (bytes / 1048576).toFixed(2).replace('.', ',') + ' MB';
                if (bytes >= 1024) return (bytes / 1024).toFixed(2).replace('.', ',') + ' KB';
                return bytes + ' B';
            },
            formatDate(iso) {
                if (!iso) return '—';
                return new Date(iso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            }
        };
    }
</script>
@endsection
