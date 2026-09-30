<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Inventario AF') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans">
        <div class="flex min-h-screen items-center justify-center bg-ink-100 px-4 py-10">
            <div class="w-full max-w-md">
                <div class="mb-6 flex items-center justify-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-700 text-white">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </span>
                    <div class="leading-tight">
                        <div class="text-base font-bold text-ink-900">Inventario AF</div>
                        <div class="text-xs text-ink-400">Inventario de Activos Fijos</div>
                    </div>
                </div>

                <div class="card card-pad">
                    {{ $slot }}
                </div>

                <p class="mt-4 text-center text-xs text-ink-400">Sistema interno · uso autorizado</p>
            </div>
        </div>
    </body>
</html>
