@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6" x-data="superadminPlans()">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Planos</h1>
            <p class="mt-1 text-sm text-neutral-400">Planos disponíveis na plataforma</p>
        </div>
        <button @click="openCreate()"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all duration-200">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Plano
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <template x-for="p in plans" :key="p.id">
            <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6 flex flex-col">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white" x-text="p.name"></h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                          :class="p.is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-neutral-800 text-neutral-500 border border-neutral-700'"
                          x-text="p.is_active ? 'Ativo' : 'Inativo'"></span>
                </div>
                <p class="mt-3 text-2xl font-bold text-amber-400" x-text="formatCents(p.price_cents)"></p>
                <p class="text-xs text-neutral-500" x-text="'por ' + p.interval"></p>

                <div class="mt-4 space-y-1.5 flex-1">
                    <template x-for="(value, key) in (p.features_json || {})" :key="key">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-neutral-400" x-text="key.replace(/_/g, ' ')"></span>
                            <span class="text-neutral-300" x-text="value === true ? 'Sim' : (value === false ? 'Não' : value)"></span>
                        </div>
                    </template>
                </div>

                <div class="mt-5 pt-4 border-t border-neutral-800 flex items-center gap-2">
                    <button @click="openEdit(p)"
                            class="flex-1 py-2 rounded-lg bg-neutral-800 hover:bg-neutral-700 text-neutral-200 text-xs font-semibold transition-all duration-200">
                        Editar
                    </button>
                    <button @click="remove(p)"
                            class="flex-1 py-2 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs font-semibold transition-all duration-200">
                        Excluir
                    </button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-neutral-900 border border-neutral-800 p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-white" x-text="editingId ? 'Editar Plano' : 'Novo Plano'"></h3>
                <button @click="showModal = false" class="text-neutral-500 hover:text-white">✕</button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Nome do plano</label>
                    <input x-model="form.name" type="text" placeholder="Ex.: Premium"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 text-white text-sm outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">Preço (centavos)</label>
                        <input x-model.number="form.price_cents" type="number" min="0" placeholder="9790"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 text-white text-sm outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-400 mb-1.5">Cobrança</label>
                        <select x-model="form.interval"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 text-white text-sm outline-none">
                            <option value="month">Mensal</option>
                            <option value="year">Anual</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1.5">Recursos (JSON, 1 por linha ou objeto)</label>
                    <textarea x-model="form.features_json" rows="7" placeholder='{"max_tables": 50, "max_products": 999}'
                              class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 text-white text-sm outline-none font-mono"></textarea>
                </div>
                <label class="flex items-center gap-2 text-sm text-neutral-300 cursor-pointer">
                    <input type="checkbox" x-model="form.is_active" class="accent-amber-500">
                    Plano ativo (disponível para contratação)
                </label>

                <div x-show="error" class="px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm" x-text="error"></div>

                <div class="flex items-center gap-3 pt-2">
                    <button @click="save()" x-show="!saving"
                            class="flex-1 py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-bold transition-all duration-200">
                        Salvar
                    </button>
                    <div x-show="saving" class="flex-1 py-3 rounded-xl bg-neutral-800 text-neutral-400 text-sm font-bold text-center">Salvando…</div>
                    <button @click="showModal = false"
                            class="px-4 py-3 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 text-sm font-semibold">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function superadminPlans() {
        return {
            plans: [],
            showModal: false,
            saving: false,
            editingId: null,
            error: '',
            form: { name: '', price_cents: 0, interval: 'month', features_json: '', is_active: true },
            init() {
                this.load();
            },
            async load() {
                const r = await fetch('/api/superadmin/plans', { headers: { 'Accept': 'application/json' } });
                if (r.ok) this.plans = await r.json();
            },
            openCreate() {
                this.editingId = null;
                this.error = '';
                this.form = { name: '', price_cents: 0, interval: 'month', features_json: '', is_active: true };
                this.showModal = true;
            },
            openEdit(p) {
                this.editingId = p.id;
                this.error = '';
                this.form = {
                    name: p.name,
                    price_cents: p.price_cents,
                    interval: p.interval,
                    features_json: JSON.stringify(p.features_json || {}, null, 2),
                    is_active: !!p.is_active
                };
                this.showModal = true;
            },
            async save() {
                this.saving = true;
                this.error = '';
                const payload = {
                    name: this.form.name,
                    price_cents: this.form.price_cents,
                    interval: this.form.interval,
                    features_json: this.form.features_json || null,
                    is_active: this.form.is_active
                };
                const method = this.editingId ? 'PUT' : 'POST';
                const url = this.editingId ? '/api/superadmin/plans/' + this.editingId : '/api/superadmin/plans';
                const r = await fetch(url, {
                    method,
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await r.json().catch(() => ({}));
                this.saving = false;
                if (!r.ok) {
                    this.error = (data.errors && Object.values(data.errors).flat().join(' • ')) || 'Falha ao salvar o plano.';
                    return;
                }
                this.showModal = false;
                this.load();
            },
            async remove(p) {
                if (!confirm('Excluir o plano "' + p.name + '"?')) return;
                const r = await fetch('/api/superadmin/plans/' + p.id, { method: 'DELETE', headers: { 'Accept': 'application/json' } });
                if (r.ok) this.load();
                else alert('Falha ao excluir o plano.');
            },
            formatCents(cents) {
                return 'R$ ' + Number(cents || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            }
        };
    }
</script>
@endsection
