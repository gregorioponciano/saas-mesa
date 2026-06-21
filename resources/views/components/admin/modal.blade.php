@props([
    'show' => false,
    'maxWidth' => 'max-w-lg',
    'title' => '',
])

@if ($show)
    <div
        {{ $attributes->wire('key') ? '' : '' }}
        class="fixed inset-0 z-60"
        x-data="{ open: @entangle(($show === true ? 'showForm' : $show)) }"
        x-show="open"
        x-cloak
        @keydown.window.escape.window="open = false"
    >
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="open = false"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full {{ $maxWidth }} p-6 rounded-2xl bg-gradient-to-br from-neutral-900 to-neutral-950 border border-neutral-800 shadow-2xl shadow-black/30">
                @if ($title)
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-lg">{{ $title }}</h3>
                        <button type="button" @click="open = false" class="p-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif
                {{ $slot }}
            </div>
        </div>
    </div>
@endif
