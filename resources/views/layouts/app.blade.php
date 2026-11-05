<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Inventario AF') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <script>
            (function () {
                var stored = localStorage.getItem('theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (!stored && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="font-sans">
        <div x-data="{ sidebar: false }" class="min-h-screen bg-canvas dark:bg-canvas-dark lg:flex">

            {{-- Backdrop (móvil) --}}
            <div x-show="sidebar" x-transition.opacity x-cloak
                 class="fixed inset-0 z-30 bg-black/60 lg:hidden" @click="sidebar = false"></div>

            <livewire:layout.navigation />

            {{-- Contenido --}}
            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-20 border-b border-line bg-surface/80 backdrop-blur dark:border-white/10 dark:bg-surface-dark/80">
                    <div class="flex items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
                        <button @click="sidebar = !sidebar" class="btn btn-ghost -ml-2 p-2 lg:hidden">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <div class="min-w-0 flex-1">
                            @isset($header)
                                {{ $header }}
                            @else
                                <h1 class="text-lg font-semibold text-ink dark:text-white">{{ config('app.name') }}</h1>
                            @endisset
                        </div>
                        <x-theme-toggle />
                    </div>
                </header>

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    <div class="mx-auto min-w-0 max-w-[100rem]">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        @if (session('status'))
            <script>
                document.addEventListener('DOMContentLoaded', () => window.toast('success', @json(session('status'))));
            </script>
        @endif
        @if (session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', () => window.toast('error', @json(session('error'))));
            </script>
        @endif

        @stack('scripts')
    </body>
</html>
