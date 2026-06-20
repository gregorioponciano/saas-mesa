@props([
    'variant' => 'neutral',
])

@php
    $variants = [
        'success' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
        'danger' => 'bg-red-500/10 text-red-400 border border-red-500/20',
        'warning' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
        'info' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
        'neutral' => 'bg-neutral-500/10 text-neutral-400 border border-neutral-500/20',
    ];

    $classes = $variants[$variant] ?? $variants['neutral'];
@endphp

<span {{ $attributes->merge(['class' => 'px-2 py-0.5 text-xs font-semibold rounded-full ' . $classes]) }}>
    {{ $slot }}
</span>
