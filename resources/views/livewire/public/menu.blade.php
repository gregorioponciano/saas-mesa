<div x-data="{ sidebarOpen: false }" class="min-h-screen flex">
    @php
        $logoW = min(max($tenant->logo_width ?? 44, 20), 120);
        $logoH = min(max($tenant->logo_height ?? 44, 20), 120);
    @endphp
    {{-- Backdrop --}}
    <div x-show="sidebarOpen" x-cloak
         class="fixed inset-0 z-40 bg-black/60 backdrop-blur-md"
         @click="sidebarOpen = false"
         x-transition:enter="transition-opacity duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    {{-- Sidebar (autenticado apenas) --}}
    @auth
    <aside x-cloak
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           @keydown.window.escape="sidebarOpen = false"
            class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] bg-neutral-900 border-r border-neutral-800 flex flex-col transition-transform duration-300 ease-in-out">
        <div class="flex flex-col h-full">
            {{-- Brand --}}
            <div class="flex items-center gap-3 px-6 h-16 border-b border-neutral-800">
            @if ($tenant->logoUrl())
                <div class="rounded-xl overflow-hidden shrink-0" style="width: {{ $logoW }}px; height: {{ $logoH }}px;">
                    <img src="{{ $tenant->logoUrl() }}" class="w-full h-full object-contain" alt="Logo">
                </div>
            @else
                <div class="rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-neutral-950 font-black text-sm shadow-lg shadow-amber-500/20" style="width: {{ $logoW }}px; height: {{ $logoH }}px;">
                    {{ mb_substr($tenant->name, 0, 1) }}
                </div>
            @endif
            <div>
                <span class="font-black text-amber-400">{{ $tenant->name }}</span>
                <span class="font-black text-white">Digital</span>
            </div>
        </div>

            {{-- User Info --}}
            <div class="px-4 py-4 border-b border-neutral-800">
                <div class="flex items-center gap-3 px-3 py-2 rounded-xl bg-neutral-800/50">
                    <div class="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold text-xs">
                        {{ substr(Auth::user()->name, 0, 2) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-neutral-400 truncate">{{ $tenant->name }}</p>
                    </div>
                    <span class="ml-auto px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Cliente</span>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <button wire:click="switchClientTab('menu'); sidebarOpen = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $clientTab === 'menu' ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    Cardapio
                </button>

                <button wire:click="switchClientTab('orders'); sidebarOpen = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $clientTab === 'orders' ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <span class="flex-1 text-left">Meus Pedidos</span>
                    @if (count($myActiveOrders) > 0)
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 animate-pulse">{{ count($myActiveOrders) }}</span>
                    @endif
                </button>

                <button wire:click="switchClientTab('history'); sidebarOpen = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $clientTab === 'history' ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="flex-1 text-left">Historico</span>
                    @if (($myOrdersCount ?? 0) > 0)
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-neutral-700/50 text-neutral-300">{{ $myOrdersCount }}</span>
                    @endif
                </button>

                @if ($pointsVisible)
                <button wire:click="switchClientTab('pontos'); sidebarOpen = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $clientTab === 'pontos' ? 'bg-emerald-500/10 text-emerald-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="flex-1 text-left">Pontos</span>
                    @if ($pointsBalance > 0)
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">{{ number_format($pointsBalance, 0, ',', '.') }}</span>
                    @endif
                </button>
                @endif

                @auth
                <button wire:click="switchClientTab('favoritos'); sidebarOpen = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $clientTab === 'favoritos' ? 'bg-red-500/10 text-red-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <span class="flex-1 text-left">Favoritos</span>
                    @if (count($favoriteProductIds) > 0)
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-500/20 text-red-400 border border-red-500/30">{{ count($favoriteProductIds) }}</span>
                    @endif
                </button>
                @endauth

                <button wire:click="switchClientTab('settings'); sidebarOpen = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $clientTab === 'settings' ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Configuracoes
                </button>

                <div class="pt-4 mt-4 border-t border-neutral-800">
                    <p class="px-4 text-xs font-medium text-neutral-500 uppercase tracking-wider">Suporte</p>
                </div>

                <button wire:click="switchClientTab('support'); sidebarOpen = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $clientTab === 'support' ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                    </svg>
                    <span class="flex-1 text-left">Suporte</span>
                </button>
            </nav>

            {{-- Cart Summary in Sidebar --}}
            <div class="px-3 py-3 border-t border-neutral-800"
                 x-data="{ badgeCount: {{ $cartItemsCount ?? 0 }} }"
                 @cart-badge-update.window="badgeCount = $event.detail.count">
                <button @click="$dispatch('open-cart'); sidebarOpen = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-neutral-400 hover:text-amber-400 hover:bg-amber-500/5 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                    </svg>
                    <span class="flex-1 text-left">Carrinho</span>
                    <template x-if="badgeCount > 0">
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30" x-text="badgeCount"></span>
                    </template>
                </button>
            </div>

            {{-- Logout --}}
            <div class="px-3 py-4 border-t border-neutral-800">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-neutral-400 hover:text-red-400 hover:bg-red-500/5 transition-all duration-200 w-full">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sair
                    </button>
                </form>
            </div>
        </div>
    </aside>
    @endauth

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col min-w-0">

        <div class="relative min-h-screen pb-40"
             x-data="{
                 search: '',
                 activeCategory: null,
                 showMobileSearch: false,
                 hasResults: true,
                 favoriteIds: @js($favoriteProductIds ?? []),
                  init() {
                      this.activeCategory = this.$el.querySelector('[data-category]')?.dataset.category || null;
                      this.$watch('search', () => {
                          if (!this.search.trim()) { this.hasResults = true; return; }
                          this.$nextTick(() => {
                              const cards = document.querySelectorAll('[data-product-card]');
                              this.hasResults = Array.from(cards).some(c => c.style.display !== 'none');
                          });
                      });
                  },
                 matchProduct(el) {
                     if (!this.search.trim()) return true;
                     const text = (el.innerText || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                     const q = this.search.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                     return text.includes(q);
                 },
                 scrollToCategory(slug) {
                     this.activeCategory = slug;
                     document.getElementById('cat-' + slug)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                 },
             }"
             x-init="
                 init();
                 const observer = new IntersectionObserver((entries) => {
                     entries.forEach(entry => {
                         if (entry.isIntersecting) activeCategory = entry.target.dataset.category;
                     });
                 }, { rootMargin: '-120px 0px -50% 0px' });
                 $nextTick(() => document.querySelectorAll('[data-category]').forEach(el => observer.observe(el)));
             ">

            {{-- Toast Notification (cart only) --}}
            <div x-data="{ toasts: [], id: 0, showToast(text, duration = 3000) { const id = ++this.id; this.toasts.push({ id, text, show: true }); setTimeout(() => { const t = this.toasts.find(x => x.id === id); if (t) t.show = false; setTimeout(() => this.toasts = this.toasts.filter(x => x.id !== id), 300); }, duration); } }"
                 x-init="
                      $wire.$on('cartUpdated', () => {
                          showToast('Item adicionado ao carrinho!');
                      });
                 "
                 class="fixed top-4 right-4 z-[80] flex flex-col gap-2 pointer-events-none"
                 x-cloak>
                <template x-for="(toast, i) in toasts" :key="toast.id">
                    <div x-show="toast.show"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="translate-x-full opacity-0"
                         x-transition:enter-end="translate-x-0 opacity-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="translate-x-0 opacity-100"
                         x-transition:leave-end="translate-x-full opacity-0"
                         class="flex items-center gap-3 px-5 py-3 rounded-xl bg-neutral-900 border border-neutral-700 shadow-2xl shadow-black/40 text-sm font-medium pointer-events-auto"
                         x-text="toast.text">
                    </div>
                </template>
            </div>

            {{-- Header --}}
            <header class="sticky top-0 z-40 bg-neutral-950/95 backdrop-blur-xl border-b border-neutral-800/80 transition-all duration-300"
                    x-data="{ scrolled: false }"
                    x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 40, { passive: true })">
                {{-- Top Bar --}}
                <div class="px-3 sm:px-4 py-2 sm:py-3" :class="scrolled && 'py-1.5 sm:py-2'">
                    <div class="flex items-center gap-2 sm:gap-3">
                        @auth
                        <button x-show="!sidebarOpen" @click="sidebarOpen = !sidebarOpen" class="p-1.5 sm:p-2 rounded-xl hover:bg-neutral-800 transition-colors shrink-0">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        @endauth
                        @if ($tenant->logoUrl())
                            <div class="rounded-xl overflow-hidden shrink-0" style="width: {{ $logoW }}px; height: {{ $logoH }}px;">
                                <img src="{{ $tenant->logoUrl() }}" class="w-full h-full object-contain" alt="Logo">
                            </div>
                        @else
                            <div class="rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-neutral-950 font-black text-sm sm:text-lg shadow-lg shadow-amber-500/20 shrink-0" style="width: {{ $logoW }}px; height: {{ $logoH }}px;">
                                {{ mb_substr($tenant->name, 0, 1) }}
                            </div>
                        @endif
                <div class="flex-1 min-w-0">
                    <button wire:click="switchClientTab('menu')"
                            class="text-left">
                        <h1 class="font-bold truncate" :class="scrolled ? 'text-xs sm:text-sm' : 'text-sm sm:text-lg'">{{ $tenant->name }}</h1>
                    </button>
                    <div class="flex items-center gap-1 sm:gap-2 mt-0.5 sm:mt-1 flex-wrap">
                        @if ($tenant->isOpen())
                            <span class="px-1.5 sm:px-2 py-0.5 rounded-full text-[10px] sm:text-xs bg-green-500/20 text-green-400 shrink-0">Aberto</span>
                        @else
                            <span class="px-1.5 sm:px-2 py-0.5 rounded-full text-[10px] sm:text-xs bg-red-500/20 text-red-400 shrink-0">Fechado</span>
                        @endif
                    </div>
                </div>
                        <div class="flex items-center gap-0.5 sm:gap-1 shrink-0">
                            <button @click="showMobileSearch = !showMobileSearch"
                                    class="p-1.5 sm:p-2 rounded-xl hover:bg-neutral-800 text-neutral-400 hover:text-white transition-all"
                                    :class="{ 'bg-neutral-800 text-amber-400': showMobileSearch || search }">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>
                            @auth
                            <div x-data="{ openMenu: false }" class="relative"
                                 @click.outside="openMenu = false">
                                <button @click="openMenu = !openMenu"
                                        class="flex items-center gap-1.5 p-1 pr-2 sm:pr-3 rounded-xl hover:bg-neutral-800 transition-all"
                                        :class="{ 'bg-neutral-800': openMenu }">
                                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-amber-500/20 flex items-center justify-center text-amber-400 font-bold text-xs shrink-0">
                                        {{ substr(Auth::user()->name, 0, 2) }}
                                    </div>
                                    @if (count($myActiveOrders) > 0)
                                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse shrink-0"></span>
                                    @endif
                                    <svg class="w-3.5 h-3.5 text-neutral-500 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="openMenu" x-cloak
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 -translate-y-2"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 translate-y-0"
                                     x-transition:leave-end="opacity-0 -translate-y-2"
                                     class="absolute right-0 mt-2 w-80 max-w-[95vw] bg-neutral-900 border border-neutral-700 rounded-2xl shadow-2xl shadow-black/50 overflow-hidden z-50">
                                    {{-- User Info --}}
                                    <div class="p-4 border-b border-neutral-800">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-amber-500/20 flex items-center justify-center text-amber-400 font-bold text-sm shrink-0">
                                                {{ substr(Auth::user()->name, 0, 2) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold truncate">{{ Auth::user()->name }}</p>
                                                <p class="text-[10px] text-neutral-400 truncate">{{ Auth::user()->email }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Notifications --}}
                                    <div class="border-b border-neutral-800">
                                        <div class="px-4 py-2 flex items-center justify-between">
                                            <p class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Notificacoes</p>
                                            @if (count($myActiveOrders) > 0)
                                                <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30">{{ count($myActiveOrders) }} ativas</span>
                                            @endif
                                        </div>
                                        <div class="max-h-48 overflow-y-auto">
                                            @forelse ($myActiveOrders as $order)
                                                <button wire:click="switchClientTab('orders'); openMenu = false"
                                                        class="w-full flex items-start gap-3 px-4 py-2.5 hover:bg-neutral-800/50 transition-colors text-left">
                                                    <div class="w-6 h-6 rounded-full bg-amber-500/10 flex items-center justify-center shrink-0 mt-0.5">
                                                        <svg class="w-3 h-3 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-xs font-medium truncate">Pedido #{{ $order->id }}</p>
                                                        <p class="text-[10px] text-neutral-500">Status: <span class="text-amber-400">{{ $order->statusLabel() }}</span></p>
                                                    </div>
                                                    <span class="text-[9px] text-neutral-600 shrink-0">{{ $order->updated_at->diffForHumans() }}</span>
                                                </button>
                                            @empty
                                                <div class="px-4 py-4 text-center text-[11px] text-neutral-500">
                                                    <p>Nenhuma notificacao</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                    {{-- Menu Links --}}
                                    <div class="p-2 space-y-0.5">
                                        <button @click="$dispatch('open-cart'); openMenu = false"
                                                x-data="{ badgeCount: {{ $cartItemsCount ?? 0 }} }"
                                                @cart-badge-update.window="badgeCount = $event.detail.count"
                                                class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-neutral-300 hover:text-white hover:bg-neutral-800/50 transition-all">
                                            <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                                            Carrinho
                                            <template x-if="badgeCount > 0">
                                                <span class="ml-auto px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30" x-text="badgeCount"></span>
                                            </template>
                                        </button>
                                        <button wire:click="switchClientTab('favoritos'); openMenu = false"
                                                class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-neutral-300 hover:text-white hover:bg-neutral-800/50 transition-all">
                                            <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                            Favoritos
                                        </button>
                                        <button wire:click="switchClientTab('orders'); openMenu = false"
                                                class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-neutral-300 hover:text-white hover:bg-neutral-800/50 transition-all">
                                            <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                            Meus Pedidos
                                            @if (count($myActiveOrders) > 0)
                                                <span class="ml-auto px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-amber-500/20 text-amber-400">{{ count($myActiveOrders) }}</span>
                                            @endif
                                        </button>
                                        <button wire:click="switchClientTab('settings'); openMenu = false"
                                                class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-neutral-300 hover:text-white hover:bg-neutral-800/50 transition-all">
                                            <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            Configuracoes
                                        </button>
                                        <button wire:click="switchClientTab('support'); openMenu = false"
                                                class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-neutral-300 hover:text-white hover:bg-neutral-800/50 transition-all">
                                            <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/></svg>
                                            Suporte
                                        </button>
                                    </div>
                                    {{-- Logout --}}
                                    <div class="border-t border-neutral-800 p-2">
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit"
                                                    class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-neutral-400 hover:text-red-400 hover:bg-red-500/5 transition-all">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                                Sair
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @else
                                <a href="{{ route('waiter.register.form', $tenant->slug) }}"
                                   class="text-xs text-neutral-500 hover:text-amber-400 transition-colors px-2">Acesso</a>
                            @endauth
                        </div>
                    </div>
                </div>

                {{-- Table Selection Bar (cliente only) --}}
                @auth
                    @if (Auth::user()->isCliente() || Auth::user()->isAdmin())
                        <div class="px-4 pb-3">
                            @if ($selectedTableId && $selectedTableNumber)
                                <div class="flex items-center gap-3 p-3 rounded-2xl bg-gradient-to-r from-amber-500/10 to-amber-600/5 border border-amber-500/20">
                                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-amber-400">Mesa {{ $selectedTableNumber }}</p>
                                        <p class="text-xs text-neutral-400 truncate">
                                            Mesa fixa — a mesa so pode ser alterada no painel administrativo
                                        </p>
                                    </div>
                                    <button wire:click="showQrCode"
                                             wire:loading.attr="disabled"
                                             class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all whitespace-nowrap disabled:opacity-50">
                                        QR Code
                                    </button>
                                    <div class="p-1.5 rounded-lg text-neutral-600 cursor-not-allowed"
                                         title="Mesa fixa — altere apenas no painel">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </div>
                                </div>
                            @else
                                <button wire:click="$set('showTablePicker', true)"
                                        class="w-full flex items-center gap-3 p-3 rounded-2xl bg-neutral-800/50 border border-dashed border-neutral-700 hover:border-amber-500/30 hover:bg-neutral-800/80 transition-all duration-200 group">
                                    <div class="w-10 h-10 rounded-xl bg-neutral-800 flex items-center justify-center shrink-0 group-hover:bg-amber-500/10 transition-colors">
                                        <svg class="w-5 h-5 text-neutral-400 group-hover:text-amber-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <p class="text-sm font-medium text-neutral-300 group-hover:text-amber-400 transition-colors">Selecionar Mesa</p>
                                        <p class="text-xs text-neutral-500">Escolha sua mesa para comecar a pedir</p>
                                    </div>
                                    <svg class="w-5 h-5 text-neutral-500 group-hover:text-amber-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endif
                @endauth

                {{-- Search Bar --}}
                <div x-show="showMobileSearch || search"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="px-4 pb-3">
                    <div class="relative">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input x-model="search"
                               type="text"
                               placeholder="Buscar no cardapio..."
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm transition-all">
                        <button x-show="search"
                                @click="search = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded text-neutral-500 hover:text-white">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <template x-if="search && !hasResults">
                        <div class="text-center py-8 text-neutral-500">
                            <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <p class="text-sm">Nenhum produto encontrado</p>
                        </div>
                    </template>
                </div>

                {{-- Category Pills --}}
                @if ($clientTab === 'menu' || !Auth::check())
                <div class="px-4 pb-3">
                    <div class="flex flex-wrap gap-1.5 sm:gap-2">
                        @foreach ($categories as $category)
                            <button @click="scrollToCategory('{{ $category->slug }}')"
                                    :class="activeCategory === '{{ $category->slug }}' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'bg-neutral-800/80 text-neutral-300 hover:bg-neutral-700 hover:text-white'"
                                    class="px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-medium whitespace-nowrap transition-all duration-200 active:scale-95">
                                {{ $category->name }}
                                <span class="ml-1 sm:ml-1.5 text-[10px] sm:text-xs opacity-60">({{ $category->products->count() }})</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                @endif

            {{-- Section Title --}}
            <div class="px-4 pt-6 pb-2">
                <h2 class="text-2xl sm:text-3xl font-black text-white">Cardapio Digital</h2>
                <p class="text-sm text-neutral-500 mt-1">Selecione seus produtos favoritos</p>
            </div>
            </header>

            {{-- TAB: Menu Products --}}
            @if ($clientTab === 'menu' || !Auth::check())
                <div class="px-4 mt-4 space-y-10">
                    @foreach ($categories as $category)
                        <section id="cat-{{ $category->slug }}"
                                 data-category="{{ $category->slug }}"
                                 class="scroll-mt-44">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-xl font-bold">{{ $category->name }}</h2>
                                <span class="text-xs text-neutral-500 bg-neutral-800/50 px-2.5 py-1 rounded-full">{{ $category->products->count() }} itens</span>
                            </div>

                        @if ($category->products->count() === 0)
                            <div class="text-center py-12 text-neutral-600 rounded-2xl bg-neutral-900/30 border border-dashed border-neutral-800"
                                 x-show="!search">
                                <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <p class="text-sm">Nenhum produto nesta categoria</p>
                            </div>
                            @else
                                <div class="grid grid-cols-1 gap-4">
                                    @foreach ($category->products as $product)
                                        <button wire:click="showProduct({{ $product->id }})"
                                                x-data="{ added: false }"
                                                x-show="matchProduct($el)"
                                                data-product-card
                                                @cart-cleared.window="added = false"
                                                 class="group relative w-full text-left p-4 rounded-2xl bg-neutral-900/70 border border-neutral-800/80 hover:border-amber-500/30 hover:bg-neutral-900 transition-all duration-300 active:scale-[0.99] hover:shadow-lg hover:shadow-amber-500/5">
                                             <span wire:click="toggleFavorite({{ $product->id }})"
                                                   class="absolute top-3 right-3 w-7 h-7 rounded-full flex items-center justify-center transition-all duration-200 hover:scale-110 active:scale-90 z-10 cursor-pointer"
                                                   :class="favoriteIds.includes({{ $product->id }}) ? 'bg-red-500/20 text-red-400' : 'bg-neutral-800 text-neutral-500 hover:text-red-400 hover:bg-red-500/20'">
                                                 <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                      :fill="favoriteIds.includes({{ $product->id }}) ? 'currentColor' : 'none'"
                                                      stroke-width="2">
                                                     <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                                 </svg>
                                             </span>
                                             <div class="flex gap-4">
                                                <div class="w-24 h-24 rounded-xl overflow-hidden shrink-0 bg-neutral-800/50 relative">
                                                    <img src="{{ $product->imageUrl() }}"
                                                         alt="{{ $product->name }}"
                                                         class="w-full h-full object-cover transition-all duration-500 group-hover:scale-110"
                                                         loading="lazy">
                                                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                                    @if ($product->isOutOfStock())
                                                        <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                                                            <span class="text-[10px] font-bold text-red-400 bg-red-500/20 px-2 py-1 rounded-md">SEM ESTOQUE</span>
                                                        </div>
                                                    @elseif ($product->stock > 0 && $product->stock <= 5)
                                                        <div class="absolute top-1 right-1">
                                                            <span class="text-[9px] font-medium text-amber-400 bg-amber-500/20 px-1.5 py-0.5 rounded-md">{{ $product->stock }} restante</span>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-1 min-w-0 flex flex-col justify-between">
                                                    <div>
                                                        <h3 class="font-semibold group-hover:text-amber-400 transition-colors duration-200">{{ $product->name }}</h3>
                                                        @if ($product->description)
                                                            <p class="text-sm text-neutral-400 mt-1 line-clamp-2 leading-relaxed">{{ $product->description }}</p>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center justify-between mt-2">
                                                            <p class="text-lg font-bold text-amber-400 group-hover:scale-105 transition-transform origin-left">R$ {{ number_format($product->price, 2, ',', '.') }}</p>
                                                        @if ($product->isOutOfStock())
                                                            <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-neutral-800 text-neutral-500 cursor-not-allowed">
                                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                                Indisponível
                                                            </span>
                                                        @elseif (!$product->attributes->count())
                                                            <span @click.stop
                                                                  @click="$wire.$dispatchTo('public.cart', 'addToCart', {productId: {{ $product->id }}, productName: @js($product->name), price: {{ $product->price }}, selectedOptions: [], quantity: 1}); added = true; setTimeout(() => added = false, 1200);"
                                                                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 cursor-pointer"
                                                                  :class="added ? 'bg-emerald-500 text-white scale-110' : 'bg-amber-500/10 text-amber-400 hover:bg-amber-500 hover:text-neutral-950'">
                                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                                <span x-text="added ? 'Adicionado' : 'Adicionar'"></span>
                                                            </span>
                                                        @else
                                                            <span class="text-xs text-neutral-500 flex items-center gap-1">
                                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                                Personalizar
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @endforeach
                </div>

            {{-- TAB: Orders --}}
            @elseif ($clientTab === 'orders' && Auth::check())
                @php
                    $filterType = $ordersFilter ?? 'all';
                    $unpaidOrders = $myUnpaidOrders;
                    if ($filterType === 'mesa') {
                        $filteredUnpaid = $unpaidOrders->where('table_id', '!=', null);
                    } elseif ($filterType === 'entrega') {
                        $filteredUnpaid = $unpaidOrders->where('table_id', null)->where('type', 'entrega');
                    } elseif ($filterType === 'retirada') {
                        $filteredUnpaid = $unpaidOrders->where('table_id', null)->where('type', 'retirada');
                    } else {
                        $filteredUnpaid = $unpaidOrders;
                    }
                @endphp
                <div class="px-4 mt-4 space-y-6 pb-8"
                     x-init="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold">Meus Pedidos</h2>
                        <button wire:click="switchClientTab('menu')"
                                class="text-xs text-amber-400 hover:text-amber-300 transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Voltar ao Cardapio
                        </button>
                    </div>

                    {{-- Type Filter --}}
                    <div class="flex gap-2">
                        <button wire:click="$set('ordersFilter', 'all')"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ ($filterType === 'all') ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/25' : 'bg-neutral-900/50 border border-neutral-800 text-neutral-400 hover:text-white' }}">
                            Todas
                        </button>
                        <button wire:click="$set('ordersFilter', 'mesa')"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ ($filterType === 'mesa') ? 'bg-emerald-500 text-neutral-950 shadow-lg shadow-emerald-500/25' : 'bg-neutral-900/50 border border-neutral-800 text-neutral-400 hover:text-white' }}">
                            Mesa
                        </button>
                        <button wire:click="$set('ordersFilter', 'entrega')"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ ($filterType === 'entrega') ? 'bg-sky-500 text-neutral-950 shadow-lg shadow-sky-500/25' : 'bg-neutral-900/50 border border-neutral-800 text-neutral-400 hover:text-white' }}">
                            Entrega
                        </button>
                        <button wire:click="$set('ordersFilter', 'retirada')"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ ($filterType === 'retirada') ? 'bg-purple-500 text-neutral-950 shadow-lg shadow-purple-500/25' : 'bg-neutral-900/50 border border-neutral-800 text-neutral-400 hover:text-white' }}">
                            Balcao
                        </button>
                    </div>

                    @forelse ($filteredUnpaid as $order)
                        @php
                            $typeLabel = match($order->type) {
                                'mesa' => 'Mesa ' . ($order->table->number ?? ''),
                                'entrega' => 'Entrega',
                                'retirada' => 'Balcao',
                                default => ucfirst($order->type ?? '')
                            };
                        @endphp
                        <div class="p-4 rounded-2xl bg-neutral-900/70 border border-neutral-800/80">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold">Pedido #{{ $order->id }}</span>
                                    <span class="text-xs text-neutral-500">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                                    <span class="text-[10px] font-medium {{ $order->type === 'mesa' ? 'bg-neutral-800 text-neutral-400 border border-neutral-700' : ($order->type === 'entrega' ? 'text-amber-400 bg-amber-500/10 border border-amber-500/20' : 'text-purple-400 bg-purple-500/10 border border-purple-500/20') }} px-1.5 py-0.5 rounded-full">
                                        {{ $typeLabel }}
                                    </span>
                                    @php
                                        $pendingAmount = $order->pendingPaymentAmount();
                                        $paidPayments = $order->payments->where('status', 'paid');
                                        $hasPaid = $paidPayments->count() > 0;
                                        $paymentMethodOnOrder = $order->payment_method ? \App\Models\Payment::PAYMENT_METHODS[$order->payment_method] ?? $order->payment_method : null;
                                    @endphp
                                    @if ($paymentMethodOnOrder)
                                        <span class="text-[10px] font-medium bg-neutral-800 text-neutral-400 border border-neutral-700 px-1.5 py-0.5 rounded-full">
                                            {{ $paymentMethodOnOrder }}
                                        </span>
                                    @endif
                                    @if ($pendingAmount > 0)
                                        <span class="text-[10px] font-medium bg-rose-500/10 text-rose-400 border border-rose-500/20 px-1.5 py-0.5 rounded-full">
                                            R$ {{ number_format($pendingAmount, 2, ',', '.') }} pendente
                                        </span>
                                    @endif
                                </div>
                                <span class="px-2.5 py-1 text-xs font-medium rounded-full border {{ $order->statusClasses() }} shrink-0">
                                    {{ $order->statusLabel() }}
                                </span>
                            </div>

                            <div class="space-y-1.5 mb-3">
                                @foreach ($order->items as $item)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-neutral-300">{{ $item->quantity }}x {{ $item->product_name }}</span>
                                        <span class="text-neutral-400">R$ {{ number_format($item->price * $item->quantity, 2, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>

                            @if ($order->notes)
                                <div class="mb-3 p-2.5 rounded-lg bg-neutral-800/30 border border-neutral-700/50 text-xs text-neutral-400 flex items-start gap-2">
                                    <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                    <span>{{ $order->notes }}</span>
                                </div>
                            @endif

                            <div class="pt-2 border-t border-neutral-800">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3 text-xs text-neutral-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                            {{ $order->items->count() }} item(ns)
                                        </span>
                                        @if (!$order->table && $order->address_json)
                                            @php $addr = is_array($order->address_json) ? $order->address_json : ['address' => $order->address_json]; @endphp
                                            <span class="flex items-start gap-1">
                                                <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <span class="text-xs text-neutral-400">
                                                    {{ $addr['street'] ?? $addr['address'] ?? '' }}
                                                    @if (!empty($addr['number'])), {{ $addr['number'] }}@endif
                                                    @if (!empty($addr['neighborhood'])) - {{ $addr['neighborhood'] }}@endif
                                                </span>
                                            </span>
                                        @endif
                                    </div>
                                    <span class="font-bold text-amber-400">R$ {{ number_format($order->total, 2, ',', '.') }}</span>
                                </div>

                                {{-- Pagamentos --}}
                                @if ($hasPaid)
                                    @foreach ($paidPayments as $payment)
                                        <div class="mt-2 flex items-center gap-2 text-[11px] text-emerald-400/80 bg-emerald-500/5 rounded-lg px-3 py-2">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>{{ $payment->paymentMethodLabel() }} — R$ {{ number_format($payment->amount, 2, ',', '.') }} — {{ $payment->paid_at?->format('d/m H:i') }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="mt-3 flex items-center gap-2 text-[11px] text-rose-400/80 bg-rose-500/5 rounded-lg px-3 py-2">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Aguardando pagamento</span>
                                    </div>
                                @endif

                                @if ($pendingAmount > 0)
                                <button wire:click="generateOrderPix({{ $order->id }})" wire:loading.attr="disabled"
                                        class="mt-3 w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-neutral-950 font-bold transition-all text-sm shadow-lg shadow-emerald-500/20">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span wire:loading.remove wire:target="generateOrderPix({{ $order->id }})">Pagar com PIX</span>
                                    <span wire:loading wire:target="generateOrderPix({{ $order->id }})">Gerando...</span>
                                </button>
                                @endif

                                @if (!$hasPaid && $order->withinClientCancellationWindow() && !$order->isCancelled())
                                    <button wire:click="cancelMyOrder({{ $order->id }})"
                                            wire:confirm="Cancelar este pedido?"
                                            class="mt-2 w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 font-semibold transition-all text-sm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Cancelar Pedido
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-16 text-neutral-600 rounded-2xl bg-neutral-900/30 border border-dashed border-neutral-800">
                            <svg class="w-14 h-14 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-sm font-medium">Nenhum pedido pendente</p>
                            <p class="text-xs text-neutral-700 mt-1">Todos os pedidos desse tipo estao pagos ou finalizados</p>
                        </div>
                    @endforelse

                    @if ($filterType === 'mesa' && $filteredUnpaid->count() > 0)
                        <div class="pt-4 border-t border-neutral-800">
                            <button wire:click="closeMyTableBill" wire:loading.attr="disabled"
                                    class="w-full flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-neutral-950 font-bold transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-emerald-500/25 disabled:opacity-50">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span wire:loading.remove wire:target="closeMyTableBill">Fechar Conta da Mesa</span>
                                <span wire:loading wire:target="closeMyTableBill">Gerando PIX...</span>
                            </button>
                        </div>
                    @endif
                </div>

            {{-- TAB: History --}}
            @elseif ($clientTab === 'history' && Auth::check())
                @php
                    $typeFilter = $orderHistoryFilter ?? 'all';
                @endphp
                <div class="px-4 mt-4 space-y-6 pb-8"
                     x-init="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold">Historico de Pedidos</h2>
                        <button wire:click="switchClientTab('menu')"
                                class="text-xs text-amber-400 hover:text-amber-300 transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Voltar ao Cardapio
                        </button>
                    </div>

                    {{-- Type Filter --}}
                    <div class="flex gap-2">
                        <button wire:click="$set('orderHistoryFilter', 'all')"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ ($typeFilter === 'all') ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/25' : 'bg-neutral-900/50 border border-neutral-800 text-neutral-400 hover:text-white' }}">
                            Todas
                        </button>
                        <button wire:click="$set('orderHistoryFilter', 'mesa')"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ ($typeFilter === 'mesa') ? 'bg-emerald-500 text-neutral-950 shadow-lg shadow-emerald-500/25' : 'bg-neutral-900/50 border border-neutral-800 text-neutral-400 hover:text-white' }}">
                            Mesa
                        </button>
                        <button wire:click="$set('orderHistoryFilter', 'entrega')"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ ($typeFilter === 'entrega') ? 'bg-sky-500 text-neutral-950 shadow-lg shadow-sky-500/25' : 'bg-neutral-900/50 border border-neutral-800 text-neutral-400 hover:text-white' }}">
                            Entrega
                        </button>
                        <button wire:click="$set('orderHistoryFilter', 'retirada')"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ ($typeFilter === 'retirada') ? 'bg-purple-500 text-neutral-950 shadow-lg shadow-purple-500/25' : 'bg-neutral-900/50 border border-neutral-800 text-neutral-400 hover:text-white' }}">
                            Balcao
                        </button>
                    </div>

                    @php
                        $filteredOrders = $myOrders;
                        if ($typeFilter !== 'all') {
                            if ($typeFilter === 'mesa') {
                                $filteredOrders = $filteredOrders->where('table_id', '!=', null);
                            } else {
                                $filteredOrders = $filteredOrders->where('table_id', null)->where('type', $typeFilter);
                            }
                        }
                    @endphp

                    @forelse ($filteredOrders as $order)
                        @php
                            $orderPaid = $order->payments->where('status', 'paid')->count() > 0;
                            $isEntregue = $order->status === 'entregue';
                            $isFechado = $order->status === 'fechado';
                            $typeLabel = match($order->type) {
                                'mesa' => 'Mesa ' . ($order->table->number ?? ''),
                                'entrega' => 'Entrega',
                                'retirada' => 'Balcao',
                                default => ucfirst($order->type ?? '')
                            };
                        @endphp
                        <div class="p-4 rounded-2xl bg-neutral-900/70 border border-neutral-800/80 {{ $orderPaid ? 'border-emerald-500/10' : '' }}">
                                <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold">Pedido #{{ $order->id }}</span>
                                    <span class="text-xs text-neutral-500">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                                    <span class="text-[10px] font-medium {{ $order->type === 'mesa' ? 'bg-neutral-800 text-neutral-400 border border-neutral-700' : ($order->type === 'entrega' ? 'text-amber-400 bg-amber-500/10 border border-amber-500/20' : 'text-purple-400 bg-purple-500/10 border border-purple-500/20') }} px-1.5 py-0.5 rounded-full">
                                        {{ $typeLabel }}
                                    </span>
                                    @php
                                        $paymentMethodOnOrder = $order->payment_method ? \App\Models\Payment::PAYMENT_METHODS[$order->payment_method] ?? $order->payment_method : null;
                                        $paidPayments = $order->payments->where('status', 'paid');
                                        $hasPaid = $paidPayments->count() > 0;
                                    @endphp
                                    @if ($paymentMethodOnOrder)
                                        <span class="text-[10px] font-medium bg-neutral-800 text-neutral-400 border border-neutral-700 px-1.5 py-0.5 rounded-full">
                                            {{ $paymentMethodOnOrder }}
                                        </span>
                                    @endif
                                    @if ($isEntregue)
                                        <span class="text-[10px] font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20 px-1.5 py-0.5 rounded-full">Entregue</span>
                                    @endif
                                    @if ($hasPaid)
                                        <span class="text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-1.5 py-0.5 rounded-full">Pago</span>
                                    @else
                                        <span class="text-[10px] font-semibold bg-neutral-800 text-neutral-500 border border-neutral-700 px-1.5 py-0.5 rounded-full">Nao Pago</span>
                                    @endif
                                </div>
                                <span class="px-2.5 py-1 text-xs font-medium rounded-full border {{ $order->statusClasses() }} shrink-0">
                                    {{ $order->statusLabel() }}
                                </span>
                            </div>

                            <div class="space-y-1.5 mb-3">
                                @foreach ($order->items as $item)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-neutral-300">{{ $item->quantity }}x {{ $item->product_name }}</span>
                                        <span class="text-neutral-400">R$ {{ number_format($item->price * $item->quantity, 2, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>

                            @if ($order->notes)
                                <div class="mb-3 p-2.5 rounded-lg bg-neutral-800/30 border border-neutral-700/50 text-xs text-neutral-400 flex items-start gap-2">
                                    <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                    <span>{{ $order->notes }}</span>
                                </div>
                            @endif

                            <div class="pt-2 border-t border-neutral-800">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3 text-xs text-neutral-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                            {{ $order->items->count() }} item(ns)
                                        </span>
                                        @if (!$order->table && $order->address_json)
                                            @php $addr = is_array($order->address_json) ? $order->address_json : ['address' => $order->address_json]; @endphp
                                            <span class="flex items-start gap-1">
                                                <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <span class="text-xs text-neutral-400">
                                                    {{ $addr['street'] ?? $addr['address'] ?? '' }}
                                                    @if (!empty($addr['number'])), {{ $addr['number'] }}@endif
                                                    @if (!empty($addr['neighborhood'])) - {{ $addr['neighborhood'] }}@endif
                                                    @if (!empty($addr['city'])) - {{ $addr['city'] }}@endif
                                                    @if (!empty($addr['state']))/{{ $addr['state'] }}@endif
                                                </span>
                                            </span>
                                        @endif
                                    </div>
                                    <span class="font-bold text-amber-400">R$ {{ number_format($order->total, 2, ',', '.') }}</span>
                                </div>

                                {{-- Pagamentos --}}
                                @if ($hasPaid)
                                    @foreach ($paidPayments as $payment)
                                        <div class="mt-2 flex items-center gap-2 text-[11px] text-emerald-400/80 bg-emerald-500/5 rounded-lg px-3 py-2">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>{{ $payment->paymentMethodLabel() }} — R$ {{ number_format($payment->amount, 2, ',', '.') }} — {{ $payment->paid_at?->format('d/m H:i') }}</span>
                                        </div>
                                    @endforeach
                                @endif

                                @if ($isEntregue && !$hasPaid)
                                    <div class="mt-2 flex items-center gap-2 text-[11px] text-neutral-500 bg-neutral-800/50 rounded-lg px-3 py-2">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2-1m2 1l2-1m2 1l2-1m2-2v2a1 1 0 001 1h2m0 0a1 1 0 100 2m-2-2a1 1 0 110 2m-10-4h.01M16 12h4m0 0l-3-3m3 3l-3 3"/></svg>
                                        <span>Pedido entregue</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-16 text-neutral-600 rounded-2xl bg-neutral-900/30 border border-dashed border-neutral-800">
                            <svg class="w-14 h-14 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-sm font-medium">Nenhum pedido no historico</p>
                            <p class="text-xs text-neutral-700 mt-1">Seus pedidos finalizados aparecerao aqui</p>
                        </div>
                    @endforelse
                </div>

            {{-- TAB: Settings --}}
            @elseif ($clientTab === 'settings' && Auth::check())
                <div class="px-4 mt-4 max-w-2xl mx-auto pb-8"
                     x-init="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold">Configuracoes</h2>
                        <button wire:click="switchClientTab('menu')"
                                class="text-xs text-amber-400 hover:text-amber-300 transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Voltar ao Cardapio
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Profile --}}
                        <div class="p-5 rounded-2xl bg-neutral-900/70 border border-neutral-800/80">
                            <h3 class="text-sm font-semibold text-neutral-300 mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Meu Perfil
                            </h3>
                            <form wire:submit="saveProfile" class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-neutral-400 mb-1">Nome</label>
                                    <input wire:model="profileName" type="text"
                                           class="w-full px-3.5 py-2 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('profileName') border-red-500 @enderror">
                                    @error('profileName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                <label class="block text-xs font-medium text-neutral-400 mb-1">Email</label>
                                <input wire:model="profileEmail" type="email"
                                       class="w-full px-3.5 py-2 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('profileEmail') border-red-500 @enderror">
                                @error('profileEmail') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div
                                 x-data="{
                                     phoneDisplay: '',
                                     init() {
                                         this.$nextTick(() => {
                                             const raw = ($wire.profilePhone || '').replace(/\D/g,'').substring(0,11);
                                             this.phoneDisplay = raw ? this.maskPhone(raw) : '';
                                         });
                                     },
                                     maskPhone(v) {
                                         let r = (v||'').replace(/\D/g,'').substring(0,11);
                                         return r.length <= 2 ? (r.length ? '('+r : '') :
                                                r.length <= 6 ? '('+r.substring(0,2)+') '+r.substring(2) :
                                                r.length <= 7 ? '('+r.substring(0,2)+') '+r.substring(2,7) :
                                                '('+r.substring(0,2)+') '+r.substring(2,7)+'-'+r.substring(7);
                                     },
                                     updatePhone(v) {
                                         const raw = (v||'').replace(/\D/g,'').substring(0,11);
                                         this.phoneDisplay = this.maskPhone(raw);
                                         $wire.set('profilePhone', raw);
                                     }
                                 }">
                                 <label class="block text-xs font-medium text-neutral-400 mb-1">Telefone</label>
                                 <input x-model="phoneDisplay" @input="updatePhone(phoneDisplay)" type="tel" inputmode="numeric" maxlength="15" placeholder="(11) 99999-9999"
                                        class="w-full px-3.5 py-2 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('profilePhone') border-red-500 @enderror">
                                 @error('profilePhone') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                             </div>
                                 <div>
                                     <label class="block text-xs font-medium text-neutral-400 mb-1">Nova Senha (opcional)</label>
                                    <input wire:model="profilePassword" type="password" placeholder="Minimo 6 caracteres"
                                           class="w-full px-3.5 py-2 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('profilePassword') border-red-500 @enderror">
                                    @error('profilePassword') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-neutral-400 mb-1">Confirmar Senha</label>
                                    <input wire:model="profilePasswordConfirmation" type="password" placeholder="Repita a senha"
                                           class="w-full px-3.5 py-2 rounded-xl bg-neutral-800 border border-neutral-700 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('profilePasswordConfirmation') border-red-500 @enderror">
                                    @error('profilePasswordConfirmation') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                                </div>
                                <button type="submit" wire:loading.attr="disabled"
                                         class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all text-sm hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 flex items-center gap-2">
                                    <span wire:loading.remove>Salvar</span>
                                    <span wire:loading class="flex items-center gap-1"><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>Salvando...</span>
                                </button>
                            </form>
                        </div>

                        {{-- Addresses --}}
                        <div class="p-5 rounded-2xl bg-neutral-900/70 border border-neutral-800/80">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-neutral-300 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Enderecos
                                    @if ($myAddresses->count() > 0)
                                        <span class="text-xs text-neutral-500 font-normal">({{ $myAddresses->count() }}/5)</span>
                                    @endif
                                </h3>
                                @if ($myAddresses->count() < 5)
                                    <button wire:click="openAddressModal" wire:loading.attr="disabled"
                                             class="flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all disabled:opacity-50">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        Novo
                                    </button>
                                @endif
                            </div>
                            @if ($myAddresses->count() === 0)
                                <div class="text-center py-6 text-neutral-500">
                                    <p class="text-xs text-neutral-400">Nenhum endereco salvo</p>
                                </div>
                            @else
                                <div class="space-y-2">
                                    @foreach ($myAddresses as $address)
                                        <div class="p-2.5 rounded-xl {{ $address->is_default ? 'bg-amber-500/5 border border-amber-500/20' : 'bg-neutral-800/50 border border-neutral-700/50' }}">
                                            <div class="flex items-start justify-between gap-1">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-1.5 mb-0.5">
                                                        <span class="text-xs font-semibold text-neutral-200">{{ $address->label }}</span>
                                                        @if ($address->is_default)
                                                            <span class="px-1 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/20 text-amber-400">Padrao</span>
                                                        @endif
                                                    </div>
                                                    <p class="text-[11px] text-neutral-400 truncate">{{ $address->summary }}</p>
                                                </div>
                                                <div class="flex items-center gap-0.5 shrink-0">
                                                    @if (!$address->is_default)
                                                        <button wire:click="setDefaultAddress({{ $address->id }})" wire:loading.attr="disabled" class="p-1 rounded text-neutral-500 hover:text-amber-400 disabled:opacity-30">✓</button>
                                                    @endif
                                                    <button wire:click="openAddressModal({{ $address->id }})" wire:loading.attr="disabled" class="p-1 rounded text-neutral-500 hover:text-blue-400 disabled:opacity-30">✎</button>
                                                    <button wire:click="confirmDeleteAddress({{ $address->id }})" wire:loading.attr="disabled" class="p-1 rounded text-neutral-500 hover:text-red-400 disabled:opacity-30">✕</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- LGPD Privacy --}}
                    <div class="mt-4 p-5 rounded-2xl bg-neutral-900/70 border border-neutral-800/80">
                        <h3 class="text-sm font-semibold text-neutral-300 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Privacidade (LGPD)
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 rounded-xl bg-neutral-800/50 border border-neutral-700/50">
                                <div>
                                    <p class="text-xs font-semibold text-neutral-200">Exportar meus dados</p>
                                    <p class="text-[11px] text-neutral-500">Baixe um JSON com seus dados pessoais</p>
                                </div>
                                <button wire:click="exportData" wire:loading.attr="disabled"
                                         class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-neutral-700 hover:bg-neutral-600 text-neutral-300 transition-all disabled:opacity-50 flex items-center gap-1">
                                    <span wire:loading.remove>Exportar</span>
                                    <span wire:loading><svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                                </button>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-neutral-800/50 border border-red-500/10">
                                <div>
                                    <p class="text-xs font-semibold text-red-400">Excluir minha conta</p>
                                    <p class="text-[11px] text-neutral-500">Remove seu usuario e anonimiza pedidos</p>
                                </div>
                                <button wire:click="confirmDeleteAccount" wire:loading.attr="disabled"
                                         class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-all disabled:opacity-50">
                                    Excluir
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="text-[10px] text-neutral-600 text-center mt-4">
                        Ao utilizar nossos servicos, voce concorda com o tratamento de seus dados conforme a Lei Geral de Protecao de Dados (LGPD).
                    </p>
                </div>

                {{-- Address Modal --}}
                <div x-data="{
                    open: @entangle('showAddressModal'),
                    viaCepLoading: false,
                    searchCep(event) {
                        let cep = (event?.target?.value || '').replace(/\D/g, '');
                        if (cep.length !== 8) return;
                        this.viaCepLoading = true;
                        $wire.lookupCep(cep).then(() => this.viaCepLoading = false);
                    }
                }"
                     x-show="open" x-cloak
                     class="fixed inset-0 z-[70] flex items-center justify-center p-4"
                     @keydown.window.escape="$wire.closeAddressModal()">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-lg" wire:click="closeAddressModal"></div>
                    <div class="relative w-full max-w-lg bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-800">
                            <h3 class="font-bold text-sm">{{ $editingAddressId ? 'Editar' : 'Novo' }} Endereco</h3>
                            <button wire:click="closeAddressModal" class="p-1 rounded-lg hover:bg-neutral-800 text-neutral-400">✕</button>
                        </div>
                        <form wire:submit="saveAddress" class="p-5 space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="col-span-3 relative">
                                    <label class="block text-xs font-medium text-neutral-400 mb-1">CEP</label>
                                    <input wire:model="addrZipcode" type="text" placeholder="00000-000" maxlength="9"
                                           x-on:blur="searchCep"
                                           x-on:input="if ($event.target.value.replace(/\D/g, '').length === 8) searchCep($event)"
                                           class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                    <div x-show="viaCepLoading" class="absolute right-2.5 top-7"><svg class="w-4 h-4 animate-spin text-amber-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></div>
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-xs font-medium text-neutral-400 mb-1">Identificacao</label>
                                    <select wire:model="addrLabel" class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                        <option value="Casa">Casa</option>
                                        <option value="Trabalho">Trabalho</option>
                                        <option value="Outro">Outro</option>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-neutral-400 mb-1">Logradouro</label>
                                    <input wire:model="addrAddress" type="text" placeholder="Rua, Avenida..." class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 @error('addrAddress') border-red-500 @enderror">
                                    @error('addrAddress') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-neutral-400 mb-1">Numero</label>
                                    <input wire:model="addrNumber" type="text" placeholder="N°" class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-neutral-400 mb-1">Complemento</label>
                                    <input wire:model="addrComplement" type="text" placeholder="Apto, Bloco..." class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-neutral-400 mb-1">Bairro</label>
                                    <input wire:model="addrNeighborhood" type="text" placeholder="Bairro" class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-neutral-400 mb-1">Cidade</label>
                                    <input wire:model="addrCity" type="text" placeholder="Cidade" class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 @error('addrCity') border-red-500 @enderror">
                                    @error('addrCity') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-neutral-400 mb-1">Estado</label>
                                    <input wire:model="addrState" type="text" placeholder="UF" class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 @error('addrState') border-red-500 @enderror">
                                    @error('addrState') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-xs font-medium text-neutral-400 mb-1">Referencia</label>
                                    <input wire:model="addrReference" type="text" placeholder="Proximo a..." class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                </div>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-neutral-300 cursor-pointer">
                                <input type="checkbox" wire:model="addrIsDefault" class="w-4 h-4 rounded bg-neutral-950 border-neutral-700 text-amber-500 focus:ring-amber-500">
                                Definir como padrao
                            </label>
                            <div class="flex items-center justify-end gap-3 pt-2 border-t border-neutral-800">
                                <button type="button" wire:click="closeAddressModal" class="px-4 py-2 text-sm text-neutral-400 hover:text-white">Cancelar</button>
                                <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl text-sm disabled:opacity-50 flex items-center gap-1">
                                    <span wire:loading.remove>{{ $editingAddressId ? 'Atualizar' : 'Salvar' }}</span>
                                    <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Delete Address Confirmation --}}
                <div x-data="{ open: @entangle('confirmDeleteAddressId') }"
                     x-show="open" x-cloak
                     class="fixed inset-0 z-[80] flex items-center justify-center p-4"
                     @keydown.window.escape="$wire.cancelDeleteAddress()">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-lg" wire:click="cancelDeleteAddress"></div>
                    <div class="relative w-full max-w-sm bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6 text-center">
                        <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-red-500/10 flex items-center justify-center"><svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg></div>
                        <h3 class="font-bold text-neutral-200 mb-2">Remover Endereco?</h3>
                        <p class="text-sm text-neutral-400 mb-6">Esta acao nao pode ser desfeita.</p>
                        <div class="flex items-center justify-center gap-3">
                            <button wire:click="cancelDeleteAddress" class="px-5 py-2 text-sm text-neutral-400 hover:text-white rounded-xl hover:bg-neutral-800">Cancelar</button>
                            <button wire:click="deleteAddress" wire:loading.attr="disabled" class="px-5 py-2 text-sm font-semibold bg-red-500 hover:bg-red-400 text-white rounded-xl disabled:opacity-50 flex items-center gap-1">
                                <span wire:loading.remove>Remover</span>
                                <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Delete Account Confirmation --}}
                <div x-data="{ open: @entangle('showDeleteAccountConfirm') }"
                     x-show="open" x-cloak
                     class="fixed inset-0 z-[80] flex items-center justify-center p-4"
                     @keydown.window.escape="$wire.cancelDeleteAccount()">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-lg" wire:click="cancelDeleteAccount"></div>
                    <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg></div>
                            <div>
                                <h3 class="text-lg font-bold text-red-400">Excluir conta</h3>
                                <p class="text-xs text-neutral-500">Esta acao nao pode ser desfeita</p>
                            </div>
                        </div>
                        <p class="text-sm text-neutral-300 mb-4">Seus pedidos serao anonimizados e sua conta removida.</p>
                        <form wire:submit="deleteMyAccount" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-400 mb-2">Digite <span class="font-bold text-red-400">EXCLUIR</span> para confirmar</label>
                                <input wire:model="deleteConfirmation" type="text" placeholder="EXCLUIR" class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-700 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-red-500">
                                @error('deleteConfirmation') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex gap-2">
                                <button type="button" wire:click="cancelDeleteAccount" class="flex-1 px-4 py-2.5 text-sm font-medium rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300">Cancelar</button>
                                <button type="submit" wire:loading.attr="disabled" class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-xl bg-red-500 hover:bg-red-400 text-white disabled:opacity-50 flex items-center justify-center gap-1">
                                    <span wire:loading.remove>Excluir Conta</span>
                                    <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            {{-- TAB: Support --}}
            @elseif ($clientTab === 'support' && Auth::check())
                <div class="px-4 mt-4 max-w-2xl mx-auto pb-8"
                     x-init="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold">Suporte</h2>
                        <button wire:click="switchClientTab('menu')"
                                class="text-xs text-amber-400 hover:text-amber-300 transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Voltar ao Cardapio
                        </button>
                    </div>

                    {{-- Support Tabs: Meus Tickets / Novo Ticket --}}
                    <div class="flex gap-1 p-1 rounded-xl bg-neutral-900 border border-neutral-800 overflow-x-auto mb-6">
                        <button wire:click="$set('supportTab', 'my_tickets')"
                                class="flex-1 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $supportTab === 'my_tickets' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' }}">
                            Meus Tickets
                        </button>
                        <button wire:click="$set('supportTab', 'new_ticket')"
                                class="flex-1 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $supportTab === 'new_ticket' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' }}">
                            Abrir Ticket
                        </button>
                    </div>

                    @if ($supportTab === 'my_tickets')
                        {{-- Ticket List --}}
                        @if ($supportViewingTicketId)
                            {{-- Ticket Detail --}}
                            <button wire:click="supportBackToList" class="flex items-center gap-2 text-sm text-neutral-400 hover:text-white transition-colors mb-4">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Voltar
                            </button>

                            <div class="p-4 rounded-2xl bg-neutral-900/70 border border-neutral-800/80 mb-4">
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <h3 class="text-sm font-bold">{{ $supportViewingTicket['subject'] ?? '' }}</h3>
                                    <span class="shrink-0 px-2.5 py-1 text-[10px] font-semibold rounded-full {{ $supportViewingTicket['statusClasses'] ?? '' }}">
                                        {{ $supportViewingTicket['statusLabel'] ?? '' }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-xs text-neutral-400">
                                    <span class="px-2 py-0.5 rounded bg-neutral-800/50">{{ $supportViewingTicket['categoryLabel'] ?? '' }}</span>
                                    <span class="px-2 py-0.5 rounded {{ $supportViewingTicket['priorityClasses'] ?? '' }}">{{ $supportViewingTicket['priorityLabel'] ?? '' }}</span>
                                    <span>Aberto: {{ $supportViewingTicket['created_at'] ?? '' }}</span>
                                </div>
                            </div>

                            <div class="space-y-3 mb-4">
                                @foreach (($supportViewingTicket['messages'] ?? []) as $msg)
                                    <div class="flex {{ $msg['author_role'] === 'cliente' ? 'justify-end' : 'justify-start' }}">
                                        <div class="max-w-[80%] p-3 rounded-2xl {{ $msg['author_role'] === 'cliente' ? 'bg-amber-500/10 border border-amber-500/20' : 'bg-neutral-800/50 border border-neutral-700/50' }}">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-medium {{ $msg['author_role'] === 'cliente' ? 'text-amber-400' : 'text-neutral-300' }}">
                                                    {{ $msg['author_name'] ?? 'Equipe' }}
                                                </span>
                                                <span class="text-[10px] text-neutral-500">{{ $msg['created_at'] }}</span>
                                            </div>
                                            <p class="text-sm text-neutral-200 whitespace-pre-wrap">{{ $msg['body'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if (!in_array(($supportViewingTicket['status'] ?? ''), ['resolvido', 'fechado']))
                                <div class="p-4 rounded-2xl bg-neutral-900/70 border border-neutral-800/80">
                                    <textarea wire:model="supportReplyBody" rows="3"
                                              class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm resize-none"
                                              placeholder="Digite sua resposta..."></textarea>
                                    @error('supportReplyBody') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                                    <div class="flex items-center justify-between mt-3">
                                        <button wire:click="supportCloseTicket({{ $supportViewingTicket['id'] ?? 0 }})"
                                                class="text-xs text-neutral-500 hover:text-red-400 transition-colors">
                                            Fechar Ticket
                                        </button>
                                        <button wire:click="supportSendReply" wire:loading.attr="disabled"
                                                class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all disabled:opacity-50">
                                            <span wire:loading.remove>Enviar Resposta</span>
                                            <span wire:loading>Enviando...</span>
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="p-4 rounded-2xl bg-neutral-900/70 border border-neutral-800/80 text-center">
                                    <p class="text-sm text-neutral-400">Ticket {{ $supportViewingTicket['statusLabel'] ?? '' }}.</p>
                                </div>
                            @endif
                        @else
                            @forelse ($this->supportMyTickets as $ticket)
                                <div wire:key="st-{{ $ticket->id }}"
                                     class="p-4 rounded-2xl bg-neutral-900/70 border border-neutral-800/80 mb-3 hover:border-neutral-700 transition-colors cursor-pointer"
                                     wire:click="supportViewTicket({{ $ticket->id }})">
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <h3 class="text-sm font-semibold">{{ $ticket->subject }}</h3>
                                        <span class="shrink-0 px-2.5 py-1 text-[10px] font-semibold rounded-full {{ $ticket->statusClasses() }}">
                                            {{ $ticket->statusLabel() }}
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 text-[10px] text-neutral-500">
                                        <span class="px-2 py-0.5 rounded bg-neutral-800/50">{{ $ticket->categoryLabel() }}</span>
                                        <span class="px-2 py-0.5 rounded {{ $ticket->priorityClasses() }}">{{ $ticket->priorityLabel() }}</span>
                                        <span>{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-16">
                                    <svg class="w-14 h-14 mx-auto text-neutral-700 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                                    </svg>
                                    <p class="text-neutral-400 mb-4">Nenhum ticket aberto.</p>
                                    <button wire:click="$set('supportTab', 'new_ticket')"
                                            class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all">
                                        Abrir Ticket
                                    </button>
                                </div>
                            @endforelse
                        @endif
                    @else
                        {{-- New Ticket Form --}}
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-300 mb-1.5">Assunto</label>
                                <input type="text" wire:model="supportNewSubject"
                                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                                       placeholder="Resumo do problema" maxlength="200">
                                @error('supportNewSubject') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-neutral-300 mb-1.5">Categoria</label>
                                    <select wire:model="supportNewCategory"
                                            class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm">
                                        @foreach (\App\Models\SupportTicket::CATEGORY_LABELS as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-300 mb-1.5">Prioridade</label>
                                    <select wire:model="supportNewPriority"
                                            class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm">
                                        @foreach (\App\Models\SupportTicket::PRIORITY_LABELS as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-neutral-300 mb-1.5">Descreva o problema</label>
                                <textarea wire:model="supportNewBody" rows="5"
                                          class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm resize-none min-h-[120px]"
                                          placeholder="Conte-nos detalhadamente o que esta acontecendo..."></textarea>
                                @error('supportNewBody') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex justify-end pt-2">
                                <button wire:click="supportOpenTicket" wire:loading.attr="disabled"
                                        class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 text-sm font-semibold transition-all disabled:opacity-50">
                                    <span wire:loading.remove>Enviar Ticket</span>
                                    <span wire:loading>Enviando...</span>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

            {{-- TAB: Pontos --}}
            @elseif ($clientTab === 'pontos' && Auth::check() && $pointsVisible)
                <div class="px-4 mt-4 max-w-2xl mx-auto pb-8"
                     x-init="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold">Meus Pontos</h2>
                        <button wire:click="switchClientTab('menu')"
                                class="text-xs text-emerald-400 hover:text-emerald-300 transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Voltar ao Cardapio
                        </button>
                    </div>

                    {{-- Points Balance Card --}}
                    <div class="p-5 rounded-2xl bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border border-emerald-500/10 mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-emerald-400/70 font-medium uppercase tracking-wider">Saldo de Pontos</p>
                                <p class="text-3xl font-black text-emerald-400 mt-1">{{ number_format($pointsBalance, 0, ',', '.') }}</p>
                                <p class="text-xs text-neutral-400 mt-1">equivalem a <strong class="text-amber-400">R$ {{ number_format($pointsBalance / 100, 2, ',', '.') }}</strong> em descontos no carrinho</p>
                            </div>
                            <button @click="$dispatch('open-cart')"
                                    class="px-4 py-2.5 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all text-sm hover:scale-[1.02] active:scale-[0.98] shrink-0">
                                Usar no Pedido
                            </button>
                        </div>
                        <div class="mt-3 pt-3 border-t border-emerald-500/10 flex items-center gap-2 text-xs text-neutral-400">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            <span>Ganhe <strong class="text-emerald-400">{{ $pointsPercentageData }}%</strong> de volta em pontos a cada pedido</span>
                        </div>
                    </div>

                    {{-- Products Exchangeable for Points --}}
                    <div>
                        <h3 class="text-sm font-semibold text-neutral-300 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            Produtos Disponiveis para Troca
                        </h3>

                        @if ($pointsProducts->count() === 0)
                            <div class="text-center py-12 text-neutral-600 rounded-2xl bg-neutral-900/30 border border-dashed border-neutral-800">
                                <svg class="w-14 h-14 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-sm font-medium text-neutral-400">Nenhum produto disponivel para troca</p>
                                <p class="text-xs text-neutral-600 mt-1">Em breve novos produtos estarao disponiveis</p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach ($pointsProducts as $product)
                                    <div class="p-4 rounded-2xl bg-neutral-900/70 border border-neutral-800/80 hover:border-emerald-500/30 transition-all duration-300 group">
                                        <div class="flex gap-4">
                                            <div class="w-20 h-20 rounded-xl overflow-hidden shrink-0 bg-neutral-800/50 relative">
                                                <img src="{{ $product->imageUrl() }}"
                                                     alt="{{ $product->name }}"
                                                     class="w-full h-full object-cover transition-all duration-500 group-hover:scale-110"
                                                     loading="lazy">
                                                @if ($product->isOutOfStock())
                                                    <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                                                        <span class="text-[9px] font-bold text-red-400 bg-red-500/20 px-1.5 py-0.5 rounded-md">SEM ESTOQUE</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0 flex flex-col justify-between">
                                                <div>
                                                    <h4 class="font-semibold text-sm group-hover:text-emerald-400 transition-colors">{{ $product->name }}</h4>
                                                    @if ($product->description)
                                                        <p class="text-xs text-neutral-400 mt-1 line-clamp-2">{{ $product->description }}</p>
                                                    @endif
                                                </div>
                                                    <div class="flex items-center justify-between mt-2">
                                                        <div>
                                                            @php $ptsPrice = (int) ($product->points_price ?? 0); @endphp
                                                            <span class="text-lg font-bold text-emerald-400">{{ number_format($ptsPrice, 0, ',', '.') }}</span>
                                                            <span class="text-xs text-neutral-500"> pontos</span>
                                                        </div>
                                                        @if ($product->isOutOfStock())
                                                            <span class="text-xs text-red-400">Sem estoque</span>
                                                        @elseif ($ptsPrice > 0 && $pointsBalance >= $ptsPrice)
                                                            <button wire:click="redeemProductWithPoints({{ $product->id }})"
                                                                    wire:loading.attr="disabled"
                                                                    wire:target="redeemProductWithPoints({{ $product->id }})"
                                                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500 hover:text-neutral-950 disabled:opacity-50">
                                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                                <span wire:loading.remove wire:target="redeemProductWithPoints({{ $product->id }})">Resgatar</span>
                                                                <span wire:loading wire:target="redeemProductWithPoints({{ $product->id }})">...</span>
                                                            </button>
                                                        @elseif ($ptsPrice > 0 && $pointsBalance < $ptsPrice)
                                                            <span class="text-xs text-neutral-500">Pontos insuficientes</span>
                                                        @else
                                                            <span class="text-xs text-neutral-600">Indisponivel</span>
                                                        @endif
                                                    </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ($pointsBalance > 0)
                        <div class="mt-6 p-4 rounded-2xl bg-amber-500/5 border border-amber-500/10">
                            <p class="text-sm text-neutral-300 flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Quer usar seus pontos como desconto?
                                <button @click="$dispatch('open-cart')" class="text-amber-400 hover:text-amber-300 underline font-medium">Clique aqui</button>
                            </p>
                        </div>
                    @endif
                </div>
            @elseif ($clientTab === 'favoritos' && Auth::check())
                <div class="px-4 mt-4 max-w-4xl mx-auto pb-8"
                     x-init="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold">Meus Favoritos</h2>
                        <button wire:click="switchClientTab('menu')"
                                class="text-xs text-red-400 hover:text-red-300 transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Voltar ao Cardapio
                        </button>
                    </div>

                    @if ($favoriteProducts->count() === 0)
                        <div class="text-center py-16 text-neutral-600 rounded-2xl bg-neutral-900/30 border border-dashed border-neutral-800">
                            <svg class="w-14 h-14 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <p class="text-sm font-medium">Nenhum favorito ainda</p>
                            <p class="text-xs text-neutral-600 mt-1">Toque no <svg class="w-3.5 h-3.5 inline-block text-red-400" fill="currentColor" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg> nos produtos que voce mais gosta</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($favoriteProducts as $product)
                                <div wire:key="fav-{{ $product->id }}"
                                     class="p-4 rounded-2xl bg-neutral-900/70 border border-neutral-800/80 hover:border-red-500/30 transition-all duration-300 group">
                                    <div class="flex gap-4">
                                        <button wire:click="showProduct({{ $product->id }})" class="w-20 h-20 rounded-xl overflow-hidden shrink-0 bg-neutral-800/50 relative">
                                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="w-full h-full object-cover" loading="lazy">
                                            @if ($product->isOutOfStock())
                                                <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                                                    <span class="text-[9px] font-bold text-red-400 bg-red-500/20 px-1.5 py-0.5 rounded-md">SEM ESTOQUE</span>
                                                </div>
                                            @endif
                                        </button>
                                        <div class="flex-1 min-w-0">
                                            <button wire:click="showProduct({{ $product->id }})" class="text-left w-full">
                                                <h4 class="font-semibold text-sm group-hover:text-red-400 transition-colors">{{ $product->name }}</h4>
                                                @if ($product->description)
                                                    <p class="text-xs text-neutral-400 mt-1 line-clamp-1">{{ $product->description }}</p>
                                                @endif
                                            </button>
                                            <div class="flex items-center justify-between mt-2">
                                                <p class="text-sm font-bold text-amber-400">R$ {{ number_format($product->price, 2, ',', '.') }}</p>
                                                @if ($product->isOutOfStock())
                                                    <span class="text-[10px] text-neutral-500">Indisponivel</span>
                                                @elseif (!$product->attributes->count())
                                                    <button @click.stop
                                                            @click="$wire.$dispatchTo('public.cart', 'addToCart', {productId: {{ $product->id }}, productName: @js($product->name), price: {{ $product->price }}, selectedOptions: [], quantity: 1})"
                                                            class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[10px] font-semibold bg-amber-500/10 text-amber-400 hover:bg-amber-500 hover:text-neutral-950 transition-all">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                        Adicionar
                                                    </button>
                                                @else
                                                    <button wire:click="showProduct({{ $product->id }})" class="text-[10px] text-neutral-500 flex items-center gap-1 hover:text-amber-400 transition-colors">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        Personalizar
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                        <button wire:click="toggleFavorite({{ $product->id }})"
                                                class="self-start w-7 h-7 rounded-full flex items-center justify-center bg-red-500/20 text-red-400 hover:bg-red-500/30 transition-all shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- CDC Policy Footer --}}
            @if ($clientTab === 'menu')
            <div class="px-4 mt-8 mb-4 max-w-4xl mx-auto"
                 x-data="{ cdcOpen: false }">
                <button @click="cdcOpen = !cdcOpen"
                        class="w-full flex items-center justify-between gap-2 px-4 py-3 rounded-xl bg-neutral-900/50 border border-neutral-800 hover:border-amber-500/30 transition-all text-left">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span class="text-xs font-medium text-neutral-300">Codigo de Defesa do Consumidor (CDC)</span>
                    </div>
                    <svg class="w-4 h-4 text-neutral-500 transition-transform duration-200" :class="cdcOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="cdcOpen" x-collapse.duration.200ms
                     class="mt-2 p-4 rounded-xl bg-neutral-900/30 border border-neutral-800/50 text-xs text-neutral-400 leading-relaxed space-y-3">
                    <p><strong class="text-neutral-300">Antes do preparo:</strong> E possivel cancelar facilmente pelo aplicativo.</p>
                    <p><strong class="text-neutral-300">Durante o preparo ou a caminho:</strong> O cancelamento pode ficar sujeito a aprovacao do restaurante, pois o alimento e perecivel.</p>
                    <p><strong class="text-neutral-300">Atrasos ou problemas:</strong> Se o prazo estipulado for ultrapassado ou o lanche chegar errado/danificado, o cliente pode cancelar sem custos.</p>
                </div>
            </div>
            @endif

            {{-- Floating Support Button --}}
            @auth
            <div class="fixed bottom-6 right-4 z-30">
                <button wire:click="switchClientTab('support')"
                        class="flex items-center gap-2 px-4 py-3 rounded-full bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold text-sm shadow-xl shadow-amber-500/30 hover:scale-105 active:scale-95 transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                    </svg>
                    <span class="hidden sm:inline">Ajuda</span>
                </button>
            </div>
            @endauth

            {{-- PIX Payment Modal --}}
            <div x-data="{ open: @entangle('showPixModal'), openedAt: 0 }"
                 x-init="$watch('open', val => { document.body.style.overflow = val ? 'hidden' : ''; if (val) openedAt = Date.now(); })"
                 x-show="open"
                 x-transition:enter="transition-opacity duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-[90] bg-black/60 backdrop-blur-lg flex items-center justify-center p-3 sm:p-4"
                 x-cloak>
                <div class="absolute inset-0" @click="Date.now() - openedAt > 1500 && $wire.closePixModal()"></div>
                <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-3xl p-6 shadow-2xl"
                     @click.stop>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold">Pagamento PIX</h3>
                        <button wire:click="closePixModal" class="p-1.5 rounded-lg hover:bg-neutral-800 text-neutral-400">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div wire:poll.5s.visible="verifyPixPayment">
                        @if ($generatingPix)
                            <div class="flex flex-col items-center py-8">
                                <svg class="w-12 h-12 animate-spin text-amber-400 mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <p class="text-sm text-neutral-400">Gerando QR Code PIX...</p>
                            </div>
                        @elseif ($pixPaymentConfirmed)
                            <div class="flex flex-col items-center py-8">
                                <svg class="w-16 h-16 text-emerald-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-lg font-bold text-emerald-400">Pagamento Confirmado!</p>
                                <p class="text-sm text-neutral-400 mt-1">Pedido #{{ $pixOrderId }} pago.</p>
                            </div>
                        @elseif ($pixQrCode)
                            <div class="space-y-4" wire:ignore>
                                <p class="text-sm text-neutral-400 text-center">Escaneie o QR Code para pagar</p>
                                <div x-data="{ remaining: 3600, timer: null }"
                                     x-init="timer = setInterval(() => { remaining > 0 ? remaining-- : clearInterval(timer) }, 1000)">
                                    <div class="flex justify-center">
                                        <img src="data:image/png;base64,{{ $pixQrCode }}" alt="QR Code PIX" class="w-56 h-56 rounded-2xl bg-white p-2">
                                    </div>
                                    <div class="flex items-center justify-center gap-2 mt-3 text-xs"
                                         :class="remaining > 300 ? 'text-neutral-500' : remaining > 60 ? 'text-amber-400' : 'text-red-400'">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span x-text="'Expira em ' + Math.floor(remaining / 60) + 'min ' + (remaining % 60) + 's'"></span>
                                    </div>
                                </div>
                                @if ($pixCopiaECola)
                                    <div class="bg-neutral-800 rounded-xl p-3">
                                        <p class="text-xs text-neutral-400 mb-1">PIX Copia e Cola</p>
                                        <div class="flex gap-2">
                                            <input type="text" readonly value="{{ $pixCopiaECola }}"
                                                   class="flex-1 bg-neutral-700 text-xs text-neutral-300 rounded-lg px-3 py-2 border border-neutral-600 select-all"
                                                   onclick="this.select()">
                                            <button onclick="navigator.clipboard.writeText('{{ $pixCopiaECola }}'); this.textContent='Copiado!'; setTimeout(()=>this.textContent='Copiar', 2000)"
                                                    class="px-3 py-2 bg-amber-500 hover:bg-amber-400 text-neutral-950 text-xs font-semibold rounded-lg transition-all shrink-0">
                                                Copiar
                                            </button>
                                        </div>
                                    </div>
                                @endif
                                <div class="flex items-center justify-center gap-2 text-xs text-neutral-500">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Codigo valido por 60 minutos</span>
                                </div>
                            </div>
                        @elseif ($pixPaymentError)
                            <div class="flex flex-col items-center py-8">
                                <p class="text-sm text-red-400">Erro ao gerar PIX. Tente novamente.</p>
                                @if ($pixPaymentErrorMsg)
                                    <p class="text-xs text-neutral-500 mt-1">{{ $pixPaymentErrorMsg }}</p>
                                @endif
                            </div>
                        @endif
                        </div>
                    <div class="mt-4 flex gap-3">
                        <button wire:click="closePixModal"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all text-sm">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Close Table PIX Modal --}}
            <div x-data="{ open: @entangle('showCloseTablePix') }"
                 x-init="$watch('open', val => document.body.style.overflow = val ? 'hidden' : '')"
                 x-show="open"
                 x-transition:enter="transition-opacity duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-[90] bg-black/60 backdrop-blur-lg flex items-center justify-center p-3 sm:p-4"
                 x-cloak>
                <div class="absolute inset-0" wire:click="cancelTablePixPayment"></div>
                <div class="relative w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-3xl p-6 shadow-2xl"
                     @click.stop>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold">Pagamento PIX - Mesa {{ $selectedTableNumber }}</h3>
                        <button wire:click="cancelTablePixPayment" class="p-1.5 rounded-lg hover:bg-neutral-800 text-neutral-400">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    @if ($generatingTablePix)
                        <div class="flex flex-col items-center py-8">
                            <svg class="w-12 h-12 animate-spin text-amber-400 mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <p class="text-sm text-neutral-400">Gerando QR Code PIX...</p>
                        </div>
                    @elseif ($closeTablePixQr)
                        <div class="space-y-4">
                            <p class="text-lg font-bold text-amber-400 text-center">R$ {{ number_format($tableBillTotal, 2, ',', '.') }}</p>
                            <p class="text-sm text-neutral-400 text-center">Escaneie o QR Code para pagar</p>
                            <div class="flex justify-center">
                                <img src="data:image/png;base64,{{ $closeTablePixQr }}" alt="QR Code PIX" class="w-56 h-56 rounded-2xl bg-white p-2">
                            </div>
                            @if ($closeTablePixCopia)
                                <div class="bg-neutral-800 rounded-xl p-3">
                                    <p class="text-xs text-neutral-400 mb-1">PIX Copia e Cola</p>
                                    <div class="flex gap-2">
                                        <input type="text" readonly value="{{ $closeTablePixCopia }}"
                                               class="flex-1 bg-neutral-700 text-xs text-neutral-300 rounded-lg px-3 py-2 border border-neutral-600 select-all"
                                               onclick="this.select()">
                                        <button onclick="navigator.clipboard.writeText('{{ $closeTablePixCopia }}'); this.textContent='Copiado!'; setTimeout(()=>this.textContent='Copiar', 2000)"
                                                class="px-3 py-2 bg-amber-500 hover:bg-amber-400 text-neutral-950 text-xs font-semibold rounded-lg transition-all shrink-0">
                                            Copiar
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="mt-6 flex gap-3">
                            <button wire:click="cancelTablePixPayment"
                                    class="flex-1 px-4 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all text-sm">
                                Cancelar
                            </button>
                            <button wire:click="confirmTablePixPayment"
                                    wire:loading.attr="disabled"
                                    class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-neutral-950 font-semibold transition-all text-sm">
                                <span wire:loading.remove>Paguei - Confirmar</span>
                                <span wire:loading>Confirmando...</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Table Picker Modal --}}
            @if ($showTablePicker)
                <div class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-4"
                     @keydown.window.escape="$wire.set('showTablePicker', false)">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-lg"
                         wire:click="$set('showTablePicker', false)"></div>
                    <div class="relative w-full max-w-lg bg-neutral-900 border border-neutral-800 rounded-t-3xl sm:rounded-2xl shadow-2xl shadow-black/60 max-h-[80vh] flex flex-col overflow-hidden">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-800 shrink-0">
                            <h3 class="text-lg font-bold">Selecione sua Mesa</h3>
                            <button wire:click="$set('showTablePicker', false)"
                                    class="p-1.5 rounded-lg hover:bg-neutral-800 text-neutral-400 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-6">
                            @if ($availableTables->count() === 0)
                                <div class="text-center py-8 text-neutral-500">
                                    <p class="text-sm">Nenhuma mesa disponivel</p>
                                </div>
                            @else
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    @foreach ($availableTables as $table)
                                        <button wire:click="selectTable({{ $table->id }})"
                                                 wire:loading.attr="disabled"
                                                 class="p-4 rounded-2xl border-2 text-center transition-all duration-200 hover:scale-[1.03] active:scale-[0.97] {{ $table->status === 'free' ? 'bg-emerald-500/5 border-emerald-500/30 hover:border-emerald-500/60' : 'bg-red-500/5 border-red-500/30 hover:border-red-500/60' }} {{ $selectedTableId === $table->id ? 'ring-2 ring-amber-500' : '' }} disabled:opacity-60">
                                            <p class="text-2xl font-black {{ $table->status === 'free' ? 'text-emerald-400' : 'text-red-400' }}">{{ $table->number }}</p>
                                            <p class="text-xs mt-1 {{ $table->status === 'free' ? 'text-emerald-400' : 'text-red-400' }}">
                                                {{ $table->status === 'free' ? 'Livre' : 'Ocupada' }}
                                            </p>
                                            <p class="text-[10px] text-neutral-500 mt-0.5">Cap. {{ $table->capacity }}</p>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- QR Code Modal --}}
            @if ($selectedTableId && $selectedTableToken && $showQrModal)
                <div class="fixed inset-0 z-[60] flex items-center justify-center p-4"
                     @keydown.window.escape="$wire.set('showQrModal', false)">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-lg"
                         wire:click="$set('showQrModal', false)"></div>
                    <div class="relative w-full max-w-sm bg-neutral-900 border border-neutral-800 rounded-3xl shadow-2xl shadow-black/60 p-8 text-center">
                        <button wire:click="$set('showQrModal', false)"
                                class="absolute top-4 right-4 p-1.5 rounded-lg hover:bg-neutral-800 text-neutral-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>

                        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-amber-500/10 flex items-center justify-center">
                            <svg class="w-8 h-8 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M12 4h8M4 8h8"/>
                            </svg>
                        </div>

                        <h3 class="text-xl font-bold mb-2">Mesa {{ $selectedTableNumber }}</h3>
                        <p class="text-sm text-neutral-400 mb-6">Compartilhe o QR code com sua mesa para todos pedirem juntos.</p>

                        <div class="bg-white rounded-2xl p-4 mb-6 inline-block">
                            <img src="{{ $qrCodeUrl }}"
                                 alt="QR Code Mesa {{ $selectedTableNumber }}"
                                 class="w-48 h-48 mx-auto"
                                 loading="lazy">
                        </div>

                        <div class="space-y-3">
                            <a href="{{ $tableEntryUrl }}"
                               class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                                Entrar na Mesa {{ $selectedTableNumber }}
                            </a>

                            <button wire:click="confirmTable" wire:loading.attr="disabled"
                                      class="w-full px-6 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-medium transition-all disabled:opacity-50">
                                Continuar no Cardapio
                            </button>
                        </div>

                        <p class="text-xs text-neutral-500 mt-4">
                            Ou compartilhe o link:
                            <a href="{{ $tableEntryUrl }}"
                               class="text-amber-400 hover:text-amber-300 underline break-all block mt-1">
                                {{ $tableEntryUrl }}
                            </a>
                        </p>
                    </div>
                </div>
            @endif

            {{-- Cart Component --}}
            <div wire:ignore>
                @livewire('public.cart', ['tenant' => $tenant, 'token' => $token])
            </div>

            {{-- Product Detail Modal --}}
            <div x-data="{
                open: false,
                closeModal() {
                    this.open = false;
                    document.body.style.overflow = '';
                    $wire.closeProduct();
                }
            }"
                 @product-selected.window="open = true; $nextTick(() => document.body.style.overflow = 'hidden')"
                 @keydown.window.escape="if (open) closeModal()"
                 x-cloak>
                {{-- Backdrop --}}
                <div x-show="open"
                     x-transition:enter="transition-opacity duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 bg-black/60 backdrop-blur-lg"
                     @click="closeModal()"></div>

                {{-- Modal Panel --}}
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="translate-y-full opacity-0"
                     x-transition:enter-end="translate-y-0 opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="translate-y-0 opacity-100"
                     x-transition:leave-end="translate-y-full opacity-0"
                     class="fixed inset-x-0 bottom-0 max-h-[88vh] z-50 overflow-y-auto rounded-t-3xl bg-neutral-900 border-t border-neutral-800 shadow-2xl shadow-black/40">

                     <div class="flex justify-center pt-2 pb-1 sticky top-0 bg-neutral-900 z-10 rounded-t-3xl">
                        <div class="w-10 h-1 rounded-full bg-neutral-700"></div>
                     </div>

                     @if ($selectedProduct)
                        <div>
                            {{-- Image Hero --}}
                             @if ($selectedProduct->image_url)
                                 <div class="relative w-full h-52 overflow-hidden">
                                     <img src="{{ $selectedProduct->imageUrl() }}"
                                          alt="{{ $selectedProduct->name }}"
                                          class="w-full h-full object-cover">
                                     <div class="absolute inset-0 bg-gradient-to-t from-neutral-900 via-neutral-900/20 to-transparent"></div>
                                 </div>
                             @endif

                             <div class="p-6 pt-4">
                                 <div class="flex items-start justify-between mb-3">
                                     <div class="flex-1 min-w-0">
                                         <h3 class="text-xl font-bold">{{ $selectedProduct->name }}</h3>
                                         @if ($selectedProduct->description)
                                             <p class="text-sm text-neutral-400 mt-1.5 leading-relaxed">{{ $selectedProduct->description }}</p>
                                         @endif
                                     </div>
                                     <div class="flex items-center gap-2 ml-4">
                                         <span wire:click="toggleFavorite({{ $selectedProduct->id }})"
                                               class="w-8 h-8 rounded-full flex items-center justify-center transition-all duration-200 hover:scale-110 active:scale-90 cursor-pointer"
                                               :class="favoriteIds.includes({{ $selectedProduct->id }}) ? 'bg-red-500/20 text-red-400' : 'bg-neutral-800 text-neutral-400 hover:text-red-400 hover:bg-red-500/20'">
                                             <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                  :fill="favoriteIds.includes({{ $selectedProduct->id }}) ? 'currentColor' : 'none'"
                                                  stroke-width="2">
                                                 <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                             </svg>
                                         </span>
                                         <button @click="closeModal()"
                                                 class="p-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 transition-colors shrink-0">
                                             <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                             </svg>
                                         </button>
                                     </div>
                                 </div>

                                <div class="flex items-center justify-between mb-6">
                                    <p class="text-2xl font-bold text-amber-400">R$ {{ number_format($selectedProduct->price, 2, ',', '.') }}</p>
                                    @if ($selectedProduct->isOutOfStock())
                                        <span class="text-xs font-bold text-red-400 bg-red-500/10 px-3 py-1.5 rounded-lg border border-red-500/20">ESGOTADO</span>
                                    @elseif ($selectedProduct->stock > 0 && $selectedProduct->stock <= 5)
                                        <span class="text-xs text-amber-400 bg-amber-500/10 px-3 py-1.5 rounded-lg border border-amber-500/20">Apenas {{ $selectedProduct->stock }} em estoque</span>
                                    @endif
                                </div>

                                <form @submit.prevent="
                                    const form = $event.target;
                                    const options = [];
                                    form.querySelectorAll('select, input[type=radio]:checked, input[type=checkbox]:checked').forEach(el => {
                                        if (el.value && el.name) {
                                            try { options.push(JSON.parse(el.value)); } catch(e) {}
                                        }
                                    });
                                    $wire.$dispatchTo('public.cart', 'addToCart', {
                                        productId: {{ $selectedProduct->id }},
                                        productName: @js($selectedProduct->name),
                                        price: {{ $selectedProduct->price }},
                                        selectedOptions: options,
                                        quantity: 1
                                    });
                                    closeModal();
                                "
                                      x-data="{ animating: false }"
                                      @submit="animating = true; setTimeout(() => animating = false, 800)">

                                    @foreach ($selectedProduct->attributes as $attribute)
                                        <div class="mb-5">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center gap-2">
                                                    <label class="font-medium text-sm">{{ $attribute->name }}</label>
                                                    @if ($attribute->price > 0)
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">R$ {{ number_format($attribute->price, 2, ',', '.') }}</span>
                                                    @endif
                                                </div>
                                                @if ($attribute->is_required)
                                                    <span class="text-xs text-red-400/80">*Obrigatorio</span>
                                                @endif
                                            </div>

                                            @if ($attribute->type === 'single')
                                                <div class="space-y-2">
                                                    @foreach ($attribute->options as $option)
                                                        <label class="flex items-center justify-between p-3 rounded-xl bg-neutral-800/40 border border-neutral-700/50 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10 transition-all cursor-pointer hover:bg-neutral-800/80">
                                                            <div class="flex items-center gap-3">
                                                                <input type="radio"
                                                                       name="attr_{{ $attribute->id }}"
                                                                 value='{{ json_encode(['attribute_id' => $attribute->id, 'attribute_name' => $attribute->name, 'option_id' => $option->id, 'option_name' => $option->name, 'price_additional' => $option->price_additional, 'attribute_price' => $attribute->price]) }}'
                                                                        class="text-amber-500 focus:ring-amber-500 bg-neutral-800 border-neutral-600"
                                                                        {{ $loop->first ? 'checked' : '' }}>
                                                                 <span class="text-sm">{{ $option->name }}</span>
                                                                 @if ($option->relationLoaded('ingredient') && $option->ingredient)
                                                                     <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $option->ingredient->name }}</span>
                                                                 @endif
                                                             </div>
                                                             @if ($option->price_additional > 0)
                                                                 <span class="text-xs text-amber-400">+R$ {{ number_format($option->price_additional, 2, ',', '.') }}</span>
                                                             @endif
                                                         </label>
                                                     @endforeach
                                                 </div>
                                             @else
                                                 <div class="space-y-2">
                                                     @foreach ($attribute->options as $option)
                                                         <label class="flex items-center justify-between p-3 rounded-xl bg-neutral-800/40 border border-neutral-700/50 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10 transition-all cursor-pointer hover:bg-neutral-800/80">
                                                             <div class="flex items-center gap-3">
                                                                 <input type="checkbox"
                                                                        name="attr_{{ $attribute->id }}[]"
                                                                        value='{{ json_encode(['attribute_id' => $attribute->id, 'attribute_name' => $attribute->name, 'option_id' => $option->id, 'option_name' => $option->name, 'price_additional' => $option->price_additional, 'attribute_price' => $attribute->price]) }}'
                                                                       class="rounded text-amber-500 focus:ring-amber-500 bg-neutral-800 border-neutral-600">
                                                                <span class="text-sm">{{ $option->name }}</span>
                                                                @if ($option->relationLoaded('ingredient') && $option->ingredient)
                                                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $option->ingredient->name }}</span>
                                                                @endif
                                                            </div>
                                                            @if ($option->price_additional > 0)
                                                                <span class="text-xs text-amber-400">+R$ {{ number_format($option->price_additional, 2, ',', '.') }}</span>
                                                            @endif
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach

                                    <button type="submit"
                                            :disabled="animating || {{ $selectedProduct->isOutOfStock() ? 'true' : 'false' }}"
                                            class="w-full py-3.5 px-4 bg-amber-500 hover:bg-amber-400 disabled:bg-amber-600 disabled:opacity-60 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] disabled:scale-100 flex items-center justify-center gap-2">
                                        <svg x-show="animating" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                        <span x-text="animating ? 'Adicionando...' : '{{ $selectedProduct->isOutOfStock() ? 'Indisponível' : 'Adicionar ao Pedido' }}'"></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>