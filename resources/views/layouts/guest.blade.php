<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-ink">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} · Acceso</title>

        @fonts

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-ink bg-grid font-sans text-slate-200 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center px-5 py-10">
            <a href="/" class="flex items-center gap-3">
                <x-application-logo class="size-10 text-white" />
                <span>
                    <span class="block font-mono text-[11px] uppercase tracking-[0.3em] text-muted">Monitor del servidor</span>
                    <span class="block font-display text-xl font-bold tracking-tight text-white">Acceso restringido</span>
                </span>
            </a>

            <div class="mt-8 w-full rounded-2xl border border-edge bg-panel/80 p-6 shadow-2xl shadow-black/40 backdrop-blur sm:max-w-md sm:p-8">
                {{ $slot }}
            </div>

            <p class="mt-8 font-mono text-xs text-muted">Los intentos de acceso quedan registrados.</p>
        </div>
    </body>
</html>
