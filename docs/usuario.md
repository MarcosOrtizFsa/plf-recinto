# Guía de usuario — Partes 1 y 2

Esta parte instala la base técnica y todavía no ofrece pantallas operativas ni acceso por roles.

El administrador técnico debe configurar MySQL y las tres variables `ROOT_*`, ejecutar las migraciones y seeders, y resguardar la contraseña inicial. Se crean exactamente 30 bancas y los seis roles previstos.

## Iniciar sesión

Abra `/login`, escriba el correo y la contraseña asignados y, en un dispositivo controlado, marque “Mantener la sesión iniciada” si corresponde. No existe registro público. Un usuario inactivo no puede ingresar.

## Administración Root

Desde **Usuarios**, Root puede buscar, crear y editar cuentas. Al elegir el rol Legisladores se habilita la selección de una banca; sólo aparecen bancas activas y disponibles. Una banca no puede asignarse a dos personas.

La contraseña en edición es opcional: si queda vacía se conserva la actual. “Desactivar usuario” bloquea el acceso pero conserva su historial. Root no puede desactivar su propia cuenta.

Los demás roles no tienen acceso al módulo Usuarios. Sus pantallas específicas se incorporarán en partes posteriores.
