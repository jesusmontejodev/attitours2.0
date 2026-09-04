<?php
/**
 * @file 2026_09_01_161900_change_itinerario_to_text_on_tours_table.php
 * @description Cambia 'itinerario' de una lista JSON de pasos {dia,titulo,descripcion} a un solo
 *              campo de texto libre, para que el Admin escriba el itinerario como un párrafo
 *              (mostrado en un recuadro en la ficha del tour) en vez de tarjetas de acordeón por
 *              paso. Convierte los datos existentes a texto legible antes de cambiar el tipo de
 *              columna, para no perder el contenido ya cargado.
 * @date 2026-09-01
 * @author Antigravity
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Convertir los pasos JSON existentes a texto legible mientras la columna todavía es JSON.
        $tours = DB::table('tours')->whereNotNull('itinerario')->select('id', 'itinerario')->get();
        foreach ($tours as $tour) {
            $pasos = json_decode($tour->itinerario, true);
            if (!is_array($pasos) || empty($pasos)) {
                continue;
            }

            $texto = collect($pasos)
                ->map(function ($paso) {
                    $titulo = trim($paso['titulo'] ?? '');
                    $descripcion = trim($paso['descripcion'] ?? '');
                    return trim($titulo . ($descripcion ? "\n" . $descripcion : ''));
                })
                ->filter()
                ->implode("\n\n");

            DB::table('tours')->where('id', $tour->id)->update(['itinerario' => json_encode($texto)]);
        }

        // 2. Cambiar el tipo de columna de json a text (MODIFY vía SQL crudo para no depender de
        //    doctrine/dbal, que Laravel 11+ ya no requiere para esto en otros drivers). SQLite no
        //    soporta ALTER ... MODIFY y tampoco distingue tipos de columna de forma estricta (type
        //    affinity), así que ahí no hace falta: el texto plano ya cabe en la columna existente.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE tours MODIFY itinerario TEXT NULL');
        }

        // 3. Ahora que la columna es texto plano, quitar las comillas JSON que le puso el paso 1
        //    (json_encode('hola') guarda "hola" con comillas; sin el cast 'array' del modelo, ya
        //    no se decodifican solas).
        $tours = DB::table('tours')->whereNotNull('itinerario')->select('id', 'itinerario')->get();
        foreach ($tours as $tour) {
            $decodificado = json_decode($tour->itinerario, true);
            if (is_string($decodificado)) {
                DB::table('tours')->where('id', $tour->id)->update(['itinerario' => $decodificado]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Best-effort: envolver el texto libre como un único paso, y volver la columna a json.
        $tours = DB::table('tours')->whereNotNull('itinerario')->select('id', 'itinerario')->get();
        foreach ($tours as $tour) {
            $texto = trim((string) $tour->itinerario);
            $pasos = $texto === '' ? [] : [['dia' => 1, 'titulo' => $texto, 'descripcion' => '']];
            DB::table('tours')->where('id', $tour->id)->update(['itinerario' => json_encode($pasos)]);
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE tours MODIFY itinerario JSON NULL');
        }
    }
};
