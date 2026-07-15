<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le impostazioni (provider LLM + API key) sono OBBLIGATORIE: senza di esse
 * l'assistente non può funzionare. Questo middleware protegge le pagine che
 * dipendono dall'LLM e, se non configurate, reindirizza alle Impostazioni con
 * un messaggio esplicativo.
 */
class EnsureSettingsConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::isConfigured()) {
            return redirect()
                ->route('settings.edit')
                ->with('needs_setup', true);
        }

        return $next($request);
    }
}
