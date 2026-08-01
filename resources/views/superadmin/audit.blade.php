@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6" x-data="superadminAudit()">
    <div>
        <h1 class="text-2xl font-bold text-white">Auditoria</h1>
        <p class="mt-1 text-sm text-neutral-400">Registro imutável das ações administrativas na plataforma</p>
    </div>

    <div class="flex flex-wrap gap-3 items-center">
        <select x-model="filters.action" @change="load()"
                class="px-3.5 py-2.5 rounded-xl bg-neutral-900 border border-neutral-800 text-white text-sm outline-none">
            <option value="">Todas as ações</option>
            <template x-for="a in actions" :key="a">
                <option :value="a" x-text="a"></option>
            </template>
        </select>
        <div>
            <label class="block text-[10px] text-neutral-500 mb-1">De</label>
            <input x-model="filters.date_from" @change="load()" type="date"
                   class="px-3.5 py-2 rounded-xl bg-neutral-900 border border-neutral-800 text-white text-sm outline-none">
        </div>
        <div>
            <label class="block text-[10px] text-neutral-500 mb-1">Até</label>
            <input x-model="filters.date_to" @change="load()" type="date"
                   class="px-3.5 py-2 rounded-xl bg-neutral-900 border border-neutral-800 text-white text-sm outline-none">
        </div>
    </div>

    <div class="rounded-2xl bg-neutral-900 border border-neutral-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-neutral-500 uppercase tracking-wider border-b border-neutral-800">
                    <th class="px-4 py-3">Quando</th>
                    <th class="px-4 py-3">Administrador</th>
                    <th class="px-4 py-3">Ação</th>
                    <th class="px-4 py-3">Descrição</th>
                    <th class="px-4 py-3">IP</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="l in logs.data" :key="l.id">
                    <tr class="border-b border-neutral-800/60 hover:bg-neutral-800/30 align-top">
                        <td class="px-4 py-3 text-neutral-400 whitespace-nowrap" x-text="formatDateTime(l.created_at)"></td>
                        <td class="px-4 py-3">
                            <p class="text-white font-medium" x-text="l.admin_name"></p>
                            <p class="text-xs text-neutral-500" x-text="l.admin_email || ''"></p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-lg bg-neutral-800 text-amber-300 text-xs font-mono" x-text="l.action"></span>
                        </td>
                        <td class="px-4 py-3 text-neutral-300" x-text="l.description || '—'"></td>
                        <td class="px-4 py-3 text-neutral-500 font-mono text-xs" x-text="l.ip || '—'"></td>
                    </tr>
                </template>
            </tbody>
        </table>
        <div x-show="!logs.data || !logs.data.length" class="p-10 text-center text-neutral-500 text-sm">Nenhum registro de auditoria encontrado.</div>
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
    function superadminAudit() {
        return {
            logs: { data: [] },
            actions: [],
            filters: { action: '', date_from: '', date_to: '' },
            init() { this.load(); },
            async load(page = 1) {
                const params = new URLSearchParams({ page });
                if (this.filters.action) params.set('action', this.filters.action);
                if (this.filters.date_from) params.set('date_from', this.filters.date_from);
                if (this.filters.date_to) params.set('date_to', this.filters.date_to);
                const r = await fetch('/api/superadmin/audit-logs?' + params, { headers: { 'Accept': 'application/json' } });
                if (r.ok) {
                    const data = await r.json();
                    this.logs = data.logs;
                    this.actions = data.actions;
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
