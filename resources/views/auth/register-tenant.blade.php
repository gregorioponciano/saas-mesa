<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Criar Conta - BurguerSaaS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-950 text-white font-['Inter'] antialiased min-h-screen flex">
    <div class="w-full max-w-2xl mx-auto p-8 flex items-center min-h-screen">
        <div class="w-full">
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500 flex items-center justify-center text-neutral-950 font-black text-xl">B</div>
                    <div class="text-left">
                        <span class="text-3xl font-black text-amber-400">Burguer</span>
                        <span class="text-3xl font-black text-white">SaaS</span>
                    </div>
                </div>
                <p class="text-neutral-400">Crie sua conta gratuita e comecce a gerenciar sua lanchonete</p>
            </div>

            <div class="mb-8 p-4 rounded-2xl bg-gradient-to-r from-amber-500/10 to-amber-600/5 border border-amber-500/10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-amber-400">Plano Gratuito - R$ 0</p>
                        <p class="text-xs text-neutral-400">Ate 2 mesas, cardapio digital, pedidos ilimitados. Sem compromisso.</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('register.tenant.store') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="tenant_name" class="block text-sm font-medium text-neutral-300 mb-2">Nome da Lanchonete</label>
                        <input type="text" name="tenant_name" id="tenant_name" value="{{ old('tenant_name') }}" required
                               class="w-full px-4 py-3 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('tenant_name') border-red-500 @enderror">
                        @error('tenant_name')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="slug" class="block text-sm font-medium text-neutral-300 mb-2">Slug do cardapio</label>
                        <div class="relative">
                            <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required
                                   class="w-full px-4 py-3 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('slug') border-red-500 @enderror">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-neutral-500">/cardapio/</span>
                        </div>
                        @error('slug')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-neutral-300 mb-2">Seu nome</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-3 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tenant_email" class="block text-sm font-medium text-neutral-300 mb-2">E-mail da Lanchonete</label>
                    <input type="email" name="tenant_email" id="tenant_email" value="{{ old('tenant_email') }}" required
                           class="w-full px-4 py-3 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('tenant_email') border-red-500 @enderror">
                    @error('tenant_email')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-neutral-300 mb-2">Seu e-mail (recuperar a senha)</label>
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
                </div>

                <button type="submit"
                        class="w-full py-3.5 px-4 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                    Criar Conta Gratuita
                </button>

                <p class="text-center text-xs text-neutral-500">
                    Ao criar uma conta, voce concorda com nossos
                    <a href="{{ route('terms') }}" target="_blank" class="text-amber-400 hover:text-amber-300 underline underline-offset-2">Termos de Servico</a>
                    e
                    <a href="{{ route('privacy') }}" target="_blank" class="text-amber-400 hover:text-amber-300 underline underline-offset-2">Politica de Privacidade</a>.
                </p>
            </form>

            <p class="mt-6 text-center text-sm text-neutral-500">
                Ja tem conta?
                <a href="{{ route('login') }}" class="text-amber-400 hover:text-amber-300 font-medium">
                    Entrar
                </a>
            </p>
        </div>
    </div>
</body>
</html>
