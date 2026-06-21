<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Criar Conta - {{ $tenant->name }}</title>
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
                @if ($tenant->logoUrl())
                    <div class="mx-auto mb-8 rounded-3xl overflow-hidden shadow-2xl shadow-amber-500/20" style="width: 120px; height: 120px;">
                        <img src="{{ $tenant->logoUrl() }}" class="w-full h-full object-contain" alt="{{ $tenant->name }}">
                    </div>
                @else
                    <div class="mx-auto w-24 h-24 rounded-3xl bg-amber-500 flex items-center justify-center text-neutral-950 font-black text-4xl mb-8 shadow-2xl shadow-amber-500/20">{{ mb_substr($tenant->name, 0, 1) }}</div>
                @endif
                <h1 class="text-3xl font-black text-white mb-3">{{ $tenant->name }}</h1>
                <p class="text-neutral-400 leading-relaxed mb-8">
                    Crie sua conta para fazer parte da equipe e comecar a atender os clientes do restaurante.
                </p>
                <div class="space-y-3">
                    <div class="flex items-center gap-3 text-sm text-neutral-500">
                        <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <span>Registre pedidos dos clientes</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-neutral-500">
                        <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span>Gerencie mesas e atendimento</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-neutral-500">
                        <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Acompanhe o status dos pedidos</span>
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
                    @if ($tenant->logoUrl())
                        <div class="mx-auto mb-3 rounded-2xl overflow-hidden" style="width: 80px; height: 80px;">
                            <img src="{{ $tenant->logoUrl() }}" class="w-full h-full object-contain" alt="{{ $tenant->name }}">
                        </div>
                    @else
                        <div class="w-16 h-16 rounded-2xl bg-amber-500 flex items-center justify-center text-neutral-950 font-black text-2xl mx-auto mb-3">{{ mb_substr($tenant->name, 0, 1) }}</div>
                    @endif
                    <h1 class="text-xl font-bold">{{ $tenant->name }}</h1>
                </div>

                <h2 class="text-2xl font-bold mb-1">Criar Conta</h2>
                <p class="text-sm text-neutral-400 mb-8">Preencha os dados para se cadastrar</p>

                @if ($errors->any())
                    <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('waiter.register', $tenant->slug) }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-neutral-300 mb-2">Nome</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                               class="w-full px-4 py-3 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-neutral-300 mb-2">E-mail</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
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

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-neutral-300 mb-2">Confirmar Senha</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                               class="w-full px-4 py-3 rounded-xl bg-neutral-900 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 px-4 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                        Criar Conta
                    </button>
                </form>

                <div class="mt-6 space-y-2 text-center">
                    <p class="text-sm text-neutral-500">
                        Ja tem conta?
                        <a href="{{ route('waiter.login.form', $tenant->slug) }}" class="text-amber-400 hover:text-amber-300 font-medium">
                            Entrar
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
