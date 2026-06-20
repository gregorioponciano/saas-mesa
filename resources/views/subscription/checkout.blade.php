@extends('layouts.admin')

@section('content')
<div class="p-4 lg:p-8 max-w-5xl mx-auto">

    {{-- Pending Payment --}}
    @if ($pendingPayment)
    <div id="pix-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-neutral-900 rounded-3xl p-8 max-w-md w-full mx-4 border border-neutral-700 shadow-2xl text-center">
            <div class="w-16 h-16 rounded-full bg-emerald-500/20 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold mb-2">Pagamento Pendente</h2>
            <p class="text-neutral-400 mb-6 text-sm">Plano <strong class="text-white">{{ $pendingPayment['plan']->name ?? 'Premium' }}</strong></p>

            @if (!empty($pendingPayment['expired']))
                <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20">
                    <p class="text-red-400 font-medium text-sm mb-1">PIX expirado</p>
                    <p class="text-neutral-500 text-xs">O QR Code gerou em {{ $pendingPayment['expires_at']?->format('d/m/Y H:i') }} e já expirou. Gere um novo para continuar.</p>
                </div>
                <form method="POST" action="{{ route('subscription.checkout.store') }}">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $pendingPayment['plan']->slug ?? 'premium' }}">
                    <button type="submit" class="w-full py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl text-sm">Gerar novo PIX</button>
                </form>
            @else
                @if ($pendingPayment['qrcode'])
                    <img src="data:image/png;base64,{{ $pendingPayment['qrcode'] }}" alt="QR Code PIX" class="w-56 h-56 mx-auto rounded-2xl bg-white p-2 mb-6">
                @endif

                @if ($pendingPayment['copy_paste'])
                <div class="mb-6 text-left">
                    <label class="text-xs text-neutral-500 block mb-2">Código PIX Copia e Cola:</label>
                    <div class="flex gap-2">
                        <input type="text" readonly value="{{ $pendingPayment['copy_paste'] }}" class="flex-1 bg-neutral-800 border border-neutral-700 rounded-xl px-4 py-3 text-xs text-neutral-300 font-mono" id="pix-code">
                        <button onclick="navigator.clipboard.writeText('{{ $pendingPayment['copy_paste'] }}').then(() => { this.textContent = 'Copiado!'; setTimeout(() => this.textContent = 'Copiar', 2000); })" class="px-4 py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl text-sm whitespace-nowrap">Copiar</button>
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-center gap-2 text-sm text-neutral-500 mb-6">
                <div class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></div>
                <span>Aguardando pagamento...</span>
            </div>

            <div class="flex gap-3">
                <form method="POST" action="{{ route('subscription.checkout.store') }}" class="flex-1">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $pendingPayment['plan']->slug ?? 'premium' }}">
                    <button type="submit" class="w-full py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl text-sm">Atualizar</button>
                </form>
                <a href="{{ route('subscription.checkout') }}" class="flex-1 py-3 bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-semibold rounded-xl text-sm text-center">Fechar</a>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Plan Status --}}
    @if ($currentSubscription)
    @php
        $now = now();
        $endsAt = $currentSubscription->current_period_end;
        $diff = $endsAt ? $now->diff($endsAt) : null;
        $expired = $endsAt && $now->isAfter($endsAt);

        $totalDays = $endsAt && $currentSubscription->current_period_start
            ? $now->diffInDays($currentSubscription->current_period_start)
            : 30;
        $remainingDays = $diff ? max(0, $diff->days) : 0;
        $progressPct = $totalDays > 0 ? min(100, ($remainingDays / $totalDays) * 100) : 0;
        $isEnding = $diff && $diff->days <= 7 && !$expired;
    @endphp
    <div class="mb-8 p-6 rounded-2xl bg-gradient-to-br {{ $expired ? 'from-red-500/10 to-red-600/5 border border-red-500/20' : ($isEnding ? 'from-amber-500/10 to-amber-600/5 border border-amber-500/20' : 'from-emerald-500/10 to-emerald-600/5 border border-emerald-500/20') }}">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 {{ $expired ? 'bg-red-500/20' : ($isEnding ? 'bg-amber-500/20' : 'bg-emerald-500/20') }}">
                @if ($expired)
                    <svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @else
                    <svg class="w-6 h-6 {{ $isEnding ? 'text-amber-400' : 'text-emerald-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="text-xl font-black tracking-tight {{ $expired ? 'text-red-400' : ($isEnding ? 'text-amber-400' : 'text-emerald-400') }}">
                                {{ $tenant->isPaid() ? 'Premium' : 'Gratuito' }}
                            </h3>
                            <span class="px-3 py-1 text-xs font-bold rounded-full {{ $expired ? 'bg-red-500/20 text-red-400' : ($isEnding ? 'bg-amber-500/20 text-amber-400' : 'bg-emerald-500/20 text-emerald-400') }}">
                                {{ $expired ? 'Expirado' : ($isEnding ? 'Vencendo' : 'Ativo') }}
                            </span>
                        </div>
                        @if ($diff && !$expired)
                            <div class="flex items-baseline gap-2 mt-2">
                                <span class="text-sm text-neutral-400">Restam</span>
                                <span class="text-2xl font-black tracking-tight text-white drop-shadow-sm">{{ $diff->d }}d</span>
                                <span class="text-lg font-bold text-neutral-300">{{ $diff->h }}h</span>
                                <span class="text-lg font-bold text-neutral-300">{{ $diff->i }}m</span>
                                <span class="text-sm text-neutral-500 ml-1">· Vence em {{ $endsAt->format('d/m/Y') }}</span>
                            </div>
                            <div class="mt-3 w-full max-w-xs h-1.5 rounded-full bg-neutral-800 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700 {{ $expired ? 'bg-red-500' : ($isEnding ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $progressPct }}%"></div>
                            </div>
                        @elseif ($expired)
                            <p class="text-sm text-red-400/80 mt-1 font-medium">Sua assinatura expirou. Escolha um período e renove abaixo.</p>
                        @else
                            <p class="text-sm text-neutral-500 mt-1">Sem data de expiração definida.</p>
                        @endif
                    </div>
                    @if ($isEnding)
                        <span class="px-4 py-2 text-sm font-bold rounded-xl bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/25 animate-pulse">
                            Expira em {{ $diff->d }}d
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Tabs --}}
    @php $tab = request('tab', 'planos'); @endphp
    <div class="flex gap-1 mb-8 p-1 rounded-xl bg-neutral-900/50 border border-neutral-800 w-fit">
        <a href="{{ route('subscription.checkout', ['tab' => 'planos']) }}" class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all {{ $tab === 'planos' ? 'bg-amber-500 text-neutral-950 shadow-lg' : 'text-neutral-400 hover:text-white' }}">Planos</a>
        <a href="{{ route('subscription.checkout', ['tab' => 'historico']) }}" class="px-5 py-2.5 text-sm font-medium rounded-lg transition-all {{ $tab === 'historico' ? 'bg-amber-500 text-neutral-950 shadow-lg' : 'text-neutral-400 hover:text-white' }}">Histórico</a>
    </div>

    {{-- Tab: Planos --}}
    @if ($tab === 'planos')
    <script>
        const discounts = {1:0, 3:15, 6:23, 12:32};
        function updatePrice(select) {
            var card = select.closest('.plan-card');
            var pricePerMonth = parseFloat(card.dataset.pricePerMonth);
            var months = parseInt(select.value);
            var fullPrice = pricePerMonth * months;
            var discount = discounts[months] || 0;
            var total = fullPrice * (100 - discount) / 100;
            card.querySelector('.plan-total').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
            card.querySelector('input[name="months"]').value = months;
        }
    </script>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-3xl mx-auto">
        {{-- Free --}}
        <div class="relative p-8 rounded-3xl bg-neutral-900/50 border {{ $tenant->isFree() ? 'border-amber-500/30 ring-2 ring-amber-500/20' : 'border-neutral-800' }} transition-all duration-300">
            @if ($tenant->isFree())
                <span class="absolute -top-3 right-6 px-4 py-1 text-xs font-semibold rounded-full bg-amber-500 text-neutral-950">Atual</span>
            @endif
            <div class="w-14 h-14 rounded-2xl bg-neutral-800 flex items-center justify-center mb-5">
                <svg class="w-7 h-7 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold mb-2">Gratuito</h2>
            <p class="text-4xl font-black mb-6">R$ 0</p>
            <ul class="space-y-3 mb-8 text-sm">
                <li class="flex items-center gap-3 text-neutral-300"><svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Até 2 mesas</li>
                <li class="flex items-center gap-3 text-neutral-300"><svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Cardápio digital ilimitado</li>
                <li class="flex items-center gap-3 text-neutral-300"><svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Pedidos ilimitados</li>
                <li class="flex items-center gap-3 text-neutral-300"><svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> 1 usuário (admin)</li>
                <li class="flex items-center gap-3 text-neutral-500"><svg class="w-5 h-5 text-neutral-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Relatórios avançados</li>
                <li class="flex items-center gap-3 text-neutral-500"><svg class="w-5 h-5 text-neutral-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Múltiplos usuários</li>
            </ul>
            <div class="w-full py-3.5 px-4 rounded-xl text-center font-semibold {{ $tenant->isFree() ? 'bg-neutral-800 text-neutral-400 cursor-not-allowed' : 'bg-neutral-800 text-neutral-500 cursor-not-allowed' }}">{{ $tenant->isFree() ? 'Plano Atual' : 'Premium Ativo' }}</div>
        </div>

        {{-- Premium --}}
        @php $plan = $plans->where('slug', 'premium')->first(); @endphp
        @if ($plan)
        <div class="plan-card relative p-8 rounded-3xl bg-gradient-to-b from-amber-500/10 to-amber-600/5 border-2 border-amber-500/30" data-price-per-month="{{ $plan->price_cents / 100 }}">
            <span class="absolute -top-3 right-6 px-4 py-1 text-xs font-semibold rounded-full bg-amber-500 text-neutral-950">Popular</span>
            <div class="w-14 h-14 rounded-2xl bg-amber-500/20 flex items-center justify-center mb-5">
                <svg class="w-7 h-7 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold mb-2">Premium</h2>
            <p class="text-sm text-neutral-500 mb-1">R$ {{ number_format($plan->price_cents / 100, 2, ',', '.') }}/mês</p>
            <p class="text-3xl font-black mb-2 plan-total">R$ {{ number_format($plan->price_cents / 100, 2, ',', '.') }}</p>
            <p class="text-xs text-neutral-500 mb-6">à vista</p>
            <ul class="space-y-3 mb-8 text-sm">
                <li class="flex items-center gap-3 text-neutral-300"><svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Mesas ilimitadas</li>
                <li class="flex items-center gap-3 text-neutral-300"><svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Cardápio digital ilimitado</li>
                <li class="flex items-center gap-3 text-neutral-300"><svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Pedidos ilimitados</li>
                <li class="flex items-center gap-3 text-neutral-300"><svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Múltiplos usuários</li>
                <li class="flex items-center gap-3 text-neutral-300"><svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Relatórios e gráficos</li>
                <li class="flex items-center gap-3 text-neutral-300"><svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Suporte prioritário</li>
            </ul>

            <form method="POST" action="{{ route('subscription.checkout.store') }}">
                @csrf
                <input type="hidden" name="plan" value="premium">
                <input type="hidden" name="months" value="1">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-neutral-400 mb-2">Período</label>
                    <select onchange="updatePrice(this)" class="w-full bg-neutral-800 border border-neutral-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-amber-500/50 text-sm">
                        @foreach ([1, 3, 6, 12] as $m)
                            @php
                                $discountPct = \App\Models\SaasPlan::getDiscountPercent($m);
                                $full = $plan->price_cents * $m;
                                $total = (int) round($full * (100 - $discountPct) / 100);
                            @endphp
                            <option value="{{ $m }}" {{ $m === 1 ? 'selected' : '' }}>
                                {{ $m }} {{ $m === 1 ? 'mês' : 'meses' }}
                                @if ($discountPct > 0)
                                    — R$ {{ number_format($total / 100, 2, ',', '.') }}
                                    <span class="text-emerald-400">({{ $discountPct }}% off)</span>
                                @else
                                    — R$ {{ number_format($total / 100, 2, ',', '.') }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full py-3.5 px-4 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200">
                    {{ $tenant->isPaid() ? 'Renovar Premium' : 'Assinar Premium' }}
                </button>
            </form>
        </div>
        @endif
    </div>
    @endif

    {{-- Tab: Histórico --}}
    @if ($tab === 'historico')
    <div class="rounded-2xl bg-neutral-900/50 border border-neutral-800 p-6 lg:p-8">
        <h2 class="text-lg font-semibold mb-6">Histórico de Pagamentos</h2>

        @if ($paymentHistory->isEmpty())
            <div class="text-center py-12">
                <svg class="w-12 h-12 text-neutral-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                </svg>
                <p class="text-neutral-500">Nenhum pagamento encontrado.</p>
                <p class="text-neutral-600 text-sm mt-1">Os pagamentos realizados aparecerão aqui.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($paymentHistory as $payment)
                <div class="flex items-center justify-between p-4 rounded-xl bg-neutral-800/30 border border-neutral-800">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-sm">{{ $payment->subscription?->plan?->name ?? 'Premium' }}</p>
                            <p class="text-xs text-neutral-500">{{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : '-' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-emerald-400">R$ {{ number_format($payment->amount_cents / 100, 2, ',', '.') }}</p>
                        <span class="text-xs text-neutral-500">{{ $payment->method ?? 'pix' }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
    @endif
</div>
@endsection
