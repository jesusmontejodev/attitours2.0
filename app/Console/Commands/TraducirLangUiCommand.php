<?php
/**
 * @file TraducirLangUiCommand.php
 * @description Comando de un solo uso para generar lang/{locale}.json de los idiomas nuevos
 * (coreano, portugués, francés, alemán) a partir de lang/es.json, vía TranslationService.
 * No se ejecuta automáticamente: se corre a mano cuando se agrega un idioma nuevo a la interfaz.
 */

namespace App\Console\Commands;

use App\Services\TranslationService;
use Illuminate\Console\Command;

class TraducirLangUiCommand extends Command
{
    protected $signature = 'lang:traducir {locales?* : Códigos de idioma a generar (ej. ko pt fr de). Por defecto, todos los de config("app.supported_locales") menos es/en/zh.}';

    protected $description = 'Genera lang/{locale}.json a partir de lang/es.json usando Google Translate';

    /**
     * Código de idioma que espera Google Translate para cada locale del sitio (solo difiere en zh).
     */
    private const CODIGOS_GOOGLE = [
        'en' => 'en',
        'zh' => 'zh-CN',
        'ko' => 'ko',
        'pt' => 'pt',
        'fr' => 'fr',
        'de' => 'de',
    ];

    public function handle(): int
    {
        $locales = $this->argument('locales');
        if (empty($locales)) {
            $locales = array_diff(config('app.supported_locales'), ['es']);
        }

        $rutaEs = lang_path('es.json');
        if (!file_exists($rutaEs)) {
            $this->error("No existe {$rutaEs}.");
            return self::FAILURE;
        }

        $textosEs = json_decode(file_get_contents($rutaEs), true, flags: JSON_THROW_ON_ERROR);
        $this->info(count($textosEs) . " claves encontradas en lang/es.json.");

        // Un puñado de claves (monthNames, dayHeadersShort...) son arrays de strings, no strings
        // sueltos. Se aplanan a sub-claves "clave.indice" para traducir elemento por elemento, y se
        // reensamblan de vuelta a array al escribir el JSON final.
        $piezas = [];
        foreach ($textosEs as $clave => $valor) {
            if (is_array($valor)) {
                foreach ($valor as $indice => $item) {
                    $piezas["{$clave}.{$indice}"] = (string) $item;
                }
            } else {
                $piezas[$clave] = (string) $valor;
            }
        }

        foreach ($locales as $locale) {
            $codigoGoogle = self::CODIGOS_GOOGLE[$locale] ?? null;
            if ($codigoGoogle === null) {
                $this->warn("Idioma '{$locale}' no tiene código de Google Translate configurado, se omite.");
                continue;
            }

            $this->info("Traduciendo a '{$locale}' ({$codigoGoogle})...");
            $inicio = microtime(true);

            $piezasTraducidas = TranslationService::traducirDiccionario($piezas, $codigoGoogle);

            $traducido = [];
            foreach ($textosEs as $clave => $valorOriginal) {
                if (is_array($valorOriginal)) {
                    $lista = [];
                    foreach ($valorOriginal as $indice => $item) {
                        $lista[] = $piezasTraducidas["{$clave}.{$indice}"] ?? $item;
                    }
                    $traducido[$clave] = $lista;
                } else {
                    $traducido[$clave] = $piezasTraducidas[$clave] ?? $valorOriginal;
                }
            }

            $ruta = lang_path("{$locale}.json");
            file_put_contents(
                $ruta,
                json_encode($traducido, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
            );

            $segundos = round(microtime(true) - $inicio, 1);
            $this->info("  → {$ruta} generado en {$segundos}s.");
        }

        $this->info('Listo. Revisa los archivos generados antes de darlos por buenos — la traducción es automática y puede tener errores.');

        return self::SUCCESS;
    }
}
