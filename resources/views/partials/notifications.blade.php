<div x-data="notifications()"
     x-on:notify.window="add($event.detail.message)"
     class="fixed top-0 left-0 right-0 z-[90] flex flex-col items-center pointer-events-none"
     x-cloak>
    <template x-for="(notification, index) in notifications" :key="index">
        <div x-show="notification.show"
             x-transition:enter="transition ease-out duration-400"
             x-transition:enter-start="opacity-0 -translate-y-6 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-6 scale-95"
             class="w-full max-w-2xl px-4 pt-4 pointer-events-auto">
            <div class="flex items-center gap-4 px-6 py-4 rounded-2xl bg-gradient-to-r from-amber-500/15 via-amber-500/10 to-neutral-900 border border-amber-500/20 shadow-2xl shadow-amber-500/10 backdrop-blur-xl">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-400 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-amber-400">Novo Pedido</p>
                    <p class="text-sm text-neutral-200" x-text="notification.message"></p>
                </div>
                <button @click="remove(index)"
                        class="p-1.5 rounded-lg hover:bg-neutral-800 text-neutral-500 hover:text-white transition-all shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </template>
</div>

<script>
    function notifications() {
        return {
            notifications: [],
            add(message) {
                const msg = typeof message === 'string' ? message : (message?.message || '');
                if (!msg) return;
                this.notifications.push({ message: msg, show: true });
                setTimeout(() => {
                    if (this.notifications.length > 0) {
                        this.notifications[0].show = false;
                        setTimeout(() => this.notifications.shift(), 300);
                    }
                }, 3000);
            },
            remove(index) {
                this.notifications[index].show = false;
                setTimeout(() => this.notifications.splice(index, 1), 300);
            }
        }
    }
</script>
