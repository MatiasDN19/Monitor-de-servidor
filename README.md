# Monitor de Servidor con Laravel

Trabajo práctico de **Programación IV** (UTN FRRe, cohorte 2026).

Aplicación Laravel que lee datos del sistema operativo del servidor (nombre del equipo, sistema operativo, versión de PHP, disco, memoria, CPU, tiempo encendido y temperatura) y los muestra de dos formas:

- **Vista web** en `/servidor`, que se actualiza sola cada 5 segundos (polling).
- **API JSON** en `/api/metrics`.

## Entorno usado

| | |
|---|---|
| Sistema operativo | Windows 11 Pro |
| PHP | 8.3 |
| Laravel | 13 |
| Base de datos | SQLite |

## Cómo correrlo

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Después abrir:

- http://127.0.0.1:8000/servidor
- http://127.0.0.1:8000/api/metrics

## Estructura

| Parte | Archivo | Responsabilidad |
|---|---|---|
| Service | `app/Services/ServerMetrics.php` | Lee los datos del sistema operativo (PHP + PowerShell/WMI) |
| Controlador | `app/Http/Controllers/ServerController.php` | Devuelve la vista o el JSON |
| Vista | `resources/views/servidor.blade.php` | Muestra las tarjetas y hace el polling |
| Rutas | `routes/web.php`, `routes/api.php` | `/servidor` y `/api/metrics` (con `throttle:60,1`) |

## Capturas

<!-- Agregar las capturas de /servidor y /api/metrics -->

## Respuestas

### 1. ¿Por qué la temperatura suele aparecer como "No disponible" en Windows?

La clase `MSAcpi_ThermalZoneTemperature` del namespace `root/wmi` necesita permisos de administrador. Si la terminal donde corre `php artisan serve` no se abrió como administrador, la consulta falla, entra al `catch` y la temperatura queda en `null`. Además, aunque se ejecute como administrador, muchas placas madre no informan su temperatura por ACPI, o informan la de una "zona térmica" que no es la de la CPU.

<!-- Completar con lo que pasó al probar como administrador -->

### 2. ¿Por qué el script de PowerShell nunca debe incluir datos que vengan del usuario?

Porque `shell_exec()` ejecuta comandos en el sistema operativo con los permisos del proceso de PHP. Si se armara el script con datos de un formulario o de la URL, alguien podría mandar un texto con comandos propios (por ejemplo, cortar el comando con `;` y agregar otro) y ejecutar cualquier cosa en el servidor: borrar archivos, leer credenciales, instalar programas. Esto se llama **inyección de comandos**. Por eso el script es fijo y no recibe ningún dato externo.

### 3. ¿Qué riesgo tiene dejar `/api/metrics` sin autenticación en un servidor real?

Cualquiera puede ver información de la infraestructura: nombre del equipo, sistema operativo, versión de PHP y uso de recursos. Esos datos le sirven a un atacante para buscar vulnerabilidades conocidas de esas versiones o para saber cuándo el servidor está más cargado. Además, como cada pedido abre PowerShell y es costoso, alguien podría llamarla muchas veces seguidas para sobrecargar el servidor (el `throttle:60,1` limita esto, pero no lo elimina). En producción habría que protegerla, por ejemplo con `auth:sanctum`.

### 4. Con polling cada 5 segundos, ¿cuántos pedidos por minuto recibe el servidor si hay 50 personas mirando?

Cada persona hace 60 / 5 = **12 pedidos por minuto**. Con 50 personas: 50 × 12 = **600 pedidos por minuto**, aunque los datos no hayan cambiado. Y cada pedido abre un proceso de PowerShell.

### 5. Si una app solo necesita la temperatura, ¿qué datos recibe de más al llamar a `/api/metrics`?

Recibe todo el resto: `hostname`, `os`, `php`, `disk` (con `total_gb`, `free_gb` y `used_pct`), `memory` (con `total_mb`, `available_mb` y `used_pct`), `cpu_pct`, `uptime_s` y `at`. Esto se llama **over-fetching**, y es el problema que resuelve GraphQL: el cliente pide solo los campos que necesita.
