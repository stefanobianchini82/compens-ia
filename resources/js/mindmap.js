/**
 * Mappe concettuali delle risposte dell'assistente.
 *
 * Pensato per lo studio DSA: ogni risposta ha un pulsante 🗺️ che genera una
 * mappa concettuale (i concetti e i loro collegamenti a colpo d'occhio).
 *
 * Il codice della mappa è prodotto dal modello lato server (`/chat/mindmap`) in
 * sintassi Mermaid «mindmap»; qui lo disegniamo nel browser. La libreria Mermaid
 * è pesante, quindi la carichiamo con import dinamico solo al primo utilizzo.
 * Il disegno viene messo in cache per bolla (nessuna nuova richiesta ai clic
 * successivi: il pulsante fa solo mostra/nascondi).
 */

let mermaidPromise = null;
let renderSeq = 0;

/** Carica e inizializza Mermaid una sola volta (import dinamico). */
const loadMermaid = () => {
    if (!mermaidPromise) {
        mermaidPromise = import('mermaid').then(({ default: mermaid }) => {
            mermaid.initialize({
                startOnLoad: false,
                // Output di un LLM: massima prudenza nella resa (niente HTML/script).
                securityLevel: 'strict',
                mindmap: { padding: 12 },
            });
            return mermaid;
        });
    }
    return mermaidPromise;
};

/**
 * Crea e collega un pulsante "Mappa" a una bolla dell'assistente.
 *
 * @param {HTMLElement} bubble  L'elemento bolla (contiene il testo renderizzato).
 * @param {object} options
 * @param {string} options.url         URL dell'endpoint che genera la mappa.
 * @param {string} options.csrf        Token CSRF.
 * @param {string|number|null} options.messageId  Id del ChatMessage.
 */
export const createMindMapButton = (bubble, { url, csrf, messageId }) => {
    if (!bubble || !url || !messageId || bubble.querySelector('.mindmap-btn')) {
        return; // manca qualcosa o pulsante già presente
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.className =
        'mindmap-btn mt-3 ml-2 inline-flex items-center gap-2 rounded-xl border-2 border-slate-300 ' +
        'bg-slate-50 px-4 py-2 text-base font-semibold text-slate-700 ' +
        'hover:bg-slate-100 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-200';
    bubble.appendChild(button);

    let state = 'idle'; // idle | loading | shown | hidden
    let panel = null; // contenitore della mappa (in cache dopo la prima resa)

    const setLabel = () => {
        const labels = {
            idle: '🗺️ Mappa',
            loading: '⏳ Creo la mappa…',
            shown: '🙈 Nascondi mappa',
            hidden: '🗺️ Mostra mappa',
        };
        button.textContent = labels[state];
        button.disabled = state === 'loading';
    };

    const showError = (message) => {
        state = 'idle';
        setLabel();
        // Messaggio breve sotto il pulsante, poi sparisce.
        let note = bubble.querySelector('.mindmap-error');
        if (!note) {
            note = document.createElement('p');
            note.className = 'mindmap-error mt-2 text-base text-red-600';
            bubble.appendChild(note);
        }
        note.textContent = message || 'Non riesco a creare la mappa in questo momento.';
        setTimeout(() => note?.remove(), 5000);
    };

    const generate = async () => {
        state = 'loading';
        setLabel();
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: new URLSearchParams({ message_id: String(messageId) }),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.mermaid) {
                showError(data.message);
                return;
            }

            const mermaid = await loadMermaid();
            const id = `mindmap-svg-${(renderSeq += 1)}`;
            const { svg } = await mermaid.render(id, data.mermaid);

            panel = document.createElement('div');
            panel.className = 'mindmap-panel mt-3';
            panel.setAttribute('role', 'img');
            panel.setAttribute('aria-label', 'Mappa concettuale della risposta');
            panel.innerHTML = svg;
            bubble.appendChild(panel);

            state = 'shown';
            setLabel();
        } catch (_) {
            showError();
        }
    };

    button.addEventListener('click', () => {
        if (state === 'loading') {
            return;
        }
        if (panel) {
            // Già generata: solo mostra/nascondi.
            const hide = state === 'shown';
            panel.hidden = hide;
            state = hide ? 'hidden' : 'shown';
            setLabel();
            return;
        }
        generate();
    });

    setLabel();
};
