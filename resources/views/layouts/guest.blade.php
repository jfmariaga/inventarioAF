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
    </head>
    <body class="font-sans antialiased bg-canvas dark:bg-canvas-dark" x-data>
        <div class="min-h-screen lg:grid lg:grid-cols-2">
            {{-- Panel de marca, solo en escritorio --}}
            <div class="hidden lg:flex relative flex-col justify-between bg-black text-white p-12 overflow-hidden">
                <div class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-brand-700/40 blur-3xl"></div>
                <div class="absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-brand-900/40 blur-3xl"></div>

                <div class="relative text-lg font-bold">Inventario AF</div>

                <div class="relative">
                    <h1 class="text-3xl font-bold leading-tight text-white">
                        Gestiona el inventario de activos fijos de forma simple y confiable.
                    </h1>
                    <p class="mt-3 text-sm text-gray-300">Acceso exclusivo para personal autorizado.</p>
                </div>

                <p class="relative text-xs text-gray-400">&copy; {{ now()->year }} Inventario AF.</p>
            </div>

            {{-- Formulario --}}
            <div class="flex flex-col justify-center px-4 py-12 sm:px-6 lg:px-12">
                <div class="flex items-center justify-between mb-8 lg:hidden">
                    <div class="text-base font-bold text-ink dark:text-white">Inventario AF</div>
                    <x-theme-toggle />
                </div>

                <div class="hidden lg:flex justify-end mb-6">
                    <x-theme-toggle />
                </div>

                <div class="w-full max-w-sm mx-auto lg:mx-0 card p-8">
                    {{ $slot }}
                </div>

                <p class="mt-4 w-full max-w-sm mx-auto lg:mx-0 text-center text-xs text-ink-secondary dark:text-gray-500">
                    Sistema interno · uso autorizado
                </p>
            </div>
        </div>
    </body>
</html>
