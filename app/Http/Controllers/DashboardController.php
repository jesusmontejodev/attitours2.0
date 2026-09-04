<?php
/**
 * @file DashboardController.php
 * @description Controlador para gestionar el panel de administración (Admin) y el panel de proveedor (PT).
 *              Incluye: métricas, reservas, gestión de tours/proveedores/usuarios y disponibilidad.
 * @date 2026-06-29
 * @author Antigravity
 */

namespace App\Http\Controllers;

use App\Models\ApiConexion;
use App\Models\Proveedor;
use App\Models\Reserva;
use App\Models\ReservaTour;
use App\Models\Tour;
use App\Models\TourApiNotificacion;
use App\Models\TourCambioPrecioApi;
use App\Models\TourFecha;
use App\Models\TourDisponibilidadSync;
use App\Models\TourImportado;
use App\Models\User;
use App\Services\TourAvailabilitySyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    /**
     * Muestra el panel correspondiente al rol del usuario.
     *
     * @return View|RedirectResponse
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Los clientes tienen su propio dashboard en /mi-cuenta
        if ($user->isCliente()) {
            return redirect()->route('cliente.dashboard');
        }

        if (!$user->isAdmin() && !$user->isProveedor()) {
            return redirect()->route('home')->with('error', __('Acceso no autorizado.'));
        }

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        } else {
            return $this->proveedorDashboard($user->proveedor_id);
        }
    }

    /**
     * Procesa y muestra los datos del Dashboard del Administrador.
     *
     * @return View
     */
    private function adminDashboard(): View
    {
        // Métricas globales
        $totalSales = Reserva::where('estado', 'Pagada')->sum('precio_total_usd');
        $totalBookings = Reserva::where('estado', 'Pagada')->count();
        $totalCommissions = Reserva::where('estado', 'Pagada')->sum('comision_total_usd');
        $netEarnings = $totalSales - $totalCommissions; // Lo que les corresponde a los proveedores

        // Historial completo de reservas
        $reservas = Reserva::with(['detalles.tour.proveedor', 'detalles.tour.apiConexion', 'detalles.apiNotificacion'])
            ->orderBy('fecha_reserva', 'desc')
            ->get();

        // Datos administrativos adicionales
        $proveedores = Proveedor::withCount('tours')->get();
        $tours = Tour::with(['proveedor', 'fechas', 'apiConexion'])->get();
        $usuarios = User::with(['proveedor'])->get();
        $apiConexiones = ApiConexion::with('proveedor')->withCount('tours')->latest()->get();
        $toursImportadosPendientes = TourImportado::where('estado', 'pendiente')
            ->with('apiConexion')
            ->latest('fecha_importado')
            ->get();
        $cambiosPrecioPendientes = TourCambioPrecioApi::where('estado', 'pendiente')
            ->with(['tour', 'apiConexion'])
            ->latest('detectado_at')
            ->get();
        $notificacionesApi = TourApiNotificacion::with(['tour', 'reserva', 'apiConexion'])
            ->latest()
            ->limit(100)
            ->get();
        $sincronizacionesDisponibilidad = TourDisponibilidadSync::with(['tour', 'apiConexion'])
            ->latest()
            ->limit(100)
            ->get();

        return view('dashboard.index', compact(
            'totalSales',
            'totalBookings',
            'totalCommissions',
            'netEarnings',
            'reservas',
            'proveedores',
            'tours',
            'usuarios',
            'apiConexiones',
            'toursImportadosPendientes',
            'cambiosPrecioPendientes',
            'notificacionesApi',
            'sincronizacionesDisponibilidad'
        ));
    }

    /**
     * Procesa y muestra los datos del Dashboard del Proveedor.
     *
     * @param int $proveedorId
     * @return View
     */
    private function proveedorDashboard(int $proveedorId): View
    {
        // Obtener tours del proveedor
        $tourIds = Tour::where('proveedor_id', $proveedorId)->pluck('id');

        // Buscar detalles de reservas asociadas a estos tours
        $reservaDetalles = ReservaTour::with(['reserva', 'tour'])
            ->whereIn('tour_id', $tourIds)
            ->whereHas('reserva', function ($q) {
                $q->where('estado', 'Pagada');
            })
            ->get();

        // Calcular métricas
        $totalSales = 0;
        $totalCommissions = 0;
        $reservaIdsUnicas = [];

        foreach ($reservaDetalles as $detalle) {
            $subtotal = $detalle->cantidad_personas * $detalle->precio_unitario_usd;
            $totalSales += $subtotal;
            $totalCommissions += $detalle->comision_usd;
            $reservaIdsUnicas[$detalle->reserva_id] = true;
        }

        $totalBookings = count($reservaIdsUnicas);
        $netEarnings = $totalSales - $totalCommissions; // La ganancia para el proveedor

        // Cargar las fechas programadas de sus tours
        $fechasDisponibles = TourFecha::with(['tour'])
            ->whereIn('tour_id', $tourIds)
            ->orderBy('fecha', 'asc')
            ->get();

        // Tours de este proveedor para el formulario de agregar fecha
        $tours = Tour::where('proveedor_id', $proveedorId)->get();

        return view('dashboard.index', compact(
            'totalSales',
            'totalBookings',
            'totalCommissions',
            'netEarnings',
            'reservaDetalles',
            'fechasDisponibles',
            'tours'
        ));
    }

    /**
     * Guarda un nuevo proveedor (Acción de Administrador).
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeProveedor(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->route('home')->with('error', __('Acceso no autorizado.'));
        }

        $validated = $request->validate([
            'nombre_empresa'          => 'required|string|max:150',
            'descripcion'             => 'required|string',
            'rfc'                     => 'nullable|string|max:20',
            'correo'                  => 'required|email|max:100|unique:users,email|unique:proveedores,correo',
            'representante_nombre'    => 'required|string|max:100',
            'representante_telefono'  => 'required|string|max:30',
            'contacto_visible_cliente'=> 'nullable|string|max:150',
            'comision_porcentaje'     => 'required|integer|min:0|max:100',
            'password'                => 'required|string|min:6',
            'foto_file'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:15360',
        ]);

        $fotoUrl = null;
        if ($request->hasFile('foto_file')) {
            $fotoUrl = \App\Services\ImageOptimizerService::uploadAndOptimize($request->file('foto_file'), 'proveedores');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $fotoUrl) {
                $proveedor = Proveedor::create([
                    'nombre_empresa'          => $validated['nombre_empresa'],
                    'descripcion'             => $validated['descripcion'],
                    'rfc'                     => $validated['rfc'] ?? null,
                    'correo'                  => $validated['correo'],
                    'representante_nombre'    => $validated['representante_nombre'],
                    'representante_telefono'  => $validated['representante_telefono'],
                    'contacto_visible_cliente'=> $validated['contacto_visible_cliente'] ?? null,
                    'comision_porcentaje'     => $validated['comision_porcentaje'],
                    'foto_url'                => $fotoUrl,
                ]);

                User::create([
                    'name'         => $validated['representante_nombre'],
                    'email'        => $validated['correo'],
                    'password'     => Hash::make($validated['password']),
                    'tipo'         => 'PT',
                    'telefono'     => $validated['representante_telefono'],
                    'proveedor_id' => $proveedor->id,
                ]);
            });
        } catch (\Throwable $e) {
            return redirect()->route('dashboard')->with('error', 'Error al crear el proveedor: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('dashboard')->with('success', __('Proveedor y usuario de acceso creados exitosamente.'))->with('active_tab', 'proveedores');
    }

    /**
     * Actualiza la información de un proveedor existente (Acción de Administrador).
     *
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function updateProveedor(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->route('home')->with('error', __('Acceso no autorizado.'));
        }

        $proveedor = Proveedor::findOrFail($id);

        $validated = $request->validate([
            'nombre_empresa'         => 'required|string|max:150',
            'descripcion'            => 'required|string',
            'rfc'                    => 'nullable|string|max:20',
            'correo'                 => 'required|email|max:100|unique:proveedores,correo,' . $id,
            'representante_nombre'   => 'required|string|max:100',
            'representante_telefono' => 'required|string|max:30',
            'contacto_visible_cliente' => 'nullable|string|max:150',
            'comision_porcentaje'    => 'required|integer|min:0|max:100',
            'foto_file'              => 'nullable|image|mimes:jpeg,png,jpg,webp|max:15360',
        ]);

        $fotoUrl = $proveedor->foto_url;
        if ($request->hasFile('foto_file')) {
            $fotoUrl = \App\Services\ImageOptimizerService::uploadAndOptimize($request->file('foto_file'), 'proveedores');
        }

        try {
            $proveedor->update([
                'nombre_empresa'         => $validated['nombre_empresa'],
                'descripcion'            => $validated['descripcion'],
                'rfc'                    => $validated['rfc'] ?? null,
                'correo'                 => $validated['correo'],
                'representante_nombre'   => $validated['representante_nombre'],
                'representante_telefono' => $validated['representante_telefono'],
                'contacto_visible_cliente' => $validated['contacto_visible_cliente'] ?? null,
                'comision_porcentaje'    => $validated['comision_porcentaje'],
                'foto_url'               => $fotoUrl,
            ]);

            // Sincronizar el correo en el usuario asociado (si existe)
            $associatedUser = User::where('proveedor_id', $proveedor->id)->where('tipo', 'PT')->first();
            if ($associatedUser) {
                $updateData = [
                    'name'     => $validated['representante_nombre'],
                    'email'    => $validated['correo'],
                    'telefono' => $validated['representante_telefono'],
                ];
                // Solo actualizar email en users si no está duplicado
                $emailExists = User::where('email', $validated['correo'])->where('id', '!=', $associatedUser->id)->exists();
                if (!$emailExists) {
                    $associatedUser->update($updateData);
                } else {
                    $associatedUser->update(['name' => $validated['representante_nombre'], 'telefono' => $validated['representante_telefono']]);
                }
            }
        } catch (\Throwable $e) {
            return redirect()->route('dashboard')->with('error', 'Error al actualizar el proveedor: ' . $e->getMessage())->with('active_tab', 'proveedores');
        }

        return redirect()->route('dashboard')->with('success', __('Proveedor actualizado exitosamente.'))->with('active_tab', 'proveedores');
    }

    /**
     * Limpia una lista de tags separados por coma: recorta espacios, quita vacíos y duplicados
     * (sin distinguir mayúsculas/minúsculas) para que sirvan de forma confiable al buscar/clasificar tours.
     *
     * @param string|null $raw
     * @param array $default
     * @return array
     */
    private function parseTags(?string $raw, array $default = ['Aventura']): array
    {
        $tags = collect(explode(',', $raw ?? ''))
            ->map(fn($t) => trim($t))
            ->filter()
            ->unique(fn($t) => mb_strtolower($t))
            ->values()
            ->all();

        return $tags ?: $default;
    }

    /**
     * Guarda un nuevo tour (Acción de Administrador).
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeTour(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->route('home')->with('error', __('Acceso no autorizado.'));
        }

        // Detectar una imagen que PHP descartó antes de llegar a la validación de Laravel
        // (p. ej. supera upload_max_filesize/post_max_size del servidor) — de lo contrario el
        // tour se crea "exitosamente" con la imagen por defecto, sin explicar por qué.
        if ($request->hasFile('imagen_destacada_file') && !$request->file('imagen_destacada_file')->isValid()) {
            return back()->withInput()
                ->withErrors(['imagen_destacada_file' => $this->mensajeErrorSubida($request->file('imagen_destacada_file')->getError())]);
        }

        $request->validate([
            'titulo_es' => 'required|string|max:150',
            'titulo_en' => 'nullable|string|max:150',
            'titulo_zh' => 'nullable|string|max:150',
            'descripcion_corta_es' => 'required|string',
            'descripcion_corta_en' => 'nullable|string',
            'descripcion_corta_zh' => 'nullable|string',
            'descripcion_larga_es' => 'required|string',
            'descripcion_larga_en' => 'nullable|string',
            'descripcion_larga_zh' => 'nullable|string',
            'precio_base_usd' => 'required|numeric|min:1',
            'duracion_es' => 'required|string|max:50',
            'duracion_en' => 'nullable|string|max:50',
            'duracion_zh' => 'nullable|string|max:50',
            'ubicacion' => 'required|string|max:100',
            'punto_encuentro' => 'nullable|string',
            'punto_encuentro_lat' => 'nullable|numeric|between:-90,90',
            'punto_encuentro_lng' => 'nullable|numeric|between:-180,180',
            'pais' => 'required|string|max:100',
            'cupo_maximo' => 'nullable|integer|min:1',
            'proveedor_id' => 'required|exists:proveedores,id',
            'tags' => 'nullable|string',
            'horarios' => 'nullable|string',
            'imagen_destacada_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:15360',
            'imagen_destacada_url' => 'nullable|url',
            'itinerario' => 'nullable|string',
            'incluye' => 'nullable|string',
            'no_incluye' => 'nullable|string',
            'tipo_modalidad' => 'required|in:compartido,privado,ambos',
            'tarifas_privadas' => 'nullable|string',
            'tour_importado_id' => 'nullable|exists:tours_importados,id',
        ]);

        // Si viene de la bandeja de "Completar y Publicar", validar que siga pendiente
        // (evita publicar dos veces el mismo importado por una doble-carga del formulario).
        $importado = null;
        if ($request->filled('tour_importado_id')) {
            $importado = TourImportado::find($request->input('tour_importado_id'));
            if (!$importado || $importado->estado !== 'pendiente') {
                return redirect()->route('dashboard')
                    ->with('error', __('Ese tour importado ya no está pendiente de publicación (puede que ya se haya publicado o descartado).'))
                    ->with('active_tab', 'apis');
            }
        }

        // Generar ID único
        $slug = \Illuminate\Support\Str::slug($request->input('titulo_es'), '_');
        $id = 'tour_' . $slug;

        if (Tour::where('id', $id)->exists()) {
            $id .= '_' . rand(100, 999);
        }

        // Parsear tags y horarios
        $tags = $this->parseTags($request->input('tags'));
        $horarios = array_map('trim', explode(',', $request->input('horarios', '09:00')));

        // Procesar Imagen de Portada (Destacada): archivo subido > URL de la API externa > placeholder
        $imagenDefault = 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?auto=format&fit=crop&w=800&q=80';
        if ($request->hasFile('imagen_destacada_file')) {
            $imagenDefault = \App\Services\ImageOptimizerService::uploadAndOptimize($request->file('imagen_destacada_file'), 'tours');
        } elseif ($request->filled('imagen_destacada_url')) {
            $imagenDefault = $request->input('imagen_destacada_url');
        }

        // Inicializar Galería (las fotos adicionales se agregan después, una por una)
        $galeria = [$imagenDefault];
        $galeriaExperiencias = [];

        // Procesar incluye y no_incluye
        $incluye = $request->filled('incluye')
            ? array_map('trim', explode(',', $request->input('incluye')))
            : [];
        $noIncluye = $request->filled('no_incluye')
            ? array_map('trim', explode(',', $request->input('no_incluye')))
            : [];

        // Parsear tarifas privadas JSON
        $tarifasPrivadas = null;
        if ($request->filled('tarifas_privadas')) {
            $tarifasPrivadas = json_decode($request->input('tarifas_privadas'), true);
        }

        $datosTour = [
            'id' => $id,
            'proveedor_id' => $request->input('proveedor_id'),
            'titulo' => [
                'es' => $request->input('titulo_es'),
                'en' => $request->input('titulo_en', ''),
                'zh' => $request->input('titulo_zh', ''),
            ],
            'descripcion_corta' => [
                'es' => $request->input('descripcion_corta_es'),
                'en' => $request->input('descripcion_corta_en', ''),
                'zh' => $request->input('descripcion_corta_zh', ''),
            ],
            'descripcion_larga' => [
                'es' => $request->input('descripcion_larga_es'),
                'en' => $request->input('descripcion_larga_en', ''),
                'zh' => $request->input('descripcion_larga_zh', ''),
            ],
            'ubicacion' => $request->input('ubicacion'),
            'punto_encuentro' => $request->input('punto_encuentro'),
            'punto_encuentro_lat' => $request->filled('punto_encuentro_lat') ? (float)$request->input('punto_encuentro_lat') : null,
            'punto_encuentro_lng' => $request->filled('punto_encuentro_lng') ? (float)$request->input('punto_encuentro_lng') : null,
            'pais' => $request->input('pais'),
            'precio_base_usd' => (float)$request->input('precio_base_usd'),
            'duracion' => [
                'es' => $request->input('duracion_es'),
                'en' => $request->input('duracion_en', ''),
                'zh' => $request->input('duracion_zh', ''),
            ],
            'imagen_destacada' => $imagenDefault,
            'galeria' => $galeria,
            'galeria_experiencias' => $galeriaExperiencias,
            'cupo_maximo' => (int)$request->input('cupo_maximo', 20),
            'tags' => $tags,
            'horarios' => $horarios,
            'itinerario' => trim((string) $request->input('itinerario', '')),
            'incluye' => $incluye,
            'no_incluye' => $noIncluye,
            'tipo_modalidad' => $request->input('tipo_modalidad'),
            'tarifas_privadas' => $tarifasPrivadas
        ];

        // Si el tour proviene de una API externa, se marca su origen y se conserva la
        // referencia necesaria para poder notificar disponibilidad al reservar (fases siguientes).
        if ($importado) {
            $datosTour['origen'] = 'api_externa';
            $datosTour['api_conexion_id'] = $importado->api_conexion_id;
            $datosTour['api_tour_id_externo'] = $importado->payload_raw['Id'] ?? null;
            $datosTour['api_locacion_id'] = $importado->locacion_externa_id;
            $datosTour['precio_api_referencia_usd'] = $importado->precio_preview;
            $datosTour['precio_api_actualizado_at'] = $importado->precio_preview !== null ? now() : null;
        }

        Tour::create($datosTour);

        if ($importado) {
            $importado->update(['estado' => 'publicado', 'tour_id' => $id]);
        }

        return redirect()->route('dashboard')->with('success', __('Tour creado exitosamente.'))->with('active_tab', 'tours')->with('new_tour_id', $id);
    }

    /**
     * Determina si las fechas que se están habilitando/consultando deben ser de modalidad
     * privada (exclusiva, ver TourFecha::estaBloqueadoPorReservaPrivada) según el tipo de
     * modalidad del tour: los "Sólo Privado" siempre son privadas, los "Sólo Compartido" nunca,
     * y los "Mixtos" dependen de lo que el Admin elija explícitamente en el formulario.
     */
    private function resolverModalidadPrivada(Tour $tour, Request $request): bool
    {
        return match ($tour->tipo_modalidad) {
            'privado' => true,
            'ambos' => $request->boolean('es_privado', false),
            default => false,
        };
    }

    /**
     * Habilita fechas para un tour (Acción de Administrador o Proveedor).
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeTourFechas(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isProveedor())) {
            return redirect()->route('home')->with('error', __('Acceso no autorizado.'));
        }

        $request->validate([
            'tour_id' => 'required|exists:tours,id',
            'fecha_inicio' => 'required|date|after_or_equal:today',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'operacion' => 'required|in:habilitar,deshabilitar',
            'horarios' => 'nullable|string',
            'cupo_maximo' => 'nullable|required_if:operacion,habilitar|integer|min:1|max:100',
            'es_privado' => 'nullable|boolean',
        ]);

        $tourId = $request->input('tour_id');
        $tour = Tour::findOrFail($tourId);

        // Si es Proveedor, validar pertenencia
        if ($user->isProveedor() && $tour->proveedor_id !== $user->proveedor_id) {
            return redirect()->back()->with('error', __('El tour seleccionado no le pertenece.'));
        }

        $fechaInicio = \Carbon\Carbon::parse($request->input('fecha_inicio'));
        $fechaFin = \Carbon\Carbon::parse($request->input('fecha_fin'));
        $operacion = $request->input('operacion');
        $cupoMax = (int) $request->input('cupo_maximo', 20);
        $esPrivado = $this->resolverModalidadPrivada($tour, $request);

        // Parsear horarios
        if ($request->filled('horarios')) {
            $horarios = array_map('trim', explode(',', $request->input('horarios')));
        } else {
            $horarios = $tour->horarios ?: ['09:00'];
        }

        $current = $fechaInicio->copy();
        $count = 0;

        while ($current->lte($fechaFin)) {
            $fechaStr = $current->format('Y-m-d');
            foreach ($horarios as $hora) {
                if ($operacion === 'habilitar') {
                    TourFecha::updateOrCreate(
                        ['tour_id' => $tourId, 'fecha' => $fechaStr, 'horario' => $hora, 'es_privado' => $esPrivado],
                        ['cupo_maximo' => $cupoMax]
                    );
                    $count++;
                } else {
                    $deleted = TourFecha::where('tour_id', $tourId)
                        ->where('fecha', $fechaStr)
                        ->where('horario', $hora)
                        ->where('es_privado', $esPrivado)
                        ->where('cupo_reservado', 0)
                        ->delete();
                    if ($deleted > 0) {
                        $count++;
                    }
                }
            }
            $current->addDay();
        }

        $msg = $operacion === 'habilitar' 
            ? __('Se han habilitado :count salidas exitosamente.', ['count' => $count])
            : __('Se han deshabilitado :count salidas (sin reservas activas).', ['count' => $count]);

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Cambia el rol de un usuario y asocia sucursal/proveedor si aplica (Acción de Administrador).
     *
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function updateUserRole(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->route('home')->with('error', __('Acceso no autorizado.'));
        }

        $request->validate([
            'tipo' => 'required|in:Admin,PT,Cliente',
            'proveedor_id' => 'nullable|required_if:tipo,PT|exists:proveedores,id'
        ]);

        $targetUser = User::findOrFail($id);

        if ($targetUser->id === $user->id && $request->input('tipo') !== 'Admin') {
            return redirect()->back()->with('error', __('No puedes quitarte el rol de Administrador a ti mismo.'));
        }

        $targetUser->update([
            'tipo' => $request->input('tipo'),
            'proveedor_id' => $request->input('tipo') === 'PT' ? $request->input('proveedor_id') : null
        ]);

        return redirect()->back()->with('success', __('Rol y permisos de usuario actualizados exitosamente.'));
    }

    /**
     * Restablece la contraseña de un usuario directamente (Acción de Administrador).
     *
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function resetUserPassword(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->route('home')->with('error', __('Acceso no autorizado.'));
        }

        $request->validate([
            'new_password' => 'required|string|min:6'
        ]);

        $targetUser = User::findOrFail($id);
        $targetUser->update([
            'password' => Hash::make($request->input('new_password'))
        ]);

        return redirect()->back()->with('success', __('Contraseña restablecida exitosamente para :nombre.', ['nombre' => $targetUser->name]));
    }



    /**
     * Permite a los proveedores añadir o modificar la disponibilidad de fechas para sus tours.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateDates(Request $request): RedirectResponse
    {
        return $this->storeTourFechas($request);
    }

    /**
     * Obtiene la disponibilidad de un tour para un mes específico en formato JSON.
     *
     * @param int|string $id ID del tour
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTourFechasJson($id, Request $request)
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isProveedor())) {
            return response()->json(['error' => __('No autorizado.')], 403);
        }

        $tour = Tour::findOrFail($id);

        if ($user->isProveedor() && $tour->proveedor_id !== $user->proveedor_id) {
            return response()->json(['error' => __('No autorizado para este tour.')], 403);
        }

        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));

        $fechas = TourFecha::where('tour_id', $id)
            ->whereYear('fecha', $year)
            ->whereMonth('fecha', $month)
            ->get();

        // Agrupar por fecha
        $resultado = [];
        foreach ($fechas as $fecha) {
            $fechaStr = $fecha->fecha->format('Y-m-d');
            if (!isset($resultado[$fechaStr])) {
                $resultado[$fechaStr] = [];
            }
            $resultado[$fechaStr][] = [
                'id' => $fecha->id,
                'horario' => $fecha->horario,
                'cupo_maximo' => $fecha->cupo_maximo,
                'cupo_reservado' => $fecha->cupo_reservado,
                'cupo_disponible' => $fecha->cupo_disponible,
                'es_privado' => $fecha->es_privado,
            ];
        }

        return response()->json([
            'success' => true,
            'fechas' => $resultado
        ]);
    }

    /**
     * Dispara manualmente la sincronización del calendario de un tour contra su API externa
     * (Admin o Proveedor dueño del tour). Solo aplica a tours de origen api_externa con
     * "Sincronizar Calendarios" activo; el resultado (fechas actualizadas/deshabilitadas o error)
     * queda registrado en el historial de sincronizaciones.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function sincronizarDisponibilidadTour(string $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isProveedor())) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $tour = Tour::findOrFail($id);

        if ($user->isProveedor() && $tour->proveedor_id !== $user->proveedor_id) {
            return response()->json(['success' => false, 'message' => 'No autorizado para este tour.'], 403);
        }

        if (!$tour->esApiExterna() || !$tour->sync_calendario_activo) {
            return response()->json([
                'success' => false,
                'message' => 'Este tour no tiene la sincronización de calendario activa.'
            ], 422);
        }

        try {
            $sync = app(TourAvailabilitySyncService::class)->sincronizarCalendario($tour, 'manual');
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error al sincronizar: ' . $e->getMessage()], 500);
        }

        if ($sync->estado === 'fallido') {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo consultar el calendario del proveedor: ' . $sync->mensaje_error,
                'fechas' => $this->getFechasDelTour($id)
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => "Calendario sincronizado: {$sync->fechas_actualizadas} fecha(s) actualizada(s), {$sync->fechas_deshabilitadas} deshabilitada(s). Esta sincronización depende de que el proveedor mantenga su propio calendario actualizado — Attitour solo refleja lo que ellos reportan.",
            'fechas' => $this->getFechasDelTour($id)
        ]);
    }

    /**
     * Helper para obtener todas las fechas próximas de un tour, usado para refrescar el
     * calendario del Admin/Proveedor tras una sincronización manual.
     */
    private function getFechasDelTour(string $tourId)
    {
        return TourFecha::where('tour_id', $tourId)
            ->where('fecha', '>=', now()->format('Y-m-d'))
            ->orderBy('fecha')
            ->get()
            ->map(function ($f) {
                return [
                    'id' => $f->id,
                    'fecha' => $f->fecha->format('Y-m-d'),
                    'horario' => $f->horario,
                    'cupo_maximo' => $f->cupo_maximo,
                    'cupo_reservado' => $f->cupo_reservado,
                    'cupo_disponible' => $f->cupo_disponible,
                ];
            });
    }

    /**
     * Actualiza de forma atómica la disponibilidad de un día específico para un tour.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSingleDayAvailability(Request $request)
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isProveedor())) {
            return response()->json(['error' => __('No autorizado.')], 403);
        }

        $request->validate([
            'tour_id' => 'required|exists:tours,id',
            'fecha' => 'required|date',
            'habilitado' => 'required|boolean',
            'es_privado' => 'nullable|boolean',
            'salidas' => 'nullable|array',
            'salidas.*.horario' => 'nullable|required_if:habilitado,true|string|regex:/^\d{2}:\d{2}$/',
            'salidas.*.cupo_maximo' => 'nullable|required_if:habilitado,true|integer|min:1'
        ]);

        $tourId = $request->input('tour_id');
        $fecha = $request->input('fecha');
        $habilitado = $request->input('habilitado');
        $salidas = $request->input('salidas', []);

        $tour = Tour::findOrFail($tourId);

        if ($user->isProveedor() && $tour->proveedor_id !== $user->proveedor_id) {
            return response()->json(['error' => __('No autorizado para este tour.')], 403);
        }

        $esPrivado = $this->resolverModalidadPrivada($tour, $request);

        // Solo se opera sobre las salidas de la modalidad que se está editando (compartida o
        // privada) — así en un tour "Mixto" no se borran/pisan las salidas de la otra modalidad
        // para ese mismo día.
        $existentes = TourFecha::where('tour_id', $tourId)
            ->where('fecha', $fecha)
            ->where('es_privado', $esPrivado)
            ->get();

        $advertencias = [];

        if (!$habilitado) {
            // Deshabilitar esta modalidad para el día
            // Verificar si hay reservas activas en algún horario de este día
            $conReservas = $existentes->where('cupo_reservado', '>', 0);
            if ($conReservas->isNotEmpty()) {
                // Borrar solo las que no tienen reservas, conservar las que sí
                $sinReservas = $existentes->where('cupo_reservado', 0);
                foreach ($sinReservas as $fechaFecha) {
                    $fechaFecha->delete();
                }

                $horariosConservados = $conReservas->pluck('horario')->toArray();
                return response()->json([
                    'success' => true,
                    'warning' => __('No se pudieron deshabilitar algunas salidas porque tienen reservas activas: :horarios', [
                        'horarios' => implode(', ', $horariosConservados)
                    ]),
                    'fechas' => $this->getFechasDeDia($tourId, $fecha)
                ]);
            } else {
                // Borrar todas las de esta modalidad
                TourFecha::where('tour_id', $tourId)->where('fecha', $fecha)->where('es_privado', $esPrivado)->delete();
                return response()->json([
                    'success' => true,
                    'message' => __('Día deshabilitado por completo.'),
                    'fechas' => $this->getFechasDeDia($tourId, $fecha)
                ]);
            }
        }

        // Si está habilitado
        $horariosEnviados = collect($salidas)->pluck('horario')->toArray();

        // 1. Eliminar horarios que ya no se enviaron, si no tienen reservas
        foreach ($existentes as $existente) {
            if (!in_array($existente->horario, $horariosEnviados)) {
                if ($existente->cupo_reservado > 0) {
                    $advertencias[] = __('El horario :horario no se pudo eliminar porque tiene reservas activas.', ['horario' => $existente->horario]);
                } else {
                    $existente->delete();
                }
            }
        }

        // 2. Crear o actualizar los horarios enviados
        foreach ($salidas as $salida) {
            $horario = $salida['horario'];
            $cupoMaximo = (int) $salida['cupo_maximo'];

            // Buscar si ya existe para ver si tiene reservas
            $existente = $existentes->firstWhere('horario', $horario);
            if ($existente && $existente->cupo_reservado > $cupoMaximo) {
                // Si el nuevo cupo máximo es menor que los reservados, no permitir
                $advertencias[] = __('No se puede bajar el cupo máximo de :horario a :cupo porque ya hay :reservas reservas.', [
                    'horario' => $horario,
                    'cupo' => $cupoMaximo,
                    'reservas' => $existente->cupo_reservado
                ]);
                // Ajustar al mínimo posible (el cupo reservado actual) o mantener
                $cupoMaximo = $existente->cupo_reservado;
            }

            TourFecha::updateOrCreate(
                ['tour_id' => $tourId, 'fecha' => $fecha, 'horario' => $horario, 'es_privado' => $esPrivado],
                ['cupo_maximo' => $cupoMaximo]
            );
        }

        return response()->json([
            'success' => true,
            'message' => __('Disponibilidad actualizada correctamente.'),
            'warnings' => $advertencias,
            'fechas' => $this->getFechasDeDia($tourId, $fecha)
        ]);
    }

    /**
     * Actualiza en lote la disponibilidad de un rango de fechas para un tour.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBatchAvailability(Request $request)
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isProveedor())) {
            return response()->json(['error' => __('No autorizado.')], 403);
        }

        $request->validate([
            'tour_id' => 'required|exists:tours,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'dias_semana' => 'required|array',
            'dias_semana.*' => 'integer|between:0,6',
            'accion' => 'required|string|in:habilitar,deshabilitar',
            'es_privado' => 'nullable|boolean',
            'salidas' => 'nullable|array',
            'salidas.*.horario' => 'nullable|required_if:accion,habilitar|string|regex:/^\d{2}:\d{2}$/',
            'salidas.*.cupo_maximo' => 'nullable|required_if:accion,habilitar|integer|min:1'
        ]);

        $tourId = $request->input('tour_id');
        $fechaInicioStr = $request->input('fecha_inicio');
        $fechaFinStr = $request->input('fecha_fin');
        $diasSemana = $request->input('dias_semana');
        $accion = $request->input('accion');
        $salidas = $request->input('salidas', []);

        $tour = Tour::findOrFail($tourId);

        if ($user->isProveedor() && $tour->proveedor_id !== $user->proveedor_id) {
            return response()->json(['error' => __('No autorizado para este tour.')], 403);
        }

        $esPrivado = $this->resolverModalidadPrivada($tour, $request);

        $inicio = new \DateTime($fechaInicioStr);
        $fin = new \DateTime($fechaFinStr);
        $intervalo = new \DateInterval('P1D');
        $periodo = new \DatePeriod($inicio, $intervalo, $fin->modify('+1 day'));

        $fechasAProcesar = [];
        foreach ($periodo as $dt) {
            $w = (int) $dt->format('w'); // 0 (Domingo) a 6 (Sábado)
            if (in_array($w, $diasSemana)) {
                $fechasAProcesar[] = $dt->format('Y-m-d');
            }
        }

        if (empty($fechasAProcesar)) {
            return response()->json([
                'success' => false,
                'message' => __('No se encontraron fechas que coincidan con los días de la semana seleccionados en el rango indicado.')
            ], 422);
        }

        $advertencias = [];
        $contadorFechasModificadas = 0;

        if ($accion === 'deshabilitar') {
            foreach ($fechasAProcesar as $fecha) {
                $existentes = TourFecha::where('tour_id', $tourId)
                    ->where('fecha', $fecha)
                    ->where('es_privado', $esPrivado)
                    ->get();

                if ($existentes->isEmpty()) {
                    continue;
                }

                $conReservas = $existentes->where('cupo_reservado', '>', 0);
                if ($conReservas->isNotEmpty()) {
                    // Borrar las que no tienen reservas
                    $sinReservas = $existentes->where('cupo_reservado', 0);
                    foreach ($sinReservas as $f) {
                        $f->delete();
                    }
                    $horariosConservados = $conReservas->pluck('horario')->toArray();
                    $advertencias[] = __(':fecha: No se pudieron deshabilitar salidas a las :horarios porque tienen reservas.', [
                        'fecha' => $fecha,
                        'horarios' => implode(', ', $horariosConservados)
                    ]);
                } else {
                    TourFecha::where('tour_id', $tourId)->where('fecha', $fecha)->where('es_privado', $esPrivado)->delete();
                }
                $contadorFechasModificadas++;
            }

            return response()->json([
                'success' => true,
                'message' => __('Proceso completado. Se deshabilitaron las salidas en el rango seleccionado.'),
                'warnings' => $advertencias,
                'total_dias' => $contadorFechasModificadas
            ]);
        }

        // Si es 'habilitar'
        // Deduplicar salidas por horario (si vienen dos filas con el mismo horario, se queda la última)
        $salidasUnicas = [];
        foreach ($salidas as $salida) {
            $salidasUnicas[$salida['horario']] = $salida;
        }
        $salidas = array_values($salidasUnicas);

        $horariosEnviados = collect($salidas)->pluck('horario')->toArray();

        foreach ($fechasAProcesar as $fecha) {
            $existentes = TourFecha::where('tour_id', $tourId)
                ->where('fecha', $fecha)
                ->where('es_privado', $esPrivado)
                ->get();

            // 1. Eliminar horarios no enviados si no tienen reservas
            foreach ($existentes as $existente) {
                if (!in_array($existente->horario, $horariosEnviados)) {
                    if ($existente->cupo_reservado > 0) {
                        $advertencias[] = __(':fecha: El horario :horario no se pudo eliminar porque tiene reservas.', [
                            'fecha' => $fecha,
                            'horario' => $existente->horario
                        ]);
                    } else {
                        $existente->delete();
                    }
                }
            }

            // 2. Crear o actualizar horarios enviados
            foreach ($salidas as $salida) {
                $horario = $salida['horario'];
                $cupoMaximo = (int) $salida['cupo_maximo'];

                $existente = $existentes->firstWhere('horario', $horario);
                if ($existente && $existente->cupo_reservado > $cupoMaximo) {
                    $advertencias[] = __(':fecha: No se pudo reducir el cupo del horario :horario a :cupo porque ya hay :reservas reservas.', [
                        'fecha' => $fecha,
                        'horario' => $horario,
                        'cupo' => $cupoMaximo,
                        'reservas' => $existente->cupo_reservado
                    ]);
                    $cupoMaximo = $existente->cupo_reservado;
                }

                TourFecha::updateOrCreate(
                    ['tour_id' => $tourId, 'fecha' => $fecha, 'horario' => $horario, 'es_privado' => $esPrivado],
                    ['cupo_maximo' => $cupoMaximo]
                );
            }
            $contadorFechasModificadas++;
        }

        return response()->json([
            'success' => true,
            'message' => __('Proceso completado. Se configuraron :total fechas de salida con éxito.', ['total' => $contadorFechasModificadas]),
            'warnings' => $advertencias,
            'total_dias' => $contadorFechasModificadas
        ]);
    }

    /**
     * Helper para obtener las fechas de un día específico.
     */
    private function getFechasDeDia($tourId, $fecha)
    {
        return TourFecha::where('tour_id', $tourId)
            ->where('fecha', $fecha)
            ->get()
            ->map(function ($f) {
                return [
                    'id' => $f->id,
                    'horario' => $f->horario,
                    'cupo_maximo' => $f->cupo_maximo,
                    'cupo_reservado' => $f->cupo_reservado,
                    'cupo_disponible' => $f->cupo_disponible,
                    'es_privado' => $f->es_privado,
                ];
            });
    }

    public function updateTour(Request $request, string $id): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->route('home')->with('error', __('Acceso no autorizado.'));
        }

        $tour = Tour::findOrFail($id);

        // Detectar una imagen que PHP descartó antes de llegar a la validación de Laravel
        // (p. ej. supera upload_max_filesize/post_max_size del servidor) — de lo contrario el
        // tour se guarda "exitosamente" con la imagen anterior, sin explicar por qué.
        if ($request->hasFile('imagen_destacada_file') && !$request->file('imagen_destacada_file')->isValid()) {
            return back()->withInput()
                ->withErrors(['imagen_destacada_file' => $this->mensajeErrorSubida($request->file('imagen_destacada_file')->getError())]);
        }

        $request->validate([
            'titulo_es' => 'required|string|max:150',
            'titulo_en' => 'nullable|string|max:150',
            'titulo_zh' => 'nullable|string|max:150',
            'descripcion_corta_es' => 'required|string',
            'descripcion_corta_en' => 'nullable|string',
            'descripcion_corta_zh' => 'nullable|string',
            'descripcion_larga_es' => 'required|string',
            'descripcion_larga_en' => 'nullable|string',
            'descripcion_larga_zh' => 'nullable|string',
            'precio_base_usd' => 'required|numeric|min:1',
            'duracion_es' => 'required|string|max:50',
            'duracion_en' => 'nullable|string|max:50',
            'duracion_zh' => 'nullable|string|max:50',
            'ubicacion' => 'required|string|max:100',
            'punto_encuentro' => 'nullable|string',
            'punto_encuentro_lat' => 'nullable|numeric|between:-90,90',
            'punto_encuentro_lng' => 'nullable|numeric|between:-180,180',
            'pais' => 'required|string|max:100',
            'cupo_maximo' => 'nullable|integer|min:1',
            'proveedor_id' => 'required|exists:proveedores,id',
            'tags' => 'nullable|string',
            'imagen_destacada_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:15360',
            'itinerario' => 'nullable|string',
            'incluye' => 'nullable|string',
            'no_incluye' => 'nullable|string',
            'tipo_modalidad' => 'required|in:compartido,privado,ambos',
            'tarifas_privadas' => 'nullable|string',
            'sync_calendario_activo' => 'nullable|boolean',
            'incluye_pickup' => 'nullable|boolean',
        ]);

        // Parsear tags
        $tags = $this->parseTags($request->input('tags'));

        // Procesar Imagen de Portada (Destacada)
        $imagenDefault = $tour->imagen_destacada ?: 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?auto=format&fit=crop&w=800&q=80';
        if ($request->hasFile('imagen_destacada_file')) {
            $imagenDefault = \App\Services\ImageOptimizerService::uploadAndOptimize($request->file('imagen_destacada_file'), 'tours');
        }

        // Nota: la galería adicional y la galería de experiencias ya no se tocan aquí,
        // se gestionan una foto a la vez desde el modal de Galería (addGalleryImage/removeGalleryImage).

        // Procesar incluye y no_incluye
        $incluye = $request->filled('incluye')
            ? array_map('trim', explode(',', $request->input('incluye')))
            : [];
        $noIncluye = $request->filled('no_incluye')
            ? array_map('trim', explode(',', $request->input('no_incluye')))
            : [];

        // Parsear tarifas privadas JSON
        $tarifasPrivadas = null;
        if ($request->filled('tarifas_privadas')) {
            $tarifasPrivadas = json_decode($request->input('tarifas_privadas'), true);
        }

        $tour->update([
            'proveedor_id' => $request->input('proveedor_id'),
            'titulo' => [
                'es' => $request->input('titulo_es'),
                'en' => $request->input('titulo_en', ''),
                'zh' => $request->input('titulo_zh', ''),
            ],
            'descripcion_corta' => [
                'es' => $request->input('descripcion_corta_es'),
                'en' => $request->input('descripcion_corta_en', ''),
                'zh' => $request->input('descripcion_corta_zh', ''),
            ],
            'descripcion_larga' => [
                'es' => $request->input('descripcion_larga_es'),
                'en' => $request->input('descripcion_larga_en', ''),
                'zh' => $request->input('descripcion_larga_zh', ''),
            ],
            'ubicacion' => $request->input('ubicacion'),
            'punto_encuentro' => $request->input('punto_encuentro'),
            'punto_encuentro_lat' => $request->filled('punto_encuentro_lat') ? (float)$request->input('punto_encuentro_lat') : null,
            'punto_encuentro_lng' => $request->filled('punto_encuentro_lng') ? (float)$request->input('punto_encuentro_lng') : null,
            'pais' => $request->input('pais'),
            'precio_base_usd' => (float)$request->input('precio_base_usd'),
            'cupo_maximo' => (int)$request->input('cupo_maximo', $tour->cupo_maximo),
            'duracion' => [
                'es' => $request->input('duracion_es'),
                'en' => $request->input('duracion_en', ''),
                'zh' => $request->input('duracion_zh', ''),
            ],
            'imagen_destacada' => $imagenDefault,
            'tags' => $tags,
            'itinerario' => trim((string) $request->input('itinerario', '')),
            'incluye' => $incluye,
            'no_incluye' => $noIncluye,
            'tipo_modalidad' => $request->input('tipo_modalidad'),
            'tarifas_privadas' => $tarifasPrivadas,
            'sync_calendario_activo' => $tour->esApiExterna() ? $request->boolean('sync_calendario_activo') : false,
            'incluye_pickup' => $tour->esApiExterna() ? $request->boolean('incluye_pickup') : true,
        ]);

        return redirect()->route('dashboard')->with('success', __('Tour actualizado exitosamente.'))->with('active_tab', 'tours');
    }

    /**
     * Agrega una sola imagen a la galería (o galería de experiencias) de un tour ya existente.
     */
    public function addGalleryImage(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        // Detectar una imagen que PHP descartó antes de llegar a la validación de Laravel
        // (p. ej. supera upload_max_filesize/post_max_size del servidor) — de lo contrario el
        // input simplemente parece no hacer nada, sin explicar por qué (el bug reportado).
        if ($request->hasFile('imagen') && !$request->file('imagen')->isValid()) {
            return response()->json([
                'success' => false,
                'message' => $this->mensajeErrorSubida($request->file('imagen')->getError()),
            ], 422);
        }

        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,webp|max:15360',
            'tipo' => 'required|in:galeria,galeria_experiencias',
        ]);

        $tour = Tour::findOrFail($id);
        $tipo = $request->input('tipo');
        $carpeta = $tipo === 'galeria' ? 'tours/galeria' : 'tours/experiencias';

        $url = \App\Services\ImageOptimizerService::uploadAndOptimize($request->file('imagen'), $carpeta);

        $lista = is_array($tour->{$tipo}) ? $tour->{$tipo} : [];
        $lista[] = $url;
        $lista = array_values(array_unique($lista));

        $tour->update([$tipo => $lista]);

        return response()->json(['success' => true, 'url' => $url, $tipo => $lista]);
    }

    /**
     * Quita una imagen de la galería (o galería de experiencias) de un tour existente.
     */
    public function removeGalleryImage(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $request->validate([
            'url' => 'required|string',
            'tipo' => 'required|in:galeria,galeria_experiencias',
        ]);

        $tour = Tour::findOrFail($id);
        $tipo = $request->input('tipo');
        $urlEliminar = $request->input('url');

        $lista = is_array($tour->{$tipo}) ? $tour->{$tipo} : [];
        $lista = array_values(array_filter($lista, fn($u) => $u !== $urlEliminar));

        $tour->update([$tipo => $lista]);

        if (str_starts_with($urlEliminar, '/storage/')) {
            $rutaRelativa = substr($urlEliminar, strlen('/storage/'));
            \Illuminate\Support\Facades\Storage::disk('public')->delete($rutaRelativa);
        }

        return response()->json(['success' => true, $tipo => $lista]);
    }

    // =========================================================================
    // ELIMINAR TOUR
    // =========================================================================

    /**
     * Elimina un tour y toda su disponibilidad si no tiene reservas activas.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroyTour(string $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $tour = Tour::find($id);
        if (!$tour) {
            return response()->json(['success' => false, 'message' => 'Tour no encontrado.'], 404);
        }

        // Verificar si existen reservas activas ligadas a este tour
        $reservasActivas = ReservaTour::where('tour_id', $id)
            ->whereHas('reserva', function ($q) {
                $q->whereIn('estado', ['pendiente', 'confirmada', 'pagada']);
            })->count();

        if ($reservasActivas > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar el tour porque tiene {$reservasActivas} reserva(s) activa(s). Cancélalas primero."
            ], 422);
        }

        // Eliminar fechas de disponibilidad y luego el tour
        TourFecha::where('tour_id', $id)->delete();
        $tour->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tour eliminado correctamente.'
        ]);
    }

    // =========================================================================
    // ELIMINAR PROVEEDOR
    // =========================================================================

    /**
     * Elimina un proveedor si ninguno de sus tours tiene reservas activas.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroyProveedor(int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado.'], 404);
        }

        // Verificar si algún tour del proveedor tiene reservas activas
        $toursIds = Tour::where('proveedor_id', $id)->pluck('id');
        $reservasActivas = ReservaTour::whereIn('tour_id', $toursIds)
            ->whereHas('reserva', function ($q) {
                $q->whereIn('estado', ['pendiente', 'confirmada', 'pagada']);
            })->count();

        if ($reservasActivas > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar el proveedor porque tiene {$reservasActivas} reserva(s) activa(s) en sus tours."
            ], 422);
        }

        // Eliminar fechas de tours, luego tours, luego el proveedor
        TourFecha::whereIn('tour_id', $toursIds)->delete();
        Tour::where('proveedor_id', $id)->delete();
        $proveedor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Proveedor y sus tours eliminados correctamente.'
        ]);
    }
}
