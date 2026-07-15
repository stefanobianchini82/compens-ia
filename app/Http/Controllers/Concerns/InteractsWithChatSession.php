<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\ChatSession;
use Illuminate\Http\Request;

/**
 * Gestione della sessione di chat "corrente" del browser (nessun login):
 * l'id viene tenuto nella sessione HTTP e riusato tra le richieste.
 */
trait InteractsWithChatSession
{
    protected function currentChatSession(Request $request): ChatSession
    {
        $sessionId = $request->session()->get('chat_session_id');

        $chat = $sessionId ? ChatSession::find($sessionId) : null;

        if ($chat === null) {
            $chat = ChatSession::create();
            $request->session()->put('chat_session_id', $chat->getKey());
        }

        return $chat;
    }
}
