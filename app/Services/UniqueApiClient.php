<?php
/**
 * @file UniqueApiClient.php
 * @description Cliente HTTP delgado para el Web Service de Unique Reservaciones (docsIA/05-api-unique-referencia.md). Maneja login por token (con reintento automático ante 401) y expone los endpoints de catálogo (locations, services, prices), de checkout (idiomas, hoteles, pickup) y de reservación (create, getByCupon).
 * @date 2026-08-06
 * @author Claude
 */

namespace App\Services;

use App\Models\ApiConexion;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class UniqueApiClient
{
    public function __construct(private readonly ApiConexion $conexion)
    {
    }

    /**
     * Inicia sesión contra Unique y cachea el auth_token en la conexión.
     * Si ya hay un token cacheado y no se fuerza, lo reutiliza.
     */
    public function login(bool $forzar = false): string
    {
        if (!$forzar && $this->conexion->auth_token) {
            return $this->conexion->auth_token;
        }

        $response = Http::timeout(15)->post($this->conexion->base_url . '/sessions', [
            'user' => $this->conexion->usuario,
            'password' => $this->conexion->password,
            'modo' => $this->conexion->modo === 'produccion' ? 'LIVE' : 'SANDBOX',
            'uniqid_empresa' => $this->conexion->uniqid_empresa,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException("Login de Unique falló: HTTP {$response->status()} - " . $response->body());
        }

        $token = $response->json('auth_token');
        if (!$token) {
            throw new RuntimeException('Unique no devolvió un auth_token válido en el login.');
        }

        $this->conexion->update(['auth_token' => $token]);

        return $token;
    }

    /**
     * Lista las locaciones/parques disponibles.
     */
    public function locations(): array
    {
        return $this->request('get', '/locations');
    }

    /**
     * Lista las actividades/tours de una locación.
     */
    public function services(string $locacion): array
    {
        return $this->request('post', '/services', ['locacion' => $locacion]);
    }

    /**
     * Consulta el precio de una actividad en una fecha dada. Se llama siempre con el Id
     * exacto del servicio (no por nombre) porque el campo "servicio" de la respuesta es
     * el nombre visible, no el Id, y no es un identificador confiable para hacer match.
     */
    public function prices(string $fecha, string $locacion, ?string $servicio = null): array
    {
        $payload = ['fecha' => $fecha, 'locacion' => $locacion];
        if ($servicio) {
            $payload['servicio'] = $servicio;
        }

        return $this->request('post', '/prices', $payload);
    }

    /**
     * Lista los idiomas disponibles para reservar un tour.
     */
    public function idiomas(): array
    {
        return $this->request('get', '/idiomas');
    }

    /**
     * Lista los hoteles (y sus lobbies) disponibles para pickup.
     */
    public function hoteles(): array
    {
        return $this->request('get', '/hoteles');
    }

    /**
     * Consulta la hora estimada de recogida para un hotel/servicio/horario dados.
     */
    public function pickup(string $locacion, string $servicio, string $hotel, string $horario, ?string $lobby = null): array
    {
        $payload = ['locacion' => $locacion, 'servicio' => $servicio, 'hotel' => $hotel, 'horario' => $horario];
        if ($lobby) {
            $payload['lobby'] = $lobby;
        }

        return $this->request('post', '/pickup', $payload);
    }

    /**
     * Crea la reserva real en Unique. Se llama cuando se confirma el pago de una reserva de
     * Attitour cuyo tour proviene de esta conexión (Fase 6). Devuelve la respuesta cruda de
     * Unique, que incluye "id" y "confirma" (folio de confirmación) si tuvo éxito.
     */
    public function create(array $payload): array
    {
        return $this->request('post', '/create', $payload);
    }

    /**
     * Busca una reserva ya creada en Unique. Confirmado con el equipo de Unique (2026-08-19):
     * a pesar del nombre del endpoint (/get/{cupon}) y del parámetro, NO se busca con el
     * "confirma"/folio que Unique devolvió en /create — se busca con la "referencia" que
     * Attitour envió (nuestro Reserva::ticket_codigo), que es el dato que nosotros controlamos
     * y garantizamos único. Buscar por "confirma" devuelve {"errors":"Booking does not exist"}
     * aunque la reserva sí exista.
     */
    public function getByCupon(string $referencia): array
    {
        return $this->request('get', '/get/' . $referencia);
    }

    /**
     * Ejecuta una llamada autenticada contra Unique, reintentando el login una sola vez
     * si la respuesta es 401 (token expirado o inválido).
     */
    private function request(string $method, string $path, array $payload = [], bool $esReintento = false): array
    {
        $token = $this->login();

        $peticion = Http::withHeaders(['Authorization' => $token])->timeout(20);

        $response = $method === 'get'
            ? $peticion->get($this->conexion->base_url . $path)
            : $peticion->post($this->conexion->base_url . $path, $payload);

        if ($response->status() === 401 && !$esReintento) {
            $this->login(forzar: true);

            return $this->request($method, $path, $payload, true);
        }

        if (!$response->successful()) {
            throw new RuntimeException("Error llamando a Unique [{$method} {$path}]: HTTP {$response->status()} - " . $response->body());
        }

        return $response->json() ?? [];
    }
}
