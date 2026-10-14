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
    'sesion_web' => [
        'normal_minutos' => (int) env('SESSION_LIFETIME', 120),
        'recinto_minutos' => (int) env('RECINTO_SESSION_LIFETIME', 1560),
        'latido_segundos' => (int) env('RECINTO_HEARTBEAT_SECONDS', 300),
        'ips_permitidas' => array_values(array_filter(array_map('trim', explode(',', (string) env('RECINTO_ALLOWED_IPS', ''))))),
    ],
];
