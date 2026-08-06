<div wire:poll.5s class="shrink-0">
    @if ($openPlatformTicketsCount > 0)
        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-500 text-white">{{ $openPlatformTicketsCount }}</span>
    @endif
</div>