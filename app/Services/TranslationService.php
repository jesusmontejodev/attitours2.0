<?php
/**
 * @file TranslationService.php
 * @description Traduce automáticamente contenido en español hacia el resto de idiomas soportados
 * (config('app.supported_locales')) usando el endpoint público (no oficial, sin API key) de Google
 * Translate. Se usa tanto para los campos multi-idioma de un tour (título, descripciones, duración)
 * como para generar los archivos lang/{locale}.json de la interfaz del sitio.
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
     * Patrón de lo que hay que preservar sin traducir dentro de una cadena: los placeholders de
     * Laravel (ej. ":name" en `__('¡Hola, :name!', ['name' => $nombre])`) y los emoji del plano
     * suplementario de Unicode (ej. 👋🔒📷, U+10000 en adelante). Estos últimos no son cosméticos:
     * el generador de tokens de Google Translate (GoogleTokenGenerator, que replica a mano el
     * cálculo de "tk" que hace el JS de Google) calcula mal el token cuando el texto contiene un
     * carácter que ocupa un par subrogado en UTF-16, y Google responde 403 al toque — así que se
     * extraen igual que los placeholders y se reinyectan después de traducir.
     */
    private const PATRON_PLACEHOLDER = '/:(\w+)|[\x{10000}-\x{10FFFF}]/u';

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

        $peticiones = self::construirPeticiones($camposEs, self::IDIOMAS);
        if (empty($peticiones)) {
            return $resultado;
        }

        self::ejecutarPool($peticiones, function (string $campo, string $idioma, string $traducido) use (&$resultado): void {
            $resultado[$campo][$idioma] = $traducido;
        }, concurrency: 3);

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
     * Traduce un diccionario clave => texto en español hacia un único idioma destino, preservando
     * los placeholders ":variable" de Laravel. Pensado para generar los archivos lang/{locale}.json
     * de la interfaz del sitio a partir de lang/es.json (ver comando `lang:traducir`); al ejecutarse
     * desde CLI no hay límite de tiempo de PHP, por lo que admite más concurrencia que las
     * traducciones en vivo del formulario de tours.
     *
     * @param array<string, string> $textosEs
     * @param string $codigoGoogle código de idioma que espera Google Translate (ej. "ko", "zh-CN")
     * @return array<string, string> mismas claves, con el texto traducido (o el original si falló)
     */
    public static function traducirDiccionario(array $textosEs, string $codigoGoogle, int $concurrency = 4): array
    {
        $resultado = $textosEs;

        $peticiones = self::construirPeticiones($textosEs, [$codigoGoogle => $codigoGoogle]);
        if (empty($peticiones)) {
            return $resultado;
        }

        self::ejecutarPool($peticiones, function (string $clave, string $idioma, string $traducido) use (&$resultado): void {
            $resultado[$clave] = $traducido;
        }, $concurrency);

        return $resultado;
    }

    /**
     * Construye las peticiones HTTP hacia Google Translate (una por cada combinación clave/idioma),
     * preservando los placeholders ":variable" de cada texto: se sustituyen por tokens antes de
     * traducir y se guardan para reinyectarlos al recibir la respuesta.
     *
     * @param array<string, string> $textos clave interna => texto en español
     * @param array<string, string> $idiomas clave interna del idioma => código Google
     * @return array<string, array{request: Request, reemplazos: array<string>}>
     */
    private static function construirPeticiones(array $textos, array $idiomas): array
    {
        $tokenGenerator = new GoogleTokenGenerator();
        $peticiones = [];

        foreach ($textos as $clave => $textoEs) {
            $textoEs = trim((string) $textoEs);
            if ($textoEs === '') {
                continue;
            }

            [$textoPreparado, $reemplazos] = self::extraerPlaceholders($textoEs);

            foreach ($idiomas as $idiomaInterno => $codigoGoogle) {
                $query = http_build_query([
                    'client' => 'webapp',
                    'sl' => 'es',
                    'tl' => $codigoGoogle,
                    'dt' => 't',
                    'ie' => 'UTF-8',
                    'oe' => 'UTF-8',
                    'q' => $textoPreparado,
                    'tk' => $tokenGenerator->generateToken('es', $codigoGoogle, $textoPreparado),
                ]);

                $peticiones["{$clave}|{$idiomaInterno}"] = [
                    'request' => new Request('GET', self::URL_TRADUCCION . '?' . $query),
                    'reemplazos' => $reemplazos,
                ];
            }
        }

        return $peticiones;
    }

    /**
     * Ejecuta el pool de peticiones y llama a $onExito(clave, idioma, textoTraducido) por cada
     * traducción exitosa. Las fallidas solo quedan registradas en el log — el llamador ya inicializó
     * el resultado con el texto original como respaldo, así que no hace falta tocarlo aquí.
     *
     * @param array<string, array{request: Request, reemplazos: array<string>}> $peticiones
     */
    private static function ejecutarPool(array $peticiones, callable $onExito, int $concurrency): void
    {
        $client = new Client(['timeout' => 8, 'connect_timeout' => 5]);
        $requests = array_map(static fn (array $p) => $p['request'], $peticiones);

        (new Pool($client, $requests, [
            'concurrency' => $concurrency,
            'fulfilled' => function (ResponseInterface $response, string $llave) use ($peticiones, $onExito): void {
                [$clave, $idioma] = explode('|', $llave, 2);
                $traducido = self::extraerTraduccion((string) $response->getBody());
                if ($traducido === null || $traducido === '') {
                    return;
                }
                $traducido = self::reinyectarPlaceholders($traducido, $peticiones[$llave]['reemplazos']);
                $onExito($clave, $idioma, $traducido);
            },
            'rejected' => function (mixed $razon, string $llave): void {
                Log::warning("TranslationService: fallo al traducir '{$llave}', se usa el texto original como respaldo.", [
                    'error' => $razon instanceof Throwable ? $razon->getMessage() : (string) $razon,
                ]);
            },
        ]))->promise()->wait();
    }

    /**
     * Sustituye los placeholders ":variable" de Laravel por tokens "#{n}" antes de traducir (así
     * Google no traduce ni reordena el nombre de la variable), devolviendo también la lista de
     * placeholders originales en orden, para reinyectarlos después de traducir.
     *
     * @return array{0: string, 1: array<string>}
     */
    private static function extraerPlaceholders(string $texto): array
    {
        $reemplazos = [];
        $preparado = preg_replace_callback(self::PATRON_PLACEHOLDER, function (array $match) use (&$reemplazos): string {
            $reemplazos[] = $match[0];
            return '#{' . (count($reemplazos) - 1) . '}';
        }, $texto);

        return [$preparado ?? $texto, $reemplazos];
    }

    /**
     * Reinyecta los placeholders originales en el texto ya traducido, en el lugar donde Google haya
     * dejado el token "#{n}" (Google a veces añade un espacio dentro de las llaves).
     *
     * @param array<string> $reemplazos
     */
    private static function reinyectarPlaceholders(string $texto, array $reemplazos): string
    {
        if (empty($reemplazos)) {
            return $texto;
        }

        $texto = preg_replace('/#\{\s*(\d+)\s*\}/', '#{$1}', $texto) ?? $texto;

        return preg_replace_callback('/#\{(\d+)\}/', static function (array $match) use ($reemplazos): string {
            return $reemplazos[(int) $match[1]] ?? $match[0];
        }, $texto) ?? $texto;
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
