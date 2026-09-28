<?php
/**
 * @file legal.php
 * @description Datos del responsable que se muestran en el Aviso de Privacidad y en los Términos
 *              y Condiciones. Se definen por .env para no tener datos legales fijos en el código.
 *              `version` se guarda junto con la aceptación del usuario (registro y checkout):
 *              al cambiar el contenido de los documentos, subir la versión.
 * @date 2026-09-28
 */

return [
    'razon_social'         => env('LEGAL_RAZON_SOCIAL', 'Attitour'),
    'domicilio'            => env('LEGAL_DOMICILIO', 'Cancún, Quintana Roo, México'),
    'email'                => env('LEGAL_EMAIL', 'hola@attitours.com'),
    'email_privacidad'     => env('LEGAL_EMAIL_PRIVACIDAD', env('LEGAL_EMAIL', 'hola@attitours.com')),
    'version'              => env('LEGAL_VERSION', '2026-09-28'),
    'fecha_actualizacion'  => env('LEGAL_FECHA_ACTUALIZACION', '28 de septiembre de 2026'),
];
