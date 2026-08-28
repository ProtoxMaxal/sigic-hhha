<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$consulta = $pdo->query(
    "SELECT
        e.id_equipo,
        e.nombre_equipo,
        e.numero_inventario,
        s.antivirus,
        s.version,
        s.activo,
        s.actualizado,
        s.fecha_revision
    FROM equipos e
    LEFT JOIN seguridad_equipo s
        ON s.id_seguridad = (
            SELECT s2.id_seguridad
            FROM seguridad_equipo s2
            WHERE s2.id_equipo = e.id_equipo
            ORDER BY s2.fecha_revision DESC,
                     s2.id_seguridad DESC
            LIMIT 1
        )
    ORDER BY e.id_equipo"
);

$registros = $consulta->fetchAll();

$nombreArchivo =
    'sigic_hhha_seguridad_' .
    date('Y-m-d_H-i-s') .
    '.csv';

header('Content-Type: text/csv; charset=UTF-8');

header(
    'Content-Disposition: attachment; filename="' .
    $nombreArchivo .
    '"'
);

$salida = fopen('php://output', 'w');

fprintf(
    $salida,
    chr(0xEF) . chr(0xBB) . chr(0xBF)
);

fputcsv(
    $salida,
    [
        'id_equipo',
        'nombre_equipo',
        'numero_inventario',
        'antivirus',
        'version',
        'activo',
        'actualizado',
        'fecha_revision'
    ],
    ';'
);

foreach ($registros as $registro) {

    fputcsv(
        $salida,
        [
            $registro['id_equipo'],
            $registro['nombre_equipo'],
            $registro['numero_inventario'] ?? '',
            $registro['antivirus'] ?? 'Sin registro',
            $registro['version'] ?? '',
            $registro['activo'] !== null
                ? ($registro['activo'] ? 'Si' : 'No')
                : 'Sin registro',
            $registro['actualizado'] !== null
                ? ($registro['actualizado'] ? 'Si' : 'No')
                : 'Sin registro',
            $registro['fecha_revision'] ?? ''
        ],
        ';'
    );
}

fclose($salida);
exit;