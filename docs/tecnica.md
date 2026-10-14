# Documentación técnica — Partes 1 y 2

## Arquitectura

El proyecto usa Laravel 12.70 sobre PHP 8.2, Blade, Vite, Tailwind CSS 4 y Alpine.js. Laravel Reverb, Echo y Pusher implementan la infraestructura WebSocket. MySQL es la única fuente de verdad: los eventos futuros sólo notificarán cambios y cada cliente volverá a consultar el estado al conectarse o reconectarse.

Se eligió autorización propia mediante roles, Gates y Policies. Los seis roles son estables y no requieren todavía permisos dinámicos; esta opción conserva una superficie pequeña y evita una dependencia adicional. Si aparecen permisos configurables por usuario, podrá migrarse a `spatie/laravel-permission`.

## Modelo de datos

- `roles`: Root, Presidente, Secretaría, Autoridades, Legisladores e Invitados.
- `bancas`: los 30 puestos físicos; su anulación administrativa es independiente de la asistencia.
- `usuarios`: credenciales, rol y banca opcional y única. El modelo `User` adapta los campos españoles a Laravel Auth.
- `sesiones_parlamentarias`: ciclo de vida de cada sesión legislativa.
- `documentos`: catálogo privado con origen `manual` o `api`, metadatos e identificador externo futuro.
- `documentos_sesion`: selección, activación y orden de documentos para una sesión.
- `asistencias`: una marca por legislador y sesión, registrada por Comando.
- `votaciones`: tema, método, ventana temporal y publicación del resultado.
- `votos`: una opción por legislador y votación. `abstencion` es la interpretación de “AFTENCION”.
- `estado_recinto`: fotografía operativa (sesión, PDF, votación, vista pública y versión) que permite resincronizar clientes.

Las tablas internas (`sessions`, `cache`, `jobs`, etc.) conservan nombres de Laravel. No existen tablas de oratoria.

## Instalación en Windows

1. Crear la base MySQL `plf_recinto` con cotejamiento `utf8mb4_unicode_ci`.
2. Copiar `.env.example` a `.env` y completar MySQL, `ROOT_NOMBRE`, `ROOT_EMAIL` y `ROOT_CONTRASENA`.
3. Ejecutar `composer install`, `npm.cmd install`, `php artisan key:generate` y `php artisan migrate:fresh --seed`.
4. En terminales separadas ejecutar `php artisan serve`, `php artisan reverb:start` y `npm.cmd run dev`.
5. Para colas, ejecutar `php artisan queue:work`.

Producción debe usar TLS (`https`/`wss`), secretos Reverb no compartidos y un supervisor para Reverb y colas.

## Tiempo real y sesiones prolongadas

Reverb quedó configurado, pero los eventos y canales privados corresponden a la Parte 4. Allí se incorporarán heartbeat, renovación CSRF, reintento controlado de 419/401 y resincronización tras reconexión. Las sesiones prolongadas se limitarán a roles y dispositivos del recinto, con TLS, bloqueo físico, revocación administrativa y auditoría. No se adelanta esa implementación en esta parte.

## Extensiones futuras

La fuente API de documentos encajará detrás de un servicio de importación y utilizará `origen` y `referencia_externa` sin cambiar consumidores. El orden de oratoria podrá agregarse como módulo relacionado con `sesiones_parlamentarias`, publicando cambios con el mismo patrón de estado persistido más aviso WebSocket; queda expresamente fuera del esquema actual.

## Autenticación y autorización — Parte 2

La autenticación usa el guard `web` nativo de Laravel y la tabla `usuarios`. El modelo adapta `contrasena` y `token_recordarme` a los contratos de Laravel, por lo que el hash y el mecanismo “recordarme” funcionan sin paquetes externos. No existe ninguna ruta de registro público.

El formulario de acceso limita a cinco intentos por combinación de correo e IP. Sólo usuarios activos pueden autenticarse; un middleware también expulsa de forma segura a una sesión cuyo usuario haya sido desactivado. Cada acceso correcto registra `ultimo_acceso_en`.

`UserPolicy` reserva listar, crear, editar y desactivar usuarios al rol `root`. Root no puede desactivarse a sí mismo. La baja es lógica (`activo = false`) para no romper la trazabilidad de asistencias, votos y documentos.

Las reglas de negocio se validan mediante Form Requests: correo único, contraseña confirmada de al menos ocho caracteres, banca única y obligatoria para Legisladores, y ausencia de banca para los demás roles. La escritura se delega a Actions para mantener controladores delgados.
