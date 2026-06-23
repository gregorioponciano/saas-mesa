<div wire:poll.15s class="flex flex-col h-full">
    {{-- Brand --}}
    @php
        $tenant = Auth::user()?->tenant;
        $logoUrl = $tenant?->logoUrl();
        $logoW = min(max($tenant?->logo_width ?? 44, 20), 120);
        $logoH = min(max($tenant?->logo_height ?? 44, 20), 120);
    @endphp
    <div class="flex items-center gap-3 px-6 h-16 border-b border-neutral-800">
        @if ($logoUrl)
            <div class="rounded-xl overflow-hidden shrink-0" style="width: {{ $logoW }}px; height: {{ $logoH }}px;">
                <img src="{{ $logoUrl }}" class="w-full h-full object-contain" alt="Logo">
            </div>
        @else
            <div class="rounded-xl bg-amber-500 flex items-center justify-center text-neutral-950 font-black text-sm" style="width: {{ $logoW }}px; height: {{ $logoH }}px;">
                {{ mb_substr($tenant?->name ?? 'B', 0, 1) }}
            </div>
        @endif
        <div>
            <span class="font-black text-amber-400">Burguer</span>
            <span class="font-black text-white">SaaS</span>
        </div>
    </div>

    {{-- User Info --}}
    <div class="px-4 py-4 border-b border-neutral-800">
        <div class="flex items-center gap-3 px-3 py-2 rounded-xl bg-neutral-800/50">
            <div class="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold text-xs">
                {{ substr(Auth::user()?->name ?? 'U', 0, 2) }}
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium truncate">{{ Auth::user()?->name }}</p>
                <p class="text-xs text-neutral-400">{{ Auth::user()?->tenant?->name }}</p>
            </div>
            <span class="ml-auto px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Cliente</span>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        @php $currentTab = request()->query('tab', 'orders'); @endphp

        <a href="?tab=orders"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $currentTab === 'orders' ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span class="flex-1">Meus Pedidos</span>
            @if ($myActiveOrdersCount > 0)
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 animate-pulse transition-all duration-300">{{ $myActiveOrdersCount }}</span>
            @endif
        </a>

        <a href="?tab=history"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $currentTab === 'history' ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Histórico
        </a>

        <div class="pt-4 mt-4 border-t border-neutral-800">
            <p class="px-4 text-xs font-medium text-neutral-500 uppercase tracking-wider">Conta</p>
        </div>

        <a href="?tab=profile"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $currentTab === 'profile' ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Perfil
        </a>

        <a href="?tab=restaurant"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $currentTab === 'restaurant' ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            Restaurante
        </a>

        <div class="pt-4 mt-4 border-t border-neutral-800">
            <p class="px-4 text-xs font-medium text-neutral-500 uppercase tracking-wider">Suporte</p>
        </div>

        <a href="{{ route('client.support', ['slug' => $tenant->slug]) }}"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('client.support') ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
            </svg>
            <span class="flex-1">Suporte</span>
        </a>
    </nav>

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
