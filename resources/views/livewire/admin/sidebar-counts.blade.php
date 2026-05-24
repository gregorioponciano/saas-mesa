<div wire:poll.3s class="flex flex-col h-full">
    {{-- Brand --}}
    <div class="flex items-center gap-3 px-6 h-16 border-b border-neutral-800">
        <div class="w-9 h-9 rounded-xl bg-amber-500 flex items-center justify-center text-neutral-950 font-black text-sm">B</div>
        <div>
            <span class="font-black text-amber-400">Burguer</span>
            <span class="font-black text-white">SaaS</span>
        </div>
    </div>

    {{-- User Info --}}
    <div class="px-4 py-4 border-b border-neutral-800">
        <div class="flex items-center gap-3 px-3 py-2 rounded-xl bg-neutral-800/50">
            <div class="w-8 h-8 rounded-full bg-amber-500/20 flex items-center justify-center text-amber-400 font-bold text-xs">
                {{ substr(Auth::user()?->name ?? 'U', 0, 2) }}
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium truncate">{{ Auth::user()?->name }}</p>
                <p class="text-xs text-neutral-400">{{ Auth::user()?->tenant?->name }}</p>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('dashboard') && request()->query('tab') !== 'grid' ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="flex-1">Dashboard</span>
            @if ($activeOrdersCount > 0)
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 transition-all duration-300">{{ $activeOrdersCount }}</span>
            @endif
        </a>

        <a href="{{ route('dashboard') }}?tab=grid"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->query('tab') === 'grid' ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            Mapa de Mesas
        </a>

        <a href="{{ route('dashboard.tables') }}"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('dashboard.tables') ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span class="flex-1">Gerenciar Mesas</span>
            @if ($occupiedTablesCount > 0)
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-500/20 text-red-400 border border-red-500/30 transition-all duration-300">{{ $occupiedTablesCount }}/{{ $tablesCount }}</span>
            @else
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-neutral-800 text-neutral-500 border border-neutral-700 transition-all duration-300">{{ $tablesCount }}</span>
            @endif
        </a>

        <a href="{{ route('dashboard.menu') }}"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('dashboard.menu') ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span class="flex-1">Gerenciar Cardápio</span>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 transition-all duration-300">{{ $activeProductsCount }}</span>
            @if ($isAdmin && $disabledProductsCount > 0)
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-neutral-600/30 text-neutral-400 border border-neutral-600/40 transition-all duration-300" title="Produtos desativados">{{ $disabledProductsCount }}</span>
            @endif
        </a>

        <a href="{{ route('dashboard.users') }}"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('dashboard.users') ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
            </svg>
            <span class="flex-1">Gerenciar Usuários</span>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-500/20 text-blue-400 border border-blue-500/30 transition-all duration-300">{{ $usersCount }}</span>
        </a>

        @if ($isAdmin)
            <a href="{{ route('dashboard.cupons') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('dashboard.cupons') ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <span class="flex-1">Cupons de Desconto</span>
            </a>
        @endif

        <div class="pt-4 mt-4 border-t border-neutral-800">
            <p class="px-4 text-xs font-medium text-neutral-500 uppercase tracking-wider">Entregas</p>
        </div>

        <a href="{{ route('dashboard.delivery-people') }}"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('dashboard.delivery-people') ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="flex-1">Entregadores</span>
        </a>

        <div class="pt-4 mt-4 border-t border-neutral-800">
            <p class="px-4 text-xs font-medium text-neutral-500 uppercase tracking-wider">Restaurante</p>
        </div>

        <a href="{{ route('menu.show', Auth::user()?->tenant?->slug) }}" target="_blank"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-neutral-400 hover:text-white hover:bg-neutral-800/50 transition-all duration-200">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            Ver Cardápio
        </a>

        <div class="pt-4 mt-4 border-t border-neutral-800">
            <p class="px-4 text-xs font-medium text-neutral-500 uppercase tracking-wider">Assinatura</p>
        </div>

        <a href="{{ route('subscription.checkout') }}"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('subscription.checkout') ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Planos
        </a>

        <a href="{{ route('dashboard.settings') }}"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('dashboard.settings') ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Configurações
        </a>
    </nav>

    {{-- Logout --}}
    <div class="px-3 py-4 border-t border-neutral-800">
        <a href="{{ route('login') }}"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-neutral-400 hover:text-red-400 hover:bg-red-500/5 transition-all duration-200">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Sair
        </a>
        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
    </div>
</div>
