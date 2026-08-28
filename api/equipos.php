<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';


/* =========================
   AUTENTICACIÓN
========================= */

if (!isset($_SESSION['id_usuario'])) {

    http_response_code(401);

    echo json_encode(
        [
            'ok' => false,
            'mensaje' => 'Usuario no autenticado.'
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =========================
   MÉTODO HTTP
========================= */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    echo json_encode(
        [
            'ok' => false,
            'mensaje' => 'Método HTTP no permitido.'
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =========================
   CONSULTAR UN EQUIPO
========================= */

$idEquipo = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if ($idEquipo) {

    $consulta = $pdo->prepare(
        "SELECT
            e.id_equipo,
            e.nombre_equipo,
            e.numero_inventario,
            e.numero_serie,
            e.uuid,
            e.modelo,
            e.tipo,
            m.nombre AS marca,
            est.nombre AS estado,
            s.nombre AS servicio,
            u.nombre AS ubicacion,
            so.nombre AS sistema_operativo,
            so.version AS version_so,
            so.arquitectura,
            r.nombre AS responsable

        FROM equipos e

        LEFT JOIN marcas m
            ON e.id_marca = m.id_marca

        INNER JOIN estados_equipo est
            ON e.id_estado = est.id_estado

        INNER JOIN ubicaciones u
            ON e.id_ubicacion = u.id_ubicacion

        INNER JOIN servicios s
            ON u.id_servicio = s.id_servicio

        LEFT JOIN sistemas_operativos so
            ON e.id_so = so.id_so

        LEFT JOIN responsables r
            ON e.id_responsable = r.id_responsable

        WHERE e.id_equipo = ?

        LIMIT 1"
    );

    $consulta->execute([$idEquipo]);

    $equipo = $consulta->fetch();

    if (!$equipo) {

        http_response_code(404);

        echo json_encode(
            [
                'ok' => false,
                'mensaje' => 'Equipo no encontrado.'
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }


    echo json_encode(
        [
            'ok' => true,
            'data' => $equipo
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_PRETTY_PRINT
    );

    exit;
}


/* =========================
   CONSULTAR TODOS
========================= */

$consulta = $pdo->query(
    "SELECT
        e.id_equipo,
        e.nombre_equipo,
        e.numero_inventario,
        e.numero_serie,
        e.uuid,
        e.modelo,
        e.tipo,
        m.nombre AS marca,
        est.nombre AS estado,
        s.nombre AS servicio,
        u.nombre AS ubicacion,
        so.nombre AS sistema_operativo,
        so.version AS version_so,
        so.arquitectura,
        r.nombre AS responsable

    FROM equipos e

    LEFT JOIN marcas m
        ON e.id_marca = m.id_marca

    INNER JOIN estados_equipo est
        ON e.id_estado = est.id_estado

    INNER JOIN ubicaciones u
        ON e.id_ubicacion = u.id_ubicacion

    INNER JOIN servicios s
        ON u.id_servicio = s.id_servicio

    LEFT JOIN sistemas_operativos so
        ON e.id_so = so.id_so

    LEFT JOIN responsables r
        ON e.id_responsable = r.id_responsable

    ORDER BY e.id_equipo DESC"
);

$equipos = $consulta->fetchAll();


echo json_encode(
    [
        'ok' => true,
        'total' => count($equipos),
        'data' => $equipos
    ],
    JSON_UNESCAPED_UNICODE |
    JSON_PRETTY_PRINT
);

exit;