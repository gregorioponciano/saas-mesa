@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6" x-data="tenantSettings()">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('superadmin.tenants') }}" class="p-2 rounded-lg hover:bg-neutral-800 text-neutral-400 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-white">Configurações da Empresa</h1>
            </div>
            <p class="mt-1 text-sm text-neutral-400" x-text="'Gerenciando: ' + (form.name || '...')"></p>
        </div>
        <span class="px-3 py-1 text-xs font-medium rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 capitalize" x-text="form.plan_label || ''"></span>
    </div>

    <template x-if="loading">
        <div class="space-y-4">
            <div class="h-64 bg-neutral-900 border border-neutral-800 rounded-2xl animate-pulse"></div>
        </div>
    </template>

    <template x-if="error">
        <div class="rounded-2xl bg-red-500/10 border border-red-500/20 p-6 text-red-400 text-sm" x-text="error"></div>
    </template>

    <template x-if="!loading && !error">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- Dados do restaurante --}}
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <h2 class="text-lg font-bold text-white">Dados do Restaurante</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Nome</label>
                            <input type="text" x-model="form.name"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Email</label>
                            <input type="email" x-model="form.email"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">WhatsApp</label>
                            <input type="text" x-model="form.whatsapp"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-neutral-300 mb-2">Abre</label>
                                <input type="time" x-model="form.opening_time"
                                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-neutral-300 mb-2">Fecha</label>
                                <input type="time" x-model="form.closing_time"
                                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Delivery --}}
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6 0a2 2 0 11-4 0m4 0a2 2 0 104 0m-4 0h4M8 16H3a1 1 0 01-1-1v-3m18 4a2 2 0 11-4 0m4 0a2 2 0 10-4 0"/>
                                </svg>
                            </div>
                            <h2 class="text-lg font-bold text-white">Entrega (Delivery)</h2>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-neutral-300">
                            <input type="checkbox" x-model="form.delivery_cost_enabled" class="rounded bg-neutral-950 border-neutral-700 text-amber-500 focus:ring-amber-500">
                            Cobrar entrega
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Taxa fixa (R$)</label>
                            <input type="number" step="0.01" min="0" x-model="form.delivery_cost_per_order"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Taxa por km (R$)</label>
                            <input type="number" step="0.01" min="0" x-model="form.delivery_cost_per_km"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Endereço</label>
                            <input type="text" x-model="form.address"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Número</label>
                            <input type="text" x-model="form.number"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Bairro</label>
                            <input type="text" x-model="form.neighborhood"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Cidade</label>
                            <input type="text" x-model="form.city"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">UF</label>
                            <input type="text" maxlength="2" x-model="form.state"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">CEP</label>
                            <input type="text" maxlength="10" x-model="form.zipcode"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Raio de entrega (km)</label>
                            <input type="number" step="0.1" min="0" x-model="form.delivery_radius"
                                   class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button @click="save()" :disabled="saving"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all duration-200 hover:scale-[1.02] disabled:opacity-60">
                        <span x-show="!saving">Salvar alterações</span>
                        <span x-show="saving">Salvando...</span>
                    </button>
                    <p x-show="message" x-cloak class="text-sm text-emerald-400" x-text="message"></p>
                    <p x-show="saveError" x-cloak class="text-sm text-red-400" x-text="saveError"></p>
                </div>
            </div>

            {{-- Resumo lateral --}}
            <div class="space-y-6">
                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <h3 class="text-xs font-semibold text-neutral-400 uppercase tracking-wide mb-4">Empresa</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Slug</span>
                            <span class="text-white font-medium">/<span x-text="form.slug"></span></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Plano</span>
                            <span class="text-white font-medium capitalize" x-text="form.plan"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Status</span>
                            <span class="text-white font-medium capitalize" x-text="form.status"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Assinatura</span>
                            <span class="text-white font-medium capitalize" x-text="form.subscription_status || '—'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Cadastro</span>
                            <span class="text-white font-medium" x-text="formatDate(form.created_at)"></span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <h3 class="text-xs font-semibold text-neutral-400 uppercase tracking-wide mb-4">Funcionalidades</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-neutral-400">Cupons</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                  :class="form.coupons_enabled ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-neutral-800 text-neutral-500 border border-neutral-700'"
                                  x-text="form.coupons_enabled ? 'Ativo' : 'Inativo'"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-neutral-400">Programa de pontos</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                  :class="form.points_enabled ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-neutral-800 text-neutral-500 border border-neutral-700'"
                                  x-text="form.points_enabled ? 'Ativo' : 'Inativo'"></span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
                    <h3 class="text-xs font-semibold text-neutral-400 uppercase tracking-wide mb-4">Logo</h3>
                    <template x-if="form.logo_url">
                        <img :src="form.logo_url" class="w-24 h-24 object-contain rounded-xl bg-neutral-950 border border-neutral-800 p-2">
                    </template>
                    <p x-show="!form.logo_url" class="text-sm text-neutral-500">Nenhuma logo cadastrada.</p>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
    function tenantSettings() {
        return {
            loading: true,
            error: null,
            saving: false,
            message: null,
            saveError: null,
            form: {},
            init() {
                fetch('/api/superadmin/tenants/{{ $tenant->id }}/settings', { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : Promise.reject(new Error('Falha ao carregar configurações (' + r.status + ')')))
                    .then(data => { this.form = data.tenant; this.loading = false; })
                    .catch(e => { this.error = e.message; this.loading = false; });
            },
            async save() {
                this.saving = true;
                this.message = null;
                this.saveError = null;
                try {
                    const r = await fetch('/api/superadmin/tenants/{{ $tenant->id }}/settings', {
                        method: 'PUT',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(this.form)
                    });
                    if (!r.ok) {
                        const err = await r.json().catch(() => ({}));
                        const first = err.errors ? Object.values(err.errors)[0] : null;
                        throw new Error(first ? first[0] : 'Falha ao salvar (' + r.status + ')');
                    }
                    const data = await r.json();
                    this.form = data.tenant;
                    this.message = data.message;
                    setTimeout(() => { this.message = null; }, 4000);
                } catch (e) {
                    this.saveError = e.message;
                    setTimeout(() => { this.saveError = null; }, 5000);
                } finally {
                    this.saving = false;
                }
            },
            formatDate(date) {
                if (!date) return '—';
                return new Date(date).toLocaleDateString('pt-BR');
            }
        };
    }
</script>
@endsection
