<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubjectController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        Subject::create([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#38bdf8',
        ]);

        return back()->with('status', 'Materia aggiunta.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        // Rimuoviamo l'indice vettoriale della materia (path grezzo usato dal
        // FileVectorStore, fuori dal disco 'local').
        @unlink(storage_path("app/vector/{$subject->slug}.store"));

        // I libri (e i loro PDF) vengono rimossi a cascata dal DB;
        // ripuliamo anche i file PDF su disco.
        foreach ($subject->books as $book) {
            Storage::disk('local')->delete($book->path);
        }

        $subject->delete();

        return back()->with('status', 'Materia eliminata.');
    }
}
