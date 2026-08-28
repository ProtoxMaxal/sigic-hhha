<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$consulta = $pdo->query(
    "SELECT
        m.id_movimiento,
        e.nombre_equipo,
        origen.nombre AS ubicacion_origen,
        destino.nombre AS ubicacion_destino,
        u.nombre AS usuario,
        m.fecha_movimiento,
        m.observacion
    FROM movimientos m

    INNER JOIN equipos e
        ON m.id_equipo = e.id_equipo

    LEFT JOIN ubicaciones origen
        ON m.id_ubicacion_origen = origen.id_ubicacion

    INNER JOIN ubicaciones destino
        ON m.id_ubicacion_destino = destino.id_ubicacion

    INNER JOIN usuarios u
        ON m.id_usuario = u.id_usuario

    ORDER BY m.fecha_movimiento DESC,
             m.id_movimiento DESC"
);

$movimientos = $consulta->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Movimientos - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Historial de Movimientos de Equipos</p>
    </div>

    <div class="usuario-info">

        <p>
            <strong>
                <?php echo htmlspecialchars($_SESSION['nombre']); ?>
            </strong>
        </p>

        <p>
            Perfil:
            <?php echo htmlspecialchars($_SESSION['rol']); ?>
        </p>

        <a
            href="../dashboard.php"
            class="logout-link"
        >
            Volver al inicio
        </a>

    </div>

</header>


<main class="equipos-content">

    <div class="equipos-header">

        <div>

            <h2>Movimientos</h2>

            <p>
                Total de movimientos:
                <strong>
                    <?php echo count($movimientos); ?>
                </strong>
            </p>

        </div>

        <a
            href="crear.php"
            class="btn-primary"
        >
            + Registrar movimiento
        </a>

    </div>


    <div class="tabla-contenedor">

        <table class="tabla-equipos">

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Equipo</th>
                    <th>Origen</th>
                    <th>Destino</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                    <th>Observación</th>
                </tr>

            </thead>

            <tbody>

                <?php if (count($movimientos) > 0): ?>

                    <?php foreach ($movimientos as $movimiento): ?>

                        <tr>

                            <td>
                                <?php echo $movimiento['id_movimiento']; ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $movimiento['nombre_equipo']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $movimiento['ubicacion_origen']
                                    ?? 'Sin ubicación anterior'
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $movimiento['ubicacion_destino']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $movimiento['usuario']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $movimiento['fecha_movimiento']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $movimiento['observacion']
                                    ?? '-'
                                );
                                ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7">
                            No existen movimientos registrados.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</body>

</html>