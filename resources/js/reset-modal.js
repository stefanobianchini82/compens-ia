/**
 * Modale di conferma generico, condiviso tra:
 *   - il pulsante "🗑️ Svuota chat" (svuotamento esplicito → reload);
 *   - il cambio materia in chat (vedi chat.js), che azzera la conversazione.
 *
 * `openConfirm({ title, message, confirmLabel, onConfirm, onCancel })` mostra il
 * modale con testo e azione dinamici. Chiusura con "Annulla", tasto Esc o click
 * sull'overlay → invoca `onCancel`. Conferma → invoca `onConfirm`.
 */

const csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const state = { onConfirm: null, onCancel: null };

const refs = () => ({
    modal: document.getElementById('chat-confirm-modal'),
    title: document.getElementById('chat-modal-title'),
    message: document.getElementById('chat-modal-message'),
    confirm: document.getElementById('chat-modal-confirm'),
    cancel: document.getElementById('chat-modal-cancel'),
});

const hide = () => {
    const { modal } = refs();
    modal.classList.add('hidden');
    modal.classList.remove('flex');
};

/** Chiude il modale annullando (esegue eventuale onCancel). */
const dismiss = () => {
    const { modal } = refs();
    if (!modal || modal.classList.contains('hidden')) {
        return;
    }
    const cancel = state.onCancel;
    state.onConfirm = null;
    state.onCancel = null;
    hide();
    if (typeof cancel === 'function') {
        cancel();
    }
};

/** Apre il modale con testo e callback dati. */
export function openConfirm({ title, message, confirmLabel = 'Conferma', onConfirm, onCancel } = {}) {
    const { modal, title: titleEl, message: messageEl, confirm: confirmBtn, cancel: cancelBtn } = refs();
    if (!modal) {
        return;
    }
    if (title != null) titleEl.textContent = title;
    if (message != null) messageEl.textContent = message;
    confirmBtn.textContent = confirmLabel;
    state.onConfirm = onConfirm;
    state.onCancel = onCancel;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    cancelBtn.focus();
}

/** Svuota la chat lato server (AJAX → 204). L'URL è nel data-attribute del modale. */
export async function postReset() {
    const { modal } = refs();
    const url = modal?.dataset.resetUrl;
    if (!url) {
        return;
    }
    await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const { modal, confirm: confirmBtn, cancel: cancelBtn } = refs();
    if (!modal) {
        return; // non siamo nella dashboard
    }

    confirmBtn.addEventListener('click', async () => {
        const onConfirm = state.onConfirm;
        state.onConfirm = null;
        state.onCancel = null; // conferma: non deve scattare onCancel
        hide();
        if (typeof onConfirm === 'function') {
            await onConfirm();
        }
    });

    cancelBtn.addEventListener('click', dismiss);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            dismiss();
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            dismiss();
        }
    });

    // Pulsante "🗑️ Svuota chat": svuota e ricarica la dashboard.
    document.getElementById('chat-reset-btn')?.addEventListener('click', () => {
        openConfirm({
            title: 'Vuoi svuotare la chat?',
            message: 'I messaggi verranno eliminati definitivamente.',
            confirmLabel: 'Svuota',
            onConfirm: async () => {
                await postReset();
                window.location.reload();
            },
        });
    });
});
