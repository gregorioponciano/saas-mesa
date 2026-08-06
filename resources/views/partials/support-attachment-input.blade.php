<div>
    <label class="flex items-center gap-2 cursor-pointer text-xs text-neutral-400 hover:text-neutral-200 transition-colors">
        <input type="file" wire:model="attachment" wire:loading.attr="disabled"
               class="text-xs text-neutral-400 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-neutral-800 file:text-neutral-200 file:cursor-pointer hover:file:bg-neutral-700 file:transition-colors disabled:opacity-40 disabled:cursor-wait">
    </label>
    <span wire:loading wire:target="attachment" class="text-[11px] text-amber-400 mt-1">Enviando anexo...</span>
    @error('attachment')
        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
    @enderror
    @if ($attachment && empty($errors->get('attachment')))
        <div class="mt-2 flex items-center gap-2 text-[11px] text-neutral-400">
            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="truncate max-w-[220px]">{{ $attachment->getClientOriginalName() }}</span>
            <button type="button" wire:click="resetSupportAttachment" wire:loading.attr="disabled" class="text-red-400 hover:text-red-300 transition-colors">remover</button>
        </div>
    @endif
</div>