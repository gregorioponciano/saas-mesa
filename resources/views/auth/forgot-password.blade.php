<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Recuperar Senha - {{ $tenant->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-950 text-white font-['Inter'] antialiased min-h-screen flex">
    <div class="w-full flex">
        <div class="hidden lg:flex w-1/2 bg-gradient-to-br from-neutral-900 to-neutral-950 p-12 items-center justify-center">
            <div class="max-w-md text-center">
                @if ($tenant->logoUrl())
                    <div class="mx-auto mb-8 rounded-3xl overflow-hidden shadow-2xl shadow-amber-500/20" style="width: 120px; height: 120px;">
                        <img src="{{ $tenant->logoUrl() }}" class="w-full h-full object-contain" alt="{{ $tenant->name }}">
                    </div>
                @else
                    <div class="mx-auto w-24 h-24 rounded-3xl bg-amber-500 flex items-center justify-center text-neutral-950 font-black text-4xl mb-8 shadow-2xl shadow-amber-500/20">{{ mb_substr($tenant->name, 0, 1) }}</div>
                @endif
                <h1 class="text-3xl font-black text-white mb-3">{{ $tenant->name }}</h1>
                <p class="text-neutral-400 leading-relaxed">
                    Receba um link para redefinir sua senha e continuar acessando o painel da equipe.
                </p>
            </div>
        </div>

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

                <h2 class="text-2xl font-bold mb-1">Recuperar Senha</h2>
                <p class="text-sm text-neutral-400 mb-8">Digite seu email para receber o link de redefinição</p>

                @if (session('status'))
                    <div class="mb-6 px-4 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('waiter.forgot.send', $tenant->slug) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-neutral-300 mb-2">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               placeholder="seu@email.com"
                               class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('email') border-red-500 @enderror">
                    </div>

                    <x-admin.button variant="primary" type="submit" class="w-full justify-center">
                        Enviar Link
                    </x-admin.button>

                    <p class="text-center text-sm text-neutral-500">
                        <a href="{{ route('waiter.login.form', $tenant->slug) }}" class="text-amber-400 hover:text-amber-300 transition-colors">Voltar ao login</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
