<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acesso - {{ $tenant->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-950 text-white font-['Inter'] antialiased min-h-screen flex">
    <div class="w-full flex">
        {{-- Company Info Panel --}}
        <div class="hidden lg:flex w-1/2 bg-gradient-to-br from-neutral-900 to-neutral-950 p-12 items-center justify-center">
            <div class="max-w-md">
                <div class="w-20 h-20 rounded-3xl bg-amber-500 flex items-center justify-center text-neutral-950 font-black text-3xl mb-8 shadow-2xl shadow-amber-500/20">B</div>
                <h1 class="text-3xl font-black text-white mb-3">{{ $tenant->name }}</h1>
                <p class="text-neutral-400 leading-relaxed mb-8">
                    Faça login para acessar o painel da equipe e gerenciar pedidos, mesas e atendimento do restaurante em tempo real.
                </p>
                <div class="space-y-3">
                    <div class="flex items-center gap-3 text-sm text-neutral-500">
                        <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <span>Gerenciamento de pedidos em tempo real</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-neutral-500">
                        <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span>Mapa visual de mesas</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-neutral-500">
                        <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                        </svg>
                        <span>Notificacoes em tempo real</span>
                    </div>
                </div>
                @if ($tenant->whatsapp)
                    <div class="mt-10 pt-8 border-t border-neutral-800">
                        <p class="text-xs text-neutral-600 mb-2">Contato do restaurante</p>
                        <p class="text-sm text-neutral-400">{{ $tenant->whatsapp }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Form Panel --}}
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 min-h-screen">
            <div class="w-full max-w-sm">
                <div class="lg:hidden text-center mb-8">
                    <div class="w-14 h-14 rounded-2xl bg-amber-500 flex items-center justify-center text-neutral-950 font-black text-xl mx-auto mb-3">B</div>
                    <h1 class="text-xl font-bold">{{ $tenant->name }}</h1>
                </div>

                <h2 class="text-2xl font-bold mb-1">Acesso da Equipe</h2>
                <p class="text-sm text-neutral-400 mb-8">Entre com suas credenciais para continuar</p>

                @if (session('error'))
                    <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('waiter.login', $tenant->slug) }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-neutral-300 mb-2">E-mail</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                               class="w-full px-4 py-3 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-neutral-300 mb-2">Senha</label>
                        <input type="password" name="password" id="password" required
                               class="w-full px-4 py-3 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 px-4 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                        Entrar
                    </button>
                </form>

                <div class="mt-6 space-y-2 text-center">
                    <p class="text-sm text-neutral-500">
                        Nao tem conta?
                        <a href="{{ route('waiter.register.form', $tenant->slug) }}" class="text-amber-400 hover:text-amber-300 font-medium">
                            Cadastre-se
                        </a>
                    </p>
                    <p class="text-xs">
                        <a href="{{ route('menu.show', $tenant->slug) }}" class="text-neutral-600 hover:text-neutral-400 transition-colors">
                            Voltar ao cardapio
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
