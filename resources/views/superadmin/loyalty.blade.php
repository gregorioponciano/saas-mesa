@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6" x-data="superadminLoyalty()">
    <div>
        <h1 class="text-2xl font-bold text-white">Programa de Pontos (Loyalty)</h1>
        <p class="mt-1 text-sm text-neutral-400">Ativação do programa de pontos por empresa</p>
    </div>

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
                            <td class="p-4 text-right">
                                <button @click="toggle(t)"
                                        class="relative inline-flex items-center h-6 w-11 rounded-full transition-colors duration-200"
                                        :class="t.points_enabled ? 'bg-amber-500' : 'bg-neutral-800'">
                                    <span class="inline-block w-4 h-4 transform rounded-full bg-white transition-transform duration-200"
                                          :class="t.points_enabled ? 'translate-x-6' : 'translate-x-1'"></span>
                                </button>
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
            init() {
                fetch('/api/superadmin/loyalty', { headers: { 'Accept': 'application/json' } })
                    .then(r => { if (!r.ok) throw new Error('Falha ao carregar loyalty (' + r.status + ')'); return r.json(); })
                    .then(data => { this.tenants = data; })
                    .catch(() => { this.tenants = []; });
            },
            async toggle(t) {
                const r = await fetch('/api/superadmin/loyalty/' + t.id + '/toggle', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                if (r.ok) {
                    const data = await r.json();
                    if (data.points_enabled !== undefined) t.points_enabled = data.points_enabled;
                }
            }
        };
    }
</script>
@endsection
