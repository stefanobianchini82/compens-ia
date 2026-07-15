<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithChatSession;
use App\Models\Book;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use InteractsWithChatSession;

    public function index(Request $request): View
    {
        $chat = $this->currentChatSession($request);

        // Materie che hanno almeno un libro pronto: sono quelle su cui la chat
        // può davvero rispondere con il RAG.
        $subjects = Subject::query()
            ->withCount(['books as ready_books_count' => function ($query): void {
                $query->where('status', Book::STATUS_READY);
            }])
            ->orderBy('name')
            ->get();

        return view('dashboard', [
            'subjects' => $subjects,
            'messages' => $chat->messages()->orderBy('id')->get(),
            'sessionTokens' => $chat->totalTokens(),
            'currentSubjectId' => $chat->subject_id,
            'hasReadyBooks' => Book::where('status', Book::STATUS_READY)->exists(),
        ]);
    }
}
