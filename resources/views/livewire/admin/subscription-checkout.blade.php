<div class="p-4 lg:p-8 space-y-6">
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold">Escolha seu Plano</h1>
        <p class="text-neutral-400 mt-2">Escolha o plano ideal para seu negocio</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-3xl mx-auto">
        {{-- Free Plan --}}
@if (!auth()->user()->tenant)
    <div class="max-w-md mx-auto mt-16 p-8 rounded-2xl bg-neutral-900 border border-neutral-800 text-center space-y-3">
        <p class="text-5xl">🏪</p>
        <h2 class="text-lg font-bold text-white">Nenhuma empresa vinculada</h2>
        <p class="text-sm text-neutral-400">Sua conta não está vinculada a nenhuma empresa.</p>
    </div>
    @php return; @endphp
@endif
        <div class="relative p-8 rounded-3xl bg-neutral-900/50 border {{ auth()->user()->tenant->isFree() ? 'border-amber-500/30 ring-2 ring-amber-500/20' : 'border-neutral-800' }} transition-all duration-300 hover:border-neutral-700">
            @if (auth()->user()->tenant->isFree())
                <span class="absolute -top-3 right-6 px-4 py-1 text-xs font-semibold rounded-full bg-amber-500 text-neutral-950">Seu Plano</span>
            @endif
            <div class="w-14 h-14 rounded-2xl bg-neutral-800 flex items-center justify-center mb-5">
                <svg class="w-7 h-7 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold mb-2">Gratuito</h2>
            <p class="text-4xl font-black mb-6">R$ <span class="text-4xl font-black">0</span></p>
            <ul class="space-y-3 mb-8">
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Ate 2 mesas
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Cardapio digital ilimitado
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Pedidos ilimitados
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    1 usuario (admin)
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Cupons de desconto
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Delivery com entregadores
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-500">
                    <svg class="w-5 h-5 text-neutral-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Programa de fidelidade (pontos)
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-500">
                    <svg class="w-5 h-5 text-neutral-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Relatorios avancados
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-500">
                    <svg class="w-5 h-5 text-neutral-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Multiplos usuarios
                </li>
            </ul>
            @if (auth()->user()->tenant->isFree())
                <div class="w-full py-3.5 px-4 bg-neutral-800 text-neutral-400 font-semibold rounded-xl text-center cursor-not-allowed">
                    Plano Atual
                </div>
            @else
                <form method="POST" action="{{ route('subscription.cancel') }}">
                    @csrf
                    <button type="submit"
                            class="w-full py-3.5 px-4 bg-neutral-800 hover:bg-neutral-700 text-white font-semibold rounded-xl transition-all duration-200">
                        Voltar para Gratuito
                    </button>
                </form>
            @endif
        </div>

        {{-- Paid Plan --}}
        <div class="relative p-8 rounded-3xl bg-gradient-to-b from-amber-500/10 to-amber-600/5 border-2 border-amber-500/30 transition-all duration-300 hover:border-amber-500/50">
            <span class="absolute -top-3 right-6 px-4 py-1 text-xs font-semibold rounded-full bg-amber-500 text-neutral-950">Popular</span>
            <div class="w-14 h-14 rounded-2xl bg-amber-500/20 flex items-center justify-center mb-5">
                <svg class="w-7 h-7 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold mb-2">Premium</h2>
            <p class="text-4xl font-black mb-6">R$ <span class="text-4xl font-black">97</span><span class="text-xl text-neutral-400">,90</span> <span class="text-sm text-neutral-500 font-normal">/mes</span></p>
            <ul class="space-y-3 mb-8">
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Mesas ilimitadas
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Cardapio digital ilimitado
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Pedidos ilimitados
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Multiplos usuarios (admin + atendente)
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Cupons de desconto
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Programa de fidelidade (pontos)
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Delivery com entregadores
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Relatorios e graficos avancados
                </li>
                <li class="flex items-center gap-3 text-sm text-neutral-300">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Suporte prioritario
                </li>
            </ul>
            @if (auth()->user()->tenant->isPaid())
                <div class="w-full py-3.5 px-4 bg-amber-500 text-neutral-950 font-semibold rounded-xl text-center cursor-not-allowed">
                    Plano Atual
                </div>
            @else
                <form method="POST" action="{{ route('subscription.checkout.store') }}">
                    @csrf
                    <input type="hidden" name="plan" value="premium">
                    <button type="submit"
                            class="w-full py-3.5 px-4 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                        Assinar Premium
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
