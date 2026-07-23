<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tenant->name ?? 'SaasMesa' }} - Rastreio de Pedido</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        @keyframes bounce-in {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 0.7; }
            50% { transform: scale(1.1); opacity: 0.3; }
            100% { transform: scale(0.95); opacity: 0.7; }
        }
        @keyframes float-up {
            0% { transform: translateY(0) scale(1); opacity: 1; }
            100% { transform: translateY(-60px) scale(0.5); opacity: 0; }
        }
        @keyframes sparkle {
            0%, 100% { opacity: 0; transform: scale(0) rotate(0deg); }
            50% { opacity: 1; transform: scale(1) rotate(180deg); }
        }
        @keyframes confetti-fall {
            0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
        .animate-bounce-in { animation: bounce-in 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards; }
        .animate-pulse-ring { animation: pulse-ring 2s ease-in-out infinite; }
        .animate-float-up { animation: float-up 1.5s ease-out forwards; }
        .animate-sparkle { animation: sparkle 1.5s ease-in-out infinite; }
        .confetti-piece {
            position: fixed;
            width: 10px;
            height: 10px;
            border-radius: 2px;
            animation: confetti-fall 3s ease-in-out forwards;
        }
        .step-completed { @apply bg-emerald-500 text-white; }
        .step-current { @apply bg-violet-500 text-white ring-2 ring-violet-500/30; }
        .step-pending { @apply bg-neutral-800 text-neutral-500; }
        .line-completed { @apply bg-emerald-500/60; }
        .line-pending { @apply bg-neutral-800; }
    </style>
</head>
<body class="bg-neutral-950 text-white font-['Inter'] antialiased min-h-screen">

    <div
        x-data="{
            orderData: null,
            loading: true,
            pollingInterval: null,
            showConfetti: false,

            init() {
                this.fetchStatus();
                this.pollingInterval = setInterval(() => this.fetchStatus(), 10000);
            },
            destroy() {
                if (this.pollingInterval) clearInterval(this.pollingInterval);
            },

            fetchStatus() {
                fetch('/api/pedido/{{ $order->id }}/status')
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'entregue' && (!this.orderData || this.orderData.status !== 'entregue')) {
                            this.showConfetti = true;
                            setTimeout(() => this.showConfetti = false, 5000);
                        }
                        this.orderData = data;
                        this.loading = false;
                    })
                    .catch(() => {});
            },

            get timeline() {
                return this.orderData?.status_timeline || [];
            },

            get currentStatusLabel() {
                return this.orderData?.status_label || 'Carregando...';
            },

            get deliveryPerson() {
                return this.orderData?.delivery_person;
            },

            get items() {
                return this.orderData?.items || [];
            },

            maskPhone(v) {
                let r = (v||'').replace(/\D/g,'').substring(0,11);
                return r.length <= 2 ? (r.length ? '('+r : '') :
                       r.length <= 6 ? '('+r.substring(0,2)+') '+r.substring(2) :
                       r.length <= 7 ? '('+r.substring(0,2)+') '+r.substring(2,7) :
                       '('+r.substring(0,2)+') '+r.substring(2,7)+'-'+r.substring(7);
            },

            get address() {
                const a = this.orderData?.address;
                if (!a) return null;
                const parts = [];
                if (a.street) parts.push(a.street);
                if (a.number) parts.push(', ' + a.number);
                if (a.neighborhood) parts.push(' - ' + a.neighborhood);
                if (a.city) parts.push((a.neighborhood ? ', ' : '') + a.city);
                if (a.state) parts.push(' - ' + a.state);
                if (a.zipcode) parts.push(', ' + a.zipcode);
                if (a.complement) parts.push('<br><span class=\"text-neutral-500\">Complemento: ' + a.complement + '</span>');
                if (a.reference) parts.push('<br><span class=\"text-neutral-500\">Ref: ' + a.reference + '</span>');
                return parts.join('');
            }
        }"
        x-cloak
        class="max-w-lg mx-auto px-4 py-6 space-y-5"
    >

        {{-- Header --}}
        <div class="text-center space-y-1">
            <h1 class="text-lg font-bold text-white">{{ $tenant->name ?? 'Restaurante' }}</h1>
            <p class="text-xs text-neutral-500">Acompanhe seu pedido em tempo real</p>
        </div>

        {{-- Loading skeleton --}}
        <template x-if="loading">
            <div class="space-y-4 animate-pulse">
                <div class="h-20 bg-neutral-900 rounded-xl"></div>
                <div class="h-32 bg-neutral-900 rounded-xl"></div>
                <div class="h-24 bg-neutral-900 rounded-xl"></div>
            </div>
        </template>

        {{-- Celebration overlay --}}
        <template x-if="showConfetti">
            <div class="fixed inset-0 z-50 pointer-events-none overflow-hidden">
                <template x-for="i in 30" :key="i">
                    <div
                        class="confetti-piece"
                        :style="{
                            left: Math.random() * 100 + '%',
                            top: '-10px',
                            background: ['#8b5cf6','#10b981','#f59e0b','#ef4444','#3b82f6','#ec4899'][Math.floor(Math.random() * 6)],
                            width: (Math.random() * 8 + 4) + 'px',
                            height: (Math.random() * 8 + 4) + 'px',
                            animationDelay: (Math.random() * 2) + 's',
                            animationDuration: (Math.random() * 2 + 2) + 's',
                            borderRadius: Math.random() > 0.5 ? '50%' : '2px',
                        }"
                    ></div>
                </template>
            </div>
        </template>

        {{-- Order Status Card --}}
        <template x-if="!loading && orderData">
            <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-5 text-center">
                <template x-if="orderData.status !== 'entregue' && orderData.status !== 'cancelado'">
                    <div>
                        <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-violet-500/10 flex items-center justify-center">
                            <svg class="w-7 h-7 text-violet-400 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <p class="text-sm text-neutral-400 mb-1">Status atual</p>
                        <p class="text-2xl font-bold text-violet-400" x-text="currentStatusLabel"></p>
                        <p class="text-xs text-neutral-500 mt-2" x-text="'Pedido #{{ $order->id }}'"></p>
                    </div>
                </template>

                <template x-if="orderData.status === 'entregue'">
                    <div>
                        <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-emerald-500/20 flex items-center justify-center animate-bounce-in">
                            <svg class="w-8 h-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-emerald-400">Pedido entregue!</p>
                        <p class="text-sm text-neutral-400 mt-1">Seu pedido foi entregue com sucesso.</p>
                        <p class="text-xs text-neutral-500 mt-2">Obrigado pela preferência!</p>
                    </div>
                </template>

                <template x-if="orderData.status === 'cancelado'">
                    <div>
                        <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-red-500/10 flex items-center justify-center">
                            <svg class="w-7 h-7 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-red-400">Pedido cancelado</p>
                        <p class="text-sm text-neutral-400 mt-1">Este pedido foi cancelado.</p>
                    </div>
                </template>
            </div>
        </template>

        {{-- Status Timeline --}}
        <template x-if="!loading && timeline.length">
            <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-5">
                <h2 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-4">Acompanhamento</h2>
                <div class="space-y-0">
                    <template x-for="(step, i) in timeline" :key="step.status">
                        <div class="flex items-start gap-3">
                            {{-- Timeline node --}}
                            <div class="flex flex-col items-center">
                                <div
                                    class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition-all duration-500"
                                    :class="step.reached ? 'step-completed' : (step.current ? 'step-current' : 'step-pending')"
                                >
                                    <template x-if="step.reached">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </template>
                                    <template x-if="!step.reached && step.current">
                                        <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                                    </template>
                                    <template x-if="!step.reached && !step.current">
                                        <span x-text="i + 1"></span>
                                    </template>
                                </div>
                                <div
                                    class="w-0.5 h-8 mt-1 transition-colors duration-500"
                                    :class="i < timeline.length - 1 ? (timeline[i + 1].reached ? 'line-completed' : 'line-pending') : 'hidden'"
                                ></div>
                            </div>
                            {{-- Step content --}}
                            <div class="pb-6" :class="i === timeline.length - 1 ? 'pb-0' : ''">
                                <p
                                    class="text-sm font-semibold transition-colors duration-500"
                                    :class="step.reached ? 'text-emerald-400' : (step.current ? 'text-violet-400' : 'text-neutral-500')"
                                    x-text="step.label"
                                ></p>
                                <p
                                    class="text-xs mt-0.5 transition-colors duration-500"
                                    x-show="step.timestamp"
                                    x-text="step.timestamp ? new Date(step.timestamp).toLocaleString('pt-BR') : ''"
                                    :class="step.reached ? 'text-emerald-400/60' : 'text-neutral-600'"
                                ></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- Delivery Person Info --}}
        <template x-if="!loading && deliveryPerson">
            <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-5">
                <h2 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">Seu entregador</h2>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-violet-500/10 flex items-center justify-center shrink-0">
                        <span class="text-sm font-bold text-violet-400" x-text="deliveryPerson.name.charAt(0).toUpperCase()"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-white truncate" x-text="deliveryPerson.name"></p>
                        <a :href="'tel:' + deliveryPerson.phone" class="text-xs text-violet-400 hover:text-violet-300 flex items-center gap-1 mt-0.5">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span x-text="maskPhone(deliveryPerson.phone)"></span>
                        </a>
                    </div>
                </div>
                <div class="flex gap-3 mt-3 pt-3 border-t border-neutral-800/50" x-show="deliveryPerson.vehicle_plate || deliveryPerson.vehicle_model">
                    <div class="flex items-center gap-1.5 text-xs text-neutral-400" x-show="deliveryPerson.vehicle_model">
                        <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span x-text="deliveryPerson.vehicle_model"></span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-neutral-400" x-show="deliveryPerson.vehicle_plate">
                        <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="uppercase" x-text="deliveryPerson.vehicle_plate"></span>
                    </div>
                </div>
            </div>
        </template>

        {{-- Order Items --}}
        <template x-if="!loading && items.length">
            <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-5">
                <h2 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">Itens do pedido</h2>
                <div class="space-y-2">
                    <template x-for="(item, i) in items" :key="i">
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-xs text-neutral-500 shrink-0" x-text="item.quantity + 'x'"></span>
                                <span class="text-white truncate" x-text="item.product_name"></span>
                            </div>
                            <span class="text-neutral-300 shrink-0" x-text="'R$ ' + item.price.toFixed(2).replace('.', ',')"></span>
                        </div>
                    </template>
                </div>
                <div class="flex items-center justify-between pt-3 mt-3 border-t border-neutral-800/50">
                    <span class="text-sm font-semibold text-neutral-400">Total</span>
                    <span class="text-lg font-bold text-violet-400" x-text="'R$ ' + (orderData.total || 0).toFixed(2).replace('.', ',')"></span>
                </div>
            </div>
        </template>

        {{-- Address --}}
        <template x-if="!loading && address">
            <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-5">
                <h2 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">Endereço de entrega</h2>
                <div class="flex items-start gap-2 text-sm text-neutral-300">
                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <div x-html="address"></div>
                </div>
            </div>
        </template>

        {{-- Customer Info --}}
        <template x-if="!loading">
            <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-5">
                <h2 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">Cliente</h2>
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="text-white" x-text="orderData?.customer_name"></span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <a :href="'tel:' + (orderData?.customer_phone || '').replace(/\D/g,'')" class="text-violet-400 hover:text-violet-300" x-text="maskPhone(orderData?.customer_phone || '')"></a>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-neutral-500">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="orderData?.created_at_diff ? 'Pedido feito ' + orderData.created_at_diff : ''"></span>
                    </div>
                </div>
            </div>
        </template>

        {{-- Footer --}}
        <div class="text-center pb-4">
            <p class="text-[10px] text-neutral-600">
                Atualizando automaticamente a cada 10 segundos
            </p>
            <p class="text-[10px] text-neutral-700 mt-0.5">
                {{ $tenant->name ?? '' }} &mdash; {{ config('app.name') }}
            </p>
        </div>
    </div>

    @livewireScripts
</body>
</html>
