/**
 * Preferenze di lettura (accessibilità DSA).
 *
 * Pannello «Aa» che permette di regolare: font (Predefinito / Atkinson
 * Hyperlegible / OpenDyslexic), dimensione del testo, spaziatura tra righe e
 * lettere, e un righello di lettura che segue il cursore.
 *
 * Le preferenze sono personali per dispositivo: non c'è login, quindi le
 * salviamo in localStorage e le applichiamo via variabili CSS su <html>.
 */

const STORAGE_KEY = 'compensia.reading';

const FONTS = {
    default: '', // stack predefinito del tema
    atkinson: "'Atkinson Hyperlegible', sans-serif",
    opendyslexic: "'OpenDyslexic', 'Atkinson Hyperlegible', sans-serif",
};

const DEFAULTS = {
    font: 'default',
    scale: 1, // moltiplicatore della dimensione base (18px)
    line: 1.7, // interlinea
    letter: 0.01, // spaziatura lettere (em)
    ruler: false,
};

const load = () => {
    try {
        return { ...DEFAULTS, ...(JSON.parse(localStorage.getItem(STORAGE_KEY)) || {}) };
    } catch (_) {
        return { ...DEFAULTS };
    }
};

const save = (prefs) => {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
    } catch (_) {
        /* localStorage non disponibile: pazienza, restano solo per la sessione. */
    }
};

/** Applica le preferenze alle variabili CSS su <html>. */
const apply = (prefs) => {
    const root = document.documentElement;
    const font = FONTS[prefs.font] ?? '';
    if (font) {
        root.style.setProperty('--reading-font', font);
    } else {
        root.style.removeProperty('--reading-font');
    }
    root.style.setProperty('--reading-scale', String(prefs.scale));
    root.style.setProperty('--reading-line', String(prefs.line));
    root.style.setProperty('--reading-letter', `${prefs.letter}em`);
};

/** Crea (una sola volta) l'elemento del righello di lettura. */
const ensureRuler = () => {
    let ruler = document.getElementById('reading-ruler');
    if (!ruler) {
        ruler = document.createElement('div');
        ruler.id = 'reading-ruler';
        ruler.className = 'reading-ruler';
        ruler.setAttribute('aria-hidden', 'true');
        document.body.appendChild(ruler);
        document.addEventListener('mousemove', (e) => {
            if (ruler.classList.contains('reading-ruler--on')) {
                ruler.style.top = `${e.clientY - ruler.offsetHeight / 2}px`;
            }
        });
    }
    return ruler;
};

const applyRuler = (on) => {
    ensureRuler().classList.toggle('reading-ruler--on', on);
};

/** Costruisce il pannello delle preferenze e lo collega allo stato. */
const buildPanel = (prefs, onChange) => {
    const panel = document.createElement('div');
    panel.id = 'reading-panel';
    panel.hidden = true;
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Preferenze di lettura');
    panel.className =
        'fixed right-4 top-16 z-50 w-80 max-w-[calc(100vw-2rem)] rounded-2xl border-2 ' +
        'border-slate-200 bg-white p-5 shadow-xl text-slate-800';

    const fontOptions = [
        ['default', 'Predefinito'],
        ['atkinson', 'Atkinson Hyperlegible'],
        ['opendyslexic', 'OpenDyslexic'],
    ]
        .map(
            ([value, label]) =>
                `<option value="${value}" ${prefs.font === value ? 'selected' : ''}>${label}</option>`,
        )
        .join('');

    panel.innerHTML = `
        <h2 class="text-xl font-bold mb-4">🔤 Come leggi meglio?</h2>
        <label class="block mb-4">
            <span class="font-semibold">Carattere</span>
            <select id="rp-font" class="mt-1 w-full rounded-xl border-2 border-slate-300 px-3 py-2 text-base focus:border-sky-500 focus:outline-none">
                ${fontOptions}
            </select>
        </label>
        <label class="block mb-4">
            <span class="font-semibold">Dimensione testo</span>
            <input id="rp-scale" type="range" min="0.9" max="1.6" step="0.05" value="${prefs.scale}" class="mt-1 w-full">
        </label>
        <label class="block mb-4">
            <span class="font-semibold">Spazio tra le righe</span>
            <input id="rp-line" type="range" min="1.4" max="2.4" step="0.1" value="${prefs.line}" class="mt-1 w-full">
        </label>
        <label class="block mb-4">
            <span class="font-semibold">Spazio tra le lettere</span>
            <input id="rp-letter" type="range" min="0" max="0.12" step="0.01" value="${prefs.letter}" class="mt-1 w-full">
        </label>
        <label class="flex items-center gap-3 mb-5">
            <input id="rp-ruler" type="checkbox" ${prefs.ruler ? 'checked' : ''} class="h-5 w-5">
            <span class="font-semibold">Righello di lettura</span>
        </label>
        <button type="button" id="rp-reset" class="w-full rounded-xl border-2 border-slate-300 bg-slate-50 px-4 py-2 text-base font-semibold text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-sky-200">
            Ripristina
        </button>
    `;
    document.body.appendChild(panel);

    const els = {
        font: panel.querySelector('#rp-font'),
        scale: panel.querySelector('#rp-scale'),
        line: panel.querySelector('#rp-line'),
        letter: panel.querySelector('#rp-letter'),
        ruler: panel.querySelector('#rp-ruler'),
        reset: panel.querySelector('#rp-reset'),
    };

    els.font.addEventListener('change', () => onChange({ font: els.font.value }));
    els.scale.addEventListener('input', () => onChange({ scale: Number(els.scale.value) }));
    els.line.addEventListener('input', () => onChange({ line: Number(els.line.value) }));
    els.letter.addEventListener('input', () => onChange({ letter: Number(els.letter.value) }));
    els.ruler.addEventListener('change', () => onChange({ ruler: els.ruler.checked }));
    els.reset.addEventListener('click', () => {
        onChange({ ...DEFAULTS });
        els.font.value = DEFAULTS.font;
        els.scale.value = String(DEFAULTS.scale);
        els.line.value = String(DEFAULTS.line);
        els.letter.value = String(DEFAULTS.letter);
        els.ruler.checked = DEFAULTS.ruler;
    });

    return panel;
};

document.addEventListener('DOMContentLoaded', () => {
    let prefs = load();
    apply(prefs);
    applyRuler(prefs.ruler);

    const onChange = (patch) => {
        prefs = { ...prefs, ...patch };
        apply(prefs);
        applyRuler(prefs.ruler);
        save(prefs);
    };

    const panel = buildPanel(prefs, onChange);

    // Pulsante «Aa» nell'header (se presente) o, in fallback, fluttuante.
    let toggle = document.getElementById('reading-prefs-btn');
    if (!toggle) {
        toggle = document.createElement('button');
        toggle.id = 'reading-prefs-btn';
        toggle.type = 'button';
        toggle.textContent = 'Aa';
        toggle.setAttribute('aria-label', 'Preferenze di lettura');
        toggle.className =
            'fixed bottom-4 right-4 z-50 rounded-full bg-white border-2 border-slate-300 ' +
            'w-12 h-12 text-lg font-bold text-slate-700 shadow hover:bg-slate-100';
        document.body.appendChild(toggle);
    }

    toggle.setAttribute('aria-expanded', 'false');
    toggle.addEventListener('click', () => {
        panel.hidden = !panel.hidden;
        toggle.setAttribute('aria-expanded', String(!panel.hidden));
    });

    // Chiude il pannello cliccando fuori.
    document.addEventListener('click', (e) => {
        if (!panel.hidden && !panel.contains(e.target) && e.target !== toggle) {
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
});
