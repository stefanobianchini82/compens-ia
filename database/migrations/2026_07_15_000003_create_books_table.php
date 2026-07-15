<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Libri scolastici caricati in PDF. Lo stato segue il ciclo di indicizzazione:
 * pending -> processing -> ready (oppure failed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('original_filename');
            $table->string('path');
            $table->string('status')->default('pending'); // pending|processing|ready|failed
            $table->unsignedInteger('chunk_count')->default(0);
            $table->unsignedInteger('embed_tokens')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
