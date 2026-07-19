@extends('layouts.app')

@section('title', 'Impostazioni')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6 space-y-6">

    @if ($needsSetup)
        <div class="rounded-2xl bg-red-100 text-red-800 px-5 py-4">
            <p class="font-bold text-lg">⚠️ Configurazione necessaria</p>
            <p>Senza il <strong>provider</strong> e la <strong>chiave API</strong> l'assistente non può funzionare.
               Inserisci i dati qui sotto e salva per iniziare a studiare.</p>
        </div>
    @endif

    <section class="rounded-2xl bg-white border border-slate-200 p-6">
        <h1 class="text-2xl font-bold mb-4">⚙️ Impostazioni</h1>

        <form action="{{ route('settings.update') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block font-semibold mb-1">Provider di intelligenza artificiale</label>
                <select name="provider"
                        class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 text-lg focus:border-sky-500 focus:outline-none">
                    <option value="openai" @selected($provider === 'openai')>OpenAI (ChatGPT)</option>
                    <option value="gemini" @selected($provider === 'gemini')>Google Gemini</option>
                </select>
                <p class="text-sm text-slate-500 mt-1">
                    Nota: sono supportati OpenAI e Gemini perché servono anche a indicizzare i libri (embeddings).
                </p>
                @error('provider')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold mb-1">Chiave API</label>
                <input type="password" name="api_key" autocomplete="off"
                       placeholder="{{ $apiKeySet ? '•••••••• (già impostata, lascia vuoto per non cambiarla)' : 'Incolla qui la tua chiave API' }}"
                       class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 text-lg focus:border-sky-500 focus:outline-none">
                @error('api_key')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold mb-1">🔊 Lettura ad alta voce</label>
                <select name="tts_engine"
                        class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 text-lg focus:border-sky-500 focus:outline-none">
                    <option value="browser" @selected($ttsEngine === 'browser')>Voce del dispositivo (gratis, sempre disponibile)</option>
                    <option value="openai" @selected($ttsEngine === 'openai')>OpenAI (qualità superiore, richiede provider OpenAI)</option>
                </select>
                <p class="text-sm text-slate-500 mt-1">
                    Aggiunge un pulsante ▶ per farti <strong>ascoltare</strong> le risposte.
                    La voce del dispositivo non ha costi. La voce OpenAI è più naturale ma
                    consuma caratteri (vedi contatore qui sotto) e funziona solo con provider OpenAI:
                    con Gemini si usa comunque la voce del dispositivo.
                </p>
                @error('tts_engine')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <details class="rounded-xl bg-slate-50 p-4">
                <summary class="cursor-pointer font-semibold">Modelli (facoltativo)</summary>
                <div class="mt-3 space-y-3">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Modello chat</label>
                        <input type="text" name="chat_model" value="{{ $chatModel }}"
                               placeholder="Predefinito in base al provider"
                               class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 focus:border-sky-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Modello embeddings</label>
                        <input type="text" name="embed_model" value="{{ $embedModel }}"
                               placeholder="Predefinito in base al provider"
                               class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 focus:border-sky-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Voce OpenAI (TTS)</label>
                        <input type="text" name="tts_voice" value="{{ $ttsVoice }}"
                               placeholder="Predefinita: nova (es. alloy, echo, fable, onyx, shimmer)"
                               class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 focus:border-sky-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Modello TTS OpenAI</label>
                        <input type="text" name="tts_model" value="{{ $ttsModel }}"
                               placeholder="Predefinito: gpt-4o-mini-tts (es. tts-1, tts-1-hd)"
                               class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 focus:border-sky-500 focus:outline-none">
                    </div>
                </div>
            </details>

            <button class="rounded-xl bg-sky-600 px-6 py-3 text-lg font-bold text-white hover:bg-sky-700">
                Salva impostazioni
            </button>
        </form>
    </section>

    {{-- Statistiche di utilizzo --}}
    <section class="rounded-2xl bg-white border border-slate-200 p-6">
        <h2 class="text-xl font-bold mb-4">📊 Utilizzo dei token</h2>
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-3xl font-bold text-sky-700">{{ number_format($totalTokens, 0, ',', '.') }}</p>
                <p class="text-slate-500">Token totali</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-3xl font-bold text-sky-700">{{ number_format($sessionTokens, 0, ',', '.') }}</p>
                <p class="text-slate-500">Token sessione corrente</p>
            </div>
        </div>

        <h3 class="text-lg font-bold mt-6 mb-3">🔊 Lettura ad alta voce (caratteri)</h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-3xl font-bold text-emerald-700">{{ number_format($ttsCharsTotal, 0, ',', '.') }}</p>
                <p class="text-slate-500">Caratteri letti totali</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-3xl font-bold text-emerald-700">{{ number_format($ttsCharsSession, 0, ',', '.') }}</p>
                <p class="text-slate-500">Caratteri letti sessione corrente</p>
            </div>
        </div>
        <p class="text-sm text-slate-500 mt-3">
            Conteggio dei caratteri sintetizzati con la voce OpenAI (unità di fatturazione del TTS).
            La voce del dispositivo non ha costi e non incrementa questi valori.
        </p>

        <h3 class="text-lg font-bold mt-6 mb-3">🎤 Dettatura vocale (token)</h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-3xl font-bold text-violet-700">{{ number_format($sttTokensTotal, 0, ',', '.') }}</p>
                <p class="text-slate-500">Token dettatura totali</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-3xl font-bold text-violet-700">{{ number_format($sttTokensSession, 0, ',', '.') }}</p>
                <p class="text-slate-500">Token dettatura sessione corrente</p>
            </div>
        </div>
        <p class="text-sm text-slate-500 mt-3">
            Conteggio dei token della trascrizione OpenAI (unità di fatturazione della dettatura).
            Il microfono del dispositivo non ha costi e non incrementa questi valori.
        </p>
    </section>
</div>
@endsection
