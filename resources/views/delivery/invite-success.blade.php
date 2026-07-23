@extends('layouts.app', ['title' => 'Conta Ativada'])

@section('content')
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-8 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-500/10 flex items-center justify-center">
            <svg class="w-8 h-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-xl font-bold">Conta Ativada!</h1>
        <p class="text-sm text-neutral-400 mt-2">Sua conta de entregador foi ativada com sucesso. Agora você pode fazer login no aplicativo usando seu telefone e a senha que definiu.</p>
        <a href="/" class="inline-block mt-6 px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold text-sm transition-all">
            Voltar
        </a>
    </div>
</div>
@endsection
