<?php
/**
 * @file 2026_09_03_000000_change_duracion_to_json_on_tours_table.php
 * @description Cambia 'duracion' de un string plano a formato JSON {es, en, zh}, igual que
 *              'titulo'/'descripcion_corta'/'descripcion_larga', para que se muestre en el idioma
 *              activo de la app en vez de quedar fijo en el idioma en que lo escribió el Admin.
 *              Envuelve el valor existente en la clave 'es' antes de cambiar el tipo de columna,
 *              para no perder el contenido ya cargado.
 * @date 2026-09-03
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
        // 1. Envolver el texto plano existente como {es: "...", en: "", zh: ""} mientras la
        //    columna todavía es string.
        $tours = DB::table('tours')->select('id', 'duracion')->get();
        foreach ($tours as $tour) {
            DB::table('tours')->where('id', $tour->id)->update([
                'duracion' => json_encode(['es' => (string) $tour->duracion, 'en' => '', 'zh' => '']),
            ]);
        }

        // 2. Cambiar el tipo de columna de string a json (MODIFY vía SQL crudo para no depender
        //    de doctrine/dbal). SQLite no soporta ALTER ... MODIFY y tampoco distingue tipos de
        //    columna de forma estricta (type affinity), así que ahí no hace falta el ALTER: el
        //    valor JSON ya cabe en la columna string existente sin que SQLite se queje.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE tours MODIFY duracion JSON NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Extraer el valor 'es' de vuelta a texto plano MIENTRAS la columna todavía es JSON
        //    (o string en SQLite), para que ya esté corto antes de encoger el tipo de columna.
        //    Hacerlo en el orden inverso (encoger la columna primero) falla en MySQL con modo
        //    estricto, porque en ese punto la columna todavía tiene el JSON completo, que es
        //    más largo que el string original.
        $tours = DB::table('tours')->select('id', 'duracion')->get();
        foreach ($tours as $tour) {
            $decodificado = json_decode($tour->duracion, true);
            $valor = is_array($decodificado) ? ($decodificado['es'] ?? '') : (string) $tour->duracion;
            DB::table('tours')->where('id', $tour->id)->update(['duracion' => $valor]);
        }

        // 2. Volver el tipo de columna a string (VARCHAR(255), el largo por defecto de Laravel
        //    con el que se creó originalmente). Igual que en up(), no aplica en SQLite.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE tours MODIFY duracion VARCHAR(255) NOT NULL');
        }
    }
};
