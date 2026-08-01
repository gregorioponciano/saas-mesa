@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6" x-data="superadminWebhooks()">
    <div>
        <h1 class="text-2xl font-bold text-white">Webhooks EFI</h1>
        <p class="mt-1 text-sm text-neutral-400">Log de chamadas recebidas da API de pagamentos</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-4">
            <p class="text-xs text-neutral-500">Total recebido</p>
            <p class="mt-1 text-2xl font-bold text-white" x-text="stats.total || 0"></p>
        </div>
        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-4">
            <p class="text-xs text-neutral-500">Últimas 24h</p>
            <p class="mt-1 text-2xl font-bold text-white" x-text="stats.last_24h || 0"></p>
        </div>
        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-4">
            <p class="text-xs text-neutral-500">Assinatura inválida</p>
            <p class="mt-1 text-2xl font-bold text-red-400" x-text="stats.invalid || 0"></p>
        </div>
        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-4">
            <p class="text-xs text-neutral-500">Com erro de processamento</p>
            <p class="mt-1 text-2xl font-bold text-amber-400" x-text="stats.errors || 0"></p>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 items-center">
        <select x-model="filters.source" @change="load()"
                class="px-3.5 py-2.5 rounded-xl bg-neutral-900 border border-neutral-800 text-white text-sm outline-none">
            <option value="">Todas as origens</option>
            <option value="saas">saas</option>
            <option value="tenant">tenant</option>
        </select>
        <select x-model="filters.valid" @change="load()"
                class="px-3.5 py-2.5 rounded-xl bg-neutral-900 border border-neutral-800 text-white text-sm outline-none">
            <option value="">Assinatura: todas</option>
            <option value="1">Válida</option>
            <option value="0">Inválida</option>
        </select>
        <select x-model="filters.processed" @change="load()"
                class="px-3.5 py-2.5 rounded-xl bg-neutral-900 border border-neutral-800 text-white text-sm outline-none">
            <option value="">Processamento: todos</option>
            <option value="1">Processado</option>
            <option value="0">Não processado</option>
        </select>
        <label class="flex items-center gap-2 text-sm text-neutral-400 cursor-pointer">
            <input type="checkbox" x-model="filters.has_error" @change="load()" class="accent-amber-500">
            Somente com erro
        </label>
    </div>

    <div class="rounded-2xl bg-neutral-900 border border-neutral-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-neutral-500 uppercase tracking-wider border-b border-neutral-800">
                    <th class="px-4 py-3">Quando</th>
                    <th class="px-4 py-3">Origem</th>
                    <th class="px-4 py-3">Empresa</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Erro</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="l in logs.data" :key="l.id">
                    <tr class="border-b border-neutral-800/60 hover:bg-neutral-800/30">
                        <td class="px-4 py-3 text-neutral-400 whitespace-nowrap" x-text="formatDateTime(l.created_at)"></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-lg bg-neutral-800 text-neutral-300 text-xs font-mono" x-text="l.source"></span>
                        </td>
                        <td class="px-4 py-3 text-neutral-300" x-text="l.tenant_name || '—'"></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                  :class="l.processed ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-neutral-800 text-neutral-500 border border-neutral-700'"
                                  x-text="l.processed ? 'Processado' : 'Pendente'"></span>
                            <span x-show="!l.is_valid" class="ml-1 px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide bg-red-500/10 text-red-400 border border-red-500/20">Inválida</span>
                        </td>
                        <td class="px-4 py-3 text-red-400 text-xs max-w-[220px] truncate" x-text="l.error_message || '—'"></td>
                        <td class="px-4 py-3">
                            <button @click="selected = selected === l.id ? null : l.id" class="text-amber-400 text-xs font-semibold hover:text-amber-300">
                                <span x-text="selected === l.id ? 'Fechar' : 'Ver payload'"></span>
                            </button>
                        </td>
                    </tr>
                    <tr x-show="selected === l.id" class="bg-neutral-950/60">
                        <td colspan="6" class="px-4 py-4">
                            <pre class="text-[11px] font-mono text-emerald-300 whitespace-pre-wrap break-words max-h-96 overflow-y-auto" x-text="l.payload_preview || 'Sem payload'"></pre>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        <div x-show="!logs.data || !logs.data.length" class="p-10 text-center text-neutral-500 text-sm">Nenhum webhook registrado.</div>
    </div>

    <div class="flex items-center justify-between text-sm text-neutral-400">
        <span x-text="'Página ' + (logs.current_page || 1) + ' de ' + (logs.last_page || 1)"></span>
        <div class="flex gap-2">
            <button @click="goPage(logs.current_page - 1)" :disabled="!logs.prev_page_url" class="px-4 py-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 text-sm font-semibold disabled:opacity-40">Anterior</button>
            <button @click="goPage(logs.current_page + 1)" :disabled="!logs.next_page_url" class="px-4 py-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 text-sm font-semibold disabled:opacity-40">Próxima</button>
        </div>
    </div>
</div>

<script>
    function superadminWebhooks() {
        return {
            logs: { data: [] },
            stats: {},
            selected: null,
            filters: { source: '', valid: '', processed: '', has_error: false },
            init() { this.load(); },
            async load(page = 1) {
                const params = new URLSearchParams({ page });
                if (this.filters.source) params.set('source', this.filters.source);
                if (this.filters.valid !== '') params.set('valid', this.filters.valid);
                if (this.filters.processed !== '') params.set('processed', this.filters.processed);
                if (this.filters.has_error) params.set('has_error', '1');
                const r = await fetch('/api/superadmin/webhook-logs?' + params, { headers: { 'Accept': 'application/json' } });
                if (r.ok) {
                    const data = await r.json();
                    this.logs = data.logs;
                    this.stats = data.stats;
                }
            },
            goPage(p) {
                if (p < 1 || (this.logs.last_page && p > this.logs.last_page)) return;
                this.load(p);
            },
            formatDateTime(d) {
                return d ? new Date(d).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : '—';
            }
        };
    }
</script>
@endsection
