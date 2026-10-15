# Guía de usuario — Partes 1 a 4

Esta parte instala la base técnica y todavía no ofrece pantallas operativas ni acceso por roles.

El administrador técnico debe configurar MySQL y las tres variables `ROOT_*`, ejecutar las migraciones y seeders, y resguardar la contraseña inicial. Se crean exactamente 30 bancas y los seis roles previstos.

## Iniciar sesión

Abra `/login`, escriba el correo y la contraseña asignados y, en un dispositivo controlado, marque “Mantener la sesión iniciada” si corresponde. No existe registro público. Un usuario inactivo no puede ingresar.

## Administración Root

Desde **Usuarios**, Root puede buscar, crear y editar cuentas. Al elegir el rol Legisladores se habilita la selección de una banca; sólo aparecen bancas activas y disponibles. Una banca no puede asignarse a dos personas.

La contraseña en edición es opcional: si queda vacía se conserva la actual. “Desactivar usuario” bloquea el acceso pero conserva su historial. Root no puede desactivar su propia cuenta.

Los demás roles no tienen acceso al módulo Usuarios. Sus pantallas específicas se incorporarán en partes posteriores.

## Configurar sesiones y PDFs

Root y Secretaría verán **Configuración** en la navegación.

1. Entre a **Configuración → Sesiones** y cree una sesión con nombre, número e inicio programado.
2. Regrese a **Documentos de sesión** y use **Cargar PDF**.
3. Indique un título y seleccione un archivo PDF de hasta 20 MB, salvo que el administrador haya cambiado el límite.
4. Seleccione la sesión en el desplegable y pulse **Activar en sesión** para cada PDF que deba estar disponible.
5. Use **Vista previa** para revisar el archivo sin hacerlo público.

**Desactivar en sesión** sólo retira el documento de esa sesión. **Archivar** lo deshabilita globalmente y también en todas las sesiones, conservándolo para auditoría. Un documento archivado puede volver a habilitarse.

## Comando

Root y Secretaría ingresan a **Comando**. Allí pueden iniciar una sesión preparada, marcar asistencia, anular o rehabilitar bancas, publicar un PDF y configurar votaciones.

La asistencia es exclusiva de Comando. Marcar **Presente** habilita la banca sólo si ésta no está anulada. La anulación manual tiene prioridad.

Para votar, configure tema, método y duración. En método asistido, use los botones Sí, No o Abs. junto a cada presente. Mientras figure “Abierta” puede volver a pulsar otra opción para corregirla. En método directo, cada banca realiza esta operación en su pantalla.

Al finalizar la sesión parlamentaria se detienen los latidos prolongados y las pantallas vuelven al estado de espera.

## Bancas

El legislador ingresa con su usuario y abre **Mi banca**. Antes de iniciar verá una pantalla de espera. Durante la sesión verá su estado de habilitación y el PDF publicado.

Cuando se abre una votación directa aparecen tres botones táctiles: **AFIRMATIVO**, **NEGATIVO** y **ABSTENCIÓN**. El voto seleccionado queda resaltado y puede cambiarse hasta el cierre.

## Monitor público

Abra `/monitor` en la pantalla pública. No requiere login y sólo muestra la información publicada: asistencia, votación en curso y resultado final agregado. No muestra votos individuales.

## Datos de simulacro

En desarrollo puede cargarse el escenario ficticio con `php artisan db:seed --class=SimulacroRecintoSeeder`. Crea 30 legisladores, uno por banca, correos desde `banca01@recinto.test` hasta `banca30@recinto.test`, y la sesión borrador **Sesión de Simulacro del Recinto**. Todos usan la contraseña definida localmente en `DEMO_CONTRASENA`.
