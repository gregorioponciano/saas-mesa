<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Termos de Uso - BurguerSaaS</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        [x-cloak] { display: none !important; }
        .terms-content h1 { font-size: 1.5rem; font-weight: 800; color: #fbbf24; margin-top: 2rem; margin-bottom: 1rem; }
        .terms-content h2 { font-size: 1.25rem; font-weight: 700; color: #fbbf24; margin-top: 1.75rem; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #262626; }
        .terms-content h3 { font-size: 1.05rem; font-weight: 600; color: #d4d4d4; margin-top: 1.25rem; margin-bottom: 0.5rem; }
        .terms-content p { margin-bottom: 0.75rem; line-height: 1.7; color: #a3a3a3; text-align: justify; }
        .terms-content strong { color: #e5e5e5; font-weight: 600; }
        .terms-content table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.85rem; }
        .terms-content th, .terms-content td { border: 1px solid #333; padding: 0.5rem 0.75rem; text-align: left; }
        .terms-content th { background: #1a1a1a; color: #d4d4d4; font-weight: 600; }
        .terms-content td { color: #a3a3a3; }
        .terms-content ul { margin-bottom: 0.75rem; padding-left: 1.25rem; }
        .terms-content li { color: #a3a3a3; line-height: 1.7; margin-bottom: 0.25rem; }
        .terms-content a { color: #fbbf24; text-decoration: underline; text-underline-offset: 2px; }
        .terms-content a:hover { color: #fcd34d; }
    </style>
</head>
<body class="bg-neutral-950 text-white antialiased min-h-screen flex flex-col">
    <header class="fixed top-0 left-0 right-0 z-50 border-b border-neutral-800/50 backdrop-blur-xl bg-neutral-950/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="/" class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-amber-500 flex items-center justify-center text-neutral-950 font-black text-sm">B</span>
                    <span class="font-bold text-lg">Burguer<span class="text-amber-500">SaaS</span></span>
                </a>
                <nav class="flex items-center gap-3">
                    <a href="{{ route('login') }}"
                       class="px-5 py-2 text-neutral-300 hover:text-white transition-colors text-sm font-medium">
                        Entrar
                    </a>
                    <a href="{{ route('register.tenant') }}"
                       class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 text-sm">
                        Começar Grátis
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="flex-1 pt-24 pb-16 px-4">
        <div class="max-w-4xl mx-auto">
            <nav class="flex items-center gap-2 text-sm text-neutral-500 mb-6">
                <a href="/" class="hover:text-amber-400 transition-colors">Início</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-neutral-400">Termos de Uso</span>
            </nav>

            <div class="p-8 sm:p-10 rounded-2xl bg-neutral-900/50 border border-neutral-800">
                <h1 class="text-3xl sm:text-4xl font-black mb-2">Termos de Uso</h1>
                <p class="text-neutral-500 text-sm mb-8">Última atualização: {{ date('d/m/Y') }}</p>
                <div class="terms-content">
                    {!! $content !!}
                </div>
            </div>
        </div>
    </main>

    <footer class="border-t border-neutral-800/50 py-8 px-4">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-neutral-600">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-amber-500 flex items-center justify-center text-neutral-950 font-black text-xs">B</span>
                <span>BurguerSaaS</span>
            </div>
            <div class="flex items-center gap-6">
                <a href="{{ route('terms') }}" class="hover:text-amber-400 transition-colors">Termos de Uso</a>
                <a href="{{ route('privacy') }}" class="hover:text-amber-400 transition-colors">Privacidade</a>
            </div>
            <p>&copy; {{ date('Y') }} BurguerSaaS. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>
