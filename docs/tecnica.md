# Documentación técnica — Partes 1 a 4

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

## Configuración de documentos — Parte 3

Root y Secretaría pueden administrar sesiones parlamentarias en estado borrador y el catálogo de PDFs. `DocumentoPolicy` y `SesionParlamentariaPolicy` impiden el acceso a los demás roles.

Los archivos se guardan en el disco privado `local` (`storage/app/private/documentos`) con un UUID físico, mientras se conserva el nombre original como metadato. No se crea enlace público. La vista previa pasa por un controlador autenticado y autorizado, responde en modo `inline` y agrega `X-Content-Type-Options: nosniff`.

`StoreDocumentoRequest` comprueba extensión, MIME detectado en el servidor y tamaño máximo configurable mediante `DOCUMENTOS_MAX_KB` (20 MB por defecto). También se registra tamaño y SHA-256. Si la escritura de metadatos falla, la Action elimina el archivo para evitar huérfanos.

`AlmacenamientoDocumentos` desacopla el dominio del disco físico; actualmente se enlaza con `AlmacenamientoPrivadoDocumentos`. Un proveedor S3 puede reemplazarlo sin modificar controladores ni Actions. La futura importación documental puede crear registros con `origen = api` y `referencia_externa`, reutilizando este contrato.

La relación `documentos_sesion` controla disponibilidad y orden por sesión. Archivar globalmente un documento lo desactiva en todas sus sesiones, pero conserva archivo y metadatos para trazabilidad.

## Sistema en vivo — Parte 4

`GestorRecinto` concentra las mutaciones transaccionales de sesión, asistencia, bancas, documento mostrado, votaciones y votos. Cada cambio incrementa `estado_recinto.version`. Después del commit se emite `EstadoRecintoActualizado`; el evento sólo contiene versión y motivo. Los clientes siempre consultan nuevamente MySQL mediante sus endpoints de estado.

Reverb publica avisos en el canal privado `recinto`, autorizado para roles operativos activos, y en `monitor-publico`, que no contiene datos privados. Si Reverb no está disponible, la transacción permanece confirmada: WebSocket no es la fuente de verdad.

### Votación

Comando configura tema, método y duración. Al iniciar, se fija una única ventana global. En voto directo cada legislador presente con banca activa puede emitir o reemplazar su voto hasta el cierre. En voto asistido Secretaría registra el voto con la misma semántica de reemplazo. La votación se cierra cuando votaron todos los habilitados, cuando Comando decide cerrarla o al vencer el tiempo.

`php artisan schedule:work` ejecuta `votaciones:cerrar-vencidas` cada segundo. Además, las consultas de estado cierran vencimientos pendientes, por lo que una interrupción breve del scheduler no deja las pantallas desactualizadas. Al cerrar se publica el resultado agregado en el monitor.

### Sesión web prolongada

- `ConfigurarSesionRecinto` aplica `RECINTO_SESSION_LIFETIME` sólo a rutas Comando, bancas, monitor y latido mientras existe una sesión parlamentaria `en_curso` y la IP está permitida.
- El valor recomendado es 1560 minutos (26 horas), para tolerar una caída de red durante una sesión de 24 horas. Fuera de la sesión se restaura `SESSION_LIFETIME`.
- El navegador llama a `/latido` cada `RECINTO_HEARTBEAT_SECONDS` únicamente mientras la sesión está activa. El endpoint actualiza la sesión y regenera CSRF.
- Axios reintenta una vez las respuestas 419/401 después de renovar. Si no puede recuperarse, muestra un aviso de reautenticación sin reemplazar el contenido visible.
- El token “recordarme” nativo permite recuperar autenticación en el siguiente request cuando la sesión desapareció.
- Echo escucha reconexiones y eventos, y dispara una resincronización completa desde MySQL.

En producción se deben definir `RECINTO_ALLOWED_IPS`, HTTPS/WSS y cookies `secure`, usar dispositivos administrados con bloqueo de pantalla, revocar inmediatamente usuarios/dispositivos extraviados y evitar “recordarme” fuera del recinto. El monitor no expone votos individuales. El heartbeat se detiene al finalizar la sesión parlamentaria.

### Procesos requeridos

Además del servidor web y Vite en desarrollo, deben mantenerse activos:

```powershell
php artisan reverb:start
php artisan schedule:work
php artisan queue:work
```

El orden de oratoria sigue fuera de alcance. Podrá acoplarse como agregado de `sesiones_parlamentarias`, utilizando el mismo patrón de persistencia, versión, evento mínimo y resincronización.
