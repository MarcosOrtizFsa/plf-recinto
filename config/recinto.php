<?php

return [
    'root' => [
        'nombre' => env('ROOT_NOMBRE'),
        'email' => env('ROOT_EMAIL'),
        'contrasena' => env('ROOT_CONTRASENA'),
    ],
    'documentos' => [
        'tamano_maximo_kb' => (int) env('DOCUMENTOS_MAX_KB', 20480),
    ],
];
