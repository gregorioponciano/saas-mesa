@props([
    'title' => '',
    'subtitle' => '',
    'icon' => null,
])

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div class="flex items-start gap-3">
        @if ($icon)
            <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center shrink-0">
                {!! $icon !!}
            </div>
        @endif
        <div>
            <h1 class="text-2xl font-bold">{{ $title }}</h1>
            @if ($subtitle)
                <p class="text-sm text-neutral-400 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @if (isset($action))
        <div class="flex gap-2 shrink-0">
            {{ $action }}
        </div>
    @endif
</div>
