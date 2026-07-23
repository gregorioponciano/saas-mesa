@extends('layouts.delivery', ['title' => 'Painel - Entregador'])

@section('content')
@php
    $activeTab = request('tab', 'disponiveis');
    $isAvailable = $delivery->status === 'active';
@endphp
<div class="p-4 lg:p-6 max-w-6xl mx-auto space-y-5"
     x-data="{
         activeTab: '{{ $activeTab }}',
         photoOrderId: null,
         photoData: null,
         showPhotoModal: false,
         settingsName: '{{ $delivery->name }}',
         settingsEmail: '{{ $delivery->email }}',
         settingsPhone: '{{ $delivery->phone }}',
         settingsPlate: '{{ $delivery->vehicle_plate ?? '' }}',
         settingsModel: '{{ $delivery->vehicle_model ?? '' }}',
         settingsPassword: '',
         settingsPasswordConfirm: '',
         settingsSaving: false,
         settingsMessage: '',
         settingsMessageType: '',

         notifUnreadCount: {{ $unreadCount ?? 0 }},
         notifList: [],
         notifShowDropdown: false,
         prevAvailable: {{ count($availableOrders) }},
         prevActive: {{ $todayStats['active'] }},
         hasNewOrder: false,
         hasNewDelivery: false,
         pollInterval: null,

         init() {
             if (window.location.search.includes('tab=')) {
                 const params = new URLSearchParams(window.location.search);
                 this.activeTab = params.get('tab');
             }
             this.pollInterval = setInterval(() => this.pollNotifications(), 12000);
         },
         destroy() {
             if (this.pollInterval) clearInterval(this.pollInterval);
         },

         pollNotifications() {
             fetch('{{ route("delivery.notifications.json") }}')
                 .then(r => r.json())
                 .then(d => {
                     const currentAvailable = d.available_orders?.length || 0;
                     const currentActive = d.todays_orders?.length || 0;
                     if (currentAvailable > this.prevAvailable) {
                         this.hasNewOrder = true;
                         this.playNotificationSound();
                     }
                     if (currentActive > this.prevActive) {
                         this.playNotificationSound();
                     }
                     this.prevAvailable = currentAvailable;
                     this.prevActive = currentActive;
                     this.notifUnreadCount = d.unread_count;
                     this.notifList = d.notifications || [];
                 })
                 .catch(() => {});
         },

         markNotifRead(id) {
             fetch('/entregador/notificacoes/' + id + '/ler', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
                 .then(r => r.json())
                 .then(d => { if (d.unread_count !== undefined) this.notifUnreadCount = d.unread_count; })
                 .catch(() => {});
         },

         playNotificationSound() {
             try {
                 const ctx = new (window.AudioContext || window.webkitAudioContext)();
                 const osc = ctx.createOscillator();
                 const gain = ctx.createGain();
                 osc.connect(gain);
                 gain.connect(ctx.destination);
                 osc.frequency.value = 880;
                 osc.type = 'sine';
                 gain.gain.setValueAtTime(0.3, ctx.currentTime);
                 gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
                 osc.start(ctx.currentTime);
                 osc.stop(ctx.currentTime + 0.4);
             } catch(e) {}
         },

         setTab(tab) {
             this.activeTab = tab;
             const url = new URL(window.location);
             url.searchParams.set('tab', tab);
             window.history.pushState({}, '', url);
             if (tab === 'disponiveis') this.hasNewOrder = false;
             if (tab === 'ativos') this.hasNewDelivery = false;
         },
         saveSettings() {
             this.settingsSaving = true;
             this.settingsMessage = '';
             const form = new FormData();
             form.append('name', this.settingsName);
             form.append('phone', this.settingsPhone.replace(/\D/g, ''));
             form.append('vehicle_plate', this.settingsPlate);
             form.append('vehicle_model', this.settingsModel);
             if (this.settingsPassword) {
                 form.append('password', this.settingsPassword);
                 form.append('password_confirmation', this.settingsPasswordConfirm);
             }
             form.append('_token', '{{ csrf_token() }}');
             fetch('{{ route("delivery.settings.update") }}', { method: 'POST', body: form })
                 .then(r => r.json())
                 .then(d => {
                     this.settingsMessage = d.message;
                     this.settingsMessageType = d.success ? 'success' : 'error';
                     if (d.success) { this.settingsPassword = ''; this.settingsPasswordConfirm = ''; }
                 })
                 .catch(() => { this.settingsMessage = 'Erro ao salvar.'; this.settingsMessageType = 'error'; })
                 .finally(() => { this.settingsSaving = false; });
         },
         startPhotoCapture(orderId) {
             this.photoOrderId = orderId;
             this.photoData = null;
             this.showPhotoModal = true;
             setTimeout(() => {
                 const video = document.getElementById('delivery-preview');
                 if (!video) return;
                 navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}})
                     .then(stream => { video.srcObject = stream; video.play(); })
                     .catch(() => { this.photoData = null; this.showPhotoModal = false; });
             }, 300);
         },
         capturePhoto() {
             const video = document.getElementById('delivery-preview');
             const canvas = document.getElementById('delivery-canvas');
             if (!video || !canvas) return;
             canvas.width = video.videoWidth || 640;
             canvas.height = video.videoHeight || 480;
             canvas.getContext('2d').drawImage(video, 0, 0);
             this.photoData = canvas.toDataURL('image/jpeg', 0.7);
             video.srcObject?.getTracks().forEach(t => t.stop());
         },
         retakePhoto() {
             this.photoData = null;
             setTimeout(() => {
                 const video = document.getElementById('delivery-preview');
                 if (!video) return;
                 navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}})
                     .then(stream => { video.srcObject = stream; video.play(); })
                     .catch(() => {});
             }, 300);
         },
         closeCamera() {
             const video = document.getElementById('delivery-preview');
             if (video?.srcObject) video.srcObject.getTracks().forEach(t => t.stop());
             this.showPhotoModal = false;
             this.photoOrderId = null;
             this.photoData = null;
         },
         submitDelivery(orderId) {
             const form = document.getElementById('delivery-form-' + orderId);
             if (!form) return;
             if (this.photoData) {
                 const input = document.createElement('input');
                 input.type = 'hidden';
                 input.name = 'photo_data';
                 input.value = this.photoData;
                 form.appendChild(input);
             }
             this.closeCamera();
             form.submit();
         },
         phoneMask(v) {
             let r = (v||'').replace(/\D/g,'').substring(0,11);
             return r.length <= 2 ? (r.length ? '('+r : '') :
                    r.length <= 6 ? '('+r.substring(0,2)+') '+r.substring(2) :
                    r.length <= 7 ? '('+r.substring(0,2)+') '+r.substring(2,7) :
                    '('+r.substring(0,2)+') '+r.substring(2,7)+'-'+r.substring(7);
         }
      }">

    {{-- =================================================================== --}}
    {{-- TOP STATS BAR --}}
    {{-- =================================================================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[11px] text-neutral-500 uppercase tracking-wider font-medium">Ganhos Hoje</p>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-emerald-400">R$ {{ number_format($todayStats['earnings'], 2, ',', '.') }}</p>
            <p class="text-[10px] text-neutral-600 mt-0.5">Total bruto do dia</p>
        </div>

        <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[11px] text-neutral-500 uppercase tracking-wider font-medium">Entregas Hoje</p>
                <div class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-violet-400">{{ $todayStats['completed'] }}</p>
            <p class="text-[10px] text-neutral-600 mt-0.5">
                {{ $todayStats['active'] }} ativas
                <span class="text-neutral-700">&#183;</span>
                {{ $todayStats['pending_pickup'] }} pendentes
            </p>
        </div>

        <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[11px] text-neutral-500 uppercase tracking-wider font-medium">Tempo Medio</p>
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-amber-400">{{ $todayStats['avg_time_today'] }} <span class="text-sm font-normal text-neutral-500">min</span></p>
            <p class="text-[10px] text-neutral-600 mt-0.5">{{ $profile['total_deliveries'] }} entregas no total</p>
        </div>

        <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[11px] text-neutral-500 uppercase tracking-wider font-medium">Disponivel</p>
                <form method="POST" action="{{ route('delivery.toggle.availability') }}" id="toggle-availability-form">
                    @csrf
                    <button type="submit"
                            class="relative w-10 h-6 rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
                            :class="$el.closest('form').querySelector('button')">
                        <span class="absolute inset-0 rounded-full {{ $isAvailable ? 'bg-emerald-500' : 'bg-neutral-700' }} transition-colors duration-300"></span>
                        <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow-md transition-transform duration-300 {{ $isAvailable ? 'translate-x-4' : 'translate-x-0' }}"></span>
                    </button>
                </form>
            </div>
            <p class="text-lg font-bold {{ $isAvailable ? 'text-emerald-400' : 'text-neutral-500' }}">{{ $isAvailable ? 'Online' : 'Offline' }}</p>
            <p class="text-[10px] text-neutral-600 mt-0.5">{{ $isAvailable ? 'Recebendo pedidos' : 'Pedidos pausados' }}</p>
        </div>
    </div>

    {{-- =================================================================== --}}
    {{-- TAB NAVIGATION --}}
    {{-- =================================================================== --}}
    <div class="flex gap-1 overflow-x-auto pb-1 scrollbar-thin">
        <template x-for="(tab, key) in {
            disponiveis: 'Disponiveis',
            ativos: 'Ativos',
            historico: 'Historico',
            ganhos: 'Ganhos',
            perfil: 'Perfil',
            configuracoes: 'Configuracoes'
        }">
            <button @click="setTab(key)"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-200"
                    :class="activeTab === key ? 'bg-violet-500/20 text-violet-300 border border-violet-500/30 shadow-sm' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50'">
                <span x-text="tab"></span>
                <span x-show="key === 'disponiveis' && hasNewOrder"
                      class="w-1.5 h-1.5 rounded-full bg-violet-400 animate-pulse"></span>
            </button>
        </template>
    </div>

    {{-- Notification banner for new orders --}}
    <div x-show="hasNewOrder && activeTab !== 'disponiveis'" x-transition:enter.duration.200ms
         class="bg-violet-500/10 border border-violet-500/20 rounded-xl p-3 flex items-center justify-between">
        <div class="flex items-center gap-2 text-sm text-violet-400">
            <svg class="w-5 h-5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span class="font-semibold">Novos pedidos disponiveis!</span>
        </div>
        <button @click="setTab('disponiveis'); hasNewOrder = false"
                class="text-xs px-3 py-1.5 rounded-lg bg-violet-500/20 text-violet-300 hover:bg-violet-500/30 font-semibold transition-colors">
            Ver Pedidos
        </button>
    </div>

    {{-- =================================================================== --}}
    {{-- TAB: DISPONIVEIS --}}
    {{-- =================================================================== --}}
    <div x-show="activeTab === 'disponiveis'" x-transition:enter.duration.200ms.opacity>
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-bold text-sm text-white flex items-center gap-2">
                Pedidos Disponiveis
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-neutral-800 text-neutral-400">{{ count($availableOrders) }}</span>
                <span x-show="hasNewOrder" class="w-2 h-2 rounded-full bg-violet-400 animate-pulse"></span>
            </h2>
            <div class="flex items-center gap-2">
                <span class="text-[10px] text-neutral-500">Atualizando a cada 12s</span>
                <a href="{{ route('delivery.dashboard', ['tab' => 'disponiveis']) }}"
                   class="text-xs text-violet-400/70 hover:text-violet-400 transition-colors flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Atualizar
                </a>
            </div>
        </div>

        @if (count($availableOrders) > 0)
            <div class="grid gap-3">
                @foreach ($availableOrders as $order)
                    <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 overflow-hidden hover:border-violet-500/20 transition-all duration-300">
                        <div class="p-4 pb-3">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-9 h-9 rounded-full bg-violet-500/10 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-sm text-white truncate">{{ $order['customer_name'] ?? 'Cliente' }}</p>
                                        <p class="text-[11px] text-neutral-500">{{ maskPhone($order['customer_phone'] ?? '') }}</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-lg font-bold text-violet-400">R$ {{ number_format($order['total'], 2, ',', '.') }}</p>
                                    <p class="text-[10px] text-neutral-500">{{ $order['created_at_diff'] }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-2 text-xs text-neutral-400 mb-2">
                                <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <div>
                                    <p>{{ $order['address'] }}</p>
                                    @if ($order['reference'])
                                        <p class="text-[11px] text-neutral-500 mt-0.5">Ref: {{ $order['reference'] }}</p>
                                    @endif
                                    @if (($order['delivery_cost'] ?? 0) > 0)
                                        <p class="text-[11px] text-emerald-400/70 mt-0.5">Taxa de entrega: R$ {{ number_format($order['delivery_cost'], 2, ',', '.') }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-1 mb-2">
                                @foreach ($order['items'] as $idx => $item)
                                    @if ($idx < 4)
                                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-neutral-800 text-neutral-300">{{ $item['quantity'] }}x {{ Str::limit($item['product'], 16) }}</span>
                                    @endif
                                @endforeach
                                @if ($order['items_count'] > 4)
                                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-neutral-800 text-neutral-500">+{{ $order['items_count'] - 4 }}</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-3 text-[11px] text-neutral-500">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    {{ $order['payment_method'] }}
                                </span>
                                @if ($order['notes'])
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                        {{ Str::limit($order['notes'], 30) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex gap-2 px-4 pb-4">
                            <form method="POST" action="{{ route('delivery.order.accept', $order['id']) }}" class="flex-1">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-2.5 rounded-xl bg-gradient-to-r from-violet-500 to-violet-600 hover:from-violet-400 hover:to-violet-500 text-white font-bold text-sm transition-all shadow-lg shadow-violet-500/20 active:scale-[0.98]">
                                    Aceitar Pedido
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl p-10 border border-neutral-800 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-neutral-800/50 flex items-center justify-center">
                    <svg class="w-8 h-8 text-neutral-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <p class="text-sm text-neutral-500 font-medium">Nenhum pedido disponivel</p>
                <p class="text-xs text-neutral-600 mt-1">Os pedidos aparecerao automaticamente aqui.</p>
            </div>
        @endif
    </div>

    {{-- =================================================================== --}}
    {{-- TAB: ATIVOS --}}
    {{-- =================================================================== --}}
    <div x-show="activeTab === 'ativos'" x-transition:enter.duration.200ms.opacity>
        <h2 class="font-bold text-sm text-white mb-3 flex items-center gap-2">
            Meus Pedidos
            @if ($todayStats['active'] > 0)
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-violet-500/20 text-violet-400 font-semibold">{{ $todayStats['active'] }} ativos</span>
            @endif
        </h2>

        @if (count($myOrders) > 0)
            <div class="grid gap-3">
                @foreach ($myOrders as $order)
                    @php
                        $steps = [
                            ['key' => 'accepted', 'label' => 'Aceito', 'done' => in_array($order['status'], ['coletado', 'saiu_entrega', 'entregue'])],
                            ['key' => 'picked', 'label' => 'Coletado', 'done' => in_array($order['status'], ['saiu_entrega', 'entregue'])],
                            ['key' => 'route', 'label' => 'Saiu p/ Entrega', 'done' => in_array($order['status'], ['entregue'])],
                            ['key' => 'delivered', 'label' => 'Entregue', 'done' => $order['status'] === 'entregue'],
                        ];
                    @endphp
                    <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 overflow-hidden">
                        <div class="p-4 pb-2">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-9 h-9 rounded-full {{ $order['status'] === 'entregue' ? 'bg-emerald-500/20' : 'bg-violet-500/20' }} flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 {{ $order['status'] === 'entregue' ? 'text-emerald-400' : 'text-violet-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-sm text-white">{{ $order['customer_name'] ?? 'Cliente' }}</p>
                                        <a href="tel:{{ preg_replace('/\D/', '', $order['customer_phone'] ?? '') }}" class="text-[11px] text-violet-400 hover:text-violet-300 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            {{ maskPhone($order['customer_phone'] ?? '') }}
                                        </a>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="font-bold text-sm text-white">R$ {{ number_format($order['total'], 2, ',', '.') }}</p>
                                    @if (($order['delivery_cost'] ?? 0) > 0)
                                        <p class="text-[10px] text-emerald-400">+ R$ {{ number_format($order['delivery_cost'], 2, ',', '.') }} entrega</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Progress Timeline --}}
                            <div class="flex items-center justify-between mt-3 mb-1 px-1">
                                @foreach ($steps as $i => $step)
                                    <div class="flex flex-col items-center {{ $i < count($steps) - 1 ? 'flex-1' : '' }}">
                                        <div class="flex items-center w-full">
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold
                                                {{ $step['done'] ? 'bg-emerald-500 text-white' : ($loop->first ? 'bg-violet-500 text-white' : 'bg-neutral-800 text-neutral-500') }}">
                                                @if ($step['done'])
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                @elseif($loop->first)
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                @else
                                                    {{ $i + 1 }}
                                                @endif
                                            </div>
                                            @if ($i < count($steps) - 1)
                                                <div class="flex-1 h-0.5 mx-1.5 rounded {{ $steps[$i + 1]['done'] ? 'bg-emerald-500/60' : 'bg-neutral-800' }}"></div>
                                            @endif
                                        </div>
                                        <p class="text-[9px] mt-1.5 {{ $step['done'] ? 'text-emerald-400 font-medium' : 'text-neutral-500' }}">{{ $step['label'] }}</p>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Address --}}
                            <div class="flex items-start gap-2 text-xs text-neutral-400 mt-3 pt-2 border-t border-neutral-800/50">
                                <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <div class="flex-1">
                                    <p>{{ $order['address'] }}</p>
                                    @if ($order['reference'])
                                        <p class="text-[11px] text-neutral-500 mt-0.5">Ref: {{ $order['reference'] }}</p>
                                    @endif
                                </div>
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($order['address']) }}"
                                   target="_blank"
                                   class="shrink-0 px-2 py-1 rounded-lg bg-violet-500/10 text-violet-400 hover:bg-violet-500/20 transition-colors text-[10px] font-semibold flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                    Rota
                                </a>
                            </div>

                            {{-- Items Summary --}}
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach ($order['items'] as $idx => $item)
                                    @if ($idx < 3)
                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-neutral-800 text-neutral-400">{{ $item['quantity'] }}x {{ Str::limit($item['product'], 14) }}</span>
                                    @endif
                                @endforeach
                                @if ($order['items_count'] > 3)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-neutral-800 text-neutral-500">+{{ $order['items_count'] - 3 }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-2 px-4 pb-4">
                            @if ($order['status'] === 'coletado')
                                <form method="POST" action="{{ route('delivery.order.pickup', $order['id']) }}" class="flex-1">
                                    @csrf
                                    <button type="submit"
                                            class="w-full px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-400 hover:to-blue-500 text-white font-bold text-sm transition-all shadow-lg shadow-blue-500/20 active:scale-[0.98]">
                                        <span class="flex items-center justify-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                            Sair para Entrega
                                        </span>
                                    </button>
                                </form>
                            @endif
                            @if (in_array($order['status'], ['coletado', 'saiu_entrega']))
                                <button @click="startPhotoCapture({{ $order['id'] }})"
                                        class="flex-1 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-white font-bold text-sm transition-all shadow-lg shadow-emerald-500/20 active:scale-[0.98]">
                                    <span class="flex items-center justify-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        {{ $order['status'] === 'saiu_entrega' ? 'Confirmar' : 'Entregar' }}
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl p-8 border border-neutral-800 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-neutral-800/50 flex items-center justify-center">
                    <svg class="w-8 h-8 text-neutral-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <p class="text-sm text-neutral-500 font-medium">Nenhum pedido ativo</p>
                <p class="text-xs text-neutral-600 mt-1">Aceite pedidos disponiveis para comecar.</p>
            </div>
        @endif
    </div>

    {{-- =================================================================== --}}
    {{-- TAB: HISTORICO --}}
    {{-- =================================================================== --}}
    <div x-show="activeTab === 'historico'" x-transition:enter.duration.200ms.opacity>
        <h2 class="font-bold text-sm text-white mb-3">Historico de Entregas</h2>

        @php $historyOrders = $history->items(); @endphp
        @if (count($historyOrders) > 0)
            <div class="space-y-2">
                @foreach ($historyOrders as $order)
                    <div class="bg-neutral-900/50 rounded-xl p-3.5 border border-neutral-800/50 flex items-start justify-between gap-3 hover:border-neutral-700/50 transition-colors">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="w-2 h-2 rounded-full {{ $order['status'] === 'cancelado' ? 'bg-red-400' : 'bg-emerald-400' }}"></span>
                                <p class="text-sm font-medium text-white truncate">{{ $order['customer_name'] }}</p>
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $order['status'] === 'cancelado' ? 'bg-red-500/10 text-red-400' : 'bg-emerald-500/10 text-emerald-400' }}">
                                    {{ $order['status_label'] }}
                                </span>
                            </div>
                            <p class="text-[11px] text-neutral-500">{{ $order['address'] }}</p>
                            @if ($order['delivered_at'])
                                <p class="text-[10px] text-neutral-600 mt-1">Entregue em {{ $order['delivered_at'] }}</p>
                            @endif
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-semibold text-white">R$ {{ number_format($order['total'], 2, ',', '.') }}</p>
                            @if (($order['delivery_cost'] ?? 0) > 0)
                                <p class="text-[10px] text-emerald-400/70">+ R$ {{ number_format($order['delivery_cost'], 2, ',', '.') }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @if ($history->hasPages())
                <div class="flex justify-center gap-2 mt-4">
                    @if ($history->onFirstPage())
                        <span class="px-3 py-1.5 text-xs text-neutral-600 bg-neutral-900 rounded-lg border border-neutral-800">Anterior</span>
                    @else
                        <a href="{{ $history->previousPageUrl() }}&tab=historico" class="px-3 py-1.5 text-xs text-neutral-300 bg-neutral-900 rounded-lg border border-neutral-800 hover:border-violet-500/30 transition-colors">Anterior</a>
                    @endif
                    <span class="px-3 py-1.5 text-xs text-neutral-500">Pagina {{ $history->currentPage() }} de {{ $history->lastPage() }}</span>
                    @if ($history->hasMorePages())
                        <a href="{{ $history->nextPageUrl() }}&tab=historico" class="px-3 py-1.5 text-xs text-neutral-300 bg-neutral-900 rounded-lg border border-neutral-800 hover:border-violet-500/30 transition-colors">Proxima</a>
                    @else
                        <span class="px-3 py-1.5 text-xs text-neutral-600 bg-neutral-900 rounded-lg border border-neutral-800">Proxima</span>
                    @endif
                </div>
            @endif
        @else
            <div class="bg-neutral-900/50 rounded-xl p-8 border border-neutral-800/50 text-center">
                <p class="text-sm text-neutral-500">Nenhuma entrega concluida ainda.</p>
            </div>
        @endif
    </div>

    {{-- =================================================================== --}}
    {{-- TAB: GANHOS --}}
    {{-- =================================================================== --}}
    <div x-show="activeTab === 'ganhos'" x-transition:enter.duration.200ms.opacity>
        <div class="grid gap-4">
            {{-- Earnings Summary Cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-4 text-center">
                    <p class="text-[10px] text-neutral-500 uppercase tracking-wider font-medium mb-1">Total Geral</p>
                    <p class="text-xl font-black text-emerald-400">R$ {{ number_format($profile['earnings'], 2, ',', '.') }}</p>
                </div>
                <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-4 text-center">
                    <p class="text-[10px] text-neutral-500 uppercase tracking-wider font-medium mb-1">Entregas Totais</p>
                    <p class="text-xl font-black text-white">{{ $profile['total_deliveries'] }}</p>
                </div>
                <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-4 text-center">
                    <p class="text-[10px] text-neutral-500 uppercase tracking-wider font-medium mb-1">Tempo Medio</p>
                    <p class="text-xl font-black text-amber-400">{{ $profile['avg_time_minutes'] }} <span class="text-sm font-normal text-neutral-500">min</span></p>
                </div>
                <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-4 text-center">
                    <p class="text-[10px] text-neutral-500 uppercase tracking-wider font-medium mb-1">Cancelamento</p>
                    <p class="text-xl font-black {{ ($profile['cancel_rate'] ?? 0) > 10 ? 'text-red-400' : 'text-emerald-400' }}">{{ $profile['cancel_rate'] }}%</p>
                </div>
            </div>

            {{-- Weekly Earnings Chart --}}
            <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-sm text-white">Ganhos da Semana</h3>
                    <p class="text-lg font-bold text-emerald-400">R$ {{ number_format($weeklyEarnings['total_earnings'], 2, ',', '.') }}</p>
                </div>
                <div class="grid grid-cols-7 gap-1.5">
                    @foreach ($weeklyEarnings['days'] as $day)
                        <div class="text-center">
                            <div class="h-24 bg-neutral-800/50 rounded-lg relative overflow-hidden mb-1.5 group cursor-pointer">
                                @php $pct = $weeklyEarnings['total_earnings'] > 0 ? ($day['earnings'] / $weeklyEarnings['total_earnings']) * 100 : 0; @endphp
                                <div class="absolute bottom-0 left-1 right-1 bg-gradient-to-t from-emerald-500/50 to-emerald-500/10 rounded-t-lg transition-all duration-500 group-hover:from-emerald-500/70"
                                     style="height: {{ max($pct, 3) }}%"></div>
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-neutral-900/60 rounded-lg">
                                    <p class="text-[10px] font-bold text-emerald-400">R$ {{ number_format($day['earnings'], 0) }}</p>
                                </div>
                            </div>
                            <p class="text-[9px] text-neutral-500 font-medium">{{ $day['date'] }}</p>
                            <p class="text-[9px] font-semibold {{ $day['earnings'] > 0 ? 'text-emerald-400' : 'text-neutral-600' }}">
                                R$ {{ number_format($day['earnings'], 0) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Ranking --}}
            @if ($ranking['total_deliverers'] > 1)
                <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-5">
                    <h3 class="font-bold text-sm text-white mb-3">Ranking de Entregadores</h3>
                    <div class="space-y-1.5">
                        @foreach ($ranking['ranking'] as $idx => $r)
                            @php
                                $isMe = $r['id'] === $delivery->id;
                                $medal = $idx === 0 ? '🥇' : ($idx === 1 ? '🥈' : ($idx === 2 ? '🥉' : ''));
                            @endphp
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg {{ $isMe ? 'bg-violet-500/10 border border-violet-500/20' : 'bg-neutral-800/30' }}">
                                <span class="w-6 text-xs font-bold text-center {{ $isMe ? 'text-violet-400' : 'text-neutral-500' }}">
                                    {{ $medal ?: $idx + 1 }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <span class="text-xs {{ $isMe ? 'text-violet-400 font-semibold' : 'text-neutral-300' }}">
                                        {{ $r['name'] }}
                                        @if ($isMe) <span class="text-[10px] text-violet-500">(voce)</span> @endif
                                    </span>
                                </div>
                                <span class="text-xs {{ $isMe ? 'text-violet-400' : 'text-neutral-500' }}">
                                    {{ $r['completed'] }} entregas
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- =================================================================== --}}
    {{-- TAB: PERFIL --}}
    {{-- =================================================================== --}}
    <div x-show="activeTab === 'perfil'" x-transition:enter.duration.200ms.opacity>
        <div class="grid gap-4">
            {{-- Profile Card --}}
            <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-5">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-violet-400 to-violet-600 flex items-center justify-center text-2xl font-bold text-neutral-950 shadow-lg shadow-violet-500/20">
                        {{ strtoupper(substr($profile['name'], 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-lg">{{ $profile['name'] }}</h3>
                        <p class="text-xs text-neutral-400">{{ maskPhone($profile['phone'] ?? '') }}</p>
                        @if ($profile['activated_at'])
                            <p class="text-[10px] text-neutral-500 mt-0.5">Entregador desde {{ \Carbon\Carbon::parse($profile['activated_at'])->format('d/m/Y') }}</p>
                        @endif
                        <span class="inline-flex items-center gap-1 mt-1.5 text-[10px] px-2 py-0.5 rounded-full {{ $isAvailable ? 'bg-emerald-500/10 text-emerald-400' : 'bg-neutral-800 text-neutral-500' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $isAvailable ? 'bg-emerald-400' : 'bg-neutral-500' }}"></span>
                            {{ $isAvailable ? 'Online' : 'Offline' }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-neutral-800/50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-white">{{ $profile['total_deliveries'] }}</p>
                        <p class="text-[10px] text-neutral-500 uppercase tracking-wider">Total Entregas</p>
                    </div>
                    <div class="bg-neutral-800/50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-emerald-400">R$ {{ number_format($profile['earnings'], 2, ',', '.') }}</p>
                        <p class="text-[10px] text-neutral-500 uppercase tracking-wider">Ganhos Totais</p>
                    </div>
                    <div class="bg-neutral-800/50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-amber-400">{{ $profile['avg_time_minutes'] }} <span class="text-sm font-normal text-neutral-500">min</span></p>
                        <p class="text-[10px] text-neutral-500 uppercase tracking-wider">Tempo Medio</p>
                    </div>
                    <div class="bg-neutral-800/50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold {{ ($profile['cancel_rate'] ?? 0) > 10 ? 'text-red-400' : 'text-emerald-400' }}">{{ $profile['cancel_rate'] }}%</p>
                        <p class="text-[10px] text-neutral-500 uppercase tracking-wider">Cancelamento</p>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-3 bg-neutral-800/30 rounded-lg px-4 py-2.5">
                    @if ($delivery->vehicle_model || $delivery->vehicle_plate)
                        <svg class="w-4 h-4 text-neutral-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span class="text-xs text-neutral-400">
                            {{ $delivery->vehicle_model ?: 'Veiculo' }}
                            @if ($delivery->vehicle_plate)
                                <span class="text-neutral-500">&#183; {{ $delivery->vehicle_plate }}</span>
                            @endif
                        </span>
                    @else
                        <svg class="w-4 h-4 text-neutral-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        <span class="text-xs text-neutral-500">Nenhum veiculo cadastrado</span>
                    @endif
                </div>
            </div>

            {{-- Weekly Earnings --}}
            <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-sm text-white">Ganhos da Semana</h3>
                    <p class="text-lg font-bold text-emerald-400">R$ {{ number_format($weeklyEarnings['total_earnings'], 2, ',', '.') }}</p>
                </div>
                <div class="grid grid-cols-7 gap-1.5">
                    @foreach ($weeklyEarnings['days'] as $day)
                        <div class="text-center">
                            <div class="h-20 bg-neutral-800/50 rounded-lg relative overflow-hidden mb-1">
                                @php $pct = $weeklyEarnings['total_earnings'] > 0 ? ($day['earnings'] / $weeklyEarnings['total_earnings']) * 100 : 0; @endphp
                                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-emerald-500/40 to-emerald-500/10 rounded-b-lg transition-all duration-500"
                                     style="height: {{ max($pct, 2) }}%"></div>
                            </div>
                            <p class="text-[10px] text-neutral-500 font-medium">{{ $day['date'] }}</p>
                            <p class="text-[10px] font-semibold {{ $day['earnings'] > 0 ? 'text-emerald-400' : 'text-neutral-600' }}">
                                R$ {{ number_format($day['earnings'], 0) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Ranking --}}
            @if ($ranking['total_deliverers'] > 1)
                <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-5">
                    <h3 class="font-bold text-sm text-white mb-3">Ranking de Entregadores</h3>
                    <div class="space-y-1.5">
                        @foreach ($ranking['ranking'] as $idx => $r)
                            @php
                                $isMe = $r['id'] === $delivery->id;
                                $medal = $idx === 0 ? '🥇' : ($idx === 1 ? '🥈' : ($idx === 2 ? '🥉' : ''));
                            @endphp
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg {{ $isMe ? 'bg-violet-500/10 border border-violet-500/20' : 'bg-neutral-800/30' }}">
                                <span class="w-6 text-xs font-bold text-center {{ $isMe ? 'text-violet-400' : 'text-neutral-500' }}">
                                    {{ $medal ?: $idx + 1 }}
                                </span>
                                <span class="text-xs {{ $isMe ? 'text-violet-400 font-semibold' : 'text-neutral-300' }}">
                                    {{ $r['name'] }}
                                    @if ($isMe) <span class="text-[10px] text-violet-500">(voce)</span> @endif
                                </span>
                                <span class="ml-auto text-xs {{ $isMe ? 'text-violet-400' : 'text-neutral-500' }}">
                                    {{ $r['completed'] }} entregas
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- =================================================================== --}}
    {{-- TAB: CONFIGURACOES --}}
    {{-- =================================================================== --}}
    <div x-show="activeTab === 'configuracoes'" x-transition:enter.duration.200ms.opacity>
        <div class="max-w-2xl mx-auto space-y-6">
            <div>
                <h2 class="font-bold text-base text-white">Configuracoes da Conta</h2>
                <p class="text-xs text-neutral-500 mt-1">Gerencie suas informacoes pessoais e veiculo</p>
            </div>

            <template x-if="settingsMessage">
                <div class="p-3 rounded-xl text-sm flex items-center gap-2"
                     :class="settingsMessageType === 'success' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400' : 'bg-red-500/10 border border-red-500/20 text-red-400'">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path :d="settingsMessageType === 'success' ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' : 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    </svg>
                    <span x-text="settingsMessage"></span>
                </div>
            </template>

            <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-5 space-y-4">
                <h3 class="font-semibold text-sm text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Informacoes Pessoais
                </h3>

                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1">Nome completo</label>
                    <input type="text" x-model="settingsName"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
                </div>

                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1">Email</label>
                    <input type="email" x-model="settingsEmail"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950/50 border border-neutral-800/50 text-neutral-400 text-sm cursor-not-allowed" readonly disabled>
                    <p class="text-[10px] text-neutral-600 mt-1">O email nao pode ser alterado.</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1">Telefone</label>
                    <input type="tel" inputmode="numeric" maxlength="15"
                           :value="phoneMask(settingsPhone)"
                           @input="settingsPhone = $event.target.value.replace(/\D/g,'')"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
                </div>

                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1">CPF</label>
                    <input type="text" value="{{ $delivery->cpf ? substr($delivery->cpf, 0, 3).'.'.substr($delivery->cpf, 3, 3).'.'.substr($delivery->cpf, 6, 3).'-'.substr($delivery->cpf, 9, 2) : 'Nao informado' }}"
                           disabled readonly
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950/50 border border-neutral-800/50 text-neutral-400 text-sm cursor-not-allowed">
                </div>
            </div>

            <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-5 space-y-4">
                <h3 class="font-semibold text-sm text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Veiculo
                </h3>

                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1">Placa</label>
                    <input type="text" x-model="settingsPlate" maxlength="8" placeholder="ABC-1234"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all uppercase">
                </div>

                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1">Modelo</label>
                    <input type="text" x-model="settingsModel" maxlength="100" placeholder="Ex: Honda CG 160"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
                </div>
            </div>

            <div class="bg-gradient-to-br from-neutral-900 to-neutral-950 rounded-xl border border-neutral-800 p-5 space-y-4">
                <h3 class="font-semibold text-sm text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Alterar Senha
                </h3>
                <p class="text-[11px] text-neutral-500">Deixe em branco para manter a senha atual.</p>

                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1">Nova senha</label>
                    <input type="password" x-model="settingsPassword" minlength="6" placeholder="Minimo 6 caracteres"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
                </div>

                <div>
                    <label class="block text-xs font-medium text-neutral-400 mb-1">Confirmar nova senha</label>
                    <input type="password" x-model="settingsPasswordConfirm" placeholder="Repita a nova senha"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
                </div>
            </div>

            <div class="flex justify-end">
                <button @click="saveSettings()" :disabled="settingsSaving"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-violet-500 to-violet-600 hover:from-violet-400 hover:to-violet-500 text-white font-semibold text-sm transition-all shadow-lg shadow-violet-500/20 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <template x-if="settingsSaving">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                    <span x-text="settingsSaving ? 'Salvando...' : 'Salvar Alteracoes'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ===== PHOTO CAPTURE MODAL ===== --}}
    <div x-show="showPhotoModal"
         x-cloak
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
         @keydown.escape="closeCamera()">
        <div class="w-full max-w-md bg-neutral-900 rounded-2xl border border-neutral-800 overflow-hidden shadow-2xl">
            <div class="p-4 border-b border-neutral-800 flex items-center justify-between">
                <h3 class="font-bold text-sm text-white">Foto da Entrega</h3>
                <button @click="closeCamera()" class="p-1 rounded-lg hover:bg-neutral-800 transition-colors">
                    <svg class="w-5 h-5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-4">
                <template x-if="!photoData">
                    <div class="relative bg-black rounded-xl overflow-hidden aspect-[4/3]">
                        <video id="delivery-preview" class="w-full h-full object-cover"></video>
                        <button @click="capturePhoto()"
                                class="absolute bottom-4 left-1/2 -translate-x-1/2 w-14 h-14 rounded-full bg-white/90 hover:bg-white flex items-center justify-center shadow-xl transition-all active:scale-95">
                            <div class="w-10 h-10 rounded-full border-2 border-neutral-900"></div>
                        </button>
                    </div>
                </template>
                <template x-if="photoData">
                    <div class="relative bg-black rounded-xl overflow-hidden aspect-[4/3]">
                        <img :src="photoData" class="w-full h-full object-cover">
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-3">
                            <button @click="retakePhoto()"
                                    class="px-4 py-2 rounded-full bg-neutral-800/80 text-white text-xs font-semibold backdrop-blur hover:bg-neutral-700 transition-colors">
                                Refazer
                            </button>
                            <button @click="submitDelivery({{ $photoOrderId ?? 0 }})"
                                    class="px-4 py-2 rounded-full bg-emerald-500 text-white text-xs font-semibold hover:bg-emerald-400 transition-colors">
                                Confirmar
                            </button>
                        </div>
                    </div>
                </template>
                <p class="text-xs text-neutral-500 text-center mt-3">Tire uma foto do local de entrega para confirmar</p>
            </div>
        </div>
    </div>

    {{-- Hidden forms for delivery --}}
    @foreach ($myOrders as $order)
        @if (in_array($order['status'], ['coletado', 'saiu_entrega']))
            <form id="delivery-form-{{ $order['id'] }}"
                  method="POST"
                  action="{{ route('delivery.order.deliver', $order['id']) }}"
                  enctype="multipart/form-data"
                  style="display:none">
                @csrf
                <input type="hidden" name="photo_file" id="photo-file-{{ $order['id'] }}">
            </form>
        @endif
    @endforeach

    <canvas id="delivery-canvas" style="display:none"></canvas>
</div>

<style>
    [x-cloak] { display: none !important; }
    .scrollbar-thin::-webkit-scrollbar { height: 4px; }
    .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
    .scrollbar-thin::-webkit-scrollbar-thumb { background: #404040; border-radius: 4px; }
</style>
@endsection
