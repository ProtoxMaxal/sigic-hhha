\# SIGIC-HHHA



\## Sistema Web de Gestión e Inventario Seguro de Equipos Computacionales



Proyecto desarrollado para la gestión de inventario de computadores del Hospital Dr. Hernán Henríquez Aravena de Temuco.



SIGIC-HHHA permite registrar y consultar equipos computacionales, mantener información técnica de hardware, red y seguridad, registrar movimientos y mantenciones, controlar usuarios mediante roles y mantener trazabilidad de las acciones realizadas en el sistema.



\---



\## Tecnologías utilizadas



\- HTML5

\- CSS3 responsive

\- JavaScript

\- PHP 8

\- PDO

\- MySQL / MariaDB

\- Apache

\- XAMPP

\- Google BigQuery Sandbox

\- GoogleSQL



\---



\## Funcionalidades implementadas



\- Inicio de sesión seguro.

\- Contraseñas almacenadas mediante `password\_hash()`.

\- Verificación mediante `password\_verify()`.

\- Sesiones de usuario.

\- Perfil Administrador.

\- Perfil Técnico.

\- Control de acceso según rol.

\- Registro de computadores.

\- Consulta de inventario.

\- Ficha técnica individual de cada equipo.

\- Registro de componentes de hardware.

\- Registro de interfaces de red.

\- Registro de información de antivirus y seguridad.

\- Registro e historial de movimientos.

\- Actualización automática de ubicación después de un movimiento.

\- Registro e historial de mantenciones.

\- Auditoría de acciones realizadas.

\- Gestión de usuarios para Administradores.

\- Reportes generales del inventario.

\- Exportación de información a CSV.

\- Análisis de datos mediante Google BigQuery Sandbox.



\---



\## Seguridad



El sistema incorpora distintas medidas de seguridad:



\- Contraseñas almacenadas mediante hash.

\- Consultas preparadas con PDO.

\- Validación de datos en servidor.

\- Control de acceso mediante sesiones.

\- Validación de permisos según rol.

\- Registro de acciones mediante auditoría.

\- Validación de identificadores recibidos por URL.

\- Protección de las funciones administrativas.

\- Cierre seguro de sesión.

\- Uso de tokens con hash para el proceso de recuperación de credenciales.



\---



\## Perfiles del sistema



\### Administrador



Puede acceder a:



\- Equipos.

\- Componentes.

\- Información de red.

\- Seguridad.

\- Movimientos.

\- Mantenciones.

\- Reportes.

\- Gestión de usuarios.

\- Auditoría.



\### Técnico



Puede acceder a las funciones operativas del inventario, pero no tiene acceso a:



\- Gestión de usuarios.

\- Auditoría administrativa.



Las restricciones se validan desde PHP y no solamente desde la interfaz.



\---



\## Base de datos



La base de datos operacional utiliza MySQL/MariaDB y se encuentra normalizada hasta Tercera Forma Normal (3FN).



El proyecto utiliza 16 tablas:



1\. roles

2\. usuarios

3\. servicios

4\. ubicaciones

5\. estados\_equipo

6\. marcas

7\. sistemas\_operativos

8\. responsables

9\. equipos

10\. componentes

11\. interfaces\_red

12\. seguridad\_equipo

13\. movimientos

14\. mantenciones

15\. auditoria

16\. tokens\_recuperacion



La estructura de la base de datos se encuentra en:



```text

database/sigic\_hhha.sql

