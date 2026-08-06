<div x-data="notifications()"
     x-on:notify.window="add($event.detail)"
     x-on:delivery-new-order.window="add(message='Novo pedido de entrega disponível!', type='order')"
     x-on:delivery-status.window="add($event.detail)"
     class="fixed top-0 left-0 right-0 z-[90] flex flex-col items-center pointer-events-none"
     x-cloak>
    <template x-for="(n, index) in notifications" :key="n.id">
        <div x-show="n.show"
             x-transition:enter="transition ease-out duration-400"
             x-transition:enter-start="opacity-0 -translate-y-6 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-6 scale-95"
             class="w-full max-w-2xl px-4 pt-4 pointer-events-auto">
            <div class="flex items-center gap-4 px-6 py-4 rounded-2xl border shadow-2xl backdrop-blur-xl"
                 :class="n.type === 'order' ? 'bg-gradient-to-r from-amber-500/15 via-amber-500/10 to-neutral-900 border-amber-500/20 shadow-amber-500/10' :
                         n.type === 'success' ? 'bg-gradient-to-r from-emerald-500/15 via-emerald-500/10 to-neutral-900 border-emerald-500/20 shadow-emerald-500/10' :
                         n.type === 'error' ? 'bg-gradient-to-r from-red-500/15 via-red-500/10 to-neutral-900 border-red-500/20 shadow-red-500/10' :
                         'bg-gradient-to-r from-violet-500/15 via-violet-500/10 to-neutral-900 border-violet-500/20 shadow-violet-500/10'">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                     :class="n.type === 'order' ? 'bg-amber-500/20' : n.type === 'success' ? 'bg-emerald-500/20' : n.type === 'error' ? 'bg-red-500/20' : 'bg-violet-500/20'">
                    <svg class="w-5 h-5" :class="n.type === 'order' ? 'text-amber-400 animate-pulse' : n.type === 'success' ? 'text-emerald-400' : n.type === 'error' ? 'text-red-400' : 'text-violet-400'"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path x-show="n.type === 'order'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        <path x-show="n.type === 'success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        <path x-show="n.type === 'error'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        <path x-show="n.type !== 'order' && n.type !== 'success' && n.type !== 'error'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold" :class="n.type === 'order' ? 'text-amber-400' : n.type === 'success' ? 'text-emerald-400' : n.type === 'error' ? 'text-red-400' : 'text-violet-400'" x-text="n.title || 'Notificação'"></p>
                    <p class="text-sm text-neutral-200" x-text="n.message"></p>
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
    let notifId = 0;
    function notifications() {
        return {
            notifications: [],
            add(detail, type, message) {
                const data = typeof detail === 'string'
                    ? { message: detail, type: type || 'info', title: 'Notificação' }
                    : (detail?.message ? { ...detail, type: detail.type || 'info' } : { message: '', type: 'info', title: 'Notificação' });

                if (!data.message) return;
                data.id = ++notifId;
                data.show = true;
                this.notifications.push(data);
                setTimeout(() => {
                    const idx = this.notifications.findIndex(n => n.id === data.id);
                    if (idx !== -1) this.remove(idx);
                }, 5000);
            },
            remove(index) {
                if (this.notifications[index]) {
                    this.notifications[index].show = false;
                    setTimeout(() => this.notifications.splice(index, 1), 300);
                }
            }
        }
    }
</script>
