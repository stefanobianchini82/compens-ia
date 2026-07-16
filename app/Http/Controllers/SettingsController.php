<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $sessionTokens = 0;
        $ttsCharsSession = 0;
        if ($sessionId = $request->session()->get('chat_session_id')) {
            $session = ChatSession::find($sessionId);
            $sessionTokens = $session?->totalTokens() ?? 0;
            $ttsCharsSession = $session?->totalTtsChars() ?? 0;
        }

        return view('settings', [
            'provider' => Setting::get('provider'),
            'apiKeySet' => filled(Setting::get('api_key')),
            'chatModel' => Setting::get('chat_model'),
            'embedModel' => Setting::get('embed_model'),
            'ttsEngine' => Setting::get('tts_engine', 'browser'),
            'ttsVoice' => Setting::get('tts_voice'),
            'ttsModel' => Setting::get('tts_model'),
            'needsSetup' => (bool) $request->session()->get('needs_setup', false),
            'totalTokens' => ChatMessage::grandTotalTokens(),
            'sessionTokens' => $sessionTokens,
            'ttsCharsTotal' => ChatMessage::grandTotalTtsChars(),
            'ttsCharsSession' => $ttsCharsSession,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', Rule::in(['openai', 'gemini'])],
            'api_key' => ['nullable', 'string', 'max:500'],
            'chat_model' => ['nullable', 'string', 'max:120'],
            'embed_model' => ['nullable', 'string', 'max:120'],
            'tts_engine' => ['required', Rule::in(['browser', 'openai'])],
            'tts_voice' => ['nullable', 'string', 'max:120'],
            'tts_model' => ['nullable', 'string', 'max:120'],
        ]);

        Setting::put('provider', $validated['provider']);

        // Aggiorna la chiave solo se l'utente ne ha inserita una nuova: così può
        // salvare le altre impostazioni senza reinserire ogni volta l'API key.
        if (filled($validated['api_key'] ?? null)) {
            Setting::put('api_key', $validated['api_key']);
        }

        Setting::put('chat_model', $validated['chat_model'] ?? null);
        Setting::put('embed_model', $validated['embed_model'] ?? null);

        // Lettura ad alta voce (Text-to-Speech). Il motore "browser" (Web Speech
        // API) è gratuito e funziona con qualunque provider; "openai" usa il TTS
        // di OpenAI (qualità superiore) ma richiede provider OpenAI.
        Setting::put('tts_engine', $validated['tts_engine']);
        Setting::put('tts_voice', $validated['tts_voice'] ?? null);
        Setting::put('tts_model', $validated['tts_model'] ?? null);

        return redirect()
            ->route('settings.edit')
            ->with('status', 'Impostazioni salvate.');
    }
}
