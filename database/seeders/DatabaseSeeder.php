<?php
/**
 * @file DatabaseSeeder.php
 * @description Seeder principal de la base de datos para inicializar Atti Tours con el proveedor y usuarios base. Email y contraseña de cada cuenta se toman de config('services.seed_users'), es decir de las variables SEED_*_EMAIL / SEED_*_PASSWORD del .env; si alguna contraseña no está definida ahí, se genera una aleatoria y se imprime en consola. No crea tours ni reservas de ejemplo (ver DemoDataSeeder para datos de prueba).
 * @date 2026-08-20
 * @author Antigravity
 */

namespace Database\Seeders;

use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Limpiar usuarios y proveedor previos si los hubiera para evitar duplicados
        User::query()->delete();
        Proveedor::query()->delete();

        // 1. Crear Proveedor por defecto
        $proveedor = Proveedor::create([
            'nombre_empresa' => 'Atti Tours Operadora Local',
            'descripcion' => 'Especialistas en recorridos de sol, playa y arqueología en Quintana Roo y el Caribe Mexicano.',
            'rfc' => 'ATT120405H89',
            'correo' => 'contacto@attitours.com',
            'representante_nombre' => 'Carlos Gómez',
            'representante_telefono' => '+52 999 645 6742',
            'comision_porcentaje' => 15
        ]);

        // 2. Crear Usuarios base (Cliente, Proveedor, Administrador) usando las
        // credenciales definidas en .env (SEED_*_EMAIL / SEED_*_PASSWORD). Si alguna
        // contraseña no está definida, se genera una aleatoria y se avisa en consola.
        $credencialesFaltantes = [];

        [$cliente, $passwordCliente] = $this->crearUsuarioBase('cliente', [
            'name' => 'Juan Pérez',
            'tipo' => 'Cliente',
            'telefono' => '+52 55 1234 5678',
            'pais' => 'México'
        ], $credencialesFaltantes);

        [$usuarioProveedor, $passwordProveedor] = $this->crearUsuarioBase('proveedor', [
            'name' => 'Carlos Gómez (Amigo Tours)',
            'tipo' => 'PT',
            'telefono' => '+52 55 8765 4321',
            'pais' => 'México',
            'proveedor_id' => $proveedor->id
        ], $credencialesFaltantes);

        [$admin, $passwordAdmin] = $this->crearUsuarioBase('admin', [
            'name' => 'Sofía Rodríguez',
            'tipo' => 'Admin',
            'telefono' => '+34 612 345 678',
            'pais' => 'España'
        ], $credencialesFaltantes);

        $this->command?->newLine();
        $this->command?->info('Cuentas creadas con las credenciales definidas en .env (SEED_*_EMAIL / SEED_*_PASSWORD).');

        // 3. Para cualquier cuenta sin contraseña definida en .env, mostrar la generada
        if (!empty($credencialesFaltantes)) {
            $this->command?->newLine();
            $this->command?->warn('No había SEED_*_PASSWORD en .env para estas cuentas — se generó una aleatoria, guárdala ahora:');
            $this->command?->table(['Rol', 'Email', 'Contraseña generada'], $credencialesFaltantes);
            $this->command?->newLine();
        }
    }

    /**
     * Crea un usuario base leyendo su email/contraseña de config('services.seed_users.{rol}').
     * Si la contraseña no está definida en .env, genera una aleatoria y la registra en
     * $credencialesFaltantes para mostrarla en consola.
     *
     * @return array{0: User, 1: string} El usuario creado y la contraseña en texto plano usada.
     */
    private function crearUsuarioBase(string $rol, array $datosAdicionales, array &$credencialesFaltantes): array
    {
        $config = config("services.seed_users.{$rol}");
        $email = $config['email'];
        $password = $config['password'];

        if (empty($password)) {
            $password = Str::password(16);
            $credencialesFaltantes[] = [ucfirst($rol), $email, $password];
        }

        $usuario = User::create(array_merge($datosAdicionales, [
            'email' => $email,
            'password' => Hash::make($password),
        ]));

        return [$usuario, $password];
    }
}
