<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MindMapController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

// Le impostazioni sono sempre accessibili (anche quando non configurate).
Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

// Tutto il resto richiede provider + API key configurati.
Route::middleware('settings')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/chat/stream', [ChatController::class, 'stream'])->name('chat.stream');
    Route::post('/chat/tts', [ChatController::class, 'tts'])->name('chat.tts');
    Route::post('/chat/stt', [ChatController::class, 'stt'])->name('chat.stt');
    Route::post('/chat/mindmap', [MindMapController::class, 'generate'])->name('chat.mindmap');
    Route::post('/chat/reset', [ChatController::class, 'reset'])->name('chat.reset');

    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::get('/books/{book}/status', [BookController::class, 'status'])->name('books.status');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

    Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
    Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
});
