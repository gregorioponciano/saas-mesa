<div class="flex flex-col h-full">
    <div class="p-6 pb-5">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20">
                <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10a2 2 0 002 2h12a2 2 0 002-2V7M4 7a2 2 0 012-2h12a2 2 0 012 2M4 7l8 5 8-5M9 17h6"/>
                </svg>
            </div>
            <div>
                <p class="text-base font-bold text-white leading-tight">BurguerSaaS</p>
                <p class="text-xs text-neutral-500">Painel Superadmin</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-4 pb-4 overflow-y-auto space-y-4">
        <div>
            <p class="px-4 text-xs font-medium text-neutral-500 uppercase tracking-wider mb-1">Geral</p>
            @php
                $geral = [
                    'superadmin.dashboard' => ['Visão Geral', 'M4 6h16M4 12h16M4 18h16'],
                    'superadmin.tenants' => ['Empresas', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                    'superadmin.backups' => ['Backups', 'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    'superadmin.users' => ['Usuários', 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 1.13a3 3 0 10-3-3'],
                    'superadmin.audit' => ['Auditoria', 'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ];
            @endphp
            @foreach ($geral as $routeName => [$label, $path])
                <a href="{{ route($routeName) }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs($routeName) ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $path }}"/>
                    </svg>
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div>
            <p class="px-4 text-xs font-medium text-neutral-500 uppercase tracking-wider mb-1">Operação</p>
            @php
                $operacao = [
                    'superadmin.financial' => ['Financeiro', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'superadmin.plans' => ['Planos', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    'superadmin.loyalty' => ['Loyalty', 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118L2.98 10.1c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
                ];
            @endphp
            @foreach ($operacao as $routeName => [$label, $path])
                <a href="{{ route($routeName) }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs($routeName) ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $path }}"/>
                    </svg>
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div>
            <p class="px-4 text-xs font-medium text-neutral-500 uppercase tracking-wider mb-1">Conformidade</p>
            @php
                $conformidade = [
                    'superadmin.webhooks' => ['Webhooks', 'M13 10V3L4 14h7v7l9-11h-7z'],
                    'superadmin.privacy' => ['Privacidade (LGPD)', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                ];
            @endphp
            @foreach ($conformidade as $routeName => [$label, $path])
                <a href="{{ route($routeName) }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs($routeName) ? 'bg-amber-500/10 text-amber-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $path }}"/>
                    </svg>
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </nav>

    <div class="p-4 border-t border-neutral-800">
        <p class="text-xs text-neutral-500">Conectado como<br><span class="text-neutral-300 font-medium">{{ Auth::user()->email }}</span></p>
    </div>
</div>
