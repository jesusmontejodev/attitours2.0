<?php
/**
 * @file DatabaseSeeder.php
 * @description Seeder principal de la base de datos para inicializar Atti Tours con usuarios de prueba, proveedores, tours y disponibilidad.
 * @date 2026-06-08
 * @author Antigravity
 */

namespace Database\Seeders;

use App\Models\Proveedor;
use App\Models\Reserva;
use App\Models\ReservaTour;
use App\Models\Tour;
use App\Models\TourFecha;
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
        // Limpiar registros previos si los hubiera para evitar duplicados
        ReservaTour::query()->delete();
        Reserva::query()->delete();
        TourFecha::query()->delete();
        Tour::query()->delete();
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

        // 2. Crear Usuarios de prueba (Cliente, Proveedor, Administrador)
        $cliente = User::create([
            'name' => 'Juan Pérez',
            'email' => 'cliente@attitours.com',
            'password' => Hash::make('cliente123'),
            'tipo' => 'Cliente',
            'telefono' => '+52 55 1234 5678',
            'pais' => 'México'
        ]);

        $usuarioProveedor = User::create([
            'name' => 'Carlos Gómez (Amigo Tours)',
            'email' => 'proveedor@attitours.com',
            'password' => Hash::make('prov123'),
            'tipo' => 'PT',
            'telefono' => '+52 55 8765 4321',
            'pais' => 'México',
            'proveedor_id' => $proveedor->id
        ]);

        $admin = User::create([
            'name' => 'Sofía Rodríguez',
            'email' => 'admin@attitours.com',
            'password' => Hash::make('admin123'),
            'tipo' => 'Admin',
            'telefono' => '+34 612 345 678',
            'pais' => 'España'
        ]);

        // 3. Crear Tours con soporte multilenguaje (JSON)
        $toursData = [
            [
                'id' => 'tour_cancun',
                'proveedor_id' => $proveedor->id,
                'titulo' => [
                    'es' => 'CANCUN: Aventura de Snórkel y Arrecife de Coral',
                    'en' => 'CANCUN: Snorkeling & Coral Reef Adventure',
                    'zh' => '坎昆：浮潜与珊瑚礁冒险游'
                ],
                'descripcion_corta' => [
                    'es' => 'Descubre la rica vida marina en las cálidas aguas turquesas del Caribe Mexicano en esta excursión inolvidable.',
                    'en' => 'Discover the rich marine life in the warm turquoise waters of the Mexican Caribbean on this unforgettable excursion.',
                    'zh' => '在这次难忘的远足中，探索墨西哥加勒比海温暖碧蓝海水中的丰富海洋生物。'
                ],
                'descripcion_larga' => [
                    'es' => 'Sumérgete en una aventura sin igual en las costas de Cancún. Guiado por instructores certificados, explorarás tres áreas del arrecife de coral del Caribe, donde podrás avistar tortugas marinas, rayas y una amplia variedad de peces multicolores. El tour incluye todo el equipamiento de snórkel de alta calidad, transporte climatizado ida y vuelta, y bebidas refrescantes.',
                    'en' => 'Immerse yourself in an unparalleled adventure off the coast of Cancun. Guided by certified instructors, you will explore three areas of the Caribbean coral reef, where you can spot sea turtles, rays, and a wide variety of multicolored fish. The tour includes all high-quality snorkeling equipment, round-trip air-conditioned transport, and refreshing drinks.',
                    'zh' => '在坎昆海岸开启一场无与伦比的冒险。在专业教练的带领下，您将探索加勒比珊瑚礁的三个区域，在那里您可以观赏海龟、鳐鱼和各种五颜六色的鱼类。行程包含所有高品质浮潜设备、往返空调接送和清凉饮料。'
                ],
                'ubicacion' => 'CANCUN',
                'pais' => 'México',
                'precio_base_usd' => 1180.00,
                'duracion' => '4 horas',
                'imagen_destacada' => 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?auto=format&fit=crop&w=800&q=80',
                'galeria' => [
                    'https://images.unsplash.com/photo-1544735716-392fe2489ffa?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1682687220063-4742bd7fd53c?auto=format&fit=crop&w=800&q=80'
                ],
                'cupo_maximo' => 18,
                'tags' => ['Playa', 'Snórkel', 'Aventura'],
                'fechas' => [
                    '2026-06-04', '2026-06-05', '2026-06-06', '2026-06-07', '2026-06-11', '2026-06-12',
                    '2026-06-13', '2026-06-14', '2026-06-18', '2026-06-19', '2026-06-20', '2026-06-21',
                    '2026-07-01', '2026-07-02', '2026-07-03' // Fechas adicionales a futuro para pruebas
                ]
            ],
            [
                'id' => 'tour_riviera_maya',
                'proveedor_id' => $proveedor->id,
                'titulo' => [
                    'es' => 'RIVIERA MAYA: Tulum Express y Cenote Sagrado',
                    'en' => 'RIVIERA MAYA: Tulum Express & Sacred Cenote',
                    'zh' => '里维拉玛雅：图伦古城与神圣塞诺特'
                ],
                'descripcion_corta' => [
                    'es' => 'Visita la zona arqueológica de Tulum a la orilla del mar y refréscate en un místico cenote de aguas cristalinas.',
                    'en' => 'Visit the archaeological site of Tulum by the sea and cool off in a mystical cenote of crystal clear waters.',
                    'zh' => '参观海滨的图伦考古遗址，并在神秘的清澈塞诺特天坑中消暑。'
                ],
                'descripcion_larga' => [
                    'es' => 'Descubre Tulum, la única ciudad maya construida frente al espectacular mar Caribe. Nuestro guía historiador te llevará en un recorrido detallado por el Templo del Dios Descendente y el icónico Castillo. Posteriormente, nos adentraremos en la selva para nadar en un impresionante cenote cerrado, venerado por los antiguos mayas. El tour incluye entradas, chaleco salvavidas y un almuerzo buffet de comida yucateca.',
                    'en' => 'Discover Tulum, the only Mayan city built facing the spectacular Caribbean Sea. Our historian guide will take you on a detailed tour of the Temple of the Descending God and the iconic Castle. Afterwards, we will venture into the jungle to swim in an impressive enclosed cenote, revered by the ancient Mayans. The tour includes entry tickets, life jackets, and a buffet lunch of Yucatecan food.',
                    'zh' => '探索图伦，这是唯一一座建在壮丽加勒比海面前的玛雅城市。我们的历史导游将带您详细游览降神庙和标志性的城堡。随后，我们将深入丛林，在古玛雅人崇敬的壮丽封闭式塞诺特天坑中游泳。行程包含门票、救生衣和尤卡坦风味自助午餐。'
                ],
                'ubicacion' => 'RIVIERA MAYA',
                'pais' => 'México',
                'precio_base_usd' => 1780.00,
                'duracion' => '8 horas',
                'imagen_destacada' => 'https://images.unsplash.com/photo-1504198453319-5ce911bafcde?auto=format&fit=crop&w=800&q=80',
                'galeria' => [
                    'https://images.unsplash.com/photo-1504198453319-5ce911bafcde?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1512813583145-baaa340ef29f?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1580216643062-cf460548a66a?auto=format&fit=crop&w=800&q=80'
                ],
                'cupo_maximo' => 20,
                'tags' => ['Arqueología', 'Historia', 'Naturaleza'],
                'fechas' => [
                    '2026-06-05', '2026-06-06', '2026-06-07', '2026-06-12', '2026-06-13', '2026-06-14',
                    '2026-06-19', '2026-06-20', '2026-06-21', '2026-06-26', '2026-06-27', '2026-06-28',
                    '2026-07-04', '2026-07-05'
                ]
            ],
            [
                'id' => 'tour_isla_mujeres',
                'proveedor_id' => $proveedor->id,
                'titulo' => [
                    'es' => 'ISLA MUJERES: Excursión en Catamarán y Club de Playa',
                    'en' => 'ISLA MUJERES: Catamaran Cruise & Beach Club',
                    'zh' => '女人岛：双体船巡游与沙滩俱乐部'
                ],
                'descripcion_corta' => [
                    'es' => 'Navega a vela hacia la encantadora Isla Mujeres en un moderno catamarán con barra libre, música y comida buffet.',
                    'en' => 'Sail towards charming Isla Mujeres on a modern catamaran with open bar, music, and a buffet lunch.',
                    'zh' => '乘坐配有敞开式吧台、音乐和自助午餐的现代双体船，扬帆驶向迷人的女人岛。'
                ],
                'descripcion_larga' => [
                    'es' => 'Disfruta de una fiesta marina navegando en un catamarán de vela de lujo. Disfruta de la barra libre nacional y haz una parada para realizar snórkel en el arrecife "El Meco". Después, desembarcaremos en un club de playa privado en Isla Mujeres para disfrutar de una comida buffet y relajarte en camastros, antes de tener tiempo libre para explorar el centro de la isla en carritos de golf o a pie.',
                    'en' => 'Enjoy a marine party sailing on a luxury sailing catamaran. Enjoy the national open bar and stop for snorkeling at the "El Meco" reef. Afterwards, we will disembark at a private beach club on Isla Mujeres to enjoy a buffet lunch and relax on lounge chairs, before having free time to explore the center of the island by golf carts or on foot.',
                    'zh' => '在豪华双体船上享受海洋派对。享用免费吧台，并在“埃尔梅科”礁石处停靠浮潜。随后，我们将在女人岛的私人海滩俱乐部登岸，享用自助午餐并躺椅放松，然后有自由时间乘高尔夫球车或步行探索岛中心。'
                ],
                'ubicacion' => 'ISLA MUJERES',
                'pais' => 'México',
                'precio_base_usd' => 1580.00,
                'duracion' => '7 horas',
                'imagen_destacada' => 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?auto=format&fit=crop&w=800&q=80',
                'galeria' => [
                    'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1542204172-e7052809a867?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1543731068-7e0f5beff43a?auto=format&fit=crop&w=800&q=80'
                ],
                'cupo_maximo' => 25,
                'tags' => ['Playa', 'Navegación', 'Fiesta'],
                'fechas' => [
                    '2026-06-06', '2026-06-08', '2026-06-10', '2026-06-13', '2026-06-15', '2026-06-17',
                    '2026-06-20', '2026-06-22', '2026-06-24', '2026-06-27', '2026-06-29',
                    '2026-07-06', '2026-07-08'
                ]
            ],
            [
                'id' => 'tour_contoy',
                'proveedor_id' => $proveedor->id,
                'titulo' => [
                    'es' => 'CONTOY: Santuario de Aves e Isla Exclusiva Protegida',
                    'en' => 'CONTOY: Bird Sanctuary & Exclusive Protected Island',
                    'zh' => '孔托伊岛：鸟类保护区与专属保护岛'
                ],
                'descripcion_corta' => [
                    'es' => 'Explora la joya virgen del Caribe: una isla protegida con acceso limitado para observar aves y playas paradisíacas intactas.',
                    'en' => 'Explore the untouched jewel of the Caribbean: a protected island with limited access for bird watching and pristine beaches.',
                    'zh' => '探索加勒比海未受污染的明珠：一座限制准入的受保护岛屿，可进行观鸟和游览原始沙滩。'
                ],
                'descripcion_larga' => [
                    'es' => 'Visita la Isla Contoy, un parque nacional protegido que solo permite el acceso a 200 personas al día. Acompañado por un biólogo, recorrerás los senderos naturales para avistar fragatas, pelícanos y otras aves migratorias. Después de la caminata, disfrutarás de tiempo libre para nadar en una playa de aguas transparentes digna de postal y comer una parrillada de pescado tikin-xic cocinada al estilo local. El tour incluye traslados, bebidas y comida.',
                    'en' => 'Visit Isla Contoy, a protected national park that only allows 200 visitors per day. Accompanied by a biologist, you will tour natural paths to spot frigates, pelicans, and other migratory birds. After the walk, you\'ll enjoy free time to swim in a picture-perfect clear beach and eat a local tikin-xic fish barbecue. The tour includes transfers, drinks, and food.',
                    'zh' => '访问孔托伊岛，这是一座每天仅限 200 名访客进入的受保护国家公园。在生物学家的陪同下，您将游览自然小径，观赏军舰鸟、鹈鹕和其他候鸟。散步后，您将享受自由时间，在明信片般清澈的海滩游泳，并享用当地风味的烤鱼。行程包含接送、饮料和膳食。'
                ],
                'ubicacion' => 'CONTOY',
                'pais' => 'México',
                'precio_base_usd' => 2580.00,
                'duracion' => '9 horas',
                'imagen_destacada' => 'https://images.unsplash.com/photo-1526392060635-9d6019884377?auto=format&fit=crop&w=800&q=80',
                'galeria' => [
                    'https://images.unsplash.com/photo-1526392060635-9d6019884377?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1509316975850-ff9c5deb0cd9?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1510414838738-d249f02062ca?auto=format&fit=crop&w=800&q=80'
                ],
                'cupo_maximo' => 12,
                'tags' => ['Ecológico', 'Aves', 'Exclusivo'],
                'fechas' => [
                    '2026-06-05', '2026-06-07', '2026-06-09', '2026-06-12', '2026-06-14', '2026-06-16',
                    '2026-06-19', '2026-06-21', '2026-06-23', '2026-06-26', '2026-06-28', '2026-06-30',
                    '2026-07-07', '2026-07-09'
                ]
            ]
        ];

        // Guardar tours y sus fechas
        foreach ($toursData as $tData) {
            $fechas = $tData['fechas'];
            unset($tData['fechas']);

            $tour = Tour::create($tData);

            foreach ($fechas as $fechaStr) {
                $horarios = $tour->horarios ?: ['09:00'];
                foreach ($horarios as $hora) {
                    TourFecha::create([
                        'tour_id' => $tour->id,
                        'fecha' => $fechaStr,
                        'horario' => $hora,
                        'cupo_maximo' => $tour->cupo_maximo,
                        'cupo_reservado' => 0
                    ]);
                }
            }
        }

        // 4. Crear una Reserva de prueba inicial (coincidente con booking_1)
        // Reservar 2 personas para Cancún en la fecha 2026-06-12
        $fechaReservaStr = '2026-06-12';
        $tourId = 'tour_cancun';
        $cantidad = 2;
        $precioUnitario = 1180.00;
        $precioTotal = $precioUnitario * $cantidad;
        $comisionTotal = $precioTotal * ($proveedor->comision_porcentaje / 100);

        // Buscar y descontar cupo en la fecha
        $tourFecha = TourFecha::where('tour_id', $tourId)
            ->where('fecha', $fechaReservaStr)
            ->where('horario', '09:00')
            ->first();
        if ($tourFecha) {
            $tourFecha->update([
                'cupo_reservado' => $cantidad
            ]);
        }

        $reserva = Reserva::create([
            'user_id' => $cliente->id,
            'nombre_cliente' => $cliente->name,
            'correo_cliente' => $cliente->email,
            'telefono_cliente' => $cliente->telefono,
            'precio_total_usd' => $precioTotal,
            'comision_total_usd' => $comisionTotal,
            'estado' => 'Pagada',
            'fecha_reserva' => '2026-05-28 14:32:00',
            'ticket_codigo' => 'TKT-' . Str::upper(Str::random(8))
        ]);

        ReservaTour::create([
            'reserva_id' => $reserva->id,
            'tour_id' => $tourId,
            'fecha_seleccionada' => $fechaReservaStr,
            'horario' => '09:00',
            'cantidad_personas' => $cantidad,
            'precio_unitario_usd' => $precioUnitario,
            'comision_usd' => $comisionTotal
        ]);
    }
}
