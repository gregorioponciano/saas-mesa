@extends('layouts.app', ['title' => 'Login Entregador'])

@section('content')
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-8">
        <div class="text-center mb-6">
            <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-amber-500/10 flex items-center justify-center">
                <svg class="w-7 h-7 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold">Entregador</h1>
            <p class="text-sm text-neutral-400 mt-1">Faça login para acessar o painel</p>
        </div>

        <form method="POST" action="{{ route('delivery.login') }}" class="space-y-4"
              x-data="{ phoneDisplay: '', phoneMask(v) { let r = (v||'').replace(/\D/g,'').substring(0,11); return r.length<=2 ? (r.length ? '('+r : '') : r.length<=6 ? '('+r.substring(0,2)+') '+r.substring(2) : r.length<=7 ? '('+r.substring(0,2)+') '+r.substring(2,7) : '('+r.substring(0,2)+') '+r.substring(2,7)+'-'+r.substring(7); }, init() { this.phoneDisplay = this.phoneMask('{{ old('phone') }}'); } }"
              @submit.prevent="let i = document.createElement('input'); i.type = 'hidden'; i.name = 'phone'; i.value = phoneDisplay.replace(/\D/g,''); $event.target.appendChild(i); $event.target.submit();">
            @csrf

            @if ($errors->any())
                <div class="p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-sm text-red-400">
                    {{ $errors->first() }}
                </div>
            @endif

            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">Telefone</label>
                <input type="tel" inputmode="numeric" placeholder="(11) 99999-9999" maxlength="15" required
                       x-model="phoneDisplay"
                       @input="phoneDisplay = phoneMask($event.target.value)"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
            </div>

            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">Senha</label>
                <input type="password" name="password" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
            </div>

            <button type="submit"
                    class="w-full px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold text-sm transition-all">
                Entrar
            </button>

            <p class="text-center text-sm text-neutral-500">
                <a href="{{ route('delivery.forgot.form') }}" class="text-violet-400 hover:text-violet-300 transition-colors">Esqueci minha senha</a>
            </p>
        </form>
    </div>
</div>
@endsection
