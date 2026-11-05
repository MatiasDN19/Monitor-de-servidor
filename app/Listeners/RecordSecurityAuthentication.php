<?php

namespace App\Listeners;

use App\Models\LoginAttempt;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

/**
 * Escucha los eventos de autenticación que ya emite Laravel (Login y Failed)
 * y los registra en la tabla login_attempts y en el canal de logs "security".
 * No modifica el login de Breeze: solo observa lo que pasa.
 */
class RecordSecurityAuthentication
{
    public function handle(Login|Failed $event): void
    {
        $request = request();
        $status = $event instanceof Login ? 'success' : 'failed';

        // En un login correcto identificamos por id de usuario;
        // en uno fallido, por el email que se intentó usar.
        $identifier = $event instanceof Login
            ? (string) $event->user->getAuthIdentifier()
            : (string) ($event->credentials['email'] ?? '');

        LoginAttempt::create([
            // Guardamos solo el hash: nunca el email en claro ni la contraseña
            'identifier_hash' => hash('sha256', mb_strtolower($identifier)),
            'ip_address' => $request->ip(),
            'status' => $status,
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
            'created_at' => now(),
        ]);

        Log::channel('security')->log(
            $status === 'success' ? 'info' : 'warning',
            'Authentication '.$status,
            ['ip' => $request->ip(), 'guard' => $event->guard]
        );
    }
}
