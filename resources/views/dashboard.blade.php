<x-app-layout>
    <x-slot name="header">
        <p class="font-mono text-xs uppercase tracking-[0.3em] text-muted">Hola, {{ Auth::user()->name }}</p>
        <h2 class="mt-2 font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-6xl gap-4 px-5 sm:grid-cols-2 sm:px-8">
            <a href="{{ url('/servidor') }}"
               class="group rounded-2xl border border-edge bg-panel/80 p-6 transition hover:border-signal/50">
                <p class="flex items-center gap-2 font-mono text-xs uppercase tracking-widest text-muted">
                    <span class="size-2 rounded-full bg-ok"></span> En vivo
                </p>
                <h3 class="mt-4 font-display text-2xl font-bold text-white">Monitor del servidor</h3>
                <p class="mt-2 text-sm text-muted">CPU, memoria, disco, temperatura y tiempo encendido, actualizados cada 5 segundos.</p>
                <p class="mt-6 font-mono text-xs text-signal transition group-hover:translate-x-1">Abrir monitor →</p>
            </a>

            @can('viewSecurityDashboard')
                <a href="{{ route('security.dashboard') }}"
                   class="group rounded-2xl border border-edge bg-panel/80 p-6 transition hover:border-signal/50">
                    <p class="flex items-center gap-2 font-mono text-xs uppercase tracking-widest text-muted">
                        <span class="size-2 rounded-full bg-warn"></span> Solo administradores
                    </p>
                    <h3 class="mt-4 font-display text-2xl font-bold text-white">Panel de seguridad</h3>
                    <p class="mt-2 text-sm text-muted">Logins exitosos, fallidos y bloqueados; IPs sospechosas y últimos eventos.</p>
                    <p class="mt-6 font-mono text-xs text-signal transition group-hover:translate-x-1">Abrir panel →</p>
                </a>
            @else
                <div class="rounded-2xl border border-dashed border-edge p-6">
                    <p class="font-mono text-xs uppercase tracking-widest text-muted">Panel de seguridad</p>
                    <p class="mt-4 text-sm text-muted">Tu cuenta no tiene permisos de administrador para ver el registro de accesos.</p>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
