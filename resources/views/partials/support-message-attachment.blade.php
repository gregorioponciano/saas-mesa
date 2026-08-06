@if (! empty($msg['attachment_url']))
    @php $isImage = isset($msg['attachment_mime']) && str_starts_with($msg['attachment_mime'], 'image/'); @endphp
    @if ($isImage)
        <a href="{{ $msg['attachment_url'] }}" target="_blank" class="mt-2 block">
            <img src="{{ $msg['attachment_url'] }}" alt="{{ $msg['attachment_name'] ?? 'Anexo' }}"
                 class="max-w-[220px] max-h-40 rounded-lg border border-neutral-700/50">
        </a>
    @else
        <a href="{{ $msg['attachment_url'] }}" target="_blank"
           class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-neutral-800/50 border border-neutral-700/50 text-[11px] text-neutral-300 hover:border-amber-500/40 transition-colors">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18.828 7.828a4 4 0 00-5.656-5.656l-6.414 6.414a6 6 0 108.485 8.485L20.5 13"/>
            </svg>
            {{ $msg['attachment_name'] ?? 'Baixar anexo' }}
        </a>
    @endif
@endif