<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Redefinir Senha - Entregador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-950 text-white font-['Inter'] antialiased min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-8">
        <div class="text-center mb-6">
            <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-violet-500/10 flex items-center justify-center">
                <svg class="w-7 h-7 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold">Redefinir Senha</h1>
            <p class="text-sm text-neutral-400 mt-1">Escolha uma nova senha para sua conta</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('delivery.reset', ['token' => $token]) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required readonly
                       class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-neutral-400 text-sm cursor-not-allowed">
            </div>
            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">Nova Senha</label>
                <input type="password" name="password" required minlength="6"
                       placeholder="Mínimo 6 caracteres"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all @error('password') border-red-500 @enderror">
            </div>
            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">Confirmar Nova Senha</label>
                <input type="password" name="password_confirmation" required minlength="6"
                       placeholder="Repita a senha"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
            </div>

            <button type="submit"
                    class="w-full px-4 py-2.5 rounded-xl bg-gradient-to-r from-violet-500 to-violet-600 hover:from-violet-400 hover:to-violet-500 text-white font-semibold text-sm transition-all shadow-lg shadow-violet-500/20">
                Redefinir Senha
            </button>
        </form>
    </div>
</body>
</html>
