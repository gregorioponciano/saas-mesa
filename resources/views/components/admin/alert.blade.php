@props([
    'variant' => 'success',
    'dismissible' => false,
])

@php
    $variants = [
        'success' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
        'error' => 'bg-red-500/10 text-red-400 border border-red-500/20',
        'warning' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
        'info' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
    ];

    $icons = [
        'success' => '<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'error' => '<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'warning' => '<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>',
        'info' => '<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    ];

    $classes = $variants[$variant] ?? $variants['success'];
    $icon = $icons[$variant] ?? $icons['success'];
@endphp

<div {{ $attributes->merge(['class' => 'p-4 rounded-xl text-sm flex items-start gap-3 ' . $classes]) }}>
    {!! $icon !!}
    <div class="flex-1">{{ $slot }}</div>
    @if ($dismissible)
        <button type="button" @click="$el.parentElement.remove()" class="shrink-0 opacity-60 hover:opacity-100 transition-opacity">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    @endif
</div>
