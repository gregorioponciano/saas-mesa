<nav class="flex flex-wrap items-center gap-1.5 p-1.5 rounded-2xl bg-neutral-900 border border-neutral-800">
    @php
        $tabs = [
            'superadmin.dashboard' => ['Visão Geral', 'M4 6h16M4 12h16M4 18h16'],
            'superadmin.reports' => ['Relatórios', 'M11 3.055A9 9 0 1020.945 13H11V3.055zM20.488 9H15V3.512A9.025 9.025 0 0120.488 9z'],
            'superadmin.tenants' => ['Empresas', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            'superadmin.financial' => ['Financeiro', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ];
    @endphp
    @foreach ($tabs as $routeName => [$label, $path])
        <a href="{{ route($routeName) }}"
           class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs($routeName) ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $path }}"/>
            </svg>
            {{ $label }}
        </a>
    @endforeach
</nav>
