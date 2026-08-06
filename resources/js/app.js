// Alpine.js is provided by Livewire v4.

/*
 * Proteção global contra duplo-clique / envio duplicado.
 * 1) Botões de ação Livewire que declaram `wire:loading.attr="disabled"`
 *    (criar/salvar/enviar/excluir) ganham um cooldown curto no cliente,
 *    fechando a janela entre o primeiro clique e o Livewire aplicar o
 *    estado de loading no próximo re-render.
 * 2) Formulários HTML comuns têm o botão de submit desabilitado após o
 *    primeiro envio, evitando POST duplicado.
 */
document.addEventListener('click', (event) => {
    const el = event.target.closest('[wire\\:click]');

    if (!el || !el.hasAttribute('wire:loading.attr')) {
        return;
    }

    if (el.dataset.doubleSubmitLocked) {
        event.preventDefault();
        event.stopImmediatePropagation();

        return;
    }

    el.dataset.doubleSubmitLocked = '1';

    setTimeout(() => {
        delete el.dataset.doubleSubmitLocked;
    }, 1500);
});

document.addEventListener(
    'submit',
    (event) => {
        if (event.defaultPrevented) {
            return;
        }

        const form = event.target;
        const button = form.querySelector('button[type="submit"], input[type="submit"]');

        if (button && !button.disabled) {
            button.disabled = true;
        }
    },
    true,
);