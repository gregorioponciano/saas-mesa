<div class="p-4 lg:p-8 max-w-3xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold">Configuração EfiBank</h1>
        <p class="text-neutral-400 mt-1 text-sm">
            Sua conta EfiBank para receber pagamentos dos clientes via PIX
        </p>
    </div>

    {{-- Status atual --}}
    @if ($has_credentials && !$saved && !$error)
        <div class="mb-8 p-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-emerald-400">Credenciais configuradas</h3>
                    <p class="text-sm text-neutral-400 mt-1">
                        Client ID: <span class="font-mono text-neutral-300">{{ $masked_client_id }}</span><br>
                        Pix: <span class="font-mono text-neutral-300">{{ $masked_pix_key ?? '---' }}</span><br>
                        Ambiente: <span class="font-semibold text-neutral-300">{{ $account_type_display }}</span>
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Teste de conexão --}}
    @if ($has_credentials)
        <div class="mb-8">
            <button wire:click="testConnection" wire:loading.attr="disabled"
                    class="px-6 py-3 bg-neutral-800 hover:bg-neutral-700 text-white font-semibold rounded-xl transition-all text-sm flex items-center gap-2">
                @if ($testing)
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Testando...
                @else
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Testar Conexão
                @endif
            </button>

            @if ($test_message)
                <div class="mt-4 p-4 rounded-xl {{ $test_result ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' }}">
                    <div class="flex items-start gap-3">
                        @if ($test_result)
                            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @else
                            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @endif
                        <p class="text-sm">{{ $test_message }}</p>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Formulario --}}
    <div class="rounded-2xl bg-neutral-900/50 border border-neutral-800 p-6 lg:p-8">
        <h2 class="text-lg font-semibold mb-6">
            {{ $has_credentials ? 'Atualizar Credenciais' : 'Configurar Credenciais' }}
        </h2>

        {{-- Erro --}}
        @if ($error)
            <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                {{ $error }}
            </div>
        @endif

        {{-- Sucesso --}}
        @if ($saved)
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Credenciais salvas com sucesso!
            </div>
        @endif

        <form wire:submit="save" class="space-y-5">
            {{-- Client ID --}}
            <div>
                <label for="client_id" class="block text-sm font-medium text-neutral-300 mb-2">
                    Client ID
                </label>
                <input type="text" id="client_id" wire:model="client_id"
                       placeholder="Client_Id_..."
                       class="w-full bg-neutral-800/50 border border-neutral-700 rounded-xl px-4 py-3 text-white placeholder-neutral-600 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 transition-all">
                @error('client_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Client Secret --}}
            <div>
                <label for="client_secret" class="block text-sm font-medium text-neutral-300 mb-2">
                    Client Secret
                </label>
                <input type="password" id="client_secret" wire:model="client_secret"
                       placeholder="Client_Secret_..."
                       class="w-full bg-neutral-800/50 border border-neutral-700 rounded-xl px-4 py-3 text-white placeholder-neutral-600 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 transition-all">
                @error('client_secret') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- PIX Key --}}
            <div>
                <label for="pix_key" class="block text-sm font-medium text-neutral-300 mb-2">
                    Chave PIX
                </label>
                <input type="text" id="pix_key" wire:model="pix_key"
                       placeholder="Chave aleatória ou CPF/CNPJ..."
                       class="w-full bg-neutral-800/50 border border-neutral-700 rounded-xl px-4 py-3 text-white placeholder-neutral-600 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 transition-all">
                @error('pix_key') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Certificado .p12 --}}
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">
                    Certificado (.p12)
                </label>
                <div class="relative">
                    <input type="file" wire:model="certificate_file" accept=".p12"
                           class="w-full text-sm text-neutral-400 file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border-0 file:bg-amber-500 file:text-neutral-950 file:font-semibold file:cursor-pointer hover:file:bg-amber-400 transition-all cursor-pointer bg-neutral-800/50 border border-neutral-700 rounded-xl">
                </div>
                @error('certificate_file') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                @if ($has_credentials)
                    <p class="text-xs text-neutral-500 mt-2">
                        Deixe em branco para manter o certificado atual.
                    </p>
                @endif
            </div>

            {{-- Senha do Certificado --}}
            <div>
                <label for="cert_password" class="block text-sm font-medium text-neutral-300 mb-2">
                    Senha do Certificado
                </label>
                <input type="password" id="cert_password" wire:model="cert_password"
                       placeholder="Senha do arquivo .p12..."
                       class="w-full bg-neutral-800/50 border border-neutral-700 rounded-xl px-4 py-3 text-white placeholder-neutral-600 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 transition-all">
                @error('cert_password') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-neutral-500 mt-2">
                    O arquivo .p12 já contém o certificado + chave privada. A senha protege este arquivo.
                </p>
            </div>

            <input type="hidden" wire:model="account_type" value="production">

            {{-- Buttons --}}
            <div class="flex items-center gap-3 pt-4">
                <button type="submit" wire:loading.attr="disabled" wire:target="save"
                        class="px-8 py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all flex items-center gap-2 disabled:opacity-60">
                    @if ($saving)
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Salvando...
                    @else
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Salvar Credenciais
                    @endif
                </button>

                @if ($has_credentials)
                    <button type="button" wire:click="clearFields"
                            class="px-6 py-3 bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-semibold rounded-xl transition-all text-sm">
                        Limpar Campos
                    </button>
                @endif
            </div>
        </form>
    </div>

    {{-- Info --}}
    <div class="mt-8 p-5 rounded-2xl bg-neutral-900/50 border border-neutral-800">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-neutral-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-neutral-500">
                <p class="font-medium text-neutral-400 mb-1">Onde encontrar essas informações?</p>
                <p>Acesse o <a href="https://gerencianet.com.br" target="_blank" class="text-amber-500 hover:text-amber-400 underline">painel EfiBank</a> &rarr; API &rarr; Credenciais de Aplicação.</p>
                <p class="mt-1">Suas credenciais são criptografadas (AES-256-GCM) antes de salvar no banco de dados. O certificado .p12 contém o certificado + chave privada em um único arquivo protegido por senha.</p>
            </div>
        </div>
    </div>
</div>
