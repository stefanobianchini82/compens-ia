<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table): void {
            // Token consumati dalla dettatura vocale (Speech-to-Text OpenAI). La
            // trascrizione avviene PRIMA che esista un messaggio (riempie solo la
            // casella della domanda), quindi il conteggio si attribuisce alla
            // sessione, non a una chat_message. Contatore SEPARATO dai token
            // dell'LLM, mostrato solo nella pagina Impostazioni. Il microfono del
            // dispositivo (fallback browser) non lo incrementa.
            $table->unsignedInteger('stt_tokens')->default(0)->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table): void {
            $table->dropColumn('stt_tokens');
        });
    }
};
