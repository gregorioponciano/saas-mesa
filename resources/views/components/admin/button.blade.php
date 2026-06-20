@props([
    'variant' => 'primary',
    'type' => 'button',
    'loading' => false,
    'icon' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 px-5 py-2.5 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed';

    $variants = [
        'primary' => 'bg-amber-500 hover:bg-amber-400 text-neutral-950',
        'secondary' => 'bg-neutral-800 hover:bg-neutral-700 text-white',
        'ghost' => 'bg-transparent hover:bg-neutral-800 text-neutral-300',
        'danger' => 'bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20',
    ];

    $variantClasses = $variants[$variant] ?? $variants['primary'];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "$baseClasses $variantClasses"]) }}
    @if ($loading) disabled @endif
>
    @if ($loading)
        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <span>{{ $slot }}</span>
    @else
        @if ($icon)
            {!! $icon !!}
        @endif
        {{ $slot }}
    @endif
</button>
