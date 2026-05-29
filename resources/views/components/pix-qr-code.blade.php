<div class="flex flex-col items-center gap-4 p-4">
    @if ($loading)
        <div class="flex items-center justify-center h-64 w-full">
            <svg class="animate-spin h-12 w-12 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    @elseif ($qrCode)
        <div class="bg-white rounded-xl p-3 shadow-lg">
            <img src="data:image/png;base64,{{ $qrCode }}" alt="PIX QR Code" class="w-64 h-64">
        </div>
        <div class="w-full space-y-2">
            <label class="block text-xs font-medium text-neutral-400">PIX Copia e Cola</label>
            <div class="flex gap-2">
                <input type="text" value="{{ $copiaECola }}" readonly
                    class="flex-1 px-3 py-2 text-xs bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-300 select-all font-mono"
                    id="pix-copy-{{ $id }}">
                <button onclick="navigator.clipboard.writeText(document.getElementById('pix-copy-{{ $id }}').value).then(() => { this.innerHTML = 'Copiado!'; setTimeout(() => { this.innerHTML = 'Copiar'; }, 2000); })"
                    class="px-4 py-2 text-xs font-medium text-neutral-900 bg-amber-400 hover:bg-amber-500 rounded-lg transition-colors">
                    Copiar
                </button>
            </div>
        </div>
        <p class="text-xs text-neutral-500 text-center">Escaneie o QR Code ou copie o codigo PIX para pagar com seu banco</p>
    @else
        <div class="flex items-center justify-center h-64 w-full">
            <p class="text-sm text-neutral-400">Falha ao gerar QR Code. Tente novamente.</p>
        </div>
    @endif
</div>
