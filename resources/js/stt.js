/**
 * Input vocale (Speech-to-Text): dettare la domanda invece di scriverla.
 *
 * Pensato per chi fatica a scrivere (disgrafia). Due modalità:
 *  - "server": registra l'audio con MediaRecorder e lo invia a `/chat/stt`
 *    (trascrizione OpenAI). Disponibile solo con provider OpenAI.
 *  - "browser": riconoscimento vocale del dispositivo (Web Speech API), usato
 *    come fallback quando l'STT server-side non è disponibile.
 *
 * Il testo trascritto viene inserito nella casella della domanda; l'invio resta
 * manuale, così il ragazzo può rileggere prima di mandare.
 */

const SpeechRecognition =
    typeof window !== 'undefined' ? window.SpeechRecognition || window.webkitSpeechRecognition : null;

const canRecord =
    typeof navigator !== 'undefined' &&
    navigator.mediaDevices &&
    typeof navigator.mediaDevices.getUserMedia === 'function' &&
    typeof window.MediaRecorder !== 'undefined';

/**
 * Sceglie un formato di registrazione supportato dal browser, restituendo anche
 * l'estensione corrispondente: OpenAI riconosce il formato dall'estensione del
 * filename, quindi devono essere coerenti.
 */
const pickRecordingFormat = () => {
    const candidates = [
        ['audio/webm', 'webm'],
        ['audio/ogg', 'ogg'],
        ['audio/mp4', 'mp4'],
        ['audio/mpeg', 'mp3'],
    ];
    const supports = window.MediaRecorder && typeof window.MediaRecorder.isTypeSupported === 'function';
    for (const [mime, ext] of candidates) {
        if (!supports || window.MediaRecorder.isTypeSupported(mime)) {
            return { mime: supports ? mime : '', ext };
        }
    }
    return { mime: '', ext: 'webm' };
};

/** Aggiunge il testo trascritto alla casella della domanda. */
const appendToInput = (input, text) => {
    if (!text) {
        return;
    }
    const sep = input.value.trim() === '' ? '' : ' ';
    input.value = `${input.value}${sep}${text}`.trim();
    input.focus();
};

export const setupSpeechToText = ({ form, input, button, csrf }) => {
    if (!button) {
        return;
    }

    const sttUrl = form.dataset.sttUrl || '';
    const serverAvailable = form.dataset.sttAvailable === '1';

    // Scelta della modalità: server (OpenAI) se disponibile e registrabile,
    // altrimenti riconoscimento del browser; se nessuna delle due, niente mic.
    let mode = null;
    if (serverAvailable && canRecord) {
        mode = 'server';
    } else if (SpeechRecognition) {
        mode = 'browser';
    }

    if (!mode) {
        button.hidden = true;
        return;
    }

    const setState = (label, { recording = false, busy = false } = {}) => {
        button.textContent = label;
        button.disabled = busy;
        button.classList.toggle('bg-red-100', recording);
        button.classList.toggle('border-red-400', recording);
        button.setAttribute('aria-label', recording ? 'Ferma la dettatura' : 'Detta la domanda a voce');
    };

    const showError = (message) => {
        setState('🎤');
        let note = form.querySelector('.stt-error');
        if (!note) {
            note = document.createElement('p');
            note.className = 'stt-error mt-1 text-base text-red-600 basis-full';
            form.appendChild(note);
        }
        note.textContent = message || 'Non riesco a usare il microfono.';
        setTimeout(() => note?.remove(), 5000);
    };

    // --- Modalità server (MediaRecorder + /chat/stt) ---
    if (mode === 'server') {
        let recorder = null;
        let chunks = [];
        const format = pickRecordingFormat();

        const upload = async (blob) => {
            setState('⏳', { busy: true });
            try {
                const body = new FormData();
                body.append('audio', blob, `dettato.${format.ext}`);
                const response = await fetch(sttUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body,
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    showError(data.message);
                    return;
                }
                appendToInput(input, data.text);
                setState('🎤');
            } catch (_) {
                showError();
            }
        };

        const startRecording = async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                recorder = new MediaRecorder(stream, format.mime ? { mimeType: format.mime } : undefined);
                chunks = [];
                recorder.ondataavailable = (e) => {
                    if (e.data.size > 0) {
                        chunks.push(e.data);
                    }
                };
                recorder.onstop = () => {
                    stream.getTracks().forEach((t) => t.stop());
                    if (chunks.length > 0) {
                        upload(new Blob(chunks, { type: recorder.mimeType || format.mime || 'audio/webm' }));
                    } else {
                        setState('🎤');
                    }
                };
                recorder.start();
                setState('⏹️', { recording: true });
            } catch (_) {
                showError('Non riesco ad accedere al microfono.');
            }
        };

        button.addEventListener('click', () => {
            if (recorder && recorder.state === 'recording') {
                recorder.stop();
            } else {
                startRecording();
            }
        });
        return;
    }

    // --- Modalità browser (Web Speech API) ---
    const recognition = new SpeechRecognition();
    recognition.lang = 'it-IT';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;
    let listening = false;

    recognition.onresult = (event) => {
        const transcript = event.results?.[0]?.[0]?.transcript ?? '';
        appendToInput(input, transcript.trim());
    };
    recognition.onerror = () => showError('Non ho capito. Riprova.');
    recognition.onend = () => {
        listening = false;
        setState('🎤');
    };

    button.addEventListener('click', () => {
        if (listening) {
            recognition.stop();
            return;
        }
        try {
            recognition.start();
            listening = true;
            setState('⏹️', { recording: true });
        } catch (_) {
            showError();
        }
    });
};
