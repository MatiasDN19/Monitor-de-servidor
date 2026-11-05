<!DOCTYPE html>
<html lang="es" class="bg-ink">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitor · {{ $data['hostname'] }}</title>
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-ink bg-grid font-sans text-slate-200 antialiased">
    <main class="mx-auto max-w-6xl px-5 py-8 sm:px-8 sm:py-12">

        {{-- Encabezado --}}
        <header class="flex flex-col gap-6 border-b border-edge pb-8 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="font-mono text-xs uppercase tracking-[0.3em] text-muted">Monitor del servidor</p>
                <h1 class="mt-2 font-display text-4xl font-bold tracking-tight text-white sm:text-5xl">
                    {{ $data['hostname'] }}
                </h1>
                <p class="mt-2 font-mono text-sm text-muted">
                    {{ $data['os'] }} <span class="text-edge">/</span> PHP {{ $data['php'] }}
                </p>
            </div>

            <div class="flex flex-col items-start gap-2 sm:items-end">
                <span id="status" class="inline-flex items-center gap-2 rounded-full border border-ok/30 bg-ok/10 px-3 py-1 font-mono text-xs font-medium uppercase tracking-widest text-ok">
                    <span class="relative flex size-2">
                        <span id="status-ping" class="absolute inline-flex size-full animate-ping rounded-full bg-current opacity-60"></span>
                        <span class="relative inline-flex size-2 rounded-full bg-current"></span>
                    </span>
                    <span id="status-text">En vivo</span>
                </span>
                <p class="font-mono text-xs text-muted">
                    Última lectura <time id="at" datetime="{{ $data['at'] }}" class="text-slate-300">{{ $data['at'] }}</time>
                </p>
            </div>
        </header>

        {{-- Medidores --}}
        <section class="mt-8 grid gap-4 md:grid-cols-3" aria-label="Uso de recursos">
            @foreach ([
                ['key' => 'cpu',    'label' => 'CPU',     'detail' => 'Carga del procesador'],
                ['key' => 'memory', 'label' => 'Memoria', 'detail' => 'RAM en uso'],
                ['key' => 'disk',   'label' => 'Disco',   'detail' => 'Espacio ocupado'],
            ] as $g)
                <article class="group relative overflow-hidden rounded-2xl border border-edge bg-panel/80 p-6 backdrop-blur">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display text-lg font-medium text-white">{{ $g['label'] }}</h2>
                        <span id="{{ $g['key'] }}-state" class="font-mono text-[11px] uppercase tracking-widest text-muted">—</span>
                    </div>

                    <div class="relative mx-auto mt-4 aspect-square w-full max-w-[220px]">
                        <svg viewBox="0 0 120 120" class="size-full -rotate-[225deg]" aria-hidden="true">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="var(--color-edge)" stroke-width="9"
                                    stroke-linecap="round" class="gauge-track" />
                            <circle id="{{ $g['key'] }}-arc" cx="60" cy="60" r="52" fill="none"
                                    stroke="var(--color-ok)" stroke-width="9" stroke-linecap="round" class="gauge-arc" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <p class="font-mono text-5xl font-bold tabular-nums text-white">
                                <span id="{{ $g['key'] }}">--</span><span class="text-2xl text-muted">%</span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-2 flex items-baseline justify-between border-t border-edge pt-4 font-mono text-xs">
                        <span class="text-muted">{{ $g['detail'] }}</span>
                        <span id="{{ $g['key'] }}-detail" class="text-slate-300"></span>
                    </div>
                </article>
            @endforeach
        </section>

        {{-- Datos secundarios --}}
        <section class="mt-4 grid gap-4 sm:grid-cols-3" aria-label="Más datos">
            <article class="rounded-2xl border border-edge bg-panel/80 p-6">
                <p class="font-mono text-xs uppercase tracking-widest text-muted">Encendido hace</p>
                <p id="uptime" class="mt-3 font-mono text-3xl font-bold tabular-nums text-white">--</p>
                <p class="mt-1 font-mono text-xs text-muted">días · horas : min : seg</p>
            </article>

            <article class="rounded-2xl border border-edge bg-panel/80 p-6">
                <p class="font-mono text-xs uppercase tracking-widest text-muted">Temperatura CPU</p>
                <p class="mt-3 font-mono text-3xl font-bold tabular-nums text-white">
                    <span id="temp">--</span><span class="text-lg text-muted"> °C</span>
                </p>
                <p id="temp-hint" class="mt-1 font-mono text-xs text-muted"></p>
            </article>

            <article class="rounded-2xl border border-edge bg-panel/80 p-6">
                <p class="font-mono text-xs uppercase tracking-widest text-muted">Pedidos a la API</p>
                <p id="requests" class="mt-3 font-mono text-3xl font-bold tabular-nums text-signal">0</p>
                <p class="mt-1 font-mono text-xs text-muted">polling cada 5 s · GET /api/metrics</p>
            </article>
        </section>

        <footer class="mt-10 font-mono text-xs text-muted">
            UTN FRRe · Programación IV · TP Monitor de Servidor
        </footer>
    </main>

    <script>
        // Largo del arco: el medidor ocupa 3/4 de la circunferencia (270°)
        const CIRC = 2 * Math.PI * 52;
        const ARC  = CIRC * 0.75;
        const NA   = '--';

        const $ = (id) => document.getElementById(id);
        const num = (n, dec = 1) => n.toLocaleString('es-AR', { maximumFractionDigits: dec });

        let uptime = null;   // segundos; avanza solo entre lecturas
        let pedidos = 0;

        // Verde, amarillo o rojo según el porcentaje
        function nivel(pct) {
            if (pct < 60) return { color: 'var(--color-ok)',     texto: 'Normal' };
            if (pct < 85) return { color: 'var(--color-warn)',   texto: 'Alta' };
            return               { color: 'var(--color-danger)', texto: 'Crítica' };
        }

        function medidor(key, pct, detalle) {
            const arc = $(key + '-arc');
            const state = $(key + '-state');
            $(key + '-detail').textContent = detalle ?? '';

            if (pct === null || pct === undefined) {
                $(key).textContent = NA;
                arc.style.strokeDashoffset = ARC;
                state.textContent = 'Sin datos';
                state.style.color = '';
                return;
            }

            const n = nivel(pct);
            $(key).textContent = num(pct, 0);
            arc.style.strokeDashoffset = ARC * (1 - Math.min(pct, 100) / 100);
            arc.style.stroke = n.color;
            state.textContent = n.texto;
            state.style.color = n.color;
        }

        function formatoUptime(s) {
            const d = Math.floor(s / 86400);
            const hms = [s % 86400 / 3600, s % 3600 / 60, s % 60]
                .map((v) => String(Math.floor(v)).padStart(2, '0'))
                .join(':');
            return `${d}d ${hms}`;
        }

        function estado(online) {
            const el = $('status');
            el.className = el.className.replace(/(border|bg|text)-(ok|danger)(\/\d+)?/g, '');
            el.classList.add(...(online
                ? ['border-ok/30', 'bg-ok/10', 'text-ok']
                : ['border-danger/30', 'bg-danger/10', 'text-danger']));
            $('status-text').textContent = online ? 'En vivo' : 'Sin conexión';
            $('status-ping').hidden = !online;
        }

        // Dibuja una lectura en la pantalla. La usa el polling y la reutilizamos en el TP de WebSockets.
        function render(d) {
            const gb = (mb) => num(mb / 1024);

            medidor('cpu', d.cpu_pct);
            medidor('memory', d.memory?.used_pct,
                d.memory ? `${gb(d.memory.total_mb - d.memory.available_mb)} / ${gb(d.memory.total_mb)} GB` : null);
            medidor('disk', d.disk.used_pct,
                `${num(d.disk.total_gb - d.disk.free_gb, 0)} / ${num(d.disk.total_gb, 0)} GB`);

            $('temp').textContent = d.cpu_temp_c ?? NA;
            $('temp-hint').textContent = d.cpu_temp_c === null ? 'No disponible · probá como administrador' : 'Sensor ACPI';

            uptime = d.uptime_s;
            $('uptime').textContent = uptime === null ? NA : formatoUptime(uptime);

            const at = new Date(d.at);
            $('at').dateTime = d.at;
            $('at').textContent = at.toLocaleTimeString('es-AR', { hour12: false });
        }

        // Polling: le pregunta a la API cada 5 segundos
        async function actualizar() {
            try {
                const res = await fetch('/api/metrics');
                if (!res.ok) throw new Error(res.status);
                render(await res.json());
                $('requests').textContent = ++pedidos;
                estado(true);
            } catch {
                estado(false);
            }
        }

        // El reloj de encendido avanza de a un segundo entre lecturas
        setInterval(() => {
            if (uptime !== null) $('uptime').textContent = formatoUptime(++uptime);
        }, 1000);

        // Prepara los arcos vacíos y después pinta la primera lectura (así se ve la animación)
        document.querySelectorAll('.gauge-arc, .gauge-track').forEach((c) => {
            c.style.strokeDasharray = `${ARC} ${CIRC}`;
        });
        document.querySelectorAll('.gauge-arc').forEach((c) => c.style.strokeDashoffset = ARC);
        requestAnimationFrame(() => requestAnimationFrame(() => render(@json($data))));

        setInterval(actualizar, 5000); // cada 5 segundos
    </script>
</body>
</html>
