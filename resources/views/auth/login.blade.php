<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Entrar - BurguerSaaS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-950 text-white font-['Inter'] antialiased min-h-screen flex">
    <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-neutral-900 via-amber-950/30 to-neutral-950">
        <div class="absolute inset-0">
            <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=1200&q=80"
                 alt="Hamburguer artesanal"
                 class="w-full h-full object-cover opacity-20">
            <div class="absolute inset-0 bg-gradient-to-t from-neutral-950 via-neutral-950/60 to-transparent"></div>
        </div>
        <div class="relative z-10 flex flex-col justify-end p-16">
            <div>
                <span class="text-4xl font-black text-amber-400">Burguer</span>
                <span class="text-4xl font-black text-white">SaaS</span>
            </div>
            <p class="mt-4 text-lg text-neutral-300 max-w-md">
                A plataforma completa para gestão do seu negócio. Cardápio digital, pedidos online e muito mais.
            </p>
            <div class="mt-8 flex gap-4">
                <div class="flex -space-x-2">
                    <div class="w-8 h-8 rounded-full bg-amber-500 border-2 border-neutral-950"></div>
                    <div class="w-8 h-8 rounded-full bg-amber-600 border-2 border-neutral-950"></div>
                    <div class="w-8 h-8 rounded-full bg-amber-700 border-2 border-neutral-950"></div>
                </div>
                <span class="text-sm text-neutral-400">+500 lanchonetes cadastradas</span>
            </div>
        </div>
    </div>

    <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
        <div class="w-full max-w-md">
            <div class="lg:hidden mb-8 text-center">
                <span class="text-3xl font-black text-amber-400">Burguer</span>
                <span class="text-3xl font-black text-white">SaaS</span>
            </div>

            <h1 class="text-2xl font-bold">Entrar</h1>
            <p class="mt-2 text-neutral-400">Acesse sua conta para gerenciar sua lanchonete</p>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
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

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="remember" class="rounded bg-neutral-900 border-neutral-700 text-amber-500 focus:ring-amber-500">
                        <span class="text-sm text-neutral-400">Lembrar-me</span>
                    </label>
                    <a href="{{ route('admin.forgot.form') }}" class="text-sm text-neutral-500 hover:text-amber-400 transition-colors">
                        Esqueceu a senha?
                    </a>
                </div>

                <button type="submit"
                        class="w-full py-3.5 px-4 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                    Entrar
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-neutral-500">
                Ainda não tem conta?
                <a href="{{ route('register.tenant') }}" class="text-amber-400 hover:text-amber-300 font-medium">
                    Criar conta
                </a>
            </p>
        </div>
    </div>
</body>
</html>
