# CLAUDE.md

**CompensIA** — assistente allo studio per ragazzi con **DSA**. Si caricano i libri scolastici in PDF
(categorizzati per materia) e si studia tramite una chat semplice, in linguaggio
accessibile, con risposte basate sui libri (RAG). App **locale, senza login**.

> Stato: progetto **Laravel 12** funzionante. La vecchia demo CLI (`src/`, `bin/`)
> è stata sostituita; le classi NeuronAI riusabili sono state portate in `app/AI/`.
> Piano di riferimento: `~/.claude/plans/crea-un-piano-per-peaceful-meadow.md`.

## Stack
- Laravel 12, PHP 8.4
- NeuronAI (`neuron-core/neuron-ai ^3`) — RAG, provider LLM, streaming
- Tailwind CSS (Vite), Vanilla JS per la chat SSE
- SQLite (usato anche per `queue` e `cache`, driver `database`)
- Vector store: `FileVectorStore` su file, **uno per materia** (`storage/app/vector/{slug}.store`)
- Dipendenza di sistema: poppler (`pdftotext`) — `brew install poppler`. Il percorso del
  binario è risolto da `app/AI/PopplerBinary`: `POPPLER_BIN_PATH` → `resources/bin/{OS}/pdftotext`
  (bundle NativePHP) → auto-discovery sul PATH. Fallback trasparente in sviluppo.
- Futuro delivery: NativePHP/Electron

## Provider LLM
Solo **OpenAI** e **Gemini** (Anthropic/Claude escluso: non offre API di embeddings,
indispensabili al RAG). Chat ed embeddings usano lo stesso provider e la stessa API key,
configurati dalla UI in Impostazioni e salvati in DB (tabella `settings`, `api_key` cifrata).
Senza provider e key valorizzati l'app reindirizza a `/settings`.

## Comandi
- `composer install`
- `php artisan migrate`
- `npm install && npm run dev` (o `npm run build`)
- `php artisan serve`
- `php artisan queue:work`  # necessario per l'ingestione dei PDF

## Architettura
- `app/AI/StudyAgent` (estende `RAG` di NeuronAI): provider / embeddings / vectorStore /
  system prompt configurati **per materia** tramite `app/AI/StudyAgentFactory`.
- `app/AI/SafeFileVectorStore` — retrieval tollerante se l'indice non esiste ancora.
- `app/AI/CountingOpenAIEmbeddingsProvider` / `CountingGeminiEmbeddingsProvider` —
  calcolano gli embeddings e accumulano i token consumati.
- `app/AI/StudyPrompt` — system prompt DSA (frasi brevi, parole semplici, tono paziente
  e incoraggiante, risposte basate solo sul libro, mai inventare).
- `app/Jobs/IngestBookJob` — estrae il testo dal PDF, calcola gli embeddings e li salva
  nel vector store della materia; aggiorna lo stato del libro (`pending`/`processing`/
  `ready`/`failed`).
- `ChatController@stream` — chat in **streaming SSE** via `response()->eventStream()`
  su `StudyAgent->stream(...)->events()`.
- `EnsureSettingsConfigured` (middleware) — gate sulle impostazioni obbligatorie.

## Modello dati (SQLite)
- `settings` (key/value: `provider`, `api_key` cifrata, `chat_model`, `embed_model`)
- `subjects` (materie: `name`, `slug`, `color`)
- `books` (`subject_id`, `title`, `path`, `status`, `chunk_count`, `embed_tokens`, `error_message`)
- `chat_sessions` (`subject_id`, `title`) — chiave `FileChatHistory` = `session-{id}`
- `chat_messages` (`role`, `content`, `input_tokens`, `output_tokens`, `embed_tokens`)

Statistiche token: "totali" = somma su tutte le `chat_messages`; "sessione corrente" = somma
filtrata sulla `chat_session` attiva. Chat da `response->getUsage()`, embeddings dai
`Counting*EmbeddingsProvider`.

## Convenzioni
- Rispondere e commentare **in italiano**.
- RAG **filtrato per materia** (un vector store per materia); in chat la materia si seleziona
  prima di scrivere.
- UI pensata per DSA: font ampio e leggibile, alto contrasto, spaziatura generosa, pulsanti
  grandi, linguaggio semplice, nessuna distrazione.
