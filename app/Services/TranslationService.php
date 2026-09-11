<?php
/**
 * @file TranslationService.php
 * @description Traduce automáticamente los campos multi-idioma de un tour (título, descripciones,
 * duración) desde el español capturado por el Admin hacia el resto de idiomas soportados, usando
 * el endpoint público (no oficial, sin API key) de Google Translate.
 */

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Stichoza\GoogleTranslate\Tokens\GoogleTokenGenerator;
use Throwable;

class TranslationService
{
    /**
     * Idiomas soportados para el contenido de los tours. La clave es el código guardado en la
     * columna JSON del tour; el valor es el código de idioma que espera Google Translate (distinto
     * solo para chino, que Google identifica como "zh-CN").
     */
    private const IDIOMAS = [
        'en' => 'en',
        'zh' => 'zh-CN',
        'ko' => 'ko',
        'pt' => 'pt',
        'fr' => 'fr',
        'de' => 'de',
    ];

    private const URL_TRADUCCION = 'https://translate.google.com/translate_a/single';

    /**
     * Traduce varios campos en español (p. ej. titulo, descripcion_corta, descripcion_larga,
     * duracion) a la vez, cada uno hacia los 6 idiomas soportados. Todas las peticiones HTTP se
     * disparan en paralelo (una por cada combinación campo/idioma) en vez de una por una: traducir
     * 4 campos × 6 idiomas de forma secuencial supera el tiempo máximo de ejecución de PHP (~24
     * peticiones HTTP en serie), mientras que en paralelo tarda lo mismo que la más lenta.
     *
     * Si la traducción de alguna combinación falla (el endpoint no es oficial, puede fallar o dar
     * rate limit), se usa el texto en español como respaldo en ese idioma para no bloquear la
     * creación o edición del tour.
     *
     * @param array<string, string> $camposEs ej. ['titulo' => 'Excursión...', 'duracion' => '3 horas']
     * @return array<string, array<string, string>> mismo shape, cada valor con las 7 claves de idioma (es + 6 traducciones)
     */
    public static function traducirCamposDesdeEspanol(array $camposEs): array
    {
        $resultado = [];
        foreach ($camposEs as $campo => $textoEs) {
            $textoEs = (string) $textoEs;
            $resultado[$campo] = ['es' => $textoEs];
            foreach (array_keys(self::IDIOMAS) as $idioma) {
                // Respaldo por defecto: si la traducción a este idioma falla, se conserva el español.
                $resultado[$campo][$idioma] = $textoEs;
            }
        }

        $tokenGenerator = new GoogleTokenGenerator();
        $peticiones = [];

        foreach ($camposEs as $campo => $textoEs) {
            $textoEs = trim((string) $textoEs);
            if ($textoEs === '') {
                continue;
            }

            foreach (self::IDIOMAS as $idioma => $codigoGoogle) {
                $query = http_build_query([
                    'client' => 'webapp',
                    'sl' => 'es',
                    'tl' => $codigoGoogle,
                    'dt' => 't',
                    'ie' => 'UTF-8',
                    'oe' => 'UTF-8',
                    'q' => $textoEs,
                    'tk' => $tokenGenerator->generateToken('es', $codigoGoogle, $textoEs),
                ]);

                $peticiones["{$campo}|{$idioma}"] = new Request('GET', self::URL_TRADUCCION . '?' . $query);
            }
        }

        if (empty($peticiones)) {
            return $resultado;
        }

        $client = new Client(['timeout' => 8, 'connect_timeout' => 5]);

        (new Pool($client, $peticiones, [
            'concurrency' => 3,
            'fulfilled' => function (ResponseInterface $response, string $clave) use (&$resultado): void {
                [$campo, $idioma] = explode('|', $clave, 2);
                $traducido = self::extraerTraduccion((string) $response->getBody());
                if ($traducido !== null && $traducido !== '') {
                    $resultado[$campo][$idioma] = $traducido;
                }
            },
            'rejected' => function (mixed $razon, string $clave): void {
                Log::warning("TranslationService: fallo al traducir '{$clave}', se usa el texto en español como respaldo.", [
                    'error' => $razon instanceof Throwable ? $razon->getMessage() : (string) $razon,
                ]);
            },
        ]))->promise()->wait();

        return $resultado;
    }

    /**
     * Traduce un único texto en español al resto de idiomas soportados. Atajo sobre
     * {@see self::traducirCamposDesdeEspanol()} para cuando solo hay un campo que traducir.
     *
     * @return array<string, string> arreglo con las 7 claves de idioma (es + 6 traducciones)
     */
    public static function traducirDesdeEspanol(string $textoEs): array
    {
        return self::traducirCamposDesdeEspanol(['campo' => $textoEs])['campo'];
    }

    /**
     * Extrae y concatena el texto traducido de la respuesta JSON de Google Translate (formato
     * dt=t: una lista de fragmentos de oración, cada uno [texto_traducido, texto_original, ...]).
     */
    private static function extraerTraduccion(string $cuerpoRespuesta): ?string
    {
        try {
            $datos = json_decode($cuerpoRespuesta, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (empty($datos[0]) || !is_array($datos[0])) {
            return null;
        }

        return array_reduce(
            $datos[0],
            static fn (string $acumulado, $fragmento) => $acumulado . ($fragmento[0] ?? ''),
            ''
        );
    }
}
