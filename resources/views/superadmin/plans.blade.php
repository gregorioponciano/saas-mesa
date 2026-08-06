@extends('layouts.superadmin')

@section('content')
<div class="p-4 lg:p-8 space-y-6" x-data="superadminPlans()">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-white">Planos</h1>
            <p class="mt-1 text-sm text-neutral-400">Gerencie os planos disponíveis na plataforma</p>
        </div>
        <button @click="openCreate()"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 active:scale-95 text-neutral-950 text-sm font-bold shadow-lg shadow-amber-500/20 transition-all duration-200">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Plano
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        <template x-for="p in plans" :key="p.id">
            <div class="group relative rounded-2xl bg-neutral-900/70 border border-neutral-800 hover:bg-neutral-900 p-6 flex flex-col transition-all duration-300"
                 :style="cardStyle(p)">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <h3 class="text-lg font-bold text-white truncate" x-text="p.name"></h3>
                        <span x-show="p.badge" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-amber-500/15 text-amber-400 border border-amber-500/30 shrink-0" x-text="p.badge"></span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide shrink-0"
                          :class="p.is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-neutral-800 text-neutral-500 border border-neutral-700'"
                          x-text="p.is_active ? 'Ativo' : 'Inativo'"></span>
                </div>

                <div class="mt-5 flex items-baseline gap-2">
                    <span class="text-3xl font-black text-amber-400" x-text="formatCents(p.price_cents)"></span>
                    <span class="text-xs text-neutral-500" x-text="intervalLabel(p.interval)"></span>
                </div>

                <div class="mt-5 flex-1 space-y-4">
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="key in knownFeatures" :key="key">
                            <div class="rounded-xl bg-neutral-950/60 border border-neutral-800 px-2.5 py-2 text-center" x-show="featureValue(p, key) !== null">
                                <div class="text-sm font-bold text-white" x-text="featureValue(p, key)"></div>
                                <div class="text-[10px] text-neutral-500 leading-tight mt-0.5" x-text="shortLabel(key)"></div>
                            </div>
                        </template>
                    </div>

                    <div class="space-y-1.5">
                        <template x-for="(item, index) in (p.feature_items || [])" :key="index">
                            <div class="flex items-center gap-2.5 text-xs">
                                <template x-if="item.included">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <template x-if="!item.included">
                                    <svg class="w-4 h-4 text-neutral-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </template>
                                <span class="text-neutral-400" x-text="item.label"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-neutral-800 flex items-center gap-2">
                    <button @click="openEdit(p)"
                            class="flex-1 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-200 text-xs font-semibold transition-all duration-200 active:scale-95">
                        Editar
                    </button>
                    <button @click="remove(p)"
                            class="flex-1 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs font-semibold transition-all duration-200 active:scale-95">
                        Excluir
                    </button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="!plans.length" x-cloak class="text-center py-16">
        <div class="w-16 h-16 rounded-2xl bg-neutral-900 border border-neutral-800 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-neutral-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="text-neutral-400 font-medium">Nenhum plano cadastrado</p>
        <p class="text-neutral-600 text-sm mt-1">Clique em "Novo Plano" para começar.</p>
    </div>

    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative w-full max-w-xl rounded-2xl bg-neutral-900 border border-neutral-800 p-6 space-y-5 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white" x-text="editingId ? 'Editar Plano' : 'Novo Plano'"></h3>
                </div>
                <button @click="showModal = false" class="w-9 h-9 rounded-lg bg-neutral-800 hover:bg-neutral-700 text-neutral-400 hover:text-white flex items-center justify-center transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-neutral-400 mb-1.5">Nome do plano</label>
                    <input x-model="form.name" type="text" placeholder="Ex.: Premium"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 text-white text-sm outline-none transition-all duration-200">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-neutral-400 mb-1.5">Preço (R$)</label>
                        <input x-model.number="form.price_reais" type="number" step="0.01" min="0" placeholder="Ex.: 97,90"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 text-white text-sm outline-none transition-all duration-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-neutral-400 mb-1.5">Cobrança</label>
                        <select x-model="form.interval"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 text-white text-sm outline-none transition-all duration-200">
                            <option value="month">Mensal (todos)</option>
                            <option value="quarter">Trimestral (3 meses)</option>
                            <option value="semiannual">Semestral (6 meses)</option>
                            <option value="year">Anual (12 meses)</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-neutral-400 mb-1.5">Cor da borda</label>
                        <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 focus-within:border-amber-500 focus-within:ring-2 focus-within:ring-amber-500/20 transition-all duration-200">
                            <input type="color" x-model="form.border_color"
                                   class="w-8 h-8 rounded-lg bg-transparent border-0 cursor-pointer p-0">
                            <span class="text-sm text-neutral-500" x-text="form.border_color || 'Padrão'"></span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-neutral-400 mb-1.5">Cor do fundo</label>
                        <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 focus-within:border-amber-500 focus-within:ring-2 focus-within:ring-amber-500/20 transition-all duration-200">
                            <input type="color" x-model="form.background_color"
                                   class="w-8 h-8 rounded-lg bg-transparent border-0 cursor-pointer p-0">
                            <span class="text-sm text-neutral-500" x-text="form.background_color || 'Padrão'"></span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-neutral-400 mb-1.5">Selo / etiqueta (ex.: Popular)</label>
                    <input x-model="form.badge" type="text" maxlength="60" placeholder="Ex.: Popular, Mais vendido, Recomendado…"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 text-white text-sm outline-none transition-all duration-200">
                </div>

                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <label class="text-xs font-semibold text-neutral-300 uppercase tracking-wide">Limites do plano</label>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] text-neutral-500 mb-1">Mesas máximas</label>
                            <input x-model="form.features.max_tables" type="number" min="0" placeholder="Ex.: 50"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 text-white text-sm outline-none transition-all duration-200">
                        </div>
                        <div>
                            <label class="block text-[11px] text-neutral-500 mb-1">Produtos máximos</label>
                            <input x-model="form.features.max_products" type="number" min="0" placeholder="Ex.: 999"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 text-white text-sm outline-none transition-all duration-200">
                        </div>
                        <div>
                            <label class="block text-[11px] text-neutral-500 mb-1">Usuários máximos</label>
                            <input x-model="form.features.max_users" type="number" min="0" placeholder="Ex.: 20"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 text-white text-sm outline-none transition-all duration-200">
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <label class="text-xs font-semibold text-neutral-300 uppercase tracking-wide">Recursos do plano</label>
                        </div>
                        <button @click="addFeatureItem()" type="button"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 text-xs font-semibold transition-all duration-200">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            Adicionar
                        </button>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(item, index) in form.feature_items" :key="index">
                            <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-neutral-950 border border-neutral-800">
                                <button @click="moveFeatureItem(index, -1)" type="button" :disabled="index === 0"
                                        class="w-7 h-7 rounded-lg bg-neutral-800 hover:bg-neutral-700 text-neutral-400 flex items-center justify-center shrink-0 disabled:opacity-30 disabled:cursor-not-allowed transition-all duration-200">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                </button>
                                <button @click="moveFeatureItem(index, 1)" type="button" :disabled="index === form.feature_items.length - 1"
                                        class="w-7 h-7 rounded-lg bg-neutral-800 hover:bg-neutral-700 text-neutral-400 flex items-center justify-center shrink-0 disabled:opacity-30 disabled:cursor-not-allowed transition-all duration-200">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <input x-model="item.label" type="text" placeholder="Ex.: Mesas ilimitadas"
                                       class="flex-1 min-w-0 px-3 py-2 rounded-lg bg-neutral-900 border border-neutral-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 text-white text-sm outline-none transition-all duration-200">
                                <button @click="toggleFeatureItem(index)" type="button"
                                        class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 transition-all duration-200 font-bold text-xs"
                                        :class="item.included ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/10 text-red-400'">
                                    <svg x-show="item.included" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <svg x-show="!item.included" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                <button @click="removeFeatureItem(index)" type="button"
                                        class="w-7 h-7 rounded-lg bg-neutral-800 hover:bg-red-500/20 text-neutral-500 hover:text-red-400 flex items-center justify-center shrink-0 transition-all duration-200">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-between px-4 py-3 rounded-xl bg-neutral-950 border border-neutral-800">
                    <div>
                        <p class="text-sm font-medium text-neutral-300">Plano ativo</p>
                        <p class="text-xs text-neutral-500">Disponível para contratação</p>
                    </div>
                    <button @click="form.is_active = !form.is_active" type="button" aria-pressed="form.is_active"
                            class="relative inline-flex h-7 w-12 items-center rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500/50 transition-colors duration-300 ease-in-out cursor-pointer"
                            :style="`background-color: ${form.is_active ? '#16a34a' : '#3f3f46'}`">
                        <span x-show="form.is_active" class="animate-pulse absolute inset-0 rounded-full bg-green-400/30"></span>
                        <span class="relative inline-flex items-center justify-center h-5 w-5 rounded-full bg-white shadow-md transition-transform duration-300 ease-in-out"
                              :style="`transform: translateX(${form.is_active ? 26 : 2}px)`">
                            <svg x-show="form.is_active" class="w-3 h-3 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg x-show="!form.is_active" class="w-3 h-3 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </span>
                    </button>
                </div>

                <div x-show="error" class="px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm" x-text="error"></div>

                <div class="flex items-center gap-3 pt-2">
                    <button @click="save()" x-show="!saving"
                            class="flex-1 py-3 rounded-xl bg-amber-500 hover:bg-amber-400 active:scale-95 text-neutral-950 text-sm font-bold shadow-lg shadow-amber-500/20 transition-all duration-200">
                        Salvar
                    </button>
                    <div x-show="saving" class="flex-1 py-3 rounded-xl bg-neutral-800 text-neutral-400 text-sm font-bold text-center">
                        <svg class="w-4 h-4 animate-spin inline mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Salvando…
                    </div>
                    <button @click="showModal = false"
                            class="px-5 py-3 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 text-sm font-semibold transition-all duration-200">
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
            extraFeatures: {},
            knownFeatures: ['max_tables', 'max_products', 'max_users'],
            descriptionFeatures: @json(\App\Models\SaasPlan::DESCRIPTION_FEATURES),
            form: { name: '', price_reais: 0, interval: 'month', badge: '', features: {}, feature_items: [], border_color: '', background_color: '', is_active: true },
            init() {
                this.load();
            },
            async load() {
                const r = await fetch('/api/superadmin/plans', { headers: { 'Accept': 'application/json' } });
                if (r.ok) this.plans = await r.json();
            },
            featureValue(p, key) {
                return (p.features_json || {})[key] ?? null;
            },
            cardStyle(p) {
                const style = {};
                if (p.border_color) style.borderColor = p.border_color;
                if (p.background_color) style.backgroundColor = p.background_color;
                if (p.slug === 'premium') {
                    if (!p.border_color) style.borderColor = '#f59e0b';
                    if (!p.background_color) style.backgroundColor = 'rgba(245, 158, 11, 0.08)';
                }
                return style;
            },
            shortLabel(key) {
                return key === 'max_tables' ? 'Mesas' : (key === 'max_products' ? 'Produtos' : 'Usuários');
            },
            emptyFeatures() {
                const numeric = Object.fromEntries(this.knownFeatures.map(k => [k, '']));
                return { ...numeric };
            },
            defaultFeatureItems() {
                return Object.keys(this.descriptionFeatures).map(key => {
                    return { label: this.descriptionFeatures[key], included: true };
                });
            },
            openCreate() {
                this.editingId = null;
                this.error = '';
                this.extraFeatures = {};
                this.form = { name: '', price_reais: 0, interval: 'month', badge: '', features: this.emptyFeatures(), feature_items: this.defaultFeatureItems(), border_color: '', background_color: '', is_active: true };
                this.showModal = true;
            },
            openEdit(p) {
                this.editingId = p.id;
                this.error = '';
                const features = p.features_json || {};
                const formFeatures = {};
                for (const key of this.knownFeatures) {
                    formFeatures[key] = features[key] ?? '';
                }
                this.extraFeatures = Object.fromEntries(
                    Object.entries(features).filter(([key]) => !this.knownFeatures.includes(key))
                );
                const featureItems = (p.feature_items && Array.isArray(p.feature_items) && p.feature_items.length)
                    ? p.feature_items.map(i => ({ label: i.label || 'Recurso', included: !!i.included }))
                    : this.defaultFeatureItems();
                this.form = {
                    name: p.name,
                    price_reais: (p.price_cents || 0) / 100,
                    interval: p.interval,
                    badge: p.badge || '',
                    features: formFeatures,
                    feature_items: featureItems,
                    border_color: p.border_color || '',
                    background_color: p.background_color || '',
                    is_active: !!p.is_active
                };
                this.showModal = true;
            },
            addFeatureItem() {
                this.form.feature_items.push({ label: '', included: true });
            },
            removeFeatureItem(index) {
                this.form.feature_items.splice(index, 1);
            },
            toggleFeatureItem(index) {
                this.form.feature_items[index].included = !this.form.feature_items[index].included;
            },
            moveFeatureItem(index, dir) {
                const target = index + dir;
                if (target < 0 || target >= this.form.feature_items.length) return;
                const arr = this.form.feature_items;
                [arr[index], arr[target]] = [arr[target], arr[index]];
            },
            buildFeatures() {
                const features = { ...this.extraFeatures };
                for (const key of this.knownFeatures) {
                    const raw = this.form.features[key];
                    features[key] = raw === '' || raw === null || raw === undefined ? null : Number(raw);
                }
                return features;
            },
            async save() {
                this.saving = true;
                this.error = '';
                const payload = {
                    name: this.form.name,
                    price_cents: Math.round((Number(this.form.price_reais) || 0) * 100),
                    interval: this.form.interval,
                    badge: this.form.badge || null,
                    features_json: this.buildFeatures(),
                    feature_items: this.form.feature_items
                        .filter(i => (i.label || '').trim() !== '')
                        .map(i => ({ label: i.label.trim(), included: !!i.included })),
                    border_color: this.form.border_color || null,
                    background_color: this.form.background_color || null,
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
                if (!await saasConfirm('Excluir o plano "' + p.name + '"?', { type: 'danger', title: 'Excluir plano', confirmLabel: 'Excluir' })) return;
                const r = await fetch('/api/superadmin/plans/' + p.id, { method: 'DELETE', headers: { 'Accept': 'application/json' } });
                if (r.ok) this.load();
                else saasAlert('Falha ao excluir o plano.', { title: 'Erro' });
            },
            formatCents(cents) {
                return 'R$ ' + (Number(cents || 0) / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            },
            intervalLabel(interval) {
                return interval === 'month' ? 'por mês'
                    : (interval === 'quarter' ? 'a cada 3 meses'
                    : (interval === 'semiannual' ? 'a cada 6 meses'
                    : (interval === 'year' ? 'por ano' : 'por ' + interval)));
            },
            featureLabels: @json(\App\Models\SaasPlan::FEATURE_LABELS),
            featureLabel(key) {
                if (this.featureLabels[key]) return this.featureLabels[key];
                return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            }
        };
    }
</script>
@endsection
