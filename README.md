# SIGIC-HHHA

Sistema Web de Gestión e Inventario Seguro de Equipos Computacionales para el Hospital Dr. Hernán Henríquez Aravena de Temuco.

Proyecto desarrollado como parte del Proyecto de Título de Técnico en Informática.

## Descripción

SIGIC-HHHA es una aplicación web orientada a la gestión y trazabilidad de computadores.

Permite registrar y consultar información relacionada con:

- Equipos computacionales.
- Hardware y componentes.
- Interfaces de red.
- Estado de seguridad y antivirus.
- Movimientos entre ubicaciones.
- Mantenciones.
- Usuarios y perfiles.
- Auditoría.
- Reportes.
- Consultas mediante API REST.
- Exportación de información para análisis en Google BigQuery.

## Aplicación publicada

La versión funcional del sistema se encuentra disponible en:

https://sigic-hhha.infinityfree.io/

El sistema fue desplegado en InfinityFree utilizando PHP y MySQL.

## Tecnologías utilizadas

### Frontend
- HTML5.
- CSS3.
- JavaScript.
- Diseño responsive.
- Fetch API.

### Backend
- PHP 8.
- PDO.
- Sesiones PHP.
- API REST.
- PHPMailer.

### Base de datos
- MySQL / MariaDB.
- Modelo relacional normalizado hasta 3FN.
- Claves primarias y foráneas.
- Restricciones de integridad.
- Índices.

### Big Data
- Google BigQuery Sandbox.
- Archivos CSV.
- GoogleSQL.

### Desarrollo y despliegue
- XAMPP.
- Composer.
- Git.
- GitHub.
- InfinityFree.

## Arquitectura general

El sistema utiliza una arquitectura web por capas:

Usuario  
↓  
Navegador  
↓  
HTML5 + CSS3 + JavaScript  
↓  
PHP / API REST  
↓  
PDO  
↓  
MySQL / MariaDB  

Para el análisis de datos:

MySQL  
↓  
Exportación CSV  
↓  
Google BigQuery  
↓  
GoogleSQL  
↓  
Indicadores

## Perfiles de usuario

SIGIC-HHHA dispone de dos perfiles principales:

### Administrador

Puede acceder a:

- Equipos.
- Movimientos.
- Mantenciones.
- Reportes.
- Gestión de usuarios.
- Auditoría.

### Técnico

Puede acceder a:

- Equipos.
- Registro de hardware.
- Información de red.
- Revisiones de seguridad.
- Movimientos.
- Mantenciones.
- Reportes.

El perfil Técnico no puede acceder a la gestión de usuarios ni a la auditoría.

## Seguridad

El sistema incorpora distintas medidas de seguridad:

- Contraseñas almacenadas mediante hash.
- Verificación mediante `password_verify()`.
- Sesiones de usuario.
- Control de acceso basado en roles.
- Consultas preparadas mediante PDO.
- Validación de formularios.
- Auditoría de acciones.
- Tokens de recuperación de un solo uso.
- Expiración de tokens de recuperación.
- Recuperación de contraseña mediante correo electrónico.
- Credenciales de configuración excluidas del repositorio mediante `.gitignore`.
- Uso de HTTPS en la versión publicada.

## Recuperación de contraseña

SIGIC-HHHA permite solicitar la recuperación de contraseña mediante correo electrónico.

El proceso es:

1. El usuario ingresa su correo.
2. El sistema genera un token aleatorio.
3. En la base de datos se almacena únicamente el hash del token.
4. El usuario recibe un enlace mediante correo electrónico.
5. El token posee una vigencia limitada.
6. El enlace puede utilizarse una sola vez.
7. Después del cambio de contraseña el token queda invalidado.

Para el envío de correo se utiliza PHPMailer mediante SMTP.

## API REST

El proyecto incluye una API REST para consultar información de equipos.

### Consultar todos los equipos

```text
/api/equipos.php