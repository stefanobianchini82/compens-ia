<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\IngestBookJob;
use App\Models\Book;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(): View
    {
        return view('books', [
            'subjects' => Subject::orderBy('name')->get(),
            'books' => Book::with('subject')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'title' => ['nullable', 'string', 'max:150'],
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:91200'], // max 50 MB
        ]);

        $file = $request->file('pdf');
        $path = $file->store('books', 'local');

        $book = Book::create([
            'subject_id' => $validated['subject_id'],
            'title' => $validated['title'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original_filename' => $file->getClientOriginalName(),
            'path' => $path,
            'status' => Book::STATUS_PENDING,
        ]);

        IngestBookJob::dispatch($book->id);

        return back()->with('status', 'Libro caricato: lo sto preparando per lo studio.');
    }

    /** Stato di indicizzazione del libro (per il polling della UI). */
    public function status(Book $book): JsonResponse
    {
        return response()->json([
            'status' => $book->status,
            'label' => $book->statusLabel(),
            'chunk_count' => $book->chunk_count,
            'error_message' => $book->error_message,
        ]);
    }

    public function destroy(Book $book): RedirectResponse
    {
        Storage::disk('local')->delete($book->path);
        $book->delete();

        return back()->with('status', 'Libro eliminato.');
    }
}
