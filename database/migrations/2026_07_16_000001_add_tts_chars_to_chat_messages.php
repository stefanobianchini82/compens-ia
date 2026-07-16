<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table): void {
            // Caratteri inviati al Text-to-Speech (motore OpenAI): unità reale di
            // fatturazione del TTS. Contatore SEPARATO dai token dell'LLM, mostrato
            // solo nella pagina Impostazioni. Il motore browser non lo incrementa.
            $table->unsignedInteger('tts_chars')->default(0)->after('embed_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->dropColumn('tts_chars');
        });
    }
};
