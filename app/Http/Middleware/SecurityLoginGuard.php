<?php

namespace App\Http\Middleware;

use App\Models\LoginAttempt;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Se ejecuta ANTES del controlador de login de Breeze.
 * Mira cuántos fallos tuvo la IP en los últimos 5 minutos y decide:
 *   - IP ya bloqueada  -> 429 y registro "blocked"
 *   - 5 o más fallos   -> bloqueo de 10 minutos, CRITICAL y 429
 *   - 3 o 4 fallos     -> ALERT, pero deja intentar
 *   - menos de 3       -> deja pasar sin hacer nada
 */
class SecurityLoginGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $key = 'security:block:'.hash('sha256', $ip);

        // 1. ¿La IP ya está bloqueada? (el bloqueo vive en la caché 10 minutos)
        if (Cache::has($key)) {
            LoginAttempt::create([
                'ip_address' => $ip,
                'status' => 'blocked',
                'user_agent' => substr($request->userAgent() ?? '', 0, 255),
                'created_at' => now(),
            ]);

            Log::channel('security')->warning('Blocked login attempt', ['ip' => $ip]);

            abort(429, 'Acceso temporalmente restringido');
        }

        // 2. Contar los fallos de esta IP en la ventana de 5 minutos
        $failures = LoginAttempt::where('ip_address', $ip)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        // 3. Cinco fallos: se bloquea la IP por 10 minutos
        if ($failures >= 5) {
            Cache::put($key, true, now()->addMinutes(10));

            Log::channel('security')->critical('Temporary IP block', ['ip' => $ip, 'failures' => $failures]);

            abort(429, 'Acceso temporalmente restringido');
        }

        // 4. Tres o cuatro fallos: posible fuerza bruta, se avisa pero no se bloquea
        if ($failures >= 3) {
            Log::channel('security')->alert('Possible brute force', ['ip' => $ip, 'failures' => $failures]);
        }

        return $next($request);
    }
}
