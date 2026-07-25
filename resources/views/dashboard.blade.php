@extends('layouts.app')

@section('title', 'CompensIA')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 flex flex-col" style="height: calc(100vh - 68px);">

    <div class="flex flex-wrap items-center gap-3 mb-4">
        <label for="subject-select" class="text-lg font-semibold">{{ __('chat.study_prompt_label') }}</label>
        <select id="subject-select"
                class="rounded-xl border-2 border-slate-300 bg-white px-4 py-2 text-lg focus:border-sky-500 focus:outline-none">
            <option value="">{{ __('chat.subject_placeholder') }}</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}"
                        @disabled($subject->ready_books_count === 0)
                        @selected($subject->id === $currentSubjectId)>
                    {{ $subject->name }}
                    @if ($subject->ready_books_count === 0) {{ __('chat.no_ready_book_suffix') }} @endif
                </option>
            @endforeach
        </select>
        <button type="button" id="chat-reset-btn"
                class="ml-auto shrink-0 rounded-xl border-2 border-slate-300 bg-white px-4 py-2 text-base font-semibold text-slate-700 hover:bg-slate-100 focus:border-sky-500 focus:outline-none">
            {{ __('chat.clear_chat') }}
        </button>
        <span class="ml-auto text-sm text-slate-500">
            {{ __('chat.session_tokens') }} <strong id="session-tokens">{{ number_format($sessionTokens, 0, ',', '.') }}</strong>
        </span>
    </div>

    @unless ($hasReadyBooks)
        <div class="rounded-xl bg-amber-100 text-amber-800 px-5 py-4 mb-4">
            {!! __('chat.no_ready_books', ['url' => route('books.index')]) !!}
        </div>
    @endunless

    <div id="chat-messages" data-tts-engine="{{ $ttsEngine }}" data-tts-url="{{ route('chat.tts') }}"
         data-mindmap-url="{{ route('chat.mindmap') }}"
         class="flex-1 overflow-y-auto space-y-4 rounded-2xl bg-slate-100 p-4">
        @forelse ($messages as $message)
            <div class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                <div @if ($message->role !== 'user') data-message-id="{{ $message->id }}" @endif
                     class="{{ $message->role === 'user'
                        ? 'bg-sky-600 text-white whitespace-pre-wrap'
                        : 'bg-white text-slate-800 border border-slate-200 chat-md' }}
                        max-w-[85%] rounded-2xl px-5 py-3 shadow-sm">{{ $message->content }}</div>
            </div>
        @empty
            <div id="chat-empty" class="h-full flex items-center justify-center text-center text-slate-500">
                {!! __('js.welcome') !!}
            </div>
        @endforelse
    </div>

    <form id="chat-form" action="{{ route('chat.stream') }}" method="POST" class="mt-4 flex items-end gap-3"
          data-stt-url="{{ route('chat.stt') }}" data-stt-available="{{ $sttAvailable ? '1' : '0' }}">
        @csrf
        <textarea id="chat-input" name="message" rows="2" required
                  placeholder="{{ __('chat.input_placeholder') }}"
                  class="flex-1 resize-none rounded-2xl border-2 border-slate-300 bg-white px-5 py-3 text-lg focus:border-sky-500 focus:outline-none"></textarea>
        <button id="chat-mic" type="button" aria-label="{{ __('chat.mic_aria') }}"
                class="shrink-0 rounded-2xl border-2 border-slate-300 bg-white px-5 py-3 text-lg font-bold text-slate-700 shadow hover:bg-slate-100 focus:border-sky-500 focus:outline-none disabled:opacity-50">
            🎤
        </button>
        <button id="chat-send" type="submit"
                class="rounded-2xl bg-sky-600 px-6 py-3 text-lg font-bold text-white shadow hover:bg-sky-700 disabled:opacity-50">
            {{ __('chat.send') }}
        </button>
    </form>
</div>

{{-- Modale di conferma generico (svuotamento chat / cambio materia). Testo e
     azione del pulsante di conferma sono impostati da JS via openConfirm(). --}}
<div id="chat-confirm-modal" data-reset-url="{{ route('chat.reset') }}"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 px-4"
     role="dialog" aria-modal="true" aria-labelledby="chat-modal-title">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h2 id="chat-modal-title" class="text-2xl font-bold mb-3">{{ __('js.clear_title') }}</h2>
        <p id="chat-modal-message" class="text-lg text-slate-600 mb-6">
            {{ __('js.clear_body') }}
        </p>
        <div class="flex flex-col gap-3 sm:flex-row-reverse">
            <button type="button" id="chat-modal-confirm"
                    class="w-full rounded-xl bg-red-600 px-6 py-3 text-lg font-bold text-white shadow hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 sm:w-auto">
                {{ __('js.clear_confirm') }}
            </button>
            <button type="button" id="chat-modal-cancel"
                    class="w-full rounded-xl border-2 border-slate-300 bg-white px-6 py-3 text-lg font-bold text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-slate-200 sm:w-auto">
                {{ __('chat.modal_cancel') }}
            </button>
        </div>
    </div>
</div>
@endsection
