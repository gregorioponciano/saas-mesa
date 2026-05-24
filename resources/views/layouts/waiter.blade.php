<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - {{ Auth::user()?->tenant?->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-neutral-950 text-white font-['Inter'] antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">
        {{-- Backdrop --}}
        <div x-show="sidebarOpen" x-cloak
             class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm"
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
        </div>

        {{-- Sidebar --}}
        <aside x-cloak
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               @keydown.window.escape="sidebarOpen = false"
               class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] bg-neutral-900 border-r border-neutral-800 flex flex-col transition-transform duration-300 ease-in-out">
            @livewire('waiter.waiter-sidebar-counts', key('waiter-sidebar-counts'))
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col min-w-0">
            {{-- Top Bar --}}
            <header class="sticky top-0 z-30 bg-neutral-950/80 backdrop-blur-xl border-b border-neutral-800">
                <div class="flex items-center justify-between h-16 px-4 lg:px-8">
                    <button x-show="!sidebarOpen" @click="sidebarOpen = true"
                            class="p-2 rounded-xl hover:bg-neutral-800 transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div class="flex items-center gap-3 ml-auto">
                        <span class="px-3 py-1 text-xs font-medium rounded-full {{ Auth::user()?->tenant?->isPaid() ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-neutral-800 text-neutral-400 border border-neutral-700' }}">
                            {{ Auth::user()?->tenant?->planLabel() }}
                        </span>
                        @if (Auth::user()?->tenant?->isFree())
                            <a href="#" 
                               onclick="event.preventDefault(); $wire.dispatch('notify', {message: 'Fale com o administrador para fazer upgrade do plano.'})"
                               class="px-4 py-1.5 text-xs font-semibold rounded-full bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-all duration-200 hover:scale-105 hidden lg:inline-block">
                                Fazer Upgrade
                            </a>
                        @endif
                    </div>
                </div>
            </header>

            {{-- Flash Messages --}}
            <div class="mx-4 lg:mx-8 mt-4">
                @include('partials.flash-messages')
            </div>

            {{-- Page Content --}}
            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>
    </div>

    @include('partials.notifications')

    @livewireScripts
</body>
</html>
