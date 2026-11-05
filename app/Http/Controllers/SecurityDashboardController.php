<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecurityDashboardController extends Controller
{
    public function index()
    {
        // Indicadores de las últimas 24 horas
        $since = now()->subHours(24);
        $stats = [];

        foreach (['success', 'failed', 'blocked'] as $status) {
            $stats[$status] = LoginAttempt::where('status', $status)
                ->where('created_at', '>=', $since)
                ->count();
        }

        // IPs con 3 o más fallos en los últimos 5 minutos (mismas reglas que el middleware)
        $suspiciousIps = LoginAttempt::select('ip_address', DB::raw('COUNT(*) as total'))
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->groupBy('ip_address')
            ->havingRaw('COUNT(*) >= 3')
            ->orderByDesc('total')
            ->get();

        // Cuentas atacadas: 3 o más fallos contra la misma cuenta en 15 minutos, desde cualquier IP.
        // Muchas IPs distintas contra una sola cuenta es la señal de un ataque distribuido.
        $targetedAccounts = LoginAttempt::select(
                'identifier_hash',
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT ip_address) as ips')
            )
            ->where('status', 'failed')
            ->whereNotNull('identifier_hash')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->groupBy('identifier_hash')
            ->havingRaw('COUNT(*) >= 3')
            ->orderByDesc('total')
            ->get();

        // Últimos eventos para revisar qué pasó
        $recent = LoginAttempt::latest('created_at')->latest('id')->limit(15)->get();

        return view('security-dashboard', compact('stats', 'suspiciousIps', 'targetedAccounts', 'recent'));
    }

    /**
     * Exportación de evidencias (mejora): descarga todos los intentos en CSV.
     * La propia descarga queda registrada en el log: quién exporta datos de auditoría también se audita.
     */
    public function export(Request $request): StreamedResponse
    {
        $filename = 'evidencias-login-'.now()->format('Y-m-d_His').'.csv';

        Log::channel('security')->notice('Security evidence exported', [
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
            'rows' => LoginAttempt::count(),
        ]);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');

            // BOM UTF-8 para que Excel muestre bien los acentos
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['id', 'created_at_utc', 'status', 'ip_address', 'identifier_hash', 'user_agent']);

            // lazy(): recorre la tabla de a bloques sin cargarla entera en memoria
            foreach (LoginAttempt::orderBy('id')->lazy() as $row) {
                fputcsv($out, [
                    $row->id,
                    $row->created_at->format('Y-m-d H:i:s'),
                    $row->status,
                    $row->ip_address,
                    $row->identifier_hash,
                    $row->user_agent,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
