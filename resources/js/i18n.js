/**
 * Piccolo ponte i18n per il codice JavaScript.
 *
 * Le traduzioni del gruppo `js` vengono iniettate dal layout Blade in
 * `window.i18n` (vedi layouts/app.blade.php: `window.i18n = @js(__('js'))`).
 * Qui offriamo un accesso comodo con fallback, così se per qualche motivo
 * l'oggetto non è presente il testo non sparisce.
 */

const dict = (typeof window !== 'undefined' && window.i18n) || {};

/** Traduce una chiave del catalogo `js`, con testo di riserva. */
export const t = (key, fallback = '') => (dict[key] != null ? dict[key] : fallback);

/** Locale corrente dell'app (es. 'it', 'en'). */
export const appLocale =
    (typeof window !== 'undefined' && window.appLocale) || 'it';

/** Lingua per il riconoscimento/sintesi vocale del browser (es. 'it-IT'). */
export const speechLang = () => t('speech_lang', 'it-IT');
