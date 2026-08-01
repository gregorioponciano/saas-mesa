<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Painel Superadmin - BurguerSaaS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-950 text-white font-['Inter'] antialiased min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 mb-5">
                <svg class="w-8 h-8 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10a2 2 0 002 2h12a2 2 0 002-2V7M4 7a2 2 0 012-2h12a2 2 0 012 2M4 7l8 5 8-5M9 17h6"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">Painel Superadmin</h1>
            <p class="mt-2 text-sm text-neutral-400">Área restrita — controle do BurguerSaaS</p>
        </div>

        <div class="rounded-2xl bg-neutral-900 border border-neutral-800 p-8 shadow-2xl">
            <form method="POST" action="{{ route('superadmin.login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-neutral-300 mb-2">E-mail</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-4 py-3 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-neutral-300 mb-2">Senha</label>
                    <input type="password" name="password" id="password" required
                           class="w-full px-4 py-3 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-neutral-400">
                    <input type="checkbox" name="remember" class="rounded bg-neutral-950 border-neutral-700 text-amber-500 focus:ring-amber-500">
                    Manter conectado
                </label>

                <button type="submit"
                        class="w-full py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold transition-all duration-200 hover:scale-[1.01]">
                    Entrar
                </button>
            </form>
        </div>

        <p class="mt-8 text-center text-xs text-neutral-600">
            Acesso restrito ao superadministrador do BurguerSaaS.
        </p>
    </div>
</body>
</html>
