<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';


/* Obtener información del inventario */
$consulta = $pdo->query(
    "SELECT
        e.id_equipo,
        e.nombre_equipo,
        e.numero_inventario,
        e.numero_serie,
        e.uuid,
        m.nombre AS marca,
        e.modelo,
        e.tipo,
        est.nombre AS estado,
        s.nombre AS servicio,
        u.nombre AS ubicacion,
        so.nombre AS sistema_operativo,
        so.version AS version_so,
        so.arquitectura,
        r.nombre AS responsable,
        e.fecha_registro

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

    ORDER BY e.id_equipo"
);

$equipos = $consulta->fetchAll();


/* Nombre del archivo */
$nombreArchivo =
    'sigic_hhha_inventario_' .
    date('Y-m-d_H-i-s') .
    '.csv';


/* Encabezados para descarga */
header('Content-Type: text/csv; charset=UTF-8');

header(
    'Content-Disposition: attachment; filename="' .
    $nombreArchivo .
    '"'
);

header('Pragma: no-cache');
header('Expires: 0');


/* Abrir salida */
$salida = fopen('php://output', 'w');


/*
 * BOM UTF-8 para que Excel
 * reconozca correctamente tildes y ñ.
 */
fprintf(
    $salida,
    chr(0xEF) . chr(0xBB) . chr(0xBF)
);


/* Encabezados del CSV */
fputcsv(
    $salida,
    [
        'id_equipo',
        'nombre_equipo',
        'numero_inventario',
        'numero_serie',
        'uuid',
        'marca',
        'modelo',
        'tipo',
        'estado',
        'servicio',
        'ubicacion',
        'sistema_operativo',
        'version_so',
        'arquitectura',
        'responsable',
        'fecha_registro'
    ],
    ';'
);


/* Escribir los equipos */
foreach ($equipos as $equipo) {

    fputcsv(
        $salida,
        [
            $equipo['id_equipo'],
            $equipo['nombre_equipo'],
            $equipo['numero_inventario'] ?? '',
            $equipo['numero_serie'] ?? '',
            $equipo['uuid'] ?? '',
            $equipo['marca'] ?? '',
            $equipo['modelo'] ?? '',
            $equipo['tipo'] ?? '',
            $equipo['estado'],
            $equipo['servicio'],
            $equipo['ubicacion'],
            $equipo['sistema_operativo'] ?? '',
            $equipo['version_so'] ?? '',
            $equipo['arquitectura'] ?? '',
            $equipo['responsable'] ?? '',
            $equipo['fecha_registro']
        ],
        ';'
    );
}

fclose($salida);

exit;