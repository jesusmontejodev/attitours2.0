<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Traduce el código de error de una subida de archivo fallida ($file->getError()) a un
     * mensaje claro para el usuario. Existe porque PHP puede descartar un archivo en silencio
     * antes de que Laravel lo valide (p. ej. si supera upload_max_filesize/post_max_size del
     * servidor) — sin esto, el formulario "se guarda" pero la foto simplemente no aparece, o el
     * validador de Laravel reporta un genérico "debe ser una imagen" que no explica la causa real.
     */
    protected function mensajeErrorSubida(int $codigoError): string
    {
        return match ($codigoError) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                'El archivo es demasiado grande para el límite configurado en el servidor (upload_max_filesize/post_max_size de PHP), aunque cumpla con el límite de la app. Pide al administrador del servidor que aumente ese límite.',
            UPLOAD_ERR_PARTIAL =>
                'La subida del archivo se interrumpió a la mitad. Intenta de nuevo.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE =>
                'El servidor no pudo guardar el archivo temporalmente. Contacta al administrador del servidor (permisos o espacio en disco).',
            default => 'No se pudo subir el archivo. Intenta de nuevo o usa otra imagen.',
        };
    }
}
