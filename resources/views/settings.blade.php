@extends('layouts.app')

@section('title', __('settings.page_title'))

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6 space-y-6">

    @if ($needsSetup)
        <div class="rounded-2xl bg-red-100 text-red-800 px-5 py-4">
            <p class="font-bold text-lg">{{ __('settings.needs_setup_title') }}</p>
            <p>{!! __('settings.needs_setup_body') !!}</p>
        </div>
    @endif

    <section class="rounded-2xl bg-white border border-slate-200 p-6">
        <h1 class="text-2xl font-bold mb-4">{{ __('settings.heading') }}</h1>

        <form action="{{ route('settings.update') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block font-semibold mb-1">{{ __('settings.language_label') }}</label>
                <select name="locale"
                        class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 text-lg focus:border-sky-500 focus:outline-none">
                    <option value="it" @selected($locale === 'it')>{{ __('settings.language_it') }}</option>
                    <option value="en" @selected($locale === 'en')>{{ __('settings.language_en') }}</option>
                </select>
                @error('locale')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold mb-1">{{ __('settings.provider_label') }}</label>
                <select name="provider"
                        class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 text-lg focus:border-sky-500 focus:outline-none">
                    <option value="openai" @selected($provider === 'openai')>{{ __('settings.provider_openai') }}</option>
                    <option value="gemini" @selected($provider === 'gemini')>{{ __('settings.provider_gemini') }}</option>
                </select>
                <p class="text-sm text-slate-500 mt-1">
                    {{ __('settings.provider_note') }}
                </p>
                @error('provider')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold mb-1">{{ __('settings.api_key_label') }}</label>
                <input type="password" name="api_key" autocomplete="off"
                       placeholder="{{ $apiKeySet ? __('settings.api_key_placeholder_set') : __('settings.api_key_placeholder_unset') }}"
                       class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 text-lg focus:border-sky-500 focus:outline-none">
                @error('api_key')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold mb-1">{{ __('settings.tts_label') }}</label>
                <select name="tts_engine"
                        class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 text-lg focus:border-sky-500 focus:outline-none">
                    <option value="browser" @selected($ttsEngine === 'browser')>{{ __('settings.tts_browser') }}</option>
                    <option value="openai" @selected($ttsEngine === 'openai')>{{ __('settings.tts_openai') }}</option>
                </select>
                <p class="text-sm text-slate-500 mt-1">
                    {!! __('settings.tts_note') !!}
                </p>
                <p class="text-sm text-slate-500 mt-1">
                    {!! __('settings.tts_karaoke_note') !!}
                </p>
                @error('tts_engine')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <details class="rounded-xl bg-slate-50 p-4">
                <summary class="cursor-pointer font-semibold">{{ __('settings.models_summary') }}</summary>
                <div class="mt-3 space-y-3">
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('settings.chat_model_label') }}</label>
                        <input type="text" name="chat_model" value="{{ $chatModel }}"
                               placeholder="{{ __('settings.model_placeholder_default') }}"
                               class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 focus:border-sky-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('settings.embed_model_label') }}</label>
                        <input type="text" name="embed_model" value="{{ $embedModel }}"
                               placeholder="{{ __('settings.model_placeholder_default') }}"
                               class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 focus:border-sky-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('settings.tts_voice_label') }}</label>
                        <input type="text" name="tts_voice" value="{{ $ttsVoice }}"
                               placeholder="{{ __('settings.tts_voice_placeholder') }}"
                               class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 focus:border-sky-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('settings.tts_model_label') }}</label>
                        <input type="text" name="tts_model" value="{{ $ttsModel }}"
                               placeholder="{{ __('settings.tts_model_placeholder') }}"
                               class="w-full rounded-xl border-2 border-slate-300 px-4 py-2 focus:border-sky-500 focus:outline-none">
                    </div>
                </div>
            </details>

            <button class="rounded-xl bg-sky-600 px-6 py-3 text-lg font-bold text-white hover:bg-sky-700">
                {{ __('settings.save') }}
            </button>
        </form>
    </section>

    {{-- Statistiche di utilizzo --}}
    <section class="rounded-2xl bg-white border border-slate-200 p-6">
        <h2 class="text-xl font-bold mb-4">{{ __('settings.usage_title') }}</h2>
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-3xl font-bold text-sky-700">{{ number_format($totalTokens, 0, ',', '.') }}</p>
                <p class="text-slate-500">{{ __('settings.tokens_total') }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-3xl font-bold text-sky-700">{{ number_format($sessionTokens, 0, ',', '.') }}</p>
                <p class="text-slate-500">{{ __('settings.tokens_session') }}</p>
            </div>
        </div>

        <h3 class="text-lg font-bold mt-6 mb-3">{{ __('settings.tts_usage_title') }}</h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-3xl font-bold text-emerald-700">{{ number_format($ttsCharsTotal, 0, ',', '.') }}</p>
                <p class="text-slate-500">{{ __('settings.tts_chars_total') }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-3xl font-bold text-emerald-700">{{ number_format($ttsCharsSession, 0, ',', '.') }}</p>
                <p class="text-slate-500">{{ __('settings.tts_chars_session') }}</p>
            </div>
        </div>
        <p class="text-sm text-slate-500 mt-3">
            {{ __('settings.tts_usage_note') }}
        </p>

        <h3 class="text-lg font-bold mt-6 mb-3">{{ __('settings.stt_usage_title') }}</h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-3xl font-bold text-violet-700">{{ number_format($sttTokensTotal, 0, ',', '.') }}</p>
                <p class="text-slate-500">{{ __('settings.stt_tokens_total') }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-3xl font-bold text-violet-700">{{ number_format($sttTokensSession, 0, ',', '.') }}</p>
                <p class="text-slate-500">{{ __('settings.stt_tokens_session') }}</p>
            </div>
        </div>
        <p class="text-sm text-slate-500 mt-3">
            {{ __('settings.stt_usage_note') }}
        </p>
    </section>
</div>
@endsection
