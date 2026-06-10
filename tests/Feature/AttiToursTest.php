<?php
/**
 * @file AttiToursTest.php
 * @description Pruebas de integración y características para validar las rutas críticas, internacionalización y flujos de Atti Tours. Modificado para validar creación/edición de tours con itinerarios, inclusiones/exclusiones dinámicos y módulo de escaneo de QR.
 * @date 2026-06-10
 * @author Antigravity
 */

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\TourFecha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttiToursTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ejecutar seeders para poblar datos de prueba (tours, fechas, etc.)
        $this->seed();
    }
    public function test_home_page_returns_successful_response(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('ATTI TOURS');
    }

    /**
     * Valida que el catálogo cargue y responda.
     */
    public function test_catalog_page_returns_successful_response(): void
    {
        $response = $this->get(route('catalog'));
        $response->assertStatus(200);
        $response->assertSee('Resultados');
    }

    /**
     * Valida que la página de login cargue correctamente.
     */
    public function test_login_page_returns_successful_response(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('Iniciar Sesión');
    }

    /**
     * Valida que el dashboard esté protegido contra accesos no autenticados y redirija a login.
     */
    public function test_dashboard_is_protected_against_unauthenticated_access(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Valida el cambio dinámico de idioma a nivel de sesión.
     */
    public function test_switching_language_works_correctly(): void
    {
        $response = $this->get(route('lang.switch', 'en'));
        $response->assertSessionHas('locale', 'en');
    }

    /**
     * Valida la consulta de disponibilidad AJAX para un tour oficial.
     */
    public function test_checking_availability_via_ajax_returns_json(): void
    {
        $tour = Tour::first();
        if (!$tour) {
            $this->markTestSkipped('No hay tours en la base de datos para realizar la prueba.');
        }

        $fecha = TourFecha::where('tour_id', $tour->id)->first();
        if (!$fecha) {
            $this->markTestSkipped('No hay fechas asociadas para realizar la prueba.');
        }

        $response = $this->getJson(route('tours.availability', [
            'id' => $tour->id,
            'fecha' => $fecha->fecha->format('Y-m-d')
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'available',
            'horarios' => [
                '*' => [
                    'horario',
                    'cupo_disponible',
                    'cupo_maximo',
                    'cupo_reservado'
                ]
            ]
        ]);
    }

    /**
     * Valida que un Administrador pueda crear un proveedor.
     */
    public function test_admin_can_create_proveedor(): void
    {
        $admin = \App\Models\User::where('email', 'admin@attitours.com')->first();
        
        $response = $this->actingAs($admin)->post(route('dashboard.proveedor'), [
            'nombre_empresa' => 'Operadora Caribeño Exclusivo',
            'descripcion' => 'Operaciones en Cozumel y Cancún',
            'rfc' => 'OCE120608X99',
            'correo' => 'caribex@attitours.com',
            'representante_nombre' => 'José Antonio',
            'representante_telefono' => '+52 998 111 2233',
            'comision_porcentaje' => 20,
            'password' => 'secret123'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('proveedores', [
            'nombre_empresa' => 'Operadora Caribeño Exclusivo'
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'caribex@attitours.com',
            'name' => 'José Antonio',
            'tipo' => 'PT'
        ]);
    }

    /**
     * Valida que un Administrador pueda crear un tour con datos localizados.
     */
    public function test_admin_can_create_tour(): void
    {
        $admin = \App\Models\User::where('email', 'admin@attitours.com')->first();
        $proveedor = \App\Models\Proveedor::first();

        $response = $this->actingAs($admin)->post(route('dashboard.tour'), [
            'titulo' => 'COZUMEL: Jeep Safari y Snórkel',
            'descripcion_corta' => 'Recorrido en jeeps por Cozumel',
            'descripcion_larga' => 'Una aventura completa de snorkel y jeeps...',
            'precio_base_usd' => 1990.00,
            'duracion' => '6 horas',
            'ubicacion' => 'COZUMEL',
            'punto_encuentro' => 'Marina Punta Norte, Cozumel',
            'pais' => 'México',
            'cupo_maximo' => 12,
            'proveedor_id' => $proveedor->id,
            'tags' => 'Aventura, Jeep, Snórkel',
            'horarios' => '09:00, 14:00',
            'galeria' => 'https://images.unsplash.com/photo-1.jpg, https://images.unsplash.com/photo-2.jpg'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tours', [
            'ubicacion' => 'COZUMEL',
            'precio_base_usd' => 1990.00,
            'punto_encuentro' => 'Marina Punta Norte, Cozumel'
        ]);
    }

    /**
     * Valida que un Administrador pueda resetear la contraseña de un usuario.
     */
    public function test_admin_can_reset_user_password(): void
    {
        $admin = \App\Models\User::where('email', 'admin@attitours.com')->first();
        $cliente = \App\Models\User::where('email', 'cliente@attitours.com')->first();

        $response = $this->actingAs($admin)->post(route('dashboard.user.reset-password', $cliente->id), [
            'new_password' => 'nuevaclave123'
        ]);

        $response->assertRedirect();
        
        // Verificar que el usuario pueda iniciar sesión con la nueva contraseña
        $this->assertTrue(auth()->validate([
            'email' => 'cliente@attitours.com',
            'password' => 'nuevaclave123'
        ]));
    }

    /**
     * Valida que un Proveedor no pueda crear un proveedor (permiso denegado).
     */
    public function test_proveedor_cannot_create_proveedor(): void
    {
        $proveedorUser = \App\Models\User::where('email', 'proveedor@attitours.com')->first();

        $response = $this->actingAs($proveedorUser)->post(route('dashboard.proveedor'), [
            'nombre_empresa' => 'Operadora Prohibida',
            'descripcion' => 'Debería fallar',
            'correo' => 'fail@attitours.com',
            'representante_nombre' => 'Intruso',
            'representante_telefono' => '000',
            'comision_porcentaje' => 15,
            'password' => 'fail123'
        ]);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseMissing('proveedores', [
            'nombre_empresa' => 'Operadora Prohibida'
        ]);
    }

    /**
     * Valida que un Administrador pueda habilitar la disponibilidad de un día específico.
     */
    public function test_admin_can_enable_single_day_availability(): void
    {
        $admin = \App\Models\User::where('email', 'admin@attitours.com')->first();
        $tour = Tour::first();
        
        $response = $this->actingAs($admin)->postJson(route('dashboard.dates.update-single-day'), [
            'tour_id' => $tour->id,
            'fecha' => '2026-06-20',
            'habilitado' => true,
            'salidas' => [
                [
                    'horario' => '10:00',
                    'cupo_maximo' => 15
                ],
                [
                    'horario' => '15:00',
                    'cupo_maximo' => 25
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('tour_fechas', [
            'tour_id' => $tour->id,
            'fecha' => '2026-06-20',
            'horario' => '10:00',
            'cupo_maximo' => 15
        ]);

        $this->assertDatabaseHas('tour_fechas', [
            'tour_id' => $tour->id,
            'fecha' => '2026-06-20',
            'horario' => '15:00',
            'cupo_maximo' => 25
        ]);
    }

    /**
     * Valida que un Administrador pueda deshabilitar la disponibilidad de un día específico.
     */
    public function test_admin_can_disable_single_day_availability(): void
    {
        $admin = \App\Models\User::where('email', 'admin@attitours.com')->first();
        $tour = Tour::first();

        // Primero creamos una salida para ese día en una fecha que no colisione con el seeder
        TourFecha::create([
            'tour_id' => $tour->id,
            'fecha' => '2026-07-21',
            'horario' => '09:00',
            'cupo_maximo' => 10,
            'cupo_reservado' => 0
        ]);

        $response = $this->actingAs($admin)->postJson(route('dashboard.dates.update-single-day'), [
            'tour_id' => $tour->id,
            'fecha' => '2026-07-21',
            'habilitado' => false,
            'salidas' => []
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('tour_fechas', [
            'tour_id' => $tour->id,
            'fecha' => '2026-07-21'
        ]);
    }

    /**
     * Valida que un Administrador pueda crear un tour con itinerario, incluye y no_incluye.
     */
    public function test_admin_can_create_tour_with_itinerary_and_inclusions(): void
    {
        $admin = \App\Models\User::where('email', 'admin@attitours.com')->first();
        $proveedor = \App\Models\Proveedor::first();

        $response = $this->actingAs($admin)->post(route('dashboard.tour'), [
            'titulo' => 'Tour con Itinerario y Mas',
            'descripcion_corta' => 'Resumen del tour especial',
            'descripcion_larga' => 'Una gran aventura paso a paso...',
            'precio_base_usd' => 1500.00,
            'duracion' => '4 horas',
            'ubicacion' => 'PLAYA DEL CARMEN',
            'punto_encuentro' => 'Lobby del hotel',
            'pais' => 'México',
            'cupo_maximo' => 10,
            'proveedor_id' => $proveedor->id,
            'tags' => 'Especial, Itinerario',
            'horarios' => '09:00',
            'itinerario_titulos' => ['Punto de Encuentro', 'Snorkel en Cenote'],
            'itinerario_descripciones' => ['Reunión a las 9 AM', 'Nado en aguas cristalinas'],
            'incluye' => 'Guía certificado, Chaleco salvavidas',
            'no_incluye' => 'Propinas, Almuerzo'
        ]);

        $response->assertRedirect();
        
        $tour = Tour::where('ubicacion', 'PLAYA DEL CARMEN')->first();
        $this->assertNotNull($tour);
        $this->assertCount(2, $tour->itinerario);
        $this->assertEquals('Punto de Encuentro', $tour->itinerario[0]['titulo']);
        $this->assertEquals('Guía certificado', $tour->incluye[0]);
        $this->assertEquals('Propinas', $tour->no_incluye[0]);
    }

    /**
     * Valida que un Administrador pueda actualizar el itinerario y las inclusiones de un tour.
     */
    public function test_admin_can_update_tour_with_itinerary_and_inclusions(): void
    {
        $admin = \App\Models\User::where('email', 'admin@attitours.com')->first();
        $tour = Tour::first();

        $response = $this->actingAs($admin)->post(route('dashboard.tour.update', $tour->id), [
            'titulo' => 'Tour Modificado con Itinerario',
            'descripcion_corta' => 'Resumen modificado',
            'descripcion_larga' => 'Detalles modificados',
            'precio_base_usd' => 1800.00,
            'duracion' => '5 horas',
            'ubicacion' => $tour->ubicacion,
            'punto_encuentro' => 'Nuevo punto de encuentro',
            'pais' => 'México',
            'proveedor_id' => $tour->proveedor_id,
            'tags' => 'Modificado',
            'itinerario_titulos' => ['Paso Actualizado 1', 'Paso Actualizado 2'],
            'itinerario_descripciones' => ['Descripción 1', 'Descripción 2'],
            'incluye' => 'Transportación, Almuerzo',
            'no_incluye' => 'Bebidas'
        ]);

        $response->assertRedirect();
        
        $tour->refresh();
        $this->assertEquals('Tour Modificado con Itinerario', $tour->nombre);
        $this->assertCount(2, $tour->itinerario);
        $this->assertEquals('Paso Actualizado 1', $tour->itinerario[0]['titulo']);
        $this->assertContains('Transportación', $tour->incluye);
        $this->assertContains('Bebidas', $tour->no_incluye);
    }

    /**
     * Valida que el escáner de QR esté protegido contra accesos no autenticados.
     */
    public function test_qr_scanner_is_protected_against_unauthenticated_access(): void
    {
        $response = $this->get(route('dashboard.qr.scanner'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Valida que un cliente no pueda acceder al escáner de QR.
     */
    public function test_cliente_cannot_access_qr_scanner(): void
    {
        $cliente = \App\Models\User::where('email', 'cliente@attitours.com')->first();
        $response = $this->actingAs($cliente)->get(route('dashboard.qr.scanner'));
        $response->assertRedirect(route('home'));
    }

    /**
     * Valida que un proveedor pueda acceder al escáner de QR.
     */
    public function test_proveedor_can_access_qr_scanner(): void
    {
        $proveedorUser = \App\Models\User::where('email', 'proveedor@attitours.com')->first();
        $response = $this->actingAs($proveedorUser)->get(route('dashboard.qr.scanner'));
        $response->assertStatus(200);
        $response->assertSee('Asistencia por Código QR');
    }
}

