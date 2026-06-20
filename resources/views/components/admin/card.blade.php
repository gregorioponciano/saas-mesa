@props([
    'padding' => true,
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl bg-neutral-900/50 border border-neutral-800 ' . ($padding ? 'p-6' : '') . ' ' . $class]) }}>
    {{ $slot }}
</div>
