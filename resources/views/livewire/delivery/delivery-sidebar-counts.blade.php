<div wire:poll.5s class="flex flex-col h-full">
    {{-- Brand --}}
    @php
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
            <div class="rounded-xl bg-violet-500 flex items-center justify-center text-neutral-950 font-black text-sm" style="width: {{ $logoW }}px; height: {{ $logoH }}px;">
                {{ mb_substr($tenant?->name ?? 'E', 0, 1) }}
            </div>
        @endif
        <div>
            <span class="font-black text-violet-400">Delivery</span>
            <span class="font-black text-white">Panel</span>
        </div>
    </div>

    {{-- Delivery Person Info --}}
    <div class="px-4 py-4 border-b border-neutral-800">
        <div class="flex items-center gap-3 px-3 py-2 rounded-xl bg-neutral-800/50">
            @if ($delivery?->avatar_path)
                <img src="{{ Storage::disk('public')->url($delivery->avatar_path) }}" alt="{{ $delivery->name }}"
                     class="w-8 h-8 rounded-full object-cover border border-neutral-700 shrink-0">
            @else
                <div class="w-8 h-8 rounded-full bg-violet-500/20 flex items-center justify-center text-violet-400 font-bold text-xs shrink-0">
                    {{ substr($delivery?->name ?? 'E', 0, 2) }}
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium truncate">{{ $delivery?->name }}</p>
                <p class="text-xs text-neutral-400">{{ $tenant?->name }}</p>
            </div>
            <span class="w-2 h-2 rounded-full {{ $delivery?->status === 'active' ? 'bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.6)]' : 'bg-neutral-500' }}"></span>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        @foreach ($tabs as $key => $tab)
            <a href="{{ $tab['route'] }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                      {{ $currentTab === $key ? 'bg-violet-500/10 text-violet-400' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' }}">
                @switch($tab['icon'])
                    @case('search')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        @break
                    @case('clipboard')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        @break
                    @case('clock')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        @break
                    @case('user')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        @break
                    @case('settings')
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        @break
                @endswitch
                <span class="flex-1">{{ $tab['label'] }}</span>
                @if (!empty($tab['badge']) && $tab['badge'] > 0)
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-violet-500/20 text-violet-400 border border-violet-500/30 transition-all duration-300">{{ $tab['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    {{-- Logout --}}
    <div class="px-3 py-4 border-t border-neutral-800">
        <a href="{{ route('delivery.login') }}"
           onclick="event.preventDefault(); document.getElementById('delivery-logout-form').submit();"
           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-neutral-400 hover:text-red-400 hover:bg-red-500/5 transition-all duration-200">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Sair
        </a>
        <form id="delivery-logout-form" method="POST" action="{{ route('delivery.logout') }}" class="hidden">@csrf</form>
    </div>
</div>
