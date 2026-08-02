@extends('layouts.app', ['title' => 'Ativar Conta - Entregador'])

@section('content')
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-2xl shadow-2xl shadow-black/60 p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-500/10 flex items-center justify-center">
                <svg class="w-8 h-8 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold">Ativar Conta</h1>
            <p class="text-sm text-neutral-400 mt-1">{{ $name }} - {{ maskPhone($phone) }}</p>
        </div>

        <form method="POST" action="{{ route('delivery.invite.accept', $token) }}" enctype="multipart/form-data" class="space-y-4" x-data="{
            cpf: '',
            get cpfValid() {
                return isValidCpf(this.cpf);
            },
            get cpfState() {
                return this.cpf === '' ? 'empty' : (this.cpfValid ? 'ok' : 'err');
            },
            onCpfInput(e) {
                this.cpf = applyCpfMask(e.target.value);
            },
            plateMask(e) {
                let v = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g,'').substring(0,7);
                e.target.value = v.length<=3 ? v : v.substring(0,3)+'-'+v.substring(3);
            }
        }">
            @csrf

            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">Senha *</label>
                <input type="password" name="password" required minlength="6"
                       class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('password') border-red-500 @enderror">
                @error('password') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">Confirmar Senha *</label>
                <input type="password" name="password_confirmation" required minlength="6"
                       class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('password_confirmation') border-red-500 @enderror">
                @error('password_confirmation') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">Email *</label>
                <input type="email" name="email" required placeholder="seu@email.com"
                       class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('email') border-red-500 @enderror">
                @error('email') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-neutral-800 my-4"></div>

            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">CPF *</label>
                <div class="relative">
                    <input type="text" name="cpf" maxlength="14" placeholder="000.000.000-00" inputmode="numeric" x-model="cpf" @input="onCpfInput"
                           class="w-full px-3.5 py-2 pr-10 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('cpf') border-red-500 @enderror">
                    <span x-show="cpfState === 'ok'" class="absolute right-3 top-1/2 -translate-y-1/2 text-emerald-400 text-sm font-bold">✓</span>
                    <span x-show="cpfState === 'err'" class="absolute right-3 top-1/2 -translate-y-1/2 text-red-400 text-sm font-bold">✗</span>
                </div>
                <p class="mt-1 text-xs text-neutral-500" x-show="cpfState === 'err'" x-cloak>CPF inválido. Verifique os dígitos.</p>
                @error('cpf') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">CNH *</label>
                <input type="text" name="cnh" maxlength="20"
                       class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('cnh') border-red-500 @enderror">
                @error('cnh') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">Placa do Veículo *</label>
                <input type="text" name="vehicle_plate" maxlength="10" placeholder="ABC-1234"
                       @input="plateMask"
                       class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('vehicle_plate') border-red-500 @enderror">
                @error('vehicle_plate') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">Modelo do Veículo *</label>
                <input type="text" name="vehicle_model" placeholder="Ex: Honda CG 160"
                       class="w-full px-3.5 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('vehicle_model') border-red-500 @enderror">
                @error('vehicle_model') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">Foto de Perfil</label>
                <input type="file" name="avatar" accept="image/*"
                       class="w-full text-sm text-neutral-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-amber-500/10 file:text-amber-400 hover:file:bg-amber-500/20 @error('avatar') border-red-500 @enderror">
                @error('avatar') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="w-full px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold text-sm transition-all mt-2">
                Ativar Conta
            </button>
        </form>
    </div>
</div>

@include('partials.cpf-validator')
@endsection
