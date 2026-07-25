@extends('layouts.app')

@section('title', __('books.page_title'))

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 space-y-8">

    <div class="grid gap-6 md:grid-cols-2">
        {{-- Aggiungi materia --}}
        <section class="rounded-2xl bg-white border border-slate-200 p-5">
            <h2 class="text-xl font-bold mb-3">{{ __('books.add_subject_title') }}</h2>
            <form action="{{ route('subjects.store') }}" method="POST" class="space-y-3">
                @csrf
                <div class="flex items-center gap-3">
                    <input type="text" name="name" required maxlength="80" placeholder="{{ __('books.subject_name_placeholder') }}"
                           class="flex-1 min-w-0 rounded-xl border-2 border-slate-300 px-4 py-2 text-lg focus:border-sky-500 focus:outline-none">
                    <input type="color" name="color" value="#38bdf8" class="h-11 w-12 shrink-0 cursor-pointer rounded-lg border border-slate-300 p-1">
                </div>
                <button class="w-full rounded-xl bg-slate-800 px-4 py-2 font-semibold text-white hover:bg-slate-700">{{ __('books.add_subject_btn') }}</button>
            </form>
            @error('name')<p class="text-red-600 mt-2">{{ $message }}</p>@enderror
        </section>

        {{-- Carica libro --}}
        <section class="rounded-2xl bg-white border border-slate-200 p-5">
            <h2 class="text-xl font-bold mb-3">{{ __('books.upload_title') }}</h2>
            @if ($subjects->isEmpty())
                <p class="text-slate-500">{{ __('books.need_subject_first') }}</p>
            @else
                <form action="{{ route('books.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <select name="subject_id" required
                            class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 text-lg focus:border-sky-500 focus:outline-none">
                        <option value="">{{ __('books.subject_select_placeholder') }}</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="title" maxlength="150" placeholder="{{ __('books.title_placeholder') }}"
                           class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 text-lg focus:border-sky-500 focus:outline-none">
                    <input type="file" name="pdf" accept="application/pdf" required
                           class="w-full rounded-xl border-2 border-dashed border-slate-300 px-4 py-3 text-slate-600">
                    <button class="w-full rounded-xl bg-sky-600 px-4 py-2 text-lg font-bold text-white hover:bg-sky-700">
                        {{ __('books.upload_btn') }}
                    </button>
                </form>
                @error('pdf')<p class="text-red-600 mt-2">{{ $message }}</p>@enderror
            @endif
        </section>
    </div>

    {{-- Elenco libri --}}
    <section>
        <h2 class="text-xl font-bold mb-3">{{ __('books.uploaded_title') }}</h2>
        @php
            $badge = [
                'pending' => 'bg-slate-200 text-slate-700',
                'processing' => 'bg-amber-100 text-amber-800',
                'ready' => 'bg-green-100 text-green-800',
                'failed' => 'bg-red-100 text-red-800',
            ];
        @endphp

        @forelse ($books as $book)
            <div class="flex items-center gap-4 rounded-2xl bg-white border border-slate-200 p-4 mb-3"
                 data-book-id="{{ $book->id }}" data-status="{{ $book->status }}">
                <div class="h-3 w-3 rounded-full" style="background: {{ $book->subject->color ?? '#94a3b8' }}"></div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold truncate">{{ $book->title }}</p>
                    <p class="text-sm text-slate-500">{{ $book->subject->name ?? __('books.no_subject') }}</p>
                    @if ($book->status === 'failed' && $book->error_message)
                        <p class="text-sm text-red-600 mt-1">{{ $book->error_message }}</p>
                    @endif
                </div>
                <span class="js-status rounded-full px-3 py-1 text-sm font-semibold {{ $badge[$book->status] ?? 'bg-slate-200' }}">
                    {{ $book->statusLabel() }}@if($book->status === 'ready') · {{ $book->chunk_count }} {{ __('books.parts') }} @endif
                </span>
                <form action="{{ route('books.destroy', $book) }}" method="POST"
                      onsubmit="return confirm('{{ __('books.delete_confirm') }}')">
                    @csrf @method('DELETE')
                    <button class="rounded-xl px-3 py-1 text-slate-400 hover:text-red-600" title="{{ __('books.delete_title') }}">🗑️</button>
                </form>
            </div>
        @empty
            <p class="text-slate-500">{{ __('books.none_yet') }}</p>
        @endforelse
    </section>
</div>

<script>
    // Polling leggero dello stato dei libri ancora in lavorazione. Il confronto è
    // sul codice di stato (data-status), non sul testo tradotto del badge.
    document.querySelectorAll('[data-book-id]').forEach((row) => {
        const status = row.getAttribute('data-status');
        if (status !== 'pending' && status !== 'processing') {
            return;
        }
        const id = row.getAttribute('data-book-id');
        const timer = setInterval(async () => {
            try {
                const res = await fetch(`/books/${id}/status`, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (data.status === 'ready' || data.status === 'failed') {
                    clearInterval(timer);
                    window.location.reload();
                }
            } catch (_) { /* riprova al prossimo giro */ }
        }, 3000);
    });
</script>
@endsection
