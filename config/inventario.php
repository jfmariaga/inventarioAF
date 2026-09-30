<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dominios de correo corporativo permitidos
    |--------------------------------------------------------------------------
    |
    | Solo se permite el registro de cuentas con un correo perteneciente a
    | alguno de estos dominios, uno por cada empresa del inventario
    | (LEVAPAN, PANAL, LEVACOL). Ver spec FR-004.
    |
    */

    'dominios_correo_permitidos' => [
        'levapan.com',
        'panalsas.com',
        'levacolsas.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reserva exclusiva de activos
    |--------------------------------------------------------------------------
    |
    | Minutos de inactividad tras los cuales se considera expirada la
    | reserva exclusiva de un activo en captura (FR-014a).
    |
    */

    'reserva_activo_minutos' => 15,

    /*
    |--------------------------------------------------------------------------
    | Retención de fotos reemplazadas
    |--------------------------------------------------------------------------
    |
    | Días que se conserva la foto anterior de un activo tras ser
    | reemplazada, antes de eliminarla definitivamente (FR-013a).
    |
    */

    'retencion_fotos_dias' => 30,

];
