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
 *
 * Detección por IP (ventana de 5 minutos):
 *   - IP ya bloqueada  -> 429 y registro "blocked"
 *   - 5 o más fallos   -> bloqueo de la IP por 10 minutos, CRITICAL y 429
 *   - 3 o 4 fallos     -> ALERT, pero deja intentar
 *
 * Detección por cuenta (mejora; ventana de 15 minutos, desde cualquier IP):
 *   - Cuenta ya bloqueada -> 429 y registro "blocked"
 *   - 5 o más fallos      -> bloqueo de la cuenta por 10 minutos, CRITICAL y 429
 *   - 3 o 4 fallos        -> ALERT por posible ataque dirigido
 *   Sirve para ataques distribuidos: muchas IPs distintas contra la misma cuenta.
 */
class SecurityLoginGuard
{
    // Reglas por IP (enunciado del TP)
    private const IP_WINDOW_MINUTES = 5;

    // Reglas por cuenta (mejora). La ventana es más larga porque los ataques distribuidos suelen ser más lentos.
    private const ACCOUNT_WINDOW_MINUTES = 15;

    private const ALERT_AT = 3;
    private const BLOCK_AT = 5;
    private const BLOCK_MINUTES = 10;

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $ipKey = 'security:block:'.hash('sha256', $ip);

        // Mismo hash que guarda el listener en los intentos fallidos
        $email = mb_strtolower((string) $request->input('email', ''));
        $identifier = $email !== '' ? hash('sha256', $email) : null;
        $accountKey = $identifier ? 'security:block:account:'.$identifier : null;

        // 1. ¿La IP o la cuenta ya están bloqueadas?
        if (Cache::has($ipKey)) {
            $this->rejectBlocked($request, $identifier, 'Blocked login attempt', ['ip' => $ip]);
        }

        if ($accountKey && Cache::has($accountKey)) {
            $this->rejectBlocked($request, $identifier, 'Blocked login attempt (locked account)', [
                'ip' => $ip,
                'account' => substr($identifier, 0, 12),
            ]);
        }

        // 2. Fallos de esta IP en los últimos 5 minutos
        $ipFailures = LoginAttempt::where('ip_address', $ip)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes(self::IP_WINDOW_MINUTES))
            ->count();

        // 3. Fallos contra esta cuenta en los últimos 15 minutos, desde cualquier IP
        $accountFailures = 0;
        $distinctIps = 0;

        if ($identifier) {
            $accountAttempts = LoginAttempt::where('identifier_hash', $identifier)
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subMinutes(self::ACCOUNT_WINDOW_MINUTES));

            $accountFailures = (clone $accountAttempts)->count();
            $distinctIps = (clone $accountAttempts)->distinct()->count('ip_address');
        }

        // 4. Bloqueos: primero por IP (regla del TP), después por cuenta (mejora)
        if ($ipFailures >= self::BLOCK_AT) {
            Cache::put($ipKey, true, now()->addMinutes(self::BLOCK_MINUTES));

            Log::channel('security')->critical('Temporary IP block', ['ip' => $ip, 'failures' => $ipFailures]);

            abort(429, 'Acceso temporalmente restringido');
        }

        if ($accountFailures >= self::BLOCK_AT) {
            Cache::put($accountKey, true, now()->addMinutes(self::BLOCK_MINUTES));

            Log::channel('security')->critical('Temporary account lock', [
                'ip' => $ip,
                'account' => substr($identifier, 0, 12),
                'failures' => $accountFailures,
                'distinct_ips' => $distinctIps,
            ]);

            abort(429, 'Acceso temporalmente restringido');
        }

        // 5. Alertas sin bloquear
        if ($ipFailures >= self::ALERT_AT) {
            Log::channel('security')->alert('Possible brute force', ['ip' => $ip, 'failures' => $ipFailures]);
        }

        if ($accountFailures >= self::ALERT_AT) {
            Log::channel('security')->alert('Possible targeted attack on account', [
                'ip' => $ip,
                'account' => substr($identifier, 0, 12),
                'failures' => $accountFailures,
                'distinct_ips' => $distinctIps,
            ]);
        }

        return $next($request);
    }

    /**
     * Registra el intento rechazado y corta la petición con HTTP 429.
     */
    private function rejectBlocked(Request $request, ?string $identifier, string $message, array $context): never
    {
        LoginAttempt::create([
            'identifier_hash' => $identifier,
            'ip_address' => $request->ip(),
            'status' => 'blocked',
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
            'created_at' => now(),
        ]);

        Log::channel('security')->warning($message, $context);

        abort(429, 'Acceso temporalmente restringido');
    }
}
