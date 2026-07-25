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

import { t } from './i18n';

let mermaidPromise = null;
let renderSeq = 0;

/**
 * Estrae l'SVG della mappa da un panel, con dimensioni intrinseche esplicite.
 *
 * Mermaid imposta la larghezza via `max-width` in stile e lascia spesso width in
 * percentuale: per rasterizzare/stampare servono width/height in pixel, che
 * ricaviamo dal `viewBox`. Ritorna un clone (non tocca l'SVG nel DOM).
 *
 * @param {HTMLElement} panel
 * @returns {{ width: number, height: number, svgString: string } | null}
 */
const getMapSvg = (panel) => {
    const svg = panel?.querySelector('svg');
    if (!svg) {
        return null;
    }

    const clone = svg.cloneNode(true);
    clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');

    // Dimensioni: prima dal viewBox, poi dal bounding box reso, infine un default.
    const viewBox = (svg.getAttribute('viewBox') || '').split(/[\s,]+/).map(Number);
    const rect = svg.getBoundingClientRect();
    const width = viewBox[2] || rect.width || 800;
    const height = viewBox[3] || rect.height || 600;

    clone.setAttribute('width', String(width));
    clone.setAttribute('height', String(height));
    // Neutralizza l'eventuale max-width che tiene la larghezza in percentuale.
    clone.style.maxWidth = 'none';

    return {
        width,
        height,
        svgString: new XMLSerializer().serializeToString(clone),
    };
};

/**
 * Rasterizza la mappa in PNG (sfondo bianco, nitido) e ne avvia il download.
 *
 * Sorgente dell'immagine: un `data:` URL (non un `blob:`). Su Chromium un SVG
 * caricato in un <img> da blob URL e disegnato su canvas rende il canvas
 * «tainted», e la successiva `toBlob()` lancia SecurityError. Il data URI evita
 * la contaminazione. Usiamo `encodeURIComponent` (non `btoa`) per gestire
 * correttamente eventuali caratteri accentati/Unicode dell'SVG.
 *
 * @param {HTMLElement} panel
 * @returns {Promise<void>} rigettata in caso di errore (gestito dal chiamante).
 */
const downloadMapPng = (panel) =>
    new Promise((resolve, reject) => {
        const map = getMapSvg(panel);
        if (!map) {
            reject(new Error('SVG non trovato'));
            return;
        }

        const scale = Math.max(2, window.devicePixelRatio || 1);
        const url = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(map.svgString);
        const img = new Image();

        img.onload = () => {
            try {
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(map.width * scale);
                canvas.height = Math.round(map.height * scale);
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                canvas.toBlob((png) => {
                    if (!png) {
                        reject(new Error('toBlob fallita'));
                        return;
                    }
                    const pngUrl = URL.createObjectURL(png);
                    const link = document.createElement('a');
                    link.href = pngUrl;
                    link.download = t('mindmap_download_filename', 'mappa-concettuale.png');
                    link.click();
                    URL.revokeObjectURL(pngUrl);
                    resolve();
                }, 'image/png');
            } catch (error) {
                reject(error);
            }
        };
        img.onerror = () => reject(new Error('Immagine non caricata'));
        img.src = url;
    });

/**
 * Manda in stampa la sola mappa tramite un iframe nascosto (più robusto di un
 * popup: non viene bloccato). Da lì l'utente può anche «Salvare come PDF».
 *
 * @param {HTMLElement} panel
 * @returns {boolean} true se la stampa è stata avviata.
 */
const printMap = (panel) => {
    const map = getMapSvg(panel);
    if (!map) {
        return false;
    }

    const iframe = document.createElement('iframe');
    iframe.setAttribute('aria-hidden', 'true');
    iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
    document.body.appendChild(iframe);

    const cleanup = () => iframe.remove();

    iframe.onload = () => {
        const win = iframe.contentWindow;
        win.onafterprint = cleanup;
        win.focus();
        win.print();
        // Fallback se onafterprint non scatta (alcuni browser).
        setTimeout(cleanup, 60000);
    };

    const doc = iframe.contentDocument;
    doc.open();
    doc.write(
        '<!doctype html><html><head><meta charset="utf-8"><title>' +
            t('mindmap_print_title', 'Mappa concettuale') +
            '</title>' +
            '<style>@page{margin:12mm}html,body{margin:0;height:100%}' +
            'body{display:flex;align-items:center;justify-content:center}' +
            'svg{max-width:100%;height:auto}</style></head><body>' +
            map.svgString +
            '</body></html>',
    );
    doc.close();
    return true;
};

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
            idle: t('mindmap_idle', '🗺️ Mappa'),
            loading: t('mindmap_loading', '⏳ Creo la mappa…'),
            shown: t('mindmap_hide', '🙈 Nascondi mappa'),
            hidden: t('mindmap_show', '🗺️ Mostra mappa'),
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
        note.textContent = message || t('mindmap_error', 'Non riesco a creare la mappa in questo momento.');
        setTimeout(() => note?.remove(), 5000);
    };

    // Barra con «Scarica immagine» e «Stampa», sotto la mappa (segue mostra/nascondi
    // perché figlia del panel). Stile coerente col pulsante Mappa, un po' più compatto.
    const buildActions = (mapPanel) => {
        const bar = document.createElement('div');
        bar.className = 'mindmap-actions mt-3 flex flex-wrap gap-2';

        const makeButton = (label, aria, onClick) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('aria-label', aria);
            btn.className =
                'inline-flex items-center gap-2 rounded-xl border-2 border-slate-300 ' +
                'bg-slate-50 px-3 py-1.5 text-base font-semibold text-slate-700 ' +
                'hover:bg-slate-100 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-200';
            btn.textContent = label;
            btn.addEventListener('click', onClick);
            return btn;
        };

        bar.appendChild(
            makeButton(
                t('mindmap_download', '🖼️ Scarica immagine'),
                t('mindmap_download_aria', 'Scarica la mappa come immagine PNG'),
                async () => {
                    try {
                        await downloadMapPng(mapPanel);
                    } catch (_) {
                        showError(t('mindmap_download_error', 'Non riesco a salvare l’immagine in questo momento.'));
                    }
                },
            ),
        );
        bar.appendChild(
            makeButton(
                t('mindmap_print', '🖨️ Stampa'),
                t('mindmap_print_aria', 'Stampa la mappa concettuale'),
                () => {
                    if (!printMap(mapPanel)) {
                        showError(t('mindmap_print_error', 'Non riesco a stampare la mappa in questo momento.'));
                    }
                },
            ),
        );

        return bar;
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
            panel.setAttribute('aria-label', t('mindmap_aria', 'Mappa concettuale della risposta'));
            panel.innerHTML = svg;
            panel.appendChild(buildActions(panel));
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
