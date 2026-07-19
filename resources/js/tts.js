/**
 * Lettura ad alta voce (Text-to-Speech) delle risposte dell'assistente.
 *
 * Pensato per l'accessibilità DSA: ogni risposta ha un pulsante ▶ per farsi
 * leggere il testo, con Pausa/Riprendi.
 *
 * Due motori:
 *  - "browser": Web Speech API (`speechSynthesis`) — gratis, offline, funziona
 *    con qualunque provider. È anche il fallback quando il TTS OpenAI non è
 *    disponibile.
 *  - "openai": scarica l'audio dal server (`/chat/tts`) e lo riproduce. Voce più
 *    naturale; il blob viene messo in cache per bolla (nessun nuovo consumo ai
 *    clic successivi).
 *
 * Un solo audio attivo alla volta: avviando la lettura di una risposta si ferma
 * quella eventualmente in corso.
 */

const supportsSpeech = typeof window !== 'undefined' && 'speechSynthesis' in window;

// Controller attualmente in riproduzione (per garantire un solo audio alla volta).
let activePlayer = null;

/** Sceglie una voce italiana tra quelle disponibili nel browser, se c'è. */
const pickItalianVoice = () => {
    if (!supportsSpeech) {
        return null;
    }
    const voices = window.speechSynthesis.getVoices();
    return voices.find((v) => v.lang && v.lang.toLowerCase().startsWith('it')) || null;
};

// Le voci possono caricarsi in modo asincrono: forziamo un primo popolamento.
if (supportsSpeech) {
    window.speechSynthesis.getVoices();
    window.speechSynthesis.addEventListener?.('voiceschanged', () => pickItalianVoice());
}

/**
 * Divide il testo di una bolla in parole, avvolgendo ciascuna in uno
 * <span class="tts-word"> (per l'evidenziazione «karaoke» durante la lettura).
 *
 * Preserva la struttura del Markdown già reso (grassetto, elenchi, titoli) e
 * salta i pulsanti/pannelli aggiunti alla bolla (lettura, mappa). Restituisce la
 * stringa completa letta e la lista dei token con gli offset in quella stringa,
 * così l'evento `onboundary` (che dà un `charIndex`) può risalire alla parola.
 *
 * @returns {{ text: string, tokens: Array<{span: HTMLElement, start: number, end: number}> }}
 */
const buildWordTokens = (bubble) => {
    const tokens = [];
    let text = '';

    const walker = document.createTreeWalker(bubble, NodeFilter.SHOW_TEXT, {
        acceptNode(node) {
            // Salta il testo dentro i pulsanti e i pannelli aggiunti alla bolla.
            let el = node.parentElement;
            while (el && el !== bubble) {
                if (
                    el.tagName === 'BUTTON' ||
                    el.classList.contains('mindmap-panel') ||
                    el.classList.contains('mindmap-error')
                ) {
                    return NodeFilter.FILTER_REJECT;
                }
                el = el.parentElement;
            }
            return NodeFilter.FILTER_ACCEPT;
        },
    });

    const textNodes = [];
    let node;
    while ((node = walker.nextNode())) {
        textNodes.push(node);
    }

    for (const textNode of textNodes) {
        // Manteniamo anche i separatori (spazi), per non sfasare gli offset.
        const parts = textNode.data.split(/(\s+)/);
        const fragment = document.createDocumentFragment();

        for (const part of parts) {
            if (part === '') {
                continue;
            }
            if (/^\s+$/.test(part)) {
                fragment.appendChild(document.createTextNode(part));
                text += part;
                continue;
            }
            const span = document.createElement('span');
            span.className = 'tts-word';
            span.textContent = part;
            const start = text.length;
            text += part;
            tokens.push({ span, start, end: text.length });
            fragment.appendChild(span);
        }

        textNode.parentNode.replaceChild(fragment, textNode);
    }

    return { text, tokens };
};

/**
 * Crea e collega un pulsante Play/Pausa a una bolla dell'assistente.
 *
 * @param {HTMLElement} bubble  L'elemento bolla (contiene il testo renderizzato).
 * @param {object} options
 * @param {string} options.engine     'browser' | 'openai'
 * @param {string} options.ttsUrl     URL dell'endpoint TTS server-side.
 * @param {string} options.csrf       Token CSRF.
 * @param {string|number|null} options.messageId  Id del ChatMessage (per il TTS OpenAI).
 */
export const createTtsButton = (bubble, { engine, ttsUrl, csrf, messageId }) => {
    if (!bubble || bubble.querySelector('.tts-btn')) {
        return; // già presente
    }
    // Il testo da leggere: catturato ORA, prima di aggiungere il pulsante alla
    // bolla (così non finisce nel testo l'etichetta del pulsante stesso).
    const text = bubble.textContent.trim();
    if (!text) {
        return;
    }

    // Se il motore è "browser" ma il browser non supporta la sintesi vocale,
    // non mostriamo il pulsante (niente su cui ripiegare).
    let useEngine = engine === 'openai' && messageId ? 'openai' : 'browser';
    if (useEngine === 'browser' && !supportsSpeech) {
        return;
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.className =
        'tts-btn mt-3 inline-flex items-center gap-2 rounded-xl border-2 border-slate-300 ' +
        'bg-slate-50 px-4 py-2 text-base font-semibold text-slate-700 ' +
        'hover:bg-slate-100 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-200';
    bubble.appendChild(button);

    let state = 'idle'; // idle | loading | playing | paused
    let audio = null; // elemento <audio> (motore openai), con blob in cache

    // Stato del karaoke (evidenziazione parola per parola, solo motore browser).
    let karaoke = null; // { text, tokens } costruito una sola volta, poi in cache
    let activeWord = null; // span attualmente evidenziato

    const clearHighlight = () => {
        if (activeWord) {
            activeWord.classList.remove('tts-word--active');
            activeWord = null;
        }
    };

    const setLabel = () => {
        const labels = {
            idle: '🔊 Ascolta',
            loading: '⏳ Preparo…',
            playing: '⏸️ Pausa',
            paused: '▶️ Riprendi',
        };
        button.textContent = labels[state];
        button.setAttribute(
            'aria-label',
            state === 'playing' ? 'Metti in pausa la lettura' : 'Ascolta la risposta',
        );
        button.disabled = state === 'loading';
    };

    const toIdle = () => {
        clearHighlight();
        state = 'idle';
        setLabel();
        if (activePlayer === controller) {
            activePlayer = null;
        }
    };

    // Evidenzia la parola che copre l'offset `charIndex` nella stringa letta.
    const highlightAt = (charIndex) => {
        if (!karaoke) {
            return;
        }
        const token =
            karaoke.tokens.find((t) => charIndex >= t.start && charIndex < t.end) ||
            karaoke.tokens.find((t) => t.start >= charIndex);
        if (!token || token.span === activeWord) {
            return;
        }
        clearHighlight();
        activeWord = token.span;
        activeWord.classList.add('tts-word--active');
        activeWord.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    };

    // --- Motore browser (Web Speech API) ---
    const browserPlay = () => {
        // Segmentiamo il testo in parole una sola volta (poi resta in cache).
        if (!karaoke) {
            karaoke = buildWordTokens(bubble);
        }
        const readText = karaoke.text.trim() !== '' ? karaoke.text : text;

        const utterance = new SpeechSynthesisUtterance(readText);
        utterance.lang = 'it-IT';
        const voice = pickItalianVoice();
        if (voice) {
            utterance.voice = voice;
        }
        utterance.rate = 0.95; // leggermente più lento: più chiaro per la lettura DSA
        // Karaoke: a ogni confine di parola evidenziamo quella corrente. Se il
        // browser/voce non emette `onboundary`, la lettura procede senza highlight.
        utterance.onboundary = (event) => {
            if (event.name && event.name !== 'word') {
                return;
            }
            highlightAt(event.charIndex ?? 0);
        };
        utterance.onend = toIdle;
        utterance.onerror = toIdle;
        window.speechSynthesis.cancel(); // sicurezza: nessuna coda residua
        window.speechSynthesis.speak(utterance);
        state = 'playing';
        setLabel();
    };

    const browserToggle = () => {
        if (state === 'playing') {
            window.speechSynthesis.pause();
            state = 'paused';
            setLabel();
        } else if (state === 'paused') {
            window.speechSynthesis.resume();
            state = 'playing';
            setLabel();
        } else {
            browserPlay();
        }
    };

    const browserStop = () => {
        window.speechSynthesis.cancel();
        clearHighlight();
    };

    // --- Motore OpenAI (audio scaricato dal server) ---
    const openaiPlay = async () => {
        if (audio) {
            audio.play();
            state = 'playing';
            setLabel();
            return;
        }

        state = 'loading';
        setLabel();
        try {
            const response = await fetch(ttsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'audio/mpeg',
                },
                body: new URLSearchParams({ message_id: String(messageId) }),
            });

            if (!response.ok) {
                // TTS OpenAI non disponibile (es. 422): ripieghiamo sulla voce del
                // dispositivo, se supportata.
                if (supportsSpeech) {
                    useEngine = 'browser';
                    browserPlay();
                    return;
                }
                toIdle();
                return;
            }

            const blob = await response.blob();
            audio = new Audio(URL.createObjectURL(blob));
            audio.onended = toIdle;
            audio.onerror = toIdle;
            await audio.play();
            state = 'playing';
            setLabel();
        } catch (_) {
            if (supportsSpeech) {
                useEngine = 'browser';
                browserPlay();
                return;
            }
            toIdle();
        }
    };

    const openaiToggle = () => {
        if (state === 'playing') {
            audio?.pause();
            state = 'paused';
            setLabel();
        } else if (state === 'paused') {
            audio?.play();
            state = 'playing';
            setLabel();
        } else {
            openaiPlay();
        }
    };

    const openaiStop = () => {
        if (audio) {
            audio.pause();
            audio.currentTime = 0;
        }
    };

    // Interfaccia comune usata per fermare la riproduzione quando ne parte un'altra.
    const controller = {
        stop() {
            if (useEngine === 'openai') {
                openaiStop();
            } else {
                browserStop();
            }
            toIdle();
        },
    };

    button.addEventListener('click', () => {
        // Avviando una nuova lettura, fermiamo quella eventualmente in corso.
        if (state === 'idle' && activePlayer && activePlayer !== controller) {
            activePlayer.stop();
        }
        if (state === 'idle') {
            activePlayer = controller;
        }

        if (useEngine === 'openai') {
            openaiToggle();
        } else {
            browserToggle();
        }
    });

    setLabel();
};
