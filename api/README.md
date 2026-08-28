\# API REST - SIGIC-HHHA



\## Descripción



SIGIC-HHHA incorpora una API REST desarrollada con PHP 8 y PDO para permitir la consulta de información del inventario mediante respuestas en formato JSON.



La API utiliza la misma base de datos MySQL/MariaDB del sistema y está protegida mediante la sesión autenticada del usuario.



\---



\## Tecnologías utilizadas



\- PHP 8

\- PDO

\- MySQL / MariaDB

\- JSON

\- HTTP

\- JavaScript Fetch API



\---



\## Endpoint de equipos



\### Consultar todos los equipos



Método:



GET



Endpoint:



/api/equipos.php



Ejemplo:



http://localhost/sigic-hhha/api/equipos.php



Respuesta:



```json

{

&#x20;   "ok": true,

&#x20;   "total": 2,

&#x20;   "data": \[]

}

