<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BurguerSaaS - Cardápio Digital para Restaurantes</title>

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-neutral-950 text-white antialiased min-h-screen flex flex-col">
    <header class="fixed top-0 left-0 right-0 z-50 border-b border-neutral-800/50 backdrop-blur-xl bg-neutral-950/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-amber-500 flex items-center justify-center text-neutral-950 font-black text-sm">B</span>
                    <span class="font-bold text-lg">Burguer<span class="text-amber-500">SaaS</span></span>
                </div>
                <nav class="flex items-center gap-3">
                    @auth
                        <a href="/dashboard"
                           class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 text-sm">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="px-5 py-2 text-neutral-300 hover:text-white transition-colors text-sm font-medium">
                            Entrar
                        </a>
                        <a href="{{ route('register.tenant') }}"
                           class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 text-sm">
                            Começar Grátis
                        </a>
                    @endauth
                </nav>
            </div>
        </div>
    </header>

    <main class="flex-1">
        {{-- Hero --}}
        <section class="relative pt-32 pb-20 px-4 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-b from-amber-500/5 via-transparent to-transparent pointer-events-none"></div>
            <div class="max-w-4xl mx-auto text-center relative">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-sm font-medium mb-8">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    Plataforma SaaS para restaurantes
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black leading-tight mb-6">
                    Cardápio Digital<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-600">
                        Inteligente e Completo
                    </span>
                </h1>
                <p class="text-lg sm:text-xl text-neutral-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                    Gerencie mesas, cardápio e pedidos do seu restaurante em tempo real.
                    Seus clientes acessam o cardápio pelo celular e fazem pedidos diretamente da mesa.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('register.tenant') }}"
                       class="px-8 py-4 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-bold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] text-lg">
                        Criar meu Cardápio Grátis
                    </a>
                    @auth
                        <a href="/dashboard"
                           class="px-8 py-4 bg-neutral-800 hover:bg-neutral-700 text-white font-semibold rounded-xl transition-all duration-200 text-lg">
                            Ir para o Dashboard
                        </a>
                    @endauth
                </div>
                <p class="text-sm text-neutral-600 mt-4">Grátis por tempo ilimitado • Até 2 mesas • Sem cartão de crédito</p>
            </div>
        </section>

        {{-- Features --}}
        <section class="py-20 px-4 border-t border-neutral-800/50">
            <div class="max-w-6xl mx-auto">
                <h2 class="text-3xl sm:text-4xl font-black text-center mb-4">Tudo que seu restaurante precisa</h2>
                <p class="text-neutral-400 text-center mb-16 max-w-xl mx-auto">
                    Uma plataforma completa para gerenciar seu restaurante com praticidade e tecnologia.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 rounded-2xl bg-neutral-900/50 border border-neutral-800 hover:border-amber-500/20 transition-all duration-300 group">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-lg mb-2">Cardápio Digital</h3>
                        <p class="text-neutral-400 text-sm leading-relaxed">Cardápio online com fotos, descrições e personalizações. Seus clientes acessam pelo QR Code da mesa.</p>
                    </div>

                    <div class="p-6 rounded-2xl bg-neutral-900/50 border border-neutral-800 hover:border-amber-500/20 transition-all duration-300 group">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-lg mb-2">Gestão de Mesas</h3>
                        <p class="text-neutral-400 text-sm leading-relaxed">Controle total das mesas com status em tempo real: livres, ocupadas ou reservadas. Criação em lote.</p>
                    </div>

                    <div class="p-6 rounded-2xl bg-neutral-900/50 border border-neutral-800 hover:border-amber-500/20 transition-all duration-300 group">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-lg mb-2">Notificações em Tempo Real</h3>
                        <p class="text-neutral-400 text-sm leading-relaxed">Pedidos chegam instantaneamente no dashboard. Acompanhe o status de cada pedido e notifique os clientes.</p>
                    </div>

                    <div class="p-6 rounded-2xl bg-neutral-900/50 border border-neutral-800 hover:border-amber-500/20 transition-all duration-300 group">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-lg mb-2">Relatórios e Métricas</h3>
                        <p class="text-neutral-400 text-sm leading-relaxed">Acompanhe faturamento, pedidos por período, desempenho do cardápio e muito mais com gráficos interativos.</p>
                    </div>

            <div class="p-6 rounded-2xl bg-neutral-900/50 border border-neutral-800 hover:border-amber-500/20 transition-all duration-300 group">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-lg mb-2">Delivery Inteligente</h3>
                <p class="text-neutral-400 text-sm leading-relaxed">Gestão completa de entregadores com app mobile próprio. Seus entregadores aceitam corridas e atualizam status em tempo real.</p>
            </div>

            <div class="p-6 rounded-2xl bg-neutral-900/50 border border-neutral-800 hover:border-amber-500/20 transition-all duration-300 group">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-lg mb-2">Pagamento PIX</h3>
                <p class="text-neutral-400 text-sm leading-relaxed">QR Code dinâmico para pagamento via PIX. O valor cai direto na conta do seu restaurante. Confirmação automática via webhook.</p>
            </div>
                </div>
            </div>
        </section>

        {{-- Plans --}}
        <section class="py-20 px-4 border-t border-neutral-800/50">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-3xl sm:text-4xl font-black mb-4">Planos Simples e Transparentes</h2>
                <p class="text-neutral-400 mb-16 max-w-xl mx-auto">Escolha o plano ideal para seu negócio. Sem taxas escondidas.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-2xl mx-auto">
                    <div class="p-8 rounded-2xl bg-neutral-900/50 border border-neutral-800 relative">
                        <h3 class="font-bold text-xl mb-2">Grátis</h3>
                        <p class="text-4xl font-black mb-6">R$ <span class="text-3xl">0</span></p>
                        <ul class="text-left space-y-3 text-neutral-400 mb-8">
                            <li class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Até 2 mesas
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Cardápio digital ilimitado
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Pedidos ilimitados
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                1 usuário
                            </li>
                        </ul>
                        <a href="{{ route('register.tenant') }}"
                           class="block w-full py-3 bg-neutral-800 hover:bg-neutral-700 text-white font-semibold rounded-xl transition-all duration-200">
                            Começar Grátis
                        </a>
                    </div>

                    <div class="p-8 rounded-2xl bg-gradient-to-br from-amber-500/10 to-transparent border border-amber-500/30 relative">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-amber-500 text-neutral-950 text-xs font-bold rounded-full">Recomendado</div>
                        <h3 class="font-bold text-xl mb-2">Premium</h3>
                        <p class="text-4xl font-black mb-2">R$ <span class="text-3xl">97</span><span class="text-lg text-neutral-400">,90/mês</span></p>
                        <p class="text-xs text-neutral-500 mb-6">
                            Descontos por período: 3m <span class="text-amber-400">-15%</span> • 6m <span class="text-amber-400">-23%</span> • 12m <span class="text-amber-400">-32%</span>
                        </p>
                        <ul class="text-left space-y-3 text-neutral-400 mb-8">
                            <li class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Até 50 mesas
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Cardápio digital ilimitado
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Pedidos ilimitados
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Múltiplos usuários
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Relatórios avançados
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Suporte prioritário
                            </li>
                        </ul>
                        <a href="{{ route('register.tenant') }}"
                           class="block w-full py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200">
                            Começar Trial Grátis
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-neutral-800/50 py-16 px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="w-8 h-8 rounded-xl bg-amber-500 flex items-center justify-center text-neutral-950 font-black text-sm">B</span>
                        <span class="font-bold text-base">BurguerSaaS</span>
                    </div>
                    <p class="text-sm text-neutral-500 leading-relaxed max-w-sm">Plataforma SaaS de gestão de restaurantes. Cardápio digital, pedidos, delivery e pagamentos.</p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-neutral-300 mb-4">Links</p>
                    <div class="space-y-3 text-sm">
                        <a href="{{ route('terms') }}" class="block text-neutral-500 hover:text-amber-400 transition-colors">Termos de Uso</a>
                        <a href="{{ route('privacy') }}" class="block text-neutral-500 hover:text-amber-400 transition-colors">Política de Privacidade</a>
                        <a href="{{ route('register.tenant') }}" class="block text-neutral-500 hover:text-amber-400 transition-colors">Criar Conta</a>
                    </div>
                </div>
                <div>
                    <p class="text-sm font-semibold text-neutral-300 mb-4">Redes Sociais</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="#" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="inline-block transform transition duration-200 hover:scale-125 hover:opacity-80">
                            <img src="{{ asset('image/sociais/instagram.png') }}" alt="Instagram" class="w-8 h-8">
                        </a>
                        <a href="#" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="inline-block transform transition duration-200 hover:scale-125 hover:opacity-80">
                            <img src="{{ asset('image/sociais/facebook.png') }}" alt="Facebook" class="w-8 h-8">
                        </a>
                        <a href="#" target="_blank" rel="noopener noreferrer" aria-label="Twitter" class="inline-block transform transition duration-200 hover:scale-125 hover:opacity-80">
                            <img src="{{ asset('image/sociais/twitter.png') }}" alt="Twitter" class="w-8 h-8">
                        </a>
                        <a href="#" target="_blank" rel="noopener noreferrer" aria-label="Telegram" class="inline-block transform transition duration-200 hover:scale-125 hover:opacity-80">
                            <img src="{{ asset('image/sociais/telegram.png') }}" alt="Telegram" class="w-8 h-8">
                        </a>
                        <a href="#" target="_blank" rel="noopener noreferrer" aria-label="Discord" class="inline-block transform transition duration-200 hover:scale-125 hover:opacity-80">
                            <img src="{{ asset('image/sociais/discord.png') }}" alt="Discord" class="w-8 h-8">
                        </a>
                        <a href="#" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" class="inline-block transform transition duration-200 hover:scale-125 hover:opacity-80">
                            <img src="{{ asset('image/sociais/whatzapp.png') }}" alt="WhatsApp" class="w-8 h-8">
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-t border-neutral-800/50 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-neutral-500">
                <p>&copy; {{ date('Y') }} BurguerSaaS. Todos os direitos reservados.</p>
                <p>Feito com <span class="text-amber-400">&hearts;</span> para restaurantes do Brasil</p>
            </div>
        </div>
    </footer>
</body>
</html>
