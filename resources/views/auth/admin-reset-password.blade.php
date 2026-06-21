<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Redefinir Senha - BurguerSaaS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-950 text-white font-['Inter'] antialiased min-h-screen flex items-center justify-center p-8">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <span class="text-3xl font-black text-amber-400">Burguer</span>
            <span class="text-3xl font-black text-white">SaaS</span>
        </div>

        <h1 class="text-2xl font-bold">Redefinir Senha</h1>
        <p class="mt-2 text-neutral-400 mb-8">Escolha uma nova senha para sua conta</p>

        @if ($errors->any())
            <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.reset', ['token' => $token]) }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required readonly
                       class="w-full px-4 py-3 rounded-xl bg-neutral-900 border border-neutral-800 text-neutral-400 placeholder-neutral-500 focus:outline-none transition-all cursor-not-allowed">
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Nova Senha</label>
                <input type="password" name="password" required minlength="8"
                       placeholder="Minimo 8 caracteres"
                       class="w-full px-4 py-3 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('password') border-red-500 @enderror">
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Confirmar Nova Senha</label>
                <input type="password" name="password_confirmation" required minlength="8"
                       placeholder="Repita a senha"
                       class="w-full px-4 py-3 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
            </div>

            <button type="submit"
                    class="w-full py-3.5 px-4 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200">
                Redefinir Senha
            </button>
        </form>
    </div>
</body>
</html>