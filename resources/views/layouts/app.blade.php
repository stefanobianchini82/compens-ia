<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CompensIA')</title>
    {{-- Ponte delle traduzioni verso i file JS (chat, mappe, TTS/STT, preferenze). --}}
    <script>
        window.i18n = @js(__('js'));
        window.appLocale = @js(app()->getLocale());
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 text-slate-800">
    <div class="min-h-full flex flex-col">
        <header class="bg-white border-b border-slate-200">
            <div class="max-w-4xl mx-auto px-4 py-3 flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="text-2xl font-bold text-sky-700 flex items-center gap-2">
                    <span>📚</span> <span>CompensIA</span>
                </a>
                <nav class="ml-auto flex items-center gap-2 text-lg">
                    @php($nav = 'inline-flex items-center gap-2 rounded-xl px-4 py-2 font-semibold transition')
                    <a href="{{ route('dashboard') }}"
                       class="{{ $nav }} {{ request()->routeIs('dashboard') ? 'bg-sky-100 text-sky-800' : 'text-slate-600 hover:bg-slate-100' }}">
                        {{ __('nav.study') }}
                    </a>
                    <a href="{{ route('books.index') }}"
                       class="{{ $nav }} {{ request()->routeIs('books.*') ? 'bg-sky-100 text-sky-800' : 'text-slate-600 hover:bg-slate-100' }}">
                        {{ __('nav.books') }}
                    </a>
                    <a href="{{ route('settings.edit') }}"
                       class="{{ $nav }} {{ request()->routeIs('settings.*') ? 'bg-sky-100 text-sky-800' : 'text-slate-600 hover:bg-slate-100' }}">
                        {{ __('nav.settings') }}
                    </a>
                    <button type="button" id="reading-prefs-btn" aria-label="{{ __('nav.reading_prefs') }}"
                            class="inline-flex items-center justify-center rounded-xl border-2 border-slate-300 bg-white w-11 h-11 text-lg font-bold text-slate-700 hover:bg-slate-100 focus:border-sky-500 focus:outline-none">
                        Aa
                    </button>
                </nav>
            </div>
        </header>

        @if (session('status'))
            <div class="max-w-4xl mx-auto w-full px-4 pt-4">
                <div class="rounded-xl bg-green-100 text-green-800 px-5 py-3 font-semibold">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        <main class="flex-1 w-full">
            @yield('content')
        </main>
    </div>
</body>
</html>
