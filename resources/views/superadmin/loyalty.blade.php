@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6" x-data="superadminLoyalty()">
    <div>
        <h1 class="text-2xl font-bold text-white">Programa de Pontos (Loyalty)</h1>
        <p class="mt-1 text-sm text-neutral-400">Ativação do programa de pontos por empresa</p>
    </div>

    <template x-if="message">
        <div class="rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-4 text-emerald-400 text-sm" x-text="message"></div>
    </template>
    <template x-if="error">
        <div class="rounded-2xl bg-red-500/10 border border-red-500/20 p-4 text-red-400 text-sm" x-text="error"></div>
    </template>

    <div class="rounded-2xl bg-neutral-900 border border-neutral-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-neutral-500 uppercase tracking-wide border-b border-neutral-800">
                        <th class="p-4 font-medium">Empresa</th>
                        <th class="p-4 font-medium">Plano</th>
                        <th class="p-4 font-medium">Status</th>
                        <th class="p-4 text-right font-medium">Pontos habilitados</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="t in tenants" :key="t.id">
                        <tr class="border-b border-neutral-800/60 last:border-0">
                            <td class="p-4">
                                <p class="text-white font-medium" x-text="t.name"></p>
                                <p class="text-xs text-neutral-500" x-text="'/' + t.slug"></p>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                      :class="t.plan === 'paid' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-neutral-800 text-neutral-400 border border-neutral-700'"
                                      x-text="t.plan_label"></span>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                      :class="t.status === 'suspended' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'"
                                      x-text="t.status"></span>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center justify-end">
                                    <button @click="toggle(t)" :disabled="t.toggling"
                                            class="relative inline-flex items-center h-7 w-12 rounded-full cursor-pointer transition-all duration-300 shrink-0 disabled:opacity-60"
                                            :class="t.points_enabled ? 'bg-emerald-500 shadow-[0_0_12px_rgba(52,211,153,.45)]' : 'bg-neutral-800 border border-neutral-700'"
                                            :aria-pressed="t.points_enabled">
                                        <span class="inline-block w-5 h-5 rounded-full bg-white shadow-md transition-transform duration-300"
                                              :style="switchStyle(t)"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="tenants.length === 0">
                        <td colspan="4" class="p-10 text-center text-neutral-500">Nenhuma empresa encontrada.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function superadminLoyalty() {
        return {
            tenants: [],
            error: '',
            message: '',
            init() {
                fetch('/api/superadmin/loyalty', { headers: { 'Accept': 'application/json' } })
                    .then(r => { if (!r.ok) throw new Error('Falha ao carregar loyalty (' + r.status + ')'); return r.json(); })
                    .then(data => {
                        this.tenants = data.map(t => ({ ...t, toggling: false }));
                    })
                    .catch(() => { this.tenants = []; });
            },
            switchStyle(t) {
                return {
                    transform: t.points_enabled ? 'translateX(1.5rem)' : 'translateX(0.25rem)',
                    transitionTimingFunction: 'cubic-bezier(.68, -0.55, .27, 1.55)',
                };
            },
            async toggle(t) {
                this.error = '';
                this.message = '';
                t.toggling = true;
                try {
                    const r = await fetch('/api/superadmin/loyalty/' + t.id + '/toggle', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    const data = await r.json().catch(() => ({}));
                    if (r.ok && data.points_enabled !== undefined) {
                        t.points_enabled = data.points_enabled;
                        this.message = data.message || (t.points_enabled ? 'Pontos habilitados' : 'Pontos desabilitados');
                    } else {
                        this.error = data.error || 'Falha ao alternar os pontos.';
                    }
                } catch {
                    this.error = 'Falha de conexão ao alternar os pontos.';
                } finally {
                    t.toggling = false;
                }
            }
        };
    }
</script>
@endsection
