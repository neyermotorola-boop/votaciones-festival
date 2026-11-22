<?php

return [

    /*
    | Notificaciones por correo (bienvenida, comprobante de voto, recuperación
    | de contraseña). Si se desactiva, el sistema sigue funcionando igual,
    | solo que no envía correos informativos.
    */
    'notificaciones_correo' => env('NOTIFICACIONES_CORREO', true),

    /*
    | Fotos de participantes: tamaño máximo en KB y formatos aceptados.
    */
    'imagen_max_kb' => (int) env('IMAGEN_MAX_KB', 2048),
    'imagen_formatos' => ['jpg', 'jpeg', 'png', 'webp'],

];
