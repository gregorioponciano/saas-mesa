<div class="p-4 lg:p-8 space-y-6">
    <x-admin.page-header
        title="Configuração de Email (SMTP)"
        subtitle="Configure o servidor de email para envio de notificações e recuperação de senha"
    />

    <x-admin.card>
        <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Host SMTP</label>
                <input wire:model="mailHost" type="text" placeholder="smtp.gmail.com"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('mailHost') border-red-500 @enderror">
                @error('mailHost') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Porta</label>
                <input wire:model="mailPort" type="text" placeholder="587"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('mailPort') border-red-500 @enderror">
                @error('mailPort') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Usuário</label>
                <input wire:model="mailUsername" type="text" placeholder="seuemail@gmail.com"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('mailUsername') border-red-500 @enderror">
                @error('mailUsername') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Senha</label>
                <input wire:model="mailPassword" type="password" placeholder="Senha do email"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('mailPassword') border-red-500 @enderror">
                @error('mailPassword') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Criptografia</label>
                <input wire:model="mailEncryption" type="text" placeholder="tls"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('mailEncryption') border-red-500 @enderror">
                @error('mailEncryption') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Email de Origem</label>
                <input wire:model="mailFromAddress" type="email" placeholder="naoresponda@restaurante.com"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('mailFromAddress') border-red-500 @enderror">
                @error('mailFromAddress') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Nome de Origem</label>
                <input wire:model="mailFromName" type="text" placeholder="Meu Restaurante"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('mailFromName') border-red-500 @enderror">
                @error('mailFromName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2 flex items-center gap-3 pt-2">
                <x-admin.button variant="primary" type="submit">
                    Salvar Configurações de Email
                </x-admin.button>
            </div>
        </form>
    </x-admin.card>

    {{-- Info --}}
    <x-admin.card>
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-neutral-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-neutral-500">
                <p class="font-medium text-neutral-400 mb-1">Onde encontrar essas informações?</p>
                <p>Se você usa Gmail, acesse <a href="https://myaccount.google.com/apppasswords" target="_blank" class="text-amber-500 hover:text-amber-400 underline">Senhas de App do Google</a> e gere uma senha para "Outro (nome personalizado)". Use seu email completo como usuário e a senha gerada.</p>
                <p class="mt-2">Provedores comuns:</p>
                <ul class="mt-1 space-y-1 list-disc list-inside">
                    <li><strong class="text-neutral-300">Gmail:</strong> smtp.gmail.com, Porta 587, TLS</li>
                    <li><strong class="text-neutral-300">Outlook/Hotmail:</strong> smtp.office365.com, Porta 587, TLS</li>
                    <li><strong class="text-neutral-300">Mailtrap (testes):</strong> sandbox.smtp.mailtrap.io, Porta 2525</li>
                </ul>
                <p class="mt-2">Suas credenciais são criptografadas (AES-256-GCM) antes de salvar no banco de dados.</p>
            </div>
        </div>
    </x-admin.card>
</div>
