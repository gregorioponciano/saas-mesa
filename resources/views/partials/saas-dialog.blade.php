{{-- Modal de confirmação/alerta padrão do SaaS (vanilla JS, sem SweetAlert2) --}}
<script>
    (function () {
        let root = null;

        function build() {
            root = document.createElement('div');
            root.setAttribute('style', 'display:none');
            root.innerHTML = `
                <div id="saas-dialog-overlay" style="background:rgba(0,0,0,.6);backdrop-filter:blur(4px)"
                     class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0" id="saas-dialog-backdrop"></div>
                    <div class="relative w-full max-w-md rounded-3xl bg-neutral-900 border border-neutral-700 shadow-2xl p-8 text-center">
                        <div id="saas-dialog-icon" class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center bg-amber-500/20">
                            <svg id="saas-dialog-icon-svg" class="w-8 h-8 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 id="saas-dialog-title" class="text-xl font-bold text-white mb-2"></h3>
                        <p id="saas-dialog-message" class="text-sm text-neutral-400 whitespace-pre-line"></p>
                        <div class="mt-8 flex gap-3">
                            <button type="button" id="saas-dialog-cancel"
                                    class="flex-1 py-3 bg-neutral-800 hover:bg-neutral-700 text-neutral-300 font-semibold rounded-xl text-sm transition-all duration-200">
                                Cancelar
                            </button>
                            <button type="button" id="saas-dialog-confirm"
                                    class="flex-1 py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl text-sm transition-all duration-200">
                                Confirmar
                            </button>
                        </div>
                    </div>
                </div>`;
            root.style.fontFamily = 'inherit';
            document.body.appendChild(root);
            document.getElementById('saas-dialog-backdrop').addEventListener('click', dismiss);
            document.getElementById('saas-dialog-cancel').addEventListener('click', dismiss);
            document.getElementById('saas-dialog-confirm').addEventListener('click', confirm);
        }

        let resolveFn = null;

        function dismiss() {
            hide();
            if (resolveFn) { const r = resolveFn; resolveFn = null; r(false); }
        }

        function confirm() {
            hide();
            if (resolveFn) { const r = resolveFn; resolveFn = null; r(true); }
        }

        function hide() {
            document.body.style.overflow = '';
            if (root) root.style.display = 'none';
        }

        function show(opts) {
            if (!root) build();
            root.style.display = 'block';
            document.body.style.overflow = 'hidden';
            document.getElementById('saas-dialog-title').textContent = opts.title || 'Confirmar';
            document.getElementById('saas-dialog-message').textContent = opts.message || '';
            document.getElementById('saas-dialog-confirm').textContent = opts.confirmLabel || 'Confirmar';
            const cancelBtn = document.getElementById('saas-dialog-cancel');
            cancelBtn.style.display = opts.hideCancel ? 'none' : '';
            const icon = document.getElementById('saas-dialog-icon');
            const iconSvg = document.getElementById('saas-dialog-icon-svg');
            if (opts.type === 'danger') {
                icon.className = 'w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center bg-red-500/20';
                iconSvg.setAttribute('class', 'w-8 h-8 text-red-400');
            } else {
                icon.className = 'w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center bg-amber-500/20';
                iconSvg.setAttribute('class', 'w-8 h-8 text-amber-400');
            }
            if (opts.type === 'danger') {
                document.getElementById('saas-dialog-confirm').className = 'flex-1 py-3 bg-red-500 hover:bg-red-400 text-white font-semibold rounded-xl text-sm transition-all duration-200';
            } else {
                document.getElementById('saas-dialog-confirm').className = 'flex-1 py-3 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl text-sm transition-all duration-200';
            }
            return new Promise((resolve) => { resolveFn = resolve; });
        }

        window.saasConfirm = function (message, options = {}) {
            return show({ type: 'confirm', title: 'Confirmar', confirmLabel: 'Confirmar', message, ...options });
        };

        window.saasAlert = function (message, options = {}) {
            return show({ type: options.type || 'confirm', title: options.title || 'Aviso', confirmLabel: 'OK', message, hideCancel: true, ...options });
        };

        window.saasConfirmSubmit = function (event, form, message, options = {}) {
            event.preventDefault();
            saasConfirm(message, options).then(ok => { if (ok) form.submit(); });
        };
    })();
</script>