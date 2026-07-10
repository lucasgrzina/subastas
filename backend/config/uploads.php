<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disco de almacenamiento
    |--------------------------------------------------------------------------
    |
    | Disco de config/filesystems.php para uploads (temporales y definitivos).
    | Default "public" (servible por URL vía storage:link). Puede ser "s3" —
    | en ese caso requiere instalar league/flysystem-aws-s3-v3 primero.
    |
    */

    'disk' => env('UPLOADS_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Carpeta temporal para subidas diferidas (staged uploads)
    |--------------------------------------------------------------------------
    |
    | Las imágenes se suben acá primero y se "promueven" a su carpeta final
    | al guardar el modelo. Los huérfanos se limpian con `uploads:cleanup-temp`.
    |
    */

    'temp_folder' => 'tmp/uploads',

    /*
    |--------------------------------------------------------------------------
    | TTL de los temporales (horas) — usado por el cleanup
    |--------------------------------------------------------------------------
    */

    'temp_ttl_hours' => (int) env('UPLOADS_TEMP_TTL_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Límites de imagen
    |--------------------------------------------------------------------------
    |
    | max_kb debe ser coherente con post_max_size / upload_max_filesize de PHP.
    | max_dimension = lado mayor (px) al re-codificar.
    |
    */

    'max_kb' => (int) env('UPLOADS_IMAGE_MAX_KB', 4096),

    'max_dimension' => (int) env('UPLOADS_IMAGE_MAX_DIMENSION', 2048),

];
