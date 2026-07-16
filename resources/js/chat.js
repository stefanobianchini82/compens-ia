/**
 * Client della chat in streaming (SSE) — Vanilla JS.
 *
 * Invia il messaggio in POST a /chat/stream e legge la risposta come flusso di
 * Server-Sent Events prodotto da Laravel (`response()->eventStream()`), mostrando
 * il testo dell'assistente man mano che arriva.
 *
 * Usiamo fetch + ReadableStream (invece di EventSource) perché EventSource
 * supporta solo GET, mentre qui serve una POST con il messaggio.
 *
 * Il testo dell'assistente è Markdown (grassetto, elenchi, titoletti): lo
 * convertiamo in HTML con `marked` e lo ripuliamo con DOMPurify prima di
 * inserirlo nel DOM, perché è output di un LLM e non ci si può fidare (rischio
 * XSS, anche via prompt-injection dal testo dei libri).
 */
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { openConfirm, postReset } from './reset-modal';
import { createTtsButton } from './tts';

// A-capo singolo → <br>: in chat va reso, non ignorato come nel Markdown standard.
marked.setOptions({ gfm: true, breaks: true });

/** Converte Markdown in HTML sicuro da inserire con innerHTML. */
const renderMarkdown = (text) => DOMPurify.sanitize(marked.parse(text));

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('chat-form');
    if (!form) {
        return; // non siamo nella dashboard
    }

    const input = document.getElementById('chat-input');
    const subjectSelect = document.getElementById('subject-select');
    const messages = document.getElementById('chat-messages');
    const sendButton = document.getElementById('chat-send');
    const sessionTokensEl = document.getElementById('session-tokens');
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Configurazione della lettura ad alta voce (Text-to-Speech).
    const ttsEngine = messages.dataset.ttsEngine || 'browser';
    const ttsUrl = messages.dataset.ttsUrl || '';
    const attachTts = (bubble, messageId) =>
        createTtsButton(bubble, { engine: ttsEngine, ttsUrl, csrf, messageId });

    // Rende in Markdown i messaggi dell'assistente già presenti (storico caricato
    // dal DB), che il server stampa come testo grezzo, e aggiunge il pulsante ▶.
    messages.querySelectorAll('.chat-md').forEach((el) => {
        el.innerHTML = renderMarkdown(el.textContent);
        attachTts(el, el.dataset.messageId || null);
    });

    // Invio invia il messaggio; Shift+Invio va a capo nella textarea.
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault(); // niente a capo
            form.requestSubmit(); // scatena il submit (con validazione)
        }
    });

    // Cambiando materia la chat va azzerata: non ha senso mescolare materie diverse
    // nella stessa cronologia (e context window). Se c'è già una conversazione
    // chiediamo conferma; su chat vuota il cambio è immediato.
    let currentSubjectId = subjectSelect ? subjectSelect.value : '';

    subjectSelect?.addEventListener('change', () => {
        const newId = subjectSelect.value;
        if (newId === currentSubjectId) {
            return;
        }

        const hasMessages = !document.getElementById('chat-empty');

        if (!newId || !hasMessages) {
            // Nessuna materia scelta o chat già vuota: niente da azzerare.
            currentSubjectId = newId;
            return;
        }

        openConfirm({
            title: 'Cambiare materia?',
            message: 'Cambiando materia la chat attuale verrà svuotata.',
            confirmLabel: 'Cambia e svuota',
            onConfirm: async () => {
                await postReset();
                restoreEmptyChat();
                currentSubjectId = newId;
            },
            onCancel: () => {
                subjectSelect.value = currentSubjectId; // annulla il cambio
            },
        });
    });

    const scrollToBottom = () => {
        messages.scrollTop = messages.scrollHeight;
    };

    // Ripristina il messaggio di benvenuto (empty-state) dopo uno svuotamento.
    const restoreEmptyChat = () => {
        messages.innerHTML =
            '<div id="chat-empty" class="h-full flex items-center justify-center text-center text-slate-500">' +
            '<p>Ciao! 👋 Scegli una materia e scrivimi la tua domanda.<br>Ti aiuto a capire, un passo alla volta.</p>' +
            '</div>';
        if (sessionTokensEl) {
            sessionTokensEl.textContent = '0';
        }
    };

    const addBubble = (role, text) => {
        // Al primo messaggio togliamo il benvenuto per non lasciarlo sopra le bolle.
        document.getElementById('chat-empty')?.remove();

        const wrapper = document.createElement('div');
        wrapper.className =
            role === 'user'
                ? 'flex justify-end'
                : 'flex justify-start';

        const bubble = document.createElement('div');
        bubble.className =
            (role === 'user'
                ? 'bg-sky-600 text-white whitespace-pre-wrap'
                : 'bg-white text-slate-800 border border-slate-200 chat-md') +
            ' max-w-[85%] rounded-2xl px-5 py-3 shadow-sm';
        // La bolla dell'utente resta testo semplice; quella dell'assistente verrà
        // riempita con Markdown renderizzato (vedi evento `delta`).
        bubble.textContent = text;

        wrapper.appendChild(bubble);
        messages.appendChild(wrapper);
        scrollToBottom();
        return bubble;
    };

    /**
     * Analizza un blocco SSE ("event: ...\ndata: ...") e restituisce {event, data}.
     */
    const parseEvent = (block) => {
        let event = 'message';
        const dataLines = [];
        for (const line of block.split('\n')) {
            if (line.startsWith('event:')) {
                event = line.slice(6).trim();
            } else if (line.startsWith('data:')) {
                dataLines.push(line.slice(5).trim());
            }
        }
        return { event, data: dataLines.join('\n') };
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const text = input.value.trim();
        const subjectId = subjectSelect ? subjectSelect.value : '';

        if (!text) {
            return;
        }
        if (!subjectId) {
            alert('Scegli prima una materia 📚');
            return;
        }

        addBubble('user', text);
        input.value = '';
        input.disabled = true;
        sendButton.disabled = true;

        const answer = addBubble('assistant', '');
        answer.classList.add('italic', 'text-slate-400');
        answer.textContent = 'Sto pensando…';
        let firstChunk = true;
        let raw = ''; // testo Markdown accumulato della risposta

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'text/event-stream',
                },
                body: new URLSearchParams({ message: text, subject_id: subjectId }),
            });

            if (!response.ok || !response.body) {
                throw new Error('Risposta non valida dal server.');
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { value, done } = await reader.read();
                if (done) {
                    break;
                }
                buffer += decoder.decode(value, { stream: true });

                let sep;
                while ((sep = buffer.indexOf('\n\n')) !== -1) {
                    const block = buffer.slice(0, sep);
                    buffer = buffer.slice(sep + 2);
                    if (!block.trim()) {
                        continue;
                    }

                    const { event, data } = parseEvent(block);
                    let payload = {};
                    try {
                        payload = data ? JSON.parse(data) : {};
                    } catch (_) {
                        continue;
                    }

                    if (event === 'delta') {
                        if (firstChunk) {
                            answer.textContent = '';
                            answer.classList.remove('italic', 'text-slate-400');
                            firstChunk = false;
                        }
                        raw += payload.content || '';
                        answer.innerHTML = renderMarkdown(raw);
                        scrollToBottom();
                    } else if (event === 'done') {
                        if (sessionTokensEl && typeof payload.sessionTokens === 'number') {
                            sessionTokensEl.textContent = payload.sessionTokens.toLocaleString('it-IT');
                        }
                        // Risposta completa: aggiungiamo il pulsante di lettura ▶.
                        attachTts(answer, payload.messageId ?? null);
                    } else if (event === 'error') {
                        answer.classList.remove('italic', 'text-slate-400');
                        answer.classList.add('text-red-600');
                        answer.textContent = payload.message || 'Si è verificato un errore.';
                    }
                }
            }
        } catch (err) {
            answer.classList.remove('italic', 'text-slate-400');
            answer.classList.add('text-red-600');
            answer.textContent = 'Non riesco a rispondere in questo momento. Riprova tra poco.';
        } finally {
            input.disabled = false;
            sendButton.disabled = false;
            input.focus();
        }
    });
});
