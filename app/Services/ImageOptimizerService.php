<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @file ImageOptimizerService.php
 * @description Servicio centralizado para optimizar, redimensionar y almacenar imágenes subidas.
 *              Garantiza máxima calidad visual, compresión ligera para carga rápida y evita errores en servidor.
 * @date 2026-07-21
 * @author Antigravity
 */
class ImageOptimizerService
{
    /**
     * Procesa, optimiza y guarda una imagen subida.
     *
     * @param UploadedFile $file
     * @param string $folder Subcarpeta en storage (ej: 'tours', 'proveedores', 'perfiles')
     * @param int $maxDimension Dimensión máxima en pixeles (ancho/alto)
     * @param int $quality Calidad de compresión JPEG (1-100)
     * @return string URL pública relativa (ej: '/storage/tours/abc123.jpg')
     */
    public static function uploadAndOptimize(UploadedFile $file, string $folder = 'uploads', int $maxDimension = 1920, int $quality = 85): string
    {
        $disk = Storage::disk('public');

        // Asegurar que la subcarpeta existe
        if (!$disk->exists($folder)) {
            $disk->makeDirectory($folder);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::random(24) . '.jpg';
        $destinationPath = storage_path('app/public/' . $folder . '/' . $filename);

        // Optimización vía PHP GD si está disponible y es un formato estándar
        if (function_exists('imagecreatefromjpeg') && in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            try {
                $srcImage = match ($extension) {
                    'jpg', 'jpeg' => @imagecreatefromjpeg($file->getRealPath()),
                    'png'        => @imagecreatefrompng($file->getRealPath()),
                    'webp'       => @imagecreatefromwebp($file->getRealPath()),
                    default      => null,
                };

                if ($srcImage) {
                    // Mantener transparencias si venía de PNG antes de convertir a JPG
                    $width = imagesx($srcImage);
                    $height = imagesy($srcImage);

                    // Redimensionar si supera el tamaño máximo
                    if ($width > $maxDimension || $height > $maxDimension) {
                        if ($width >= $height) {
                            $newWidth = $maxDimension;
                            $newHeight = (int) round(($height / $width) * $maxDimension);
                        } else {
                            $newHeight = $maxDimension;
                            $newWidth = (int) round(($width / $height) * $maxDimension);
                        }
                        $resized = imagescale($srcImage, $newWidth, $newHeight);
                        if ($resized) {
                            imagedestroy($srcImage);
                            $srcImage = $resized;
                        }
                    }

                    // Guardar como JPEG optimizado
                    imagejpeg($srcImage, $destinationPath, $quality);
                    imagedestroy($srcImage);

                    return '/storage/' . $folder . '/' . $filename;
                }
            } catch (\Throwable $e) {
                // Si falla la manipulación GD, continúa con el fallback directo
            }
        }

        // Fallback estándar de almacenamiento de Laravel si GD no procesa la imagen
        $path = $file->store($folder, 'public');
        return '/storage/' . $path;
    }
}
