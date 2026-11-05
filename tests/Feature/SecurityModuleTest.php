<?php

namespace Tests\Feature;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del módulo de seguridad: reglas por IP (enunciado) y las dos mejoras
 * de la actividad integradora (detección por cuenta y exportación de evidencias).
 */
class SecurityModuleTest extends TestCase
{
    use RefreshDatabase;

    /** Intenta loguearse desde una IP concreta */
    private function attempt(string $email, string $password, string $ip = '10.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post('/login', ['email' => $email, 'password' => $password]);
    }

    // ---------- Reglas por IP (enunciado del TP) ----------

    public function test_login_correcto_registra_success(): void
    {
        $user = User::factory()->create();

        $this->attempt($user->email, 'password')->assertRedirect('/dashboard');

        $this->assertDatabaseHas('login_attempts', ['status' => 'success', 'ip_address' => '10.0.0.1']);
    }

    public function test_cinco_fallos_desde_una_ip_bloquean_el_intento_siguiente(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 5; $i++) {
            $this->attempt($user->email, 'incorrecta')->assertRedirect();
        }

        $this->attempt($user->email, 'incorrecta')->assertStatus(429);

        // Con la IP bloqueada, ni la contraseña correcta pasa
        $this->attempt($user->email, 'password')->assertStatus(429);
        $this->assertDatabaseHas('login_attempts', ['status' => 'blocked', 'ip_address' => '10.0.0.1']);
    }

    // ---------- Mejora 1: detección por cuenta ----------

    public function test_ataque_distribuido_bloquea_la_cuenta_aunque_cada_ip_falle_una_sola_vez(): void
    {
        $user = User::factory()->create();

        // 5 IPs distintas, un solo fallo cada una: por IP nunca se llega al umbral
        foreach (['10.0.0.1', '10.0.0.2', '10.0.0.3', '10.0.0.4', '10.0.0.5'] as $ip) {
            $this->attempt($user->email, 'incorrecta', $ip)->assertRedirect();
        }

        // Una sexta IP, nueva, es rechazada porque la cuenta está bloqueada
        $this->attempt($user->email, 'incorrecta', '10.0.0.6')->assertStatus(429);

        // Y desde otra IP más, aun con la contraseña correcta, la cuenta sigue bloqueada
        $this->attempt($user->email, 'password', '10.0.0.7')->assertStatus(429);

        $this->assertDatabaseHas('login_attempts', [
            'status' => 'blocked',
            'ip_address' => '10.0.0.7',
            'identifier_hash' => hash('sha256', $user->email),
        ]);
        $this->assertGuest();
    }

    public function test_tres_fallos_contra_una_cuenta_alertan_pero_no_bloquean(): void
    {
        $user = User::factory()->create();

        foreach (['10.0.0.1', '10.0.0.2', '10.0.0.3'] as $ip) {
            $this->attempt($user->email, 'incorrecta', $ip);
        }

        // Con 3 fallos todavía puede entrar con la contraseña correcta
        $this->attempt($user->email, 'password', '10.0.0.4')->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_el_bloqueo_de_una_cuenta_no_afecta_a_otras_cuentas(): void
    {
        $victima = User::factory()->create();
        $otro = User::factory()->create();

        foreach (['10.0.0.1', '10.0.0.2', '10.0.0.3', '10.0.0.4', '10.0.0.5'] as $ip) {
            $this->attempt($victima->email, 'incorrecta', $ip);
        }
        $this->attempt($victima->email, 'incorrecta', '10.0.0.6')->assertStatus(429);

        // Otra cuenta, desde una IP limpia, entra normalmente
        $this->attempt($otro->email, 'password', '10.0.0.9')->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($otro);
    }

    public function test_el_panel_muestra_las_cuentas_atacadas(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $victima = User::factory()->create();

        foreach (['10.0.0.1', '10.0.0.2', '10.0.0.3'] as $ip) {
            $this->attempt($victima->email, 'incorrecta', $ip);
        }

        $this->actingAs($admin)
            ->get('/security-dashboard')
            ->assertOk()
            ->assertSee('Cuentas atacadas')
            ->assertSee(substr(hash('sha256', $victima->email), 0, 12))
            ->assertSee('distribuido');
    }

    // ---------- Mejora 2: exportación de evidencias ----------

    public function test_un_invitado_no_puede_exportar(): void
    {
        $this->get('/security-dashboard/export')->assertRedirect('/login');
    }

    public function test_un_usuario_comun_no_puede_exportar(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/security-dashboard/export')
            ->assertForbidden();
    }

    public function test_un_admin_descarga_el_csv_con_los_intentos(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        LoginAttempt::create(['ip_address' => '10.0.0.1', 'status' => 'failed', 'identifier_hash' => 'abc', 'created_at' => now()]);
        LoginAttempt::create(['ip_address' => '10.0.0.2', 'status' => 'blocked', 'created_at' => now()]);

        $response = $this->actingAs($admin)->get('/security-dashboard/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=evidencias-login-', $response->headers->get('content-disposition'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('id,created_at_utc,status,ip_address,identifier_hash,user_agent', $csv);
        // Columnas: id, created_at_utc, status, ip_address, ...
        $this->assertStringContainsString('failed,10.0.0.1,abc', $csv);
        $this->assertStringContainsString('blocked,10.0.0.2', $csv);
    }
}
