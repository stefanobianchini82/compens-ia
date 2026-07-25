<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applica a ogni richiesta la lingua scelta dall'utente in Impostazioni.
 *
 * La lingua è salvata come impostazione `locale` (tabella `settings`); in
 * assenza si usa il default di configurazione (italiano). Registrato sull'intero
 * gruppo web, così vale anche su `/settings`, che è fuori dal middleware
 * `settings`. Le lingue sono limitate a quelle effettivamente tradotte.
 */
class SetLocale
{
    /** Lingue supportate dall'interfaccia. */
    public const SUPPORTED = ['it', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = Setting::get('locale', config('app.locale'));

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
